@extends('layout')

@section('Content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card" style="border: none; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <div class="card-header text-white d-flex justify-content-between align-items-center border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0;">
                    <h4 class="mb-0 fw-bold">
                        <i class="fas fa-sliders-h me-2"></i>
                        Configuraciones del Sitio
                    </h4>
                    <a href="{{ route('cms.index') }}" class="btn btn-light" style="border-radius: 10px; font-weight: 500;">
                        <i class="fas fa-arrow-left me-2"></i>Volver al CMS
                    </a>
                </div>
                <div class="card-body" style="background-color: #f8f9fa;">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" style="border-radius: 12px; border-left: 4px solid #10b981;">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <div class="mb-4">
                        <button class="btn btn-success" data-toggle="modal" data-target="#createConfigModal" style="border-radius: 10px; font-weight: 500; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);">
                            <i class="fas fa-plus me-2"></i>Nueva Configuración
                        </button>
                    </div>

                    <!-- Agrupar por grupos -->
                    @php
                        $groupedConfigs = $configs->groupBy('group');
                    @endphp

                    @foreach($groupedConfigs as $group => $groupConfigs)
                        <div class="card mb-4" style="border: none; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                            <div class="card-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <h5 class="mb-0 text-capitalize fw-bold"><i class="fas fa-folder me-2"></i>{{ $group }}</h5>
                            </div>
                            <div class="card-body" style="background: white;">
                                <div class="row">
                                    @foreach($groupConfigs as $config)
                                        <div class="col-md-6 mb-3">
                                            <form action="{{ route('cms.configs.update', $config->id) }}" method="POST">
                                                @csrf
                                                <label class="form-label font-weight-bold">
                                                    <i class="fas fa-cog text-primary me-2"></i>{{ $config->key }}
                                                    @if($config->description)
                                                        <small class="text-muted d-block"><i class="fas fa-info-circle me-2"></i>{{ $config->description }}</small>
                                                    @endif
                                                </label>

                                                <div class="input-group">
                                                    @if($config->type === 'color')
                                                        <input type="color" name="value" class="form-control form-control-color" value="{{ $config->value }}" required style="border-radius: 10px 0 0 10px;">
                                                    @elseif($config->type === 'boolean')
                                                        <select name="value" class="form-control" required style="border-radius: 10px 0 0 10px;">
                                                            <option value="1" {{ $config->value == '1' ? 'selected' : '' }}>Sí</option>
                                                            <option value="0" {{ $config->value == '0' ? 'selected' : '' }}>No</option>
                                                        </select>
                                                    @elseif($config->type === 'number')
                                                        <input type="number" name="value" class="form-control" value="{{ $config->value }}" required style="border-radius: 10px 0 0 10px;">
                                                    @elseif($config->type === 'json')
                                                        <textarea name="value" class="form-control" rows="3" required style="border-radius: 10px;">{{ $config->value }}</textarea>
                                                    @else
                                                        <input type="text" name="value" class="form-control" value="{{ $config->value }}" required style="border-radius: 10px 0 0 10px;">
                                                    @endif
                                                    
                                                    @if($config->type !== 'json')
                                                    <button type="submit" class="btn btn-primary" style="border-radius: 0 10px 10px 0;">
                                                        <i class="fas fa-save me-2"></i>
                                                    </button>
                                                    @else
                                                    <button type="submit" class="btn btn-primary mt-2" style="border-radius: 10px; width: 100%;">
                                                        <i class="fas fa-save me-2"></i>Guardar
                                                    </button>
                                                    @endif
                                                </div>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @if($configs->isEmpty())
                        <div class="alert alert-info" style="border-radius: 12px; border-left: 4px solid #3b82f6;">
                            <i class="fas fa-info-circle me-2"></i>
                            No hay configuraciones creadas. Haz clic en "Nueva Configuración" para comenzar.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear Configuración -->
<div class="modal fade" id="createConfigModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 20px; overflow: hidden; border: none;">
            <form action="{{ route('cms.configs.create') }}" method="POST">
                @csrf
                <div class="modal-header text-white border-0" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 20px 20px 0 0;">
                    <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i>Nueva Configuración</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" style="background: #f8f9fa;">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold"><i class="fas fa-key text-primary me-2"></i>Clave (key) *</label>
                        <input type="text" name="key" class="form-control" required placeholder="ej: primary_color" style="border-radius: 10px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold"><i class="fas fa-font text-primary me-2"></i>Valor *</label>
                        <input type="text" name="value" class="form-control" required style="border-radius: 10px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold"><i class="fas fa-list text-primary me-2"></i>Tipo *</label>
                        <select name="type" class="form-control" required style="border-radius: 10px;">
                            <option value="text">Texto</option>
                            <option value="color">Color</option>
                            <option value="number">Número</option>
                            <option value="boolean">Booleano (Sí/No)</option>
                            <option value="json">JSON</option>
                            <option value="image">Imagen (URL)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold"><i class="fas fa-folder text-primary me-2"></i>Grupo *</label>
                        <input type="text" name="group" class="form-control" required placeholder="ej: colors, general" value="general" style="border-radius: 10px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold"><i class="fas fa-align-left text-primary me-2"></i>Descripción</label>
                        <textarea name="description" class="form-control" rows="2" style="border-radius: 10px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0" style="background: #f8f9fa;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 10px;">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-success" style="border-radius: 10px; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);">
                        <i class="fas fa-check me-2"></i>Crear
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
