<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    public function getloginrol()
    {
        if (Session::get('user') != null) {
            return true;
        } else {
            return false;
        }
    }

    protected function validate_recaptcha(Request $request)
    {
        $token = $request->input('token');
        $action = $request->input('action');

        if (empty($token) || empty($action)) {
            throw ValidationException::withMessages([
                'recaptcha' => ('Captcha vencido.')
            ]);
        }

        $recaptcha_key_secret = DB::table('configs')->where('name','recaptcha_key_secret')->value('value') ?? '';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"https://www.google.com/recaptcha/api/siteverify");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('secret' => $recaptcha_key_secret, 'response' => $token)));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $arrResponse = json_decode($response, true);

        // verify the response
        if(
            $arrResponse["success"] != '1' || 
            $arrResponse["action"] != $action || 
            $arrResponse["score"] < 0.5
        ) {
            throw ValidationException::withMessages([
                'recaptcha' => ('Captcha vencido.')
            ]);
        }
    }

    /**
     * Centraliza la subida de un archivo individual a Google Drive
     * Organizado por Cliente y Tarea.
     * * @return array [ruta_completa, filename]
     */
    protected function subirArchivoADrive($attachment, $nombreCliente, $nombreTarea, $filename)
    {
        // 1. Obtener la instancia nativa del servicio de Google Drive
        $adapter = Storage::disk('google')->getAdapter();
        $service = $adapter->getService();
        
        // El ID de la carpeta raíz que configuraste en tu .env
        $rootFolderId = config('filesystems.disks.google.folderId');

        $clienteSlug = Str::slug($nombreCliente);
        $tareaSlug = Str::slug($nombreTarea);

        try {
            // 2. BUSCAR O CREAR LA CARPETA DEL CLIENTE
            $queryCliente = "name='{$clienteSlug}' and mimeType='application/vnd.google-apps.folder' and '{$rootFolderId}' in parents and trashed=false";
            $listaClientes = $service->files->listFiles(['q' => $queryCliente, 'fields' => 'files(id)']);
            
            if (count($listaClientes->getFiles()) > 0) {
                $clienteFolderId = $listaClientes->getFiles()[0]->id;
            } else {
                // Si no existe, se crea en la raíz
                $metaCliente = new \Google\Service\Drive\DriveFile([
                    'name' => $clienteSlug,
                    'mimeType' => 'application/vnd.google-apps.folder',
                    'parents' => [$rootFolderId]
                ]);
                $folderCliente = $service->files->create($metaCliente, ['fields' => 'id']);
                $clienteFolderId = $folderCliente->id;
            }

            // 3. BUSCAR O CREAR LA CARPETA DE LA TAREA (Dentro de la del cliente)
            $queryTarea = "name='{$tareaSlug}' and mimeType='application/vnd.google-apps.folder' and '{$clienteFolderId}' in parents and trashed=false";
            $listaTareas = $service->files->listFiles(['q' => $queryTarea, 'fields' => 'files(id)']);

            if (count($listaTareas->getFiles()) > 0) {
                $tareaFolderId = $listaTareas->getFiles()[0]->id;
            } else {
                // Si no existe, se crea dentro del cliente
                $metaTarea = new \Google\Service\Drive\DriveFile([
                    'name' => $tareaSlug,
                    'mimeType' => 'application/vnd.google-apps.folder',
                    'parents' => [$clienteFolderId]
                ]);
                $folderTarea = $service->files->create($metaTarea, ['fields' => 'id']);
                $tareaFolderId = $folderTarea->id;
            }

            // 4. SUBIR EL ARCHIVO FINAL (Dentro de la carpeta de la tarea)
            $metaArchivo = new \Google\Service\Drive\DriveFile([
                'name' => $filename,
                'parents' => [$tareaFolderId]
            ]);

            // Dependiendo de si viene optimizado o directo de un request, leemos el path físico
            $realPath = method_exists($attachment, 'getRealPath') ? $attachment->getRealPath() : $attachment->getPathname();

            $archivoSubido = $service->files->create($metaArchivo, [
                'data' => file_get_contents($realPath),
                'mimeType' => $attachment->getMimeType(),
                'uploadType' => 'multipart',
                'fields' => 'id'
            ]);

            // Retornamos el ID real de Google Drive del archivo para guardarlo en la BD
            // Esto solucionará de raíz tus problemas al eliminar o visualizar imágenes más adelante
            return [
                'ruta_completa' => $archivoSubido->id, // Guardamos el ID del archivo
                'filename' => $filename
            ];

        } catch (\Exception $e) {
            Log::error("Error subiendo a Google Drive: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Centraliza la eliminación de un archivo en Google Drive
     */
    protected function eliminarArchivoDeDrive($idDrive)
    {
        try {
            $adapter = Storage::disk('google')->getAdapter();
            $service = $adapter->getService();
            
            // Google elimina directamente apuntando al ID único del archivo
            $service->files->delete($idDrive);
            return true;
        } catch (\Exception $e) {
            Log::error("Error al eliminar archivo de Drive: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Centraliza la eliminación de una carpeta COMPLETA (Cliente o Tarea)
     */
    protected function eliminarCarpetaDeDrive($rutaCarpeta)
    {
        if (Storage::disk('google')->exists($rutaCarpeta)) {
            return Storage::disk('google')->deleteDirectory($rutaCarpeta);
        }
        return false;
    }

    /**
     * Genera un streaming seguro de la imagen desde Google Drive usando su ID
     */
    public function verArchivoDesdeDrive($idDrive)
    {
        try {
            $adapter = Storage::disk('google')->getAdapter();
            $service = $adapter->getService();

            // 1. Obtener la metadata para conocer el tipo de archivo (MimeType)
            $fileMetadata = $service->files->get($idDrive, ['fields' => 'mimeType']);
            $mimeType = $fileMetadata->getMimeType();

            // 2. Descargar el contenido del archivo en crudo
            $response = $service->files->get($idDrive, ['alt' => 'media']);
            $content = $response->getBody()->getContents();

            // 3. Devolverlo al navegador con las cabeceras correctas
            return response($content, 200)->header('Content-Type', $mimeType);

        } catch (\Exception $e) {
            \Log::error("Error al visualizar archivo de Drive: " . $e->getMessage());
            abort(404, "Archivo no encontrado en Google Drive.");
        }
    }
}
