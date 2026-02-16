<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\CmsSection;
use App\Models\CmsConfig;

class CmsSectionsSeeder extends Seeder
{
    public function run()
    {
        // Migrar datos existentes de cms_configs si existen
        $existingConfigs = [];
        if (DB::getSchemaBuilder()->hasTable('cms_configs')) {
            $configs = CmsConfig::all();
            foreach ($configs as $config) {
                $existingConfigs[$config->key] = $config->value;
            }
        }

        $sections = [
            // 1. CONFIGURACIÓN GENERAL (colores globales de fallback)
            [
                'name' => 'Configuración General',
                'slug' => 'general',
                'order' => 0,
                'config' => [
                    'background_color' => '#ffffff',
                    'icon_color' => '#667eea',
                    'primary_text_color' => '#1f2937',
                    'secondary_text_color' => '#6b7280',
                    'social_color' => '#667eea',
                ]
            ],

            // 2. HEADER
            [
                'name' => 'Header',
                'slug' => 'header',
                'order' => 1,
                'config' => [
                    'logo' => $existingConfigs['header.logo'] ?? '/assets/media/Logo.png',
                    'logo_alt' => 'Strupeni Electrónica',
                    'background_color' => '#ffffff',
                    'text_color' => '#1f2937',
                    'text_hover_color' => '#667eea',
                    // Redes Sociales - URLs individuales
                    'facebook_url' => $existingConfigs['header.facebook_url'] ?? 'https://facebook.com/strupeni',
                    'instagram_url' => $existingConfigs['header.instagram_url'] ?? 'https://instagram.com/strupeni',
                    'linkedin_url' => $existingConfigs['header.linkedin_url'] ?? 'https://linkedin.com/company/strupeni',
                    'whatsapp_url' => $existingConfigs['header.whatsapp_url'] ?? 'https://wa.me/5491112345678',
                    'social_icon_color' => '#667eea',
                ]
            ],

            // 3. CARRUSEL
            [
                'name' => 'Carrusel de Imágenes',
                'slug' => 'carousel',
                'order' => 2,
                'config' => [
                    'images' => [], // IDs de CmsMedia
                    'autoplay' => true,
                    'autoplay_speed' => 5000, // ms
                    'show_arrows' => true,
                    'show_dots' => true,
                    'height' => '500px',
                    'overlay_color' => 'rgba(0,0,0,0.3)',
                ]
            ],

            // 4. NUESTRA HISTORIA
            [
                'name' => 'Nuestra Historia',
                'slug' => 'historia',
                'order' => 3,
                'config' => [
                    'titulo' => 'Conoce Nuestra Historia',
                    'resumen' => 'Somos una empresa dedicada a la excelencia en servicios electrónicos desde hace más de 20 años.',
                    'texto_completo' => 'Historia completa de la empresa aquí...',
                    'imagen' => null, // ID de CmsMedia
                    'boton_texto' => 'Leer más',
                    'boton_color' => '#667eea',
                    'boton_text_color' => '#ffffff',
                    'boton_hover_color' => '#764ba2',
                    'background_color' => '#f9fafb',
                    'titulo_color' => '#1f2937',
                    'texto_color' => '#6b7280',
                ]
            ],

            // 5. SERVICIOS
            [
                'name' => 'Nuestros Servicios',
                'slug' => 'servicios',
                'order' => 4,
                'config' => [
                    'titulo' => 'Nuestros Servicios',
                    'subtitulo' => 'Soluciones profesionales para cada necesidad',
                    'images' => [], // IDs de CmsMedia - carrusel de 3 visibles
                    'visible_items' => 3,
                    'autoplay' => true,
                    'autoplay_speed' => 4000,
                    'show_all_text' => 'Ver todos',
                    'background_color' => '#ffffff',
                    'titulo_color' => '#1f2937',
                    'subtitulo_color' => '#6b7280',
                    'icon_color' => '#667eea',
                    'icon_hover_color' => '#764ba2',
                ]
            ],

            // 6. BANNER
            [
                'name' => 'Banner Empresa',
                'slug' => 'banner',
                'order' => 5,
                'config' => [
                    'imagen' => null, // ID de CmsMedia
                    'link' => null,
                    'target' => '_self',
                    'alt' => 'Banner promocional',
                ]
            ],

            // 7. INSTAGRAM
            [
                'name' => 'Sección Instagram',
                'slug' => 'instagram',
                'order' => 6,
                'config' => [
                    'titulo' => 'Síguenos en Instagram',
                    'username' => '@strupeni',
                    'feed_url' => 'https://instagram.com/strupeni',
                    'background_color' => '#f9fafb',
                    'titulo_color' => '#1f2937',
                    'icon_color' => '#e4405f',
                    'icon_hover_color' => '#c13584',
                    // Integración con API de Instagram (futuro)
                    'api_token' => null,
                    'show_feed' => false,
                ]
            ],

            // 8. FOOTER
            [
                'name' => 'Footer',
                'slug' => 'footer',
                'order' => 7,
                'config' => [
                    'logo' => null, // ID de CmsMedia
                    'logo_alt' => 'Strupeni Electrónica',
                    'descripcion' => 'Empresa líder en soluciones electrónicas.',
                    'telefono' => '+54 11 1234-5678',
                    'email' => 'contacto@strupeni.com',
                    'direccion' => 'Av. Principal 123, Buenos Aires',
                    'horarios' => 'Lunes a Viernes: 9:00 - 18:00',
                    'copyright' => '© 2026 Strupeni Electrónica. Todos los derechos reservados.',
                    'background_color' => '#1f2937',
                    'text_color' => '#ffffff',
                    'text_secondary_color' => '#9ca3af',
                    'link_color' => '#667eea',
                    'link_hover_color' => '#764ba2',
                    'social' => [
                        'facebook' => [
                            'url' => $existingConfigs['header.facebook_url'] ?? 'https://facebook.com',
                            'active' => true,
                            'color' => '#1877f2'
                        ],
                        'instagram' => [
                            'url' => $existingConfigs['header.instagram_url'] ?? 'https://instagram.com',
                            'active' => true,
                            'color' => '#e4405f'
                        ],
                        'linkedin' => [
                            'url' => $existingConfigs['header.linkedin_url'] ?? 'https://linkedin.com',
                            'active' => true,
                            'color' => '#0a66c2'
                        ]
                    ]
                ]
            ],

            // 9. FLUTTER THEME
            [
                'name' => 'Tema Aplicación Flutter',
                'slug' => 'flutter_theme',
                'order' => 8,
                'config' => [
                    'name' => 'Default Theme',
                    'version' => '1.0.0',
                    'primary_color' => '#667eea',
                    'secondary_color' => '#764ba2',
                    'accent_color' => '#10b981',
                    'background_color' => '#ffffff',
                    'surface_color' => '#f9fafb',
                    'error_color' => '#ef4444',
                    'text_primary_color' => '#1f2937',
                    'text_secondary_color' => '#6b7280',
                    'card_elevation' => 2,
                    'border_radius' => 12,
                    'font_family' => 'Roboto',
                ]
            ],
        ];

        foreach ($sections as $sectionData) {
            CmsSection::updateOrCreate(
                ['slug' => $sectionData['slug']],
                $sectionData
            );
        }

        $this->command->info('✅ CMS Sections creadas exitosamente!');
        $this->command->info('📋 9 secciones configuradas con colores completos');
        
        if (!empty($existingConfigs)) {
            $this->command->info('📦 Datos migrados de cms_configs al header');
        }
    }
}
