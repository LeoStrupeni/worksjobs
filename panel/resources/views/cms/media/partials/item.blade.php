<div class="col-6 col-md-4 col-lg-3 col-xl-2 mb-3">
    <div class="card h-100 media-item" style="box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        <div style="position: relative; padding-top: 100%; overflow: hidden; background: #f8f9fa;">
            @if($item->type == 'image')
                @php
                    // Construir URL correcta para la imagen
                    $imageUrl = $item->path;
                    
                    // Si el path es "cms-media/filename.png", agregar /storage/
                    if (!str_starts_with($imageUrl, 'http') && !str_starts_with($imageUrl, '/')) {
                        $imageUrl = '/' . $imageUrl;
                    }
                    if (!str_starts_with($imageUrl, 'http') && !str_contains($imageUrl, '/storage/')) {
                        $imageUrl = '/storage/' . ltrim($imageUrl, '/');
                    }
                    
                    $fullImageUrl = asset(ltrim($imageUrl, '/'));
                @endphp
                <a href="{{ $fullImageUrl }}" data-fancybox="gallery" data-caption="{{ $item->display_name ?: $item->original_name }}">
                    <img src="{{ $fullImageUrl }}" 
                        alt="{{ $item->alt_text ?? $item->original_name }}" 
                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; cursor: pointer;"
                        onerror="console.error('Error loading:', this.src); this.src='{{ asset('assets/media/no-image.png') }}'; this.style.objectFit='contain'; this.style.padding='20px';">
                </a>
            @elseif($item->type == 'video')
                @php
                    $videoUrl = $item->path;
                    if (!str_starts_with($videoUrl, 'http') && !str_contains($videoUrl, '/storage/')) {
                        $videoUrl = '/storage/' . ltrim($videoUrl, '/');
                    }
                    $fullVideoUrl = asset(ltrim($videoUrl, '/'));
                @endphp
                <a href="{{ $fullVideoUrl }}" data-fancybox="gallery" data-caption="{{ $item->display_name ?: $item->original_name }}">
                    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; cursor: pointer;">
                        <i class="fas fa-play-circle text-white me-2"></i>
                    </div>
                </a>
            @elseif($item->type == 'audio')
                @php
                    $audioUrl = $item->path;
                    if (!str_starts_with($audioUrl, 'http') && !str_contains($audioUrl, '/storage/')) {
                        $audioUrl = '/storage/' . ltrim($audioUrl, '/');
                    }
                    $fullAudioUrl = asset(ltrim($audioUrl, '/'));
                @endphp
                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 10px;">
                    <i class="fas fa-music text-white me-2"></i>
                    <audio controls style="width: 100%; max-width: 150px;">
                        <source src="{{ $fullAudioUrl }}" type="{{ $item->mime_type }}">
                    </audio>
                </div>
            @elseif($item->type == 'document')
                @php
                    $docUrl = $item->path;
                    if (!str_starts_with($docUrl, 'http') && !str_contains($docUrl, '/storage/')) {
                        $docUrl = '/storage/' . ltrim($docUrl, '/');
                    }
                    $fullDocUrl = asset(ltrim($docUrl, '/'));
                    
                    // Iconos por tipo de documento
                    $icon = 'fa-file-alt';
                    if (str_contains($item->mime_type, 'pdf')) $icon = 'fa-file-pdf';
                    elseif (str_contains($item->mime_type, 'word') || str_contains($item->original_name, '.doc')) $icon = 'fa-file-word';
                    elseif (str_contains($item->mime_type, 'excel') || str_contains($item->original_name, '.xls')) $icon = 'fa-file-excel';
                    elseif (str_contains($item->mime_type, 'powerpoint') || str_contains($item->original_name, '.ppt')) $icon = 'fa-file-powerpoint';
                @endphp
                <a href="{{ $fullDocUrl }}" target="_blank">
                    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: #6c757d; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                        <i class="fas {{ $icon }} text-white" style="font-size: 3rem;"></i>
                    </div>
                </a>
            @else
                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: #e9ecef; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-file text-muted me-2"></i>
                </div>
            @endif
        </div>
        <div class="card-body p-2">
            @php
                // Prioridad: display_name > nombre sin extensión
                $displayText = $item->display_name ?: pathinfo($item->original_name, PATHINFO_FILENAME);
            @endphp
            <p class="mb-1 small text-truncate" 
               id="media-name-{{ $item->id }}"
               data-media-id="{{ $item->id }}"
               title="Click para editar nombre. Original: {{ $item->original_name }}" 
               style="cursor: pointer; color: #667eea; font-weight: 600;" 
               onclick="editMediaName({{ $item->id }}, '{{ addslashes($displayText) }}', '{{ addslashes($item->original_name) }}')">
                <i class="fas fa-edit me-2"></i>{{ $displayText }}
            </p>
            <p class="mb-2 text-muted" style="font-size: 0.75rem;">{{ $item->formatted_size }}</p>
            <div class="btn-group btn-group-sm d-flex" role="group">
                @php
                    $copyUrl = $item->path;
                    if (!str_starts_with($copyUrl, 'http') && !str_contains($copyUrl, '/storage/')) {
                        $copyUrl = asset('storage/' . ltrim($copyUrl, '/'));
                    }
                @endphp
                <button type="button" class="btn btn-outline-primary flex-fill" onclick="copyUrl('{{ $copyUrl }}')" title="Copiar URL">
                    <i class="fas fa-copy me-2"></i>
                </button>
                @if($canDelete)
                <button type="button" class="btn btn-outline-danger flex-fill" onclick="deleteMedia({{ $item->id }}, '{{ addslashes($item->display_name ?: $item->original_name) }}')" title="Eliminar">
                    <i class="fas fa-trash me-2"></i>
                </button>
                @endif
            </div>
        </div>
    </div>
</div>
