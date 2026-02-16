<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CmsConfig;

class HeaderConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        echo "Agregando configuraciones del Header...\n";

        // Logo del Header
        CmsConfig::create([
            'key' => 'header.logo',
            'value' => '/assets/media/Logo.png',
            'type' => 'text',
            'group' => 'header',
            'description' => 'Logo principal del sitio (sube una imagen desde Librería de Medios y pega la URL aquí)'
        ]);

        // Mostrar/Ocultar Login
        CmsConfig::create([
            'key' => 'header.mostrar_login',
            'value' => 'true',
            'type' => 'boolean',
            'group' => 'header',
            'description' => 'Mostrar enlace de Login en menú (true/false)'
        ]);

        // Actualizar URLs de redes sociales (ya existen en CmsContentSeeder pero las actualizamos)
        $facebook = CmsConfig::where('key', 'facebook_url')->first();
        if ($facebook) {
            $facebook->update([
                'group' => 'header',
                'description' => 'URL de Facebook (dejar vacío para ocultar)'
            ]);
        }

        $instagram = CmsConfig::where('key', 'instagram_url')->first();
        if ($instagram) {
            $instagram->update([
                'group' => 'header',
                'description' => 'URL de Instagram (dejar vacío para ocultar)'
            ]);
        }

        $linkedin = CmsConfig::where('key', 'linkedin_url')->first();
        if ($linkedin) {
            $linkedin->update([
                'group' => 'header',
                'description' => 'URL de LinkedIn (dejar vacío para ocultar)'
            ]);
        }

        echo "✓ Configuraciones del header creadas/actualizadas\n";
        echo "\n🎯 PARA CAMBIAR EL LOGO:\n";
        echo "   1. Ve a 'Configuración → Librería de Medios'\n";
        echo "   2. Sube tu logo\n";
        echo "   3. Copia la URL de la imagen\n";
        echo "   4. Ve a 'Configuración → CMS → Configuraciones'\n";
        echo "   5. Edita 'header.logo' y pega la URL\n";
        echo "   6. ¡Listo! El logo cambiará automáticamente en el sitio público\n\n";
    }
}
