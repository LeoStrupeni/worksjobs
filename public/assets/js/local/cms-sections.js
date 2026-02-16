// CMS Sections Manager
// Gestión de pestañas y formularios del CMS

$(document).ready(function() {
    
    // Variable para guardar el campo destino de la imagen
    let currentImageTarget = null;
    
    // ========== SINCRONIZACIÓN DE COLOR PICKERS ==========
    // Cuando cambia el color picker, actualiza el texto
    $(document).on('input change', '.color-picker', function() {
        const textTarget = $(this).data('text-target');
        $(textTarget).val(this.value);
    });

    // Cuando se edita el texto hex, actualiza el color picker
    $(document).on('input', '.color-text', function() {
        const value = $(this).val();
        // Validar formato hexadecimal
        if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
            const pickerTarget = $(this).data('picker-target');
            $(pickerTarget).val(value);
            $(this).removeClass('is-invalid');
        } else if (value.length === 7) {
            $(this).addClass('is-invalid');
        }
    });
    
    // ========== SELECTOR DE IMÁGENES ==========
    // Abrir modal de librería de medios
    $(document).on('click', '.select-image-btn', function() {
        currentImageTarget = $(this).data('target');
        loadMediaLibrary();
        $('#mediaLibraryModal').modal('show');
    });
    
    // Cargar imágenes de la librería
    function loadMediaLibrary() {
        console.log('🔄 Cargando librería de medios...');
        console.log('🌐 app_url:', app_url);
        
        $('#mediaLibraryContent').html(`
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Cargando...</span>
                </div>
            </div>
        `);
        
        $.ajax({
            url: '/cms/media',
            method: 'GET',
            data: { 
                type: 'image',
                format: 'data'
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                console.log('📦 Media response:', response);
                
                if (response.media && response.media.data && response.media.data.length > 0) {
                    console.log('✅ Encontradas', response.media.data.length, 'imágenes');
                    
                    let html = '<div class="row">';
                    response.media.data.forEach(function(item) {
                        // Construir URL correcta para mostrar la imagen
                        let imagePath = item.path;
                        let displayPath = imagePath; // Path para guardar en el campo
                        
                        console.log('📁 Path DB:', imagePath);
                        
                        // Si el path es 'cms-media/...' (formato nuevo), convertir a 'storage/cms-media/...'
                        if (imagePath.startsWith('cms-media/')) {
                            displayPath = '/storage/' + imagePath;
                        } 
                        // Si ya tiene 'storage/', dejarlo como está
                        else if (!imagePath.startsWith('/storage/') && !imagePath.startsWith('storage/')) {
                            // Formato antiguo o assets
                            displayPath = '/' + imagePath;
                        }
                        
                        const fullUrl = displayPath.startsWith('/') ? app_url + displayPath : app_url + '/' + displayPath;
                        
                        console.log('🖼️  URL final:', fullUrl);
                        
                        // Usar display_name si existe, sino filename sin extensión
                        const displayName = item.display_name || item.filename.replace(/\.[^/.]+$/, '');
                        
                        html += `
                            <div class="col-md-2 col-sm-3 col-4 mb-3">
                                <div class="media-item" style="cursor: pointer; border: 2px solid transparent; border-radius: 8px; padding: 5px; transition: all 0.2s;" data-path="${displayPath}" data-filename="${item.filename}" data-display-name="${displayName}">
                                    <img src="${fullUrl}" 
                                         alt="${item.alt_text || displayName}" 
                                         class="img-fluid" 
                                         style="border-radius: 5px; width: 100%; height: 120px; object-fit: cover;"
                                         onerror="if(!this.dataset.errored){this.dataset.errored='1';this.src='${app_url}/assets/media/no-image.png';}else{this.style.display='none';}">
                                    <small class="d-block text-center mt-1 text-truncate" title="${displayName}">${displayName}</small>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    $('#mediaLibraryContent').html(html);
                } else {
                    $('#mediaLibraryContent').html(`
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-images fa-3x text-muted mb-3 me-2"></i>
                            <p class="text-muted">No hay imágenes en la librería</p>
                            <a href="/cms/media" class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i>Subir Imágenes
                            </a>
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error ajax:', xhr.status, error);
                $('#mediaLibraryContent').html(`
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3 me-2"></i>
                        <p class="text-danger">Error al cargar la librería de medios</p>
                        <p class="small text-muted">Error: ${xhr.status} - ${error}</p>
                    </div>
                `);
            }
        });
    }
    
    // Seleccionar imagen
    $(document).on('click', '.media-item', function() {
        let path = $(this).data('path');
        let displayName = $(this).data('display-name');
        
        console.log('🎯 Imagen seleccionada. Path:', path, 'Display:', displayName);
        
        // Resaltar selección
        $('.media-item').css('border-color', 'transparent');
        $(this).css('border-color', '#667eea');
        
        // Actualizar campos
        if (currentImageTarget) {
            // La ruta debe empezar con / para ser absoluta
            let finalPath = path.startsWith('/') ? path : '/' + path;
            
            // Actualizar campo hidden con la URL
            $(currentImageTarget).val(finalPath);
            
            // Actualizar campo visible con el nombre amigable
            const displayTarget = $('.select-image-btn[data-target="' + currentImageTarget + '"]').data('display-target');
            if (displayTarget) {
                $(displayTarget).val(displayName);
            }
            
            console.log('💾 URL guardada:', finalPath, '| Nombre visible:', displayName);
            
            // Construir URL completa para el preview
            let previewUrl = finalPath.startsWith('/') ? app_url + finalPath : app_url + '/' + finalPath;
            
            console.log('🖼️  Preview URL:', previewUrl);
            
            // Actualizar preview si existe
            const previewContainer = $(currentImageTarget).closest('.col-md-6').find('.img-thumbnail');
            if (previewContainer.length) {
                previewContainer.attr('src', previewUrl);
            } else {
                // Crear preview si no existe
                $(currentImageTarget).closest('.input-group').after(`
                    <div class="mt-2">
                        <img src="${previewUrl}" alt="Preview" class="img-thumbnail" style="max-height: 100px; border-radius: 8px;" onerror="this.style.display='none';">
                    </div>
                `);
            }
        }
        
        // Cerrar modal
        setTimeout(function() {
            $('#mediaLibraryModal').modal('hide');
        }, 300);
    });
    
    // ========== CAMBIO DE PESTAÑAS ==========
    $('.section-tab').on('click', function (e) {
        e.preventDefault();
        
        const $this = $(this);
        const targetId = $this.data('target');
        
        // Si ya está activa, no hacer nada
        if ($this.hasClass('active')) {
            return;
        }
        
        // Desactivar todas las pestañas y contenidos
        $('.section-tab').removeClass('active');
        $('.tab-pane').removeClass('show active');
        
        // Activar esta pestaña y su contenido
        $this.addClass('active');
        $(targetId).addClass('show active');
    });

    // ========== SUBMIT DEL FORMULARIO ==========
    $(document).on('submit', '.section-form', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const slug = form.data('slug');
        const formData = new FormData(form[0]);

        // Convertir FormData a objeto
        const config = {};
        for (let [key, value] of formData.entries()) {
            const fieldName = key.replace('config[', '').replace(']', '');
            config[fieldName] = value;
        }

        // Mostrar loading
        Swal.fire({
            title: 'Procesando...',
            text: 'Guardando cambios',
            imageUrl: app_url + '/assets/media/Cargando.gif',
            imageWidth: 100,
            imageHeight: 100,
            imageAlt: 'Cargando',
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false
        });

        $.ajax({
            url: '/cms/sections/' + slug,
            method: 'POST',
            data: { config: config },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Guardado exitosamente',
                    text: response.message || 'Los cambios se guardaron correctamente',
                    timer: 2000,
                    showConfirmButton: false
                });
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al guardar',
                    text: 'Ocurrió un error al guardar los cambios. Por favor intenta nuevamente.',
                });
            }
        });
    });
});
