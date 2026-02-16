@extends('layout')

@section('style_by_page')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />
    <style>
    .media-item {
        transition: transform 0.2s, box-shadow 0.2s;
        border-radius: 12px;
        overflow: hidden;
    }
    .media-item:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.25) !important;
    }
    .object-fit-cover {
        object-fit: cover;
    }
    .filter-type {
        border-radius: 8px !important;
        font-weight: 500;
    }
    .filter-type.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
    }
    </style>
@endsection

@section('Content')
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card" style="border: none; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                        <div class="card-header d-flex justify-content-between align-items-center text-white border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0;">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-images me-2"></i>Librería de Medios</h5>
                            @if(in_array('create', Session::get('user')['permissions']['cms'] ?? []))
                            <button type="button" class="btn btn-light" onclick="document.getElementById('media-upload').click()" style="border-radius: 10px; font-weight: 500;">
                                <i class="fas fa-upload me-2"></i>Subir Archivos
                            </button>
                            <form id="upload-form" action="{{ route('cms.media.upload-multiple') }}" method="POST" enctype="multipart/form-data" style="display: none;">
                                @csrf
                                <input type="file" id="media-upload" name="files[]" multiple accept="image/*,video/*,audio/*,application/pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt">
                            </form>
                            @endif
                        </div>

                        <div class="card-body" style="background-color: #f8f9fa;">
                            <!-- Filtros de Tipo -->
                            <ul class="nav nav-tabs mb-3" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link filter-type {{ !request('type') ? 'active' : '' }}" href="#" data-type="all">
                                        Todos (<span id="count-all">{{ $media->total() }}</span>)
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link filter-type {{ request('type') == 'image' ? 'active' : '' }}" href="#" data-type="image">
                                        <i class="fas fa-image me-2"></i>Imágenes (<span id="count-image">{{ \App\Models\CmsMedia::where('type', 'image')->count() }}</span>)
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link filter-type {{ request('type') == 'video' ? 'active' : '' }}" href="#" data-type="video">
                                        <i class="fas fa-video me-2"></i>Videos (<span id="count-video">{{ \App\Models\CmsMedia::where('type', 'video')->count() }}</span>)
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link filter-type {{ request('type') == 'document' ? 'active' : '' }}" href="#" data-type="document">
                                        <i class="fas fa-file-alt me-2"></i>Documentos (<span id="count-document">{{ \App\Models\CmsMedia::where('type', 'document')->count() }}</span>)
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link filter-type {{ request('type') == 'audio' ? 'active' : '' }}" href="#" data-type="audio">
                                        <i class="fas fa-music me-2"></i>Audio (<span id="count-audio">{{ \App\Models\CmsMedia::where('type', 'audio')->count() }}</span>)
                                    </a>
                                </li>
                            </ul>

                            @if($media->isEmpty())
                                <div class="text-center py-5">
                                    <i class="fas fa-folder-open fa-4x text-muted mb-4 d-block me-2"></i>
                                    <h5 class="text-muted">No hay archivos en la librería</h5>
                                    <p class="text-muted">Comienza subiendo tu primer archivo multimedia</p>
                                    @if(in_array('create', Session::get('user')['permissions']['cms'] ?? []))
                                    <button type="button" class="btn btn-primary mt-3" onclick="document.getElementById('media-upload').click()" style="border-radius: 10px; font-weight: 500; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);">
                                        <i class="fas fa-upload me-2"></i>Subir primer archivo
                                    </button>
                                    @endif
                                </div>
                            @else
                                <!-- Grid de Medios -->
                                <div class="row g-3" id="media-grid">
                                    @foreach($media as $item)
                                        @include('cms.media.partials.item', [
                                            'item' => $item,
                                            'canEdit' => in_array('update', Session::get('user')['permissions']['cms'] ?? []),
                                            'canDelete' => in_array('delete', Session::get('user')['permissions']['cms'] ?? [])
                                        ])
                                    @endforeach
                                </div>

                                <!-- Botón Cargar Más -->
                                @if($media->hasMorePages())
                                <div class="text-center mt-4">
                                    <button type="button" class="btn btn-primary" id="load-more-btn" data-page="2" style="border-radius: 10px; font-weight: 500; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);">
                                        <i class="fas fa-chevron-down me-2"></i>Cargar más
                                    </button>
                                </div>
                                @endif
                            @endif
                        </div>
                    </div>
            </div>
        </div>
    </div>
@endsection

@section('script_by_page')
    <script>
        // Configuración para cms-media.js
        window.cmsMediaConfig = {
            csrfToken: '{{ csrf_token() }}',
            uploadUrl: '{{ route('cms.media.upload-multiple') }}',
            mediaIndexUrl: '{{ route('cms.media') }}',
            initialType: '{{ request('type', 'all') }}'
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <script>
        // Inicializar Fancybox para lightbox de imágenes y videos
        Fancybox.bind('[data-fancybox]', {
            groupAll: true,
            Toolbar: {
                display: {
                    left: [],
                    middle: [],
                    right: ['close']
                }
            }
        });
    </script>
    <script src="{{ env('APP_URL') }}/assets/js/local/cms-media.js"></script>
@endsection

