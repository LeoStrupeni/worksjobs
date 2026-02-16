@extends('layout')

@section('Content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Historial de Versiones: {{ $page->title }}
                    </h4>
                    <div>
                        <a href="{{ route('cms.pages.edit', $page->id) }}" class="btn btn-light btn-sm me-2">
                            <i class="fas fa-edit me-2"></i>Editar
                        </a>
                        <a href="{{ route('cms.index') }}" class="btn btn-light btn-sm">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Las versiones se guardan automáticamente cada vez que guardas cambios en el borrador. Puedes restaurar cualquier versión anterior.
                    </div>

                    @if($page->versions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 10%">Versión</th>
                                        <th style="width: 20%">Fecha</th>
                                        <th style="width: 20%">Creado por</th>
                                        <th style="width: 35%">Vista Previa</th>
                                        <th style="width: 15%" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($page->versions as $version)
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary fs-6">
                                                v{{ $version->version_number }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $version->created_at->format('d/m/Y H:i:s') }}
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                ({{ $version->created_at->diffForHumans() }})
                                            </small>
                                        </td>
                                        <td>
                                            <i class="fas fa-user me-2"></i>
                                            {{ $version->creator->name ?? 'Usuario eliminado' }}
                                        </td>
                                        <td>
                                            <div class="version-preview" style="max-height: 100px; overflow: hidden;">
                                                <small class="text-muted">
                                                    {!! Str::limit(strip_tags($version->content), 150) !!}
                                                </small>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <button 
                                                type="button" 
                                                class="btn btn-sm btn-primary me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#previewModal{{ $version->id }}"
                                                title="Ver contenido completo">
                                                <i class="fas fa-eye me-2"></i>
                                            </button>
                                            <form action="{{ route('cms.pages.restore', $page->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Restaurar esta versión al borrador? El contenido actual del borrador se guardará como nueva versión.');">
                                                @csrf
                                                <input type="hidden" name="version_id" value="{{ $version->id }}">
                                                <button type="submit" class="btn btn-sm btn-success" title="Restaurar versión">
                                                    <i class="fas fa-undo me-2"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Modal de Preview -->
                                    <div class="modal fade" id="previewModal{{ $version->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                            <div class="modal-content">
                                                <div class="modal-header bg-secondary text-white">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-eye me-2"></i>
                                                        Versión {{ $version->version_number }} - {{ $version->created_at->format('d/m/Y H:i') }}
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <strong>Creado por:</strong> {{ $version->creator->name ?? 'Usuario eliminado' }}
                                                        <br>
                                                        <strong>Fecha:</strong> {{ $version->created_at->format('d/m/Y H:i:s') }}
                                                    </div>
                                                    <hr>
                                                    <div class="content-preview">
                                                        {!! $version->content !!}
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                    <form action="{{ route('cms.pages.restore', $page->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Restaurar esta versión al borrador?');">
                                                        @csrf
                                                        <input type="hidden" name="version_id" value="{{ $version->id }}">
                                                        <button type="submit" class="btn btn-success">
                                                            <i class="fas fa-undo me-2"></i>Restaurar esta versión
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <div class="alert alert-light border">
                                <strong>Total de versiones:</strong> {{ $page->versions->count() }}
                                <br>
                                <strong>Primera versión:</strong> {{ $page->versions->last()->created_at->format('d/m/Y H:i') }}
                                <br>
                                <strong>Última versión:</strong> {{ $page->versions->first()->created_at->format('d/m/Y H:i') }}
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No hay versiones guardadas para esta página. Las versiones se crearán automáticamente cuando guardes cambios en el borrador.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.version-preview {
    font-size: 0.85rem;
    line-height: 1.4;
}

.content-preview {
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 5px;
}

.content-preview img {
    max-width: 100%;
    height: auto;
}

.content-preview table {
    width: 100%;
    margin-bottom: 1rem;
}
</style>
@endsection
