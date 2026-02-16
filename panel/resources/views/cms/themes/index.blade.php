@extends('layout')

@section('Content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-mobile-alt me-2"></i>
                        Temas de la Aplicación Flutter
                    </h4>
                    <a href="{{ route('cms.index') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-2"></i>Volver al CMS
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="mb-4">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createThemeModal">
                            <i class="fas fa-plus me-2"></i>Nuevo Tema
                        </button>
                        
                        <a href="{{ route('api.flutter.theme') }}" class="btn btn-info" target="_blank">
                            <i class="fas fa-code me-2"></i>Ver API JSON
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Versión</th>
                                    <th>Estado</th>
                                    <th>Descripción</th>
                                    <th>Última actualización</th>
                                    <th>Editado por</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($themes as $theme)
                                    <tr class="{{ $theme->is_active ? 'table-success' : '' }}">
                                        <td><strong>{{ $theme->name }}</strong></td>
                                        <td><code>{{ $theme->version }}</code></td>
                                        <td>
                                            @if($theme->is_active)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle me-2"></i>
                                                    Activo
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>{{ $theme->description ?? '-' }}</td>
                                        <td>{{ $theme->updated_at->format('d/m/Y H:i') }}</td>
                                        <td>{{ $theme->user->name ?? '-' }}</td>
                                        <td>
                                            <a href="{{ route('cms.themes.edit', $theme->id) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit me-2"></i>Editar
                                            </a>
                                            
                                            @if(!$theme->is_active)
                                                <form action="{{ route('cms.themes.activate', $theme->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Activar este tema? Se desactivarán los demás.');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="fas fa-power-off me-2"></i>Activar
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No hay temas creados</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Información de ayuda -->
                    <div class="alert alert-info mt-4">
                        <h6><i class="fas fa-info-circle me-2"></i>Cómo usar los temas Flutter:</h6>
                        <ul class="mb-0">
                            <li>Los temas se configuran en formato JSON con colores, fuentes y estilos</li>
                            <li>Solo un tema puede estar activo a la vez</li>
                            <li>La app Flutter consume el tema activo desde: <code>/api/flutter/theme</code></li>
                            <li>No requiere rebuild de la app, solo reiniciarla</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear Tema -->
<div class="modal fade" id="createThemeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('cms.themes.create') }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Crear Nuevo Tema Flutter</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Tema *</label>
                        <input type="text" name="name" class="form-control" required placeholder="ej: Tema Oscuro 2024">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Versión *</label>
                        <input type="text" name="version" class="form-control" required value="1.0.0">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Configuración JSON *</label>
                        <textarea name="config_json" class="form-control font-monospace" rows="10" required>{
  "primaryColor": "#1976D2",
  "accentColor": "#FF4081",
  "backgroundColor": "#FFFFFF",
  "textColor": "#000000",
  "fontFamily": "Roboto"
}</textarea>
                        <small class="text-muted">Formato JSON válido con la configuración del tema</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Descripción del tema"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Crear Tema</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
