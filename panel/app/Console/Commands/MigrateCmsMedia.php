<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CmsMedia;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class MigrateCmsMedia extends Command
{
    protected $signature = 'cms:migrate-media';
    protected $description = 'Migra las imágenes CMS de public/assets/cms-media a storage/app/public/cms-media';

    public function handle()
    {
        $this->info('🔄 Iniciando migración de medios CMS...');
        
        $oldPath = public_path('assets/cms-media');
        $mediaRecords = CmsMedia::all();
        
        $migrated = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($mediaRecords as $media) {
            try {
                $oldPathFormat = false;
                
                // Verificar si usa el formato antiguo
                if (str_starts_with($media->path, 'assets/cms-media/')) {
                    $oldPathFormat = true;
                    $filename = basename($media->path);
                    $oldFile = public_path($media->path);
                    
                    // Copiar archivo a storage si existe
                    if (file_exists($oldFile)) {
                        Storage::disk('public')->putFileAs(
                            'cms-media',
                            new \Illuminate\Http\File($oldFile),
                            $filename
                        );
                        
                        // Actualizar path en BD
                        $media->path = 'cms-media/' . $filename;
                        $media->disk = 'public';
                        $media->save();
                        
                        $this->line("✅ Migrado: {$filename}");
                        $migrated++;
                    } else {
                        $this->warn("⚠️  Archivo no encontrado: {$oldFile}");
                        
                        // Actualizar path de todos modos
                        $media->path = 'cms-media/' . $filename;
                        $media->disk = 'public';
                        $media->save();
                        
                        $skipped++;
                    }
                } else if (!str_starts_with($media->path, 'cms-media/')) {
                    // Si tiene otro formato, intentar normalizarlo
                    $filename = basename($media->path);
                    
                    // Verificar si el archivo existe en storage
                    if (Storage::disk('public')->exists('cms-media/' . $filename)) {
                        $media->path = 'cms-media/' . $filename;
                        $media->disk = 'public';
                        $media->save();
                        
                        $this->line("✅ Normalizado: {$filename}");
                        $migrated++;
                    } else {
                        $this->warn("⚠️  No se pudo normalizar: {$media->path}");
                        $skipped++;
                    }
                } else {
                    // Ya tiene el formato correcto
                    $this->line("⏭️  Ya migrado: {$media->path}");
                    $skipped++;
                }
                
            } catch (\Exception $e) {
                $this->error("❌ Error con {$media->path}: " . $e->getMessage());
                $errors++;
            }
        }
        
        $this->newLine();
        $this->info('📊 Resumen de migración:');
        $this->table(
            ['Estado', 'Cantidad'],
            [
                ['Migrados', $migrated],
                ['Omitidos', $skipped],
                ['Errores', $errors],
                ['Total', $mediaRecords->count()],
            ]
        );
        
        if ($migrated > 0) {
            $this->newLine();
            $this->info('💡 Recomendación:');
            $this->line('   Puedes eliminar la carpeta public/assets/cms-media si ya no la necesitas:');
            $this->line('   rm -rf ' . $oldPath);
        }
        
        $this->newLine();
        $this->info('✅ Migración completada!');
        
        return 0;
    }
}
