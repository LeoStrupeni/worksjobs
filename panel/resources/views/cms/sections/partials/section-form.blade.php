<form id="form-{{ $section->slug }}" class="section-form" data-slug="{{ $section->slug }}">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <i class="fas fa-{{ getSectionIcon($section->slug) }} me-2 text-primary"></i>
            {{ $section->name }}
        </h4>
        <span class="badge badge-{{ $section->is_active ? 'success' : 'secondary' }}" style="border-radius: 8px; padding: 8px 14px;">
            <i class="fas fa-{{ $section->is_active ? 'check-circle' : 'minus-circle' }} me-2"></i>
            {{ $section->is_active ? __('cms.status.active') : __('cms.status.inactive') }}
        </span>
    </div>

    <div class="row">
        @foreach($section->config as $key => $value)
            @if(is_array($value) || is_object($value))
                <!-- Configuraciones complejas (objetos/arrays) -->
                <div class="col-12 mb-3">
                    <div class="alert alert-info" style="border-radius: 10px; border-left: 4px solid #3b82f6;">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>{{ trans('cms.fields.' . $key) ?: ucwords(str_replace('_', ' ', $key)) }}:</strong> 
                        {{ __('cms.messages.advanced_config') }}
                        <br>
                        <small class="text-muted">{{ json_encode($value) }}</small>
                    </div>
                </div>
            @else
                <!-- Campos simples -->
                <div class="col-md-6 mb-3">
                    <label class="font-weight-bold">
                        @if(strpos($key, 'color') !== false)
                            <i class="fas fa-palette me-2 text-primary"></i>
                        @elseif(strpos($key, 'url') !== false || strpos($key, 'link') !== false)
                            <i class="fas fa-link me-2 text-primary"></i>
                        @elseif(strpos($key, 'image') !== false || strpos($key, 'logo') !== false)
                            <i class="fas fa-image me-2 text-primary"></i>
                        @elseif(is_bool($value))
                            <i class="fas fa-toggle-on me-2 text-primary"></i>
                        @elseif(is_numeric($value))
                            <i class="fas fa-hashtag me-2 text-primary"></i>
                        @else
                            <i class="fas fa-font me-2 text-primary"></i>
                        @endif
                        {{ trans('cms.fields.' . $key) ?: ucwords(str_replace('_', ' ', $key)) }}
                    </label>

                    @if(is_bool($value))
                        <!-- Boolean: Select Sí/No -->
                        <select name="config[{{ $key }}]" class="form-control" style="border-radius: 10px;">
                            <option value="1" {{ $value ? 'selected' : '' }}>Sí</option>
                            <option value="0" {{ !$value ? 'selected' : '' }}>No</option>
                        </select>
                    @elseif(strpos($key, 'color') !== false)
                        <!-- Color picker -->
                        <div class="input-group">
                            <input type="color" 
                                   id="picker-{{ $section->slug }}-{{ $key }}" 
                                   class="form-control form-control-color color-picker" 
                                   value="{{ $value }}"
                                   data-text-target="#text-{{ $section->slug }}-{{ $key }}"
                                   style="max-width: 80px;">
                            <input type="text" 
                                   id="text-{{ $section->slug }}-{{ $key }}"
                                   name="config[{{ $key }}]" 
                                   class="form-control color-text" 
                                   value="{{ $value }}"
                                   data-picker-target="#picker-{{ $section->slug }}-{{ $key }}"
                                   pattern="^#[0-9A-Fa-f]{6}$"
                                   maxlength="7"
                                   placeholder="#000000">
                        </div>
                    @elseif(is_numeric($value))
                        <!-- Número -->
                        <input type="number" 
                               name="config[{{ $key }}]" 
                               class="form-control" 
                               value="{{ $value }}"
                               style="border-radius: 10px;">
                    @elseif(!str_ends_with($key, '_alt') && !str_ends_with($key, '_text') && (strpos($key, 'logo') !== false || strpos($key, 'image') !== false || strpos($key, 'imagen') !== false))
                        <!-- Campo de imagen con selector -->
                        @php
                            // Obtener el display_name si existe
                            $displayName = '';
                            if ($value) {
                                // Extraer filename de la URL
                                $urlParts = explode('/', $value);
                                $filename = end($urlParts);
                                
                                // Buscar en base de datos por filename o path
                                $mediaItem = \App\Models\CmsMedia::where('filename', $filename)
                                    ->orWhere('path', 'LIKE', '%' . $filename)
                                    ->first();
                                
                                if ($mediaItem && $mediaItem->display_name) {
                                    $displayName = $mediaItem->display_name;
                                } else {
                                    // Fallback: usar filename sin extensión
                                    $displayName = pathinfo($filename, PATHINFO_FILENAME);
                                }
                            }
                        @endphp
                        <div class="input-group">
                            <!-- Campo oculto con la URL real (lo que se envía al servidor) -->
                            <input type="hidden" 
                                   id="field-{{ $section->slug }}-{{ $key }}"
                                   name="config[{{ $key }}]" 
                                   value="{{ $value }}">
                            
                            <!-- Campo visible con el nombre amigable -->
                            <input type="text" 
                                   id="display-{{ $section->slug }}-{{ $key }}"
                                   class="form-control image-field" 
                                   value="{{ $displayName }}"
                                   placeholder="Seleccionar imagen..."
                                   readonly
                                   style="border-radius: 10px 0 0 10px; cursor: pointer; background-color: #fff;">
                            <button type="button" 
                                    class="btn btn-primary select-image-btn" 
                                    data-target="#field-{{ $section->slug }}-{{ $key }}"
                                    data-display-target="#display-{{ $section->slug }}-{{ $key }}"
                                    style="border-radius: 0 10px 10px 0;">
                                <i class="fas fa-images me-2"></i>Seleccionar
                            </button>
                        </div>
                        @if($value)
                            <div class="mt-2">
                                <img src="{{ env('APP_URL') }}/{{ $value }}" 
                                     alt="Preview" 
                                     class="img-thumbnail" 
                                     style="max-height: 100px; border-radius: 8px;"
                                     onerror="this.onerror=null; this.src='{{ env('APP_URL') }}/assets/media/no-image.png';">
                            </div>
                        @endif
                    @else
                        <!-- Texto normal -->
                        <input type="text" 
                               name="config[{{ $key }}]" 
                               class="form-control" 
                               value="{{ $value }}"
                               style="border-radius: 10px;">
                    @endif
                </div>
            @endif
        @endforeach
    </div>

    <div class="mt-4 pt-3 border-top">
        <button type="submit" class="btn btn-success" style="border-radius: 10px; font-weight: 500; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);">
            <i class="fas fa-save me-2"></i>{{ __('cms.buttons.save') }}
        </button>
        <a href="{{ route('cms.sections.versions', $section->slug) }}" class="btn btn-outline-secondary ms-2" style="border-radius: 10px;">
            <i class="fas fa-history me-2"></i>{{ __('cms.buttons.view_history') }}
        </a>
        <small class="text-muted ms-3">
            <i class="fas fa-clock me-2"></i>
            {{ __('cms.messages.last_update') }}: {{ $section->updated_at->format('d/m/Y H:i') }}
        </small>
    </div>
</form>
