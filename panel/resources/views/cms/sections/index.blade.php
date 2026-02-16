@php
function getSectionIcon($slug) {
    $icons = [
        'general' => 'cog',
        'header' => 'window-maximize',
        'carousel' => 'images',
        'historia' => 'book',
        'servicios' => 'briefcase',
        'banner' => 'image',
        'instagram' => 'instagram',
        'footer' => 'window-minimize',
        'flutter_theme' => 'mobile-alt',
    ];
    return $icons[$slug] ?? 'file';
}
@endphp

@extends('layout')

@section('style_by_page')
    <style>
        /* Espaciado de iconos - Global */
        i.fas, i.far, i.fab {
            margin-right: 0.5rem;
        }

        /* Separación adicional en botones */
        .btn i.fas {
            margin-right: 0.5rem;
        }

        .nav-tabs .nav-link {
            color: #6b7280;
            border: none;
            padding: 12px 20px;
        }
        .nav-tabs .nav-link:hover {
            color: #667eea;
            background: #f3f4f6;
        }
        .nav-tabs .nav-link.active {
            color: white !important;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            border: none !important;
        }
        .color-text.is-invalid {
            border-color: #ef4444 !important;
            background-color: #fee2e2 !important;
        }

        /* Modal de librería de medios */
        .media-item:hover {
            border-color: #667eea !important;
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
    </style>
@endsection

@section('Content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm" style="border-radius: 15px; overflow: hidden;">
                    <div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <h4 class="mb-0">
                            <i class="fas fa-sliders-h me-2"></i>
                            Gestión de Secciones del Sitio Web
                        </h4>
                        <div class="d-flex gap-2">
                            <a href="{{ route('cms.api-config.index') }}" class="btn btn-light" title="Ir a Configuración de API">
                                <i class="fas fa-cog me-2"></i>
                                Configuración API
                            </a>
                            <a href="/cms/media" class="btn btn-light" title="Ir a la Librería de Medios">
                                <i class="fas fa-images me-2"></i>
                                Librería de Medios
                            </a>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <!-- Tabs de Secciones -->
                        <ul class="nav nav-tabs border-bottom pt-3 px-3" role="tablist" style="background: #f8f9fa;">
                            @foreach($sections as $index => $section)
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link section-tab {{ $index === 0 ? 'active' : '' }}" 
                                       id="tab-{{ $section->slug }}" 
                                       href="javascript:void(0)"
                                       data-target="#section-{{ $section->slug }}"
                                       data-slug="{{ $section->slug }}"
                                       style="border-radius: 8px 8px 0 0; font-weight: 500; transition: all 0.2s;">
                                        <i class="fas fa-{{ getSectionIcon($section->slug) }} me-2"></i>
                                        {{ $section->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        <!-- Contenido de Tabs -->
                        <div class="tab-content p-4" id="sectionTabsContent" style="background: white;">
                            @foreach($sections as $index => $section)
                                <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" 
                                     id="section-{{ $section->slug }}" 
                                     role="tabpanel">
                                    
                                    <!-- Todas las secciones pre-cargadas -->
                                    @include('cms.sections.partials.section-form', ['section' => $section])
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Librería de Medios -->
    <div class="modal fade" id="mediaLibraryModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content" style="border-radius: 15px;">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px 15px 0 0;">
                    <h5 class="modal-title">
                        <i class="fas fa-images me-2"></i>
                        Seleccionar Imagen
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="min-height: 400px;">
                    <div id="mediaLibraryContent" class="row">
                        <div class="col-12 text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script_by_page')
    <script>
        // URL base de la aplicación para JavaScript
        const app_url = "{{ env('APP_URL') }}";
        
        // Traducciones globales para el archivo JavaScript externo
        window.cmsTranslations = @json(trans('cms'));
    </script>
    <script src="{{env('APP_URL')}}/assets/js/local/cms-sections.js"></script>
@endsection
