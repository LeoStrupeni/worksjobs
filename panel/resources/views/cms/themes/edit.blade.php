@extends('layout')

@section('Content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-palette me-2"></i>
                        Editar Tema: {{ $theme->name }}
                    </h4>
                    <a href="{{ route('cms.themes') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('cms.themes.update', $theme->id) }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label">Nombre del Tema *</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $theme->name) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Versión *</label>
                            <input type="text" name="version" class="form-control" value="{{ old('version', $theme->version) }}" required>
                            <small class="text-muted">Formato sugerido: 1.0.0</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Estado</label>
                            <div>
                                @if($theme->is_active)
                                    <span class="badge bg-success fs-6">
                                        <i class="fas fa-check-circle me-2"></i>
                                        Tema Activo
                                    </span>
                                @else
                                    <span class="badge bg-secondary fs-6">Inactivo</span>
                                @endif
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Configuración JSON del Tema *</label>
                            <textarea 
                                name="config_json" 
                                id="config_json" 
                                class="form-control font-monospace" 
                                rows="20" 
                                required
                                style="font-size: 14px;"
                            >{{ old('config_json', json_encode($theme->config_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) }}</textarea>
                            <small class="text-muted">Debe ser un JSON válido</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $theme->description) }}</textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>
                                Guardar Cambios
                            </button>

                            @if(!$theme->is_active)
                                <button type="button" class="btn btn-success btn-lg" onclick="activateTheme()">
                                    <i class="fas fa-power-off me-2"></i>
                                    Activar Este Tema
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Ayuda
                    </h5>
                </div>
                <div class="card-body">
                    <h6>Estructura JSON sugerida:</h6>
                    <pre class="bg-light p-3 rounded"><code>{
  "primaryColor": "#1976D2",
  "secondaryColor": "#424242",
  "accentColor": "#FF4081",
  "backgroundColor": "#FFFFFF",
  "surfaceColor": "#F5F5F5",
  "errorColor": "#F44336",
  "textPrimaryColor": "#212121",
  "textSecondaryColor": "#757575",
  "fontFamily": "Roboto",
  "fontSize": {
    "small": 12,
    "medium": 14,
    "large": 16,
    "xlarge": 20
  },
  "borderRadius": 8,
  "spacing": {
    "xs": 4,
    "sm": 8,
    "md": 16,
    "lg": 24,
    "xl": 32
  }
}</code></pre>

                    <div class="alert alert-warning mt-3">
                        <small>
                            <strong>Nota:</strong> Los cambios se aplican inmediatamente en la API. 
                            La app Flutter debe reiniciarse para ver los cambios.
                        </small>
                    </div>

                    <hr>

                    <h6>Validar JSON:</h6>
                    <button type="button" class="btn btn-sm btn-secondary w-100" onclick="validateJSON()">
                        <i class="fas fa-check-circle me-2"></i>
                        Validar JSON
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script_by_page')
<script>
function validateJSON() {
    const jsonText = document.getElementById('config_json').value;
    try {
        JSON.parse(jsonText);
        alert('✅ JSON válido!');
    } catch (e) {
        alert('❌ JSON inválido:\n' + e.message);
    }
}

function activateTheme() {
    if (confirm('¿Activar este tema? Se desactivarán todos los demás temas.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("cms.themes.activate", $theme->id) }}';
        
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = '{{ csrf_token() }}';
        
        form.appendChild(csrf);
        document.body.appendChild(form);
        form.submit();
    }
}

// Auto-formatear JSON al perder el foco
document.getElementById('config_json').addEventListener('blur', function() {
    try {
        const obj = JSON.parse(this.value);
        this.value = JSON.stringify(obj, null, 2);
    } catch (e) {
        // No hacer nada si el JSON es inválido
    }
});
</script>
@endsection
