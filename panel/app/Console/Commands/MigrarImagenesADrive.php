<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Jobs_file;
use App\Models\Job;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MigrarImagenesADrive extends Command
{
    // El comando que vas a escribir en la terminal
    protected $signature = 'images:migrate-to-drive';

    protected $description = 'Migra las imágenes locales viejas de las tareas hacia Google Drive organizadas por Cliente/Tarea';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    { 
        // 1. Buscar registros que NO tengan formato de ID de Google Drive (asumiendo que los viejos no tienen guiones o tienen la extensión en el nombre)
        // O más fácil: filtramos los que tengan en su 'name' un patrón local o que no correspondan a las últimas 4 IDs subidas.
        // Vamos a traer todos los archivos activos cuyo 'name' no empiece con un ID típico o que sepamos que están en disco local.
        $files = Jobs_file::whereNull('deleted_at')
            ->where('name', 'NOT LIKE', '1%') 
            ->take(20)
            ->get();

        $total = $files->count();
        $this->info("Se encontraron {$total} imágenes locales para migrar.");

        if ($total === 0) {
            $this->info("No hay imágenes pendientes de migración.");
            return 0;
        }

        // Código limpio original (En Linux funciona perfecto sin parches)
        $adapter = Storage::disk('google')->getAdapter();
        $service = $adapter->getService();
        $rootFolderId = config('filesystems.disks.google.folderId');

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($files as $file) {
            // Ruta física donde está el archivo local actualmente
            $pathLocal = storage_path('app/public/' . $file->name);

            // Si el archivo físico ya no existe en el servidor local, saltamos al siguiente
            if (!file_exists($pathLocal)) {
                $bar->advance();
                continue;
            }

            // Obtener los nombres de cliente y tarea para organizar en Drive (puedes ajustar esta lógica según cómo tengas estructurada tu base de datos)
            $job = Job::with('client')->find($file->job_id); 
            $nombreCliente = $job && $job->client ? $job->client->first_name.($job->client->last_name!=null? ' '.$job->client->last_name : '') : 'cliente-desconocido';
            $nombreTarea = 'tarea_' . $file->job_id;

            $clienteSlug = Str::slug($nombreCliente);
            $tareaSlug = Str::slug($nombreTarea);

            try {
                // --- MISMALÓGICA DE TU CONTROLLER PARA BUSCAR/CREAR CARPETAS ---
                
                // Buscar/Crear Cliente
                $queryCliente = "name='{$clienteSlug}' and mimeType='application/vnd.google-apps.folder' and '{$rootFolderId}' in parents and trashed=false";
                $listaClientes = $service->files->listFiles(['q' => $queryCliente, 'fields' => 'files(id)']);
                $clienteFolderId = count($listaClientes->getFiles()) > 0 
                    ? $listaClientes->getFiles()[0]->id 
                    : $service->files->create(new \Google\Service\Drive\DriveFile(['name' => $clienteSlug, 'mimeType' => 'application/vnd.google-apps.folder', 'parents' => [$rootFolderId]]), ['fields' => 'id'])->id;

                // Buscar/Crear Tarea
                $queryTarea = "name='{$tareaSlug}' and mimeType='application/vnd.google-apps.folder' and '{$clienteFolderId}' in parents and trashed=false";
                $listaTareas = $service->files->listFiles(['q' => $queryTarea, 'fields' => 'files(id)']);
                $tareaFolderId = count($listaTareas->getFiles()) > 0 
                    ? $listaTareas->getFiles()[0]->id 
                    : $service->files->create(new \Google\Service\Drive\DriveFile(['name' => $tareaSlug, 'mimeType' => 'application/vnd.google-apps.folder', 'parents' => [$clienteFolderId]]), ['fields' => 'id'])->id;

                // Subir el archivo local a Google Drive
                $metaArchivo = new \Google\Service\Drive\DriveFile([
                    'name' => $file->name, // Mantiene el nombre que ya tenía asignado
                    'parents' => [$tareaFolderId]
                ]);

                // Detectar MimeType local
                $mimeType = mime_content_type($pathLocal);

                $archivoSubido = $service->files->create($metaArchivo, [
                    'data' => file_get_contents($pathLocal),
                    'mimeType' => $mimeType,
                    'uploadType' => 'multipart',
                    'fields' => 'id'
                ]);

                // Actualizar el registro en la Base de Datos con el ID de Drive
                $file->update([
                    'name' => $archivoSubido->id
                ]);

                // OPCIONAL: Eliminar el archivo del servidor local para liberar espacio
                // unlink($pathLocal); 

            } catch (\Exception $e) {
                Log::error("Error migrando archivo ID {$file->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->info("\n¡Migración completada con éxito!");
        return 0;
    }
}
