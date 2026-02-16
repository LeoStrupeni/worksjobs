@extends('layout')

@section('Content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card" style="border: none; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <div class="card-header text-white border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0;">
                    <h3 class="mb-0 fw-bold">
                        <i class="fas fa-cog me-2"></i>
                        Panel de Gestión de Contenidos (CMS)
                    </h3>
                </div>
                <div class="card-body" style="background-color: #f8f9fa;">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 12px; border-left: 4px solid #10b981;">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <!-- Tabs de navegación -->
                    <ul class="nav nav-tabs mb-4 border-0" id="cmsTabs" role="tablist" style="background: white; border-radius: 12px; padding: 8px;">
                        <li class="nav-item">
                            <a class="nav-link active" id="pages-tab" data-toggle="tab" href="#pages" style="border-radius: 8px; font-weight: 500;">
                                <i class="fas fa-file-alt me-2"></i>Páginas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="media-tab" data-toggle="tab" href="#media" style="border-radius: 8px; font-weight: 500;">
                                <i class="fas fa-images me-2"></i>Librería de Medios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="configs-tab" data-toggle="tab" href="#configs" style="border-radius: 8px; font-weight: 500;">
                                <i class="fas fa-sliders-h me-2"></i>Configuraciones
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="themes-tab" data-toggle="tab" href="#themes" style="border-radius: 8px; font-weight: 500;">
                                <i class="fas fa-mobile-alt me-2"></i>Temas Flutter
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="api-config-tab" href="{{ route('cms.api-config.index') }}" style="border-radius: 8px; font-weight: 500;">
                                <i class="fas fa-cog me-2"></i>Configuración API
                            </a>
                        </li>
                    </ul>

                    <!-- Contenido de tabs -->
                    <div class="tab-content" id="cmsTabsContent">
                        <!-- Tab Páginas -->
                        <div class="tab-pane fade show active" id="pages">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="fw-bold"><i class="fas fa-file-alt me-2 text-primary"></i>Gestión de Páginas Web</h5>
                                <button class="btn btn-success" data-toggle="modal" data-target="#createPageModal" style="border-radius: 10px; font-weight: 500; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);">
                                    <i class="fas fa-plus me-2"></i>Nueva Página
                                </button>
                            </div>

                            <div class="table-responsive" style="border-radius: 15px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                <table class="table table-hover mb-0" style="background: white;">
                                    <thead style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                        <tr>
                                            <th>Título</th>
                                            <th>Slug</th>
                                            <th>Estado</th>
                                            <th>Actualizado</th>
                                            <th class="text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($pages as $page)
                                            <tr>
                                                <td><strong>{{ $page->title }}</strong></td>
                                                <td><code style="background: #f1f5f9; padding: 4px 8px; border-radius: 6px;">{{ $page->key }}</code></td>
                                                <td>
                                                    @if($page->is_published)
                                                        <span class="badge badge-success" style="border-radius: 8px; padding: 6px 12px;">
                                                            <i class="fas fa-check-circle me-2"></i>Publicado
                                                        </span>
                                                    @else
                                                        <span class="badge badge-warning" style="border-radius: 8px; padding: 6px 12px;">
                                                            <i class="fas fa-clock me-2"></i>Borrador
                                                        </span>
                                                    @endif
                                                </td>
                                                <td><i class="fas fa-calendar-alt text-muted me-2"></i>{{ $page->updated_at->format('d/m/Y H:i') }}</td>
                                                <td class="text-right">
                                                    <a href="{{ route('cms.pages.edit', $page->id) }}" class="btn btn-sm btn-primary me-2" style="border-radius: 8px;">
                                                        <i class="fas fa-edit me-2"></i>
                                                    </a>
                                                    @if($page->draft_content && !$page->is_published)
                                                        <a href="{{ route('cms.pages.preview', $page->id) }}" class="btn btn-sm btn-info me-2" target="_blank" style="border-radius: 8px;">
                                                            <i class="fas fa-eye me-2"></i>
                                                        </a>
                                                    @endif
                                                    <a href="{{ route('cms.pages.versions', $page->id) }}" class="btn btn-sm btn-secondary" title="Ver historial de versiones" style="border-radius: 8px;">
                                                        <i class="fas fa-history me-2"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-4">
                                                    <i class="fas fa-folder-open fa-3x text-muted mb-3 d-block me-2"></i>
                                                    <p class="text-muted mb-0">No hay páginas creadas aún</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab Librería de Medios -->
                        <div class="tab-pane fade" id="media" role="tabpanel">
                            <div class="card" style="border: none; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                <div class="card-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <h5 class="mb-0"><i class="fas fa-images me-2"></i>Librería de Medios</h5>
                                </div>
                                <div class="card-body" style="background: white;">
                                    <p class="text-muted mb-3">
                                        <i class="fas fa-info-circle text-info me-2"></i>
                                        Gestiona imágenes, videos y otros archivos multimedia del sitio web.
                                    </p>
                                    <a href="{{ route('cms.media') }}" class="btn btn-primary" style="border-radius: 10px; font-weight: 500; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);">
                                        <i class="fas fa-images me-2"></i>Administrar Medios
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Configuraciones -->
                        <div class="tab-pane fade" id="configs" role="tabpanel">
                            <div class="card" style="border: none; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                <div class="card-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Configuraciones del Sitio</h5>
                                </div>
                                <div class="card-body" style="background: white;">
                                    <p class="text-muted mb-3">
                                        <i class="fas fa-info-circle text-info me-2"></i>
                                        Gestiona colores, textos, imágenes y otros valores de configuración del sitio web.
                                    </p>
                                    <a href="{{ route('cms.configs') }}" class="btn btn-primary" style="border-radius: 10px; font-weight: 500; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);">
                                        <i class="fas fa-cog me-2"></i>Administrar Configuraciones
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Temas Flutter -->
                        <div class="tab-pane fade" id="themes" role="tabpanel">
                            <div class="card" style="border: none; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                                <div class="card-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <h5 class="mb-0"><i class="fas fa-mobile-alt me-2"></i>Temas de la Aplicación Flutter</h5>
                                </div>
                                <div class="card-body" style="background: white;">
                                    <div class="table-responsive" style="border-radius: 12px; overflow: hidden;">
                                        <table class="table table-hover mb-0">
                                            <thead style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                                <tr>
                                                    <th>Nombre</th>
                                                    <th>Versión</th>
                                                    <th>Estado</th>
                                                    <th>Última actualización</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($themes as $theme)
                                                    <tr>
                                                        <td><strong>{{ $theme->name }}</strong></td>
                                                        <td><code style="background: #f1f5f9; padding: 4px 8px; border-radius: 6px;">{{ $theme->version }}</code></td>
                                                        <td>
                                                            @if($theme->is_active)
                                                                <span class="badge badge-success" style="border-radius: 8px; padding: 6px 12px;">
                                                                    <i class="fas fa-check-circle me-2"></i>Activo
                                                                </span>
                                                            @else
                                                                <span class="badge badge-secondary" style="border-radius: 8px; padding: 6px 12px;">
                                                                    <i class="fas fa-minus-circle me-2"></i>Inactivo
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td><i class="fas fa-calendar-alt text-muted me-2"></i>{{ $theme->updated_at->format('d/m/Y H:i') }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="text-center py-4">
                                                            <i class="fas fa-palette fa-3x text-muted mb-3 d-block me-2"></i>
                                                            <p class="text-muted mb-0">No hay temas creados aún</p>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="mt-3">
                                        <a href="{{ route('cms.themes') }}" class="btn btn-primary" style="border-radius: 10px; font-weight: 500; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);">
                                            <i class="fas fa-palette me-2"></i>Administrar Temas
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear Página -->
<div class="modal fade" id="createPageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style=\"border-radius: 20px; overflow: hidden; border: none;\">
            <form action="{{ route('cms.pages.create') }}" method="POST">
                @csrf
                <div class="modal-header text-white border-0" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 20px 20px 0 0;">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-plus-circle me-2"></i>
                        Nueva Página
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" style="background: #f8f9fa;">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold"><i class="fas fa-key me-2 text-primary"></i>Identificador (key) *</label>
                        <input type="text" name="key" class="form-control" required placeholder="ej: home_hero, about_us" style="border-radius: 10px;">
                        <small class="text-muted"><i class="fas fa-info-circle me-2"></i>Solo letras, números y guiones bajos. Sin espacios.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold"><i class="fas fa-heading text-primary me-2"></i>Título *</label>
                        <input type="text" name="title" class="form-control" required placeholder="ej: Hero de Home" style="border-radius: 10px;">
                    </div>
                </div>
                <div class="modal-footer border-0" style="background: #f8f9fa;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 10px;">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-success" style="border-radius: 10px; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);">
                        <i class="fas fa-check me-2"></i>Crear Página
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script_by_page')
<script>
    // Auto-dismiss alerts
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
</script>
@endsection
