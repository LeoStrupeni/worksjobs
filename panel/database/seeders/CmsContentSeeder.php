<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CmsPage;
use App\Models\CmsConfig;
use App\Models\CmsFlutterTheme;
use App\Models\User;

class CmsContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Obtener el primer usuario admin para asignar como creador
        $adminUser = User::whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })->first();

        if (!$adminUser) {
            $adminUser = User::first();
        }

        // ============= PÁGINAS CMS =============
        echo "Creando páginas CMS...\n";

        // Página: Hero de inicio
        CmsPage::create([
            'key' => 'home_hero',
            'title' => 'Hero Principal - Inicio',
            'content' => '<div class="hero-section">
    <h1>Bienvenido a Strupeni Electrónica</h1>
    <p class="lead">Soluciones tecnológicas de vanguardia para tu negocio</p>
    <div class="cta-buttons">
        <a href="#servicios" class="btn btn-primary btn-lg">Nuestros Servicios</a>
        <a href="#contacto" class="btn btn-outline-light btn-lg">Contáctanos</a>
    </div>
</div>',
            'draft_content' => '<div class="hero-section">
    <h1>Bienvenido a Strupeni Electrónica</h1>
    <p class="lead">Soluciones tecnológicas de vanguardia para tu negocio</p>
    <div class="cta-buttons">
        <a href="#servicios" class="btn btn-primary btn-lg">Nuestros Servicios</a>
        <a href="#contacto" class="btn btn-outline-light btn-lg">Contáctanos</a>
    </div>
</div>',
            'is_published' => true,
            'published_at' => now(),
            'user_id' => $adminUser->id
        ]);

        // Página: Servicios
        CmsPage::create([
            'key' => 'home_services',
            'title' => 'Sección de Servicios',
            'content' => '<div class="services-section">
    <h2>Nuestros Servicios</h2>
    <div class="row">
        <div class="col-md-4">
            <div class="service-card">
                <i class="fas fa-laptop-code fa-3x mb-3"></i>
                <h3>Desarrollo de Software</h3>
                <p>Aplicaciones web y móviles a medida para optimizar tus procesos de negocio.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="service-card">
                <i class="fas fa-mobile-alt fa-3x mb-3"></i>
                <h3>Aplicaciones Móviles</h3>
                <p>Apps nativas y multiplataforma con Flutter para Android e iOS.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="service-card">
                <i class="fas fa-cogs fa-3x mb-3"></i>
                <h3>Soporte Técnico</h3>
                <p>Mantenimiento y soporte continuo para tus sistemas electrónicos.</p>
            </div>
        </div>
    </div>
</div>',
            'draft_content' => '<div class="services-section">
    <h2>Nuestros Servicios</h2>
    <div class="row">
        <div class="col-md-4">
            <div class="service-card">
                <i class="fas fa-laptop-code fa-3x mb-3"></i>
                <h3>Desarrollo de Software</h3>
                <p>Aplicaciones web y móviles a medida para optimizar tus procesos de negocio.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="service-card">
                <i class="fas fa-mobile-alt fa-3x mb-3"></i>
                <h3>Aplicaciones Móviles</h3>
                <p>Apps nativas y multiplataforma con Flutter para Android e iOS.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="service-card">
                <i class="fas fa-cogs fa-3x mb-3"></i>
                <h3>Soporte Técnico</h3>
                <p>Mantenimiento y soporte continuo para tus sistemas electrónicos.</p>
            </div>
        </div>
    </div>
</div>',
            'is_published' => true,
            'published_at' => now(),
            'user_id' => $adminUser->id
        ]);

        // Página: About Us
        CmsPage::create([
            'key' => 'about_us',
            'title' => 'Acerca de Nosotros',
            'content' => '<div class="about-section">
    <h2>¿Quiénes Somos?</h2>
    <p>Strupeni Electrónica es una empresa líder en soluciones tecnológicas con más de 10 años de experiencia en el mercado.</p>
    
    <h3 class="mt-4">Nuestra Misión</h3>
    <p>Proporcionar soluciones tecnológicas innovadoras que impulsen el crecimiento de nuestros clientes, manteniendo los más altos estándares de calidad y servicio.</p>
    
    <h3 class="mt-4">Nuestra Visión</h3>
    <p>Ser reconocidos como el socio tecnológico preferido por empresas que buscan excelencia e innovación en sus procesos digitales.</p>
    
    <h3 class="mt-4">Valores</h3>
    <ul>
        <li><strong>Innovación:</strong> Siempre buscando las mejores tecnologías</li>
        <li><strong>Calidad:</strong> Excelencia en cada proyecto</li>
        <li><strong>Compromiso:</strong> Dedicados al éxito de nuestros clientes</li>
        <li><strong>Integridad:</strong> Honestidad y transparencia en todo momento</li>
    </ul>
</div>',
            'draft_content' => '<div class="about-section">
    <h2>¿Quiénes Somos?</h2>
    <p>Strupeni Electrónica es una empresa líder en soluciones tecnológicas con más de 10 años de experiencia en el mercado.</p>
    
    <h3 class="mt-4">Nuestra Misión</h3>
    <p>Proporcionar soluciones tecnológicas innovadoras que impulsen el crecimiento de nuestros clientes, manteniendo los más altos estándares de calidad y servicio.</p>
    
    <h3 class="mt-4">Nuestra Visión</h3>
    <p>Ser reconocidos como el socio tecnológico preferido por empresas que buscan excelencia e innovación en sus procesos digitales.</p>
    
    <h3 class="mt-4">Valores</h3>
    <ul>
        <li><strong>Innovación:</strong> Siempre buscando las mejores tecnologías</li>
        <li><strong>Calidad:</strong> Excelencia en cada proyecto</li>
        <li><strong>Compromiso:</strong> Dedicados al éxito de nuestros clientes</li>
        <li><strong>Integridad:</strong> Honestidad y transparencia en todo momento</li>
    </ul>
</div>',
            'is_published' => true,
            'published_at' => now(),
            'user_id' => $adminUser->id
        ]);

        // Página: Contacto
        CmsPage::create([
            'key' => 'contact',
            'title' => 'Contacto',
            'content' => '<div class="contact-section">
    <h2>Contáctanos</h2>
    <p class="lead">Estamos aquí para ayudarte. Ponte en contacto con nosotros.</p>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <h4><i class="fas fa-map-marker-alt me-2"></i>Dirección</h4>
            <p>Av. Principal #123<br>Ciudad, País</p>
            
            <h4 class="mt-4"><i class="fas fa-phone me-2"></i>Teléfono</h4>
            <p>+123 456 7890</p>
            
            <h4 class="mt-4"><i class="fas fa-envelope me-2"></i>Email</h4>
            <p>info@strupeni.com</p>
            
            <h4 class="mt-4"><i class="fas fa-clock me-2"></i>Horario</h4>
            <p>Lunes a Viernes: 8:00 AM - 6:00 PM<br>Sábados: 9:00 AM - 2:00 PM</p>
        </div>
        <div class="col-md-6">
            <h4>Envíanos un mensaje</h4>
            <form>
                <div class="mb-3">
                    <input type="text" class="form-control" placeholder="Tu nombre" required>
                </div>
                <div class="mb-3">
                    <input type="email" class="form-control" placeholder="Tu email" required>
                </div>
                <div class="mb-3">
                    <textarea class="form-control" rows="5" placeholder="Tu mensaje" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Enviar Mensaje</button>
            </form>
        </div>
    </div>
</div>',
            'draft_content' => '<div class="contact-section">
    <h2>Contáctanos</h2>
    <p class="lead">Estamos aquí para ayudarte. Ponte en contacto con nosotros.</p>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <h4><i class="fas fa-map-marker-alt me-2"></i>Dirección</h4>
            <p>Av. Principal #123<br>Ciudad, País</p>
            
            <h4 class="mt-4"><i class="fas fa-phone me-2"></i>Teléfono</h4>
            <p>+123 456 7890</p>
            
            <h4 class="mt-4"><i class="fas fa-envelope me-2"></i>Email</h4>
            <p>info@strupeni.com</p>
            
            <h4 class="mt-4"><i class="fas fa-clock me-2"></i>Horario</h4>
            <p>Lunes a Viernes: 8:00 AM - 6:00 PM<br>Sábados: 9:00 AM - 2:00 PM</p>
        </div>
        <div class="col-md-6">
            <h4>Envíanos un mensaje</h4>
            <form>
                <div class="mb-3">
                    <input type="text" class="form-control" placeholder="Tu nombre" required>
                </div>
                <div class="mb-3">
                    <input type="email" class="form-control" placeholder="Tu email" required>
                </div>
                <div class="mb-3">
                    <textarea class="form-control" rows="5" placeholder="Tu mensaje" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Enviar Mensaje</button>
            </form>
        </div>
    </div>
</div>',
            'is_published' => true,
            'published_at' => now(),
            'user_id' => $adminUser->id
        ]);

        echo "✓ 4 páginas creadas\n";

        // ============= CONFIGURACIONES =============
        echo "\nCreando configuraciones del sitio...\n";

        // General
        CmsConfig::create([
            'key' => 'site_title',
            'value' => 'Strupeni Electrónica',
            'type' => 'text',
            'group' => 'general',
            'description' => 'Título principal del sitio web'
        ]);

        CmsConfig::create([
            'key' => 'site_tagline',
            'value' => 'Soluciones Tecnológicas Avanzadas',
            'type' => 'text',
            'group' => 'general',
            'description' => 'Eslogan del sitio'
        ]);

        CmsConfig::create([
            'key' => 'site_email',
            'value' => 'info@strupeni.com',
            'type' => 'text',
            'group' => 'general',
            'description' => 'Email de contacto principal'
        ]);

        CmsConfig::create([
            'key' => 'site_phone',
            'value' => '+123 456 7890',
            'type' => 'text',
            'group' => 'general',
            'description' => 'Teléfono principal'
        ]);

        // Colores
        CmsConfig::create([
            'key' => 'primary_color',
            'value' => '#007bff',
            'type' => 'color',
            'group' => 'diseño',
            'description' => 'Color principal del tema'
        ]);

        CmsConfig::create([
            'key' => 'secondary_color',
            'value' => '#6c757d',
            'type' => 'color',
            'group' => 'diseño',
            'description' => 'Color secundario del tema'
        ]);

        CmsConfig::create([
            'key' => 'accent_color',
            'value' => '#28a745',
            'type' => 'color',
            'group' => 'diseño',
            'description' => 'Color de acento'
        ]);

        // Redes sociales
        CmsConfig::create([
            'key' => 'facebook_url',
            'value' => 'https://facebook.com/strupeni',
            'type' => 'text',
            'group' => 'redes_sociales',
            'description' => 'URL de Facebook'
        ]);

        CmsConfig::create([
            'key' => 'instagram_url',
            'value' => 'https://instagram.com/strupeni',
            'type' => 'text',
            'group' => 'redes_sociales',
            'description' => 'URL de Instagram'
        ]);

        CmsConfig::create([
            'key' => 'linkedin_url',
            'value' => 'https://linkedin.com/company/strupeni',
            'type' => 'text',
            'group' => 'redes_sociales',
            'description' => 'URL de LinkedIn'
        ]);

        // SEO
        CmsConfig::create([
            'key' => 'meta_description',
            'value' => 'Strupeni Electrónica ofrece soluciones tecnológicas innovadoras: desarrollo de software, aplicaciones móviles y soporte técnico especializado.',
            'type' => 'text',
            'group' => 'seo',
            'description' => 'Meta descripción del sitio'
        ]);

        CmsConfig::create([
            'key' => 'meta_keywords',
            'value' => 'desarrollo software, aplicaciones móviles, flutter, tecnología, electrónica',
            'type' => 'text',
            'group' => 'seo',
            'description' => 'Palabras clave para SEO'
        ]);

        echo "✓ 12 configuraciones creadas\n";

        // ============= TEMA FLUTTER =============
        echo "\nCreando tema Flutter por defecto...\n";

        $flutterThemeConfig = [
            'colors' => [
                'primary' => '#007bff',
                'primaryDark' => '#0056b3',
                'primaryLight' => '#4da3ff',
                'secondary' => '#6c757d',
                'accent' => '#28a745',
                'background' => '#ffffff',
                'surface' => '#f8f9fa',
                'error' => '#dc3545',
                'success' => '#28a745',
                'warning' => '#ffc107',
                'info' => '#17a2b8',
                'textPrimary' => '#212529',
                'textSecondary' => '#6c757d',
                'textOnPrimary' => '#ffffff',
                'divider' => '#dee2e6'
            ],
            'typography' => [
                'fontFamily' => 'Roboto',
                'fontSize' => [
                    'headline1' => 96,
                    'headline2' => 60,
                    'headline3' => 48,
                    'headline4' => 34,
                    'headline5' => 24,
                    'headline6' => 20,
                    'subtitle1' => 16,
                    'subtitle2' => 14,
                    'body1' => 16,
                    'body2' => 14,
                    'button' => 14,
                    'caption' => 12,
                    'overline' => 10
                ],
                'fontWeight' => [
                    'light' => 300,
                    'regular' => 400,
                    'medium' => 500,
                    'bold' => 700
                ]
            ],
            'spacing' => [
                'xs' => 4,
                'sm' => 8,
                'md' => 16,
                'lg' => 24,
                'xl' => 32,
                'xxl' => 48
            ],
            'borderRadius' => [
                'none' => 0,
                'sm' => 4,
                'md' => 8,
                'lg' => 12,
                'xl' => 16,
                'round' => 999
            ],
            'elevation' => [
                'none' => 0,
                'low' => 2,
                'medium' => 4,
                'high' => 8,
                'veryHigh' => 16
            ],
            'buttons' => [
                'height' => 48,
                'borderRadius' => 8,
                'elevation' => 2
            ],
            'cards' => [
                'borderRadius' => 12,
                'elevation' => 2,
                'padding' => 16
            ],
            'appBar' => [
                'height' => 56,
                'elevation' => 4
            ]
        ];

        CmsFlutterTheme::create([
            'name' => 'Tema Strupeni - Azul',
            'config_json' => json_encode($flutterThemeConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'is_active' => true,
            'version' => '1.0.0',
            'description' => 'Tema por defecto con colores corporativos de Strupeni Electrónica. Incluye esquema de colores azul profesional, tipografía Roboto y espaciados estándar.',
            'user_id' => $adminUser->id
        ]);

        echo "✓ 1 tema Flutter creado y activado\n";

        echo "\n";
        echo "════════════════════════════════════════════\n";
        echo "  ✓ CONTENIDO CMS CREADO EXITOSAMENTE\n";
        echo "════════════════════════════════════════════\n";
        echo "  • 4 páginas publicadas\n";
        echo "  • 12 configuraciones del sitio\n";
        echo "  • 1 tema Flutter activo\n";
        echo "════════════════════════════════════════════\n";
        echo "\nPuedes acceder al panel CMS en: /cms\n";
    }
}
