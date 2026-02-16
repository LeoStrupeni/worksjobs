// CMS Media Library Manager
// Gestión de librería de medios con upload, paginación y edición

document.addEventListener('DOMContentLoaded', function() {
    
    // Variables globales para paginación
    let currentPage = 1;
    let currentType = window.cmsMediaConfig.initialType || 'all';
    let isLoading = false;

    // Auto-submit upload form
    const uploadInput = document.getElementById('media-upload');
    if (uploadInput) {
        uploadInput.addEventListener('change', function() {
            if(this.files.length > 0) {
                const formData = new FormData(document.getElementById('upload-form'));
                
                // Mostrar loading
                Swal.fire({
                    title: 'Subiendo archivos...',
                    text: 'Por favor espera',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                fetch(window.cmsMediaConfig.uploadUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': window.cmsMediaConfig.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.json();
                })
                .then(data => {
                    if(data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: data.uploaded + ' archivo(s) subido(s) correctamente',
                            timer: 2000
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message || 'Error al subir archivos', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Error al subir los archivos: ' + error.message, 'error');
                });
            }
            this.value = ''; // Reset input
        });
    }

    // Filtros de tipo
    document.querySelectorAll('.filter-type').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const type = this.dataset.type;
            
            // Actualizar clase activa
            document.querySelectorAll('.filter-type').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            
            // Mostrar loading overlay
            const grid = document.getElementById('media-grid');
            grid.innerHTML = `
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-3 text-muted">Cargando archivos...</p>
                </div>
            `;
            
            // Resetear y cargar
            currentType = type;
            currentPage = 1;
            loadMedia(true);
        });
    });

    // Botón cargar más
    const loadMoreBtn = document.getElementById('load-more-btn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            currentPage = parseInt(this.dataset.page);
            loadMedia(false);
        });
    }

    // Función para cargar medios via Ajax
    function loadMedia(replace = false) {
        if (isLoading) return;
        isLoading = true;
        
        const btn = document.getElementById('load-more-btn');
        if (btn && !replace) {
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>Cargando...';
            btn.disabled = true;
        }
        
        fetch(`${window.cmsMediaConfig.mediaIndexUrl}?type=${currentType}&page=${currentPage}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const grid = document.getElementById('media-grid');
            
            if (replace) {
                if (data.html.trim() === '' || data.total === 0) {
                    // Mostrar mensaje de vacío
                    grid.innerHTML = `
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-folder-open fa-4x text-muted mb-4 d-block me-2"></i>
                            <h5 class="text-muted">No hay archivos de este tipo</h5>
                            <p class="text-muted">Intenta con otro filtro o sube nuevos archivos</p>
                        </div>
                    `;
                    // Eliminar botón cargar más si existe
                    const existingBtn = document.getElementById('load-more-btn');
                    if (existingBtn) existingBtn.parentElement.remove();
                } else {
                    grid.innerHTML = data.html;
                }
            } else {
                grid.insertAdjacentHTML('beforeend', data.html);
            }
            
            // Re-inicializar Fancybox para nuevos elementos
            if (typeof Fancybox !== 'undefined') {
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
            }
            
            // Actualizar botón cargar más
            if (data.hasMore && data.total > 0) {
                if (!btn) {
                    const container = grid.parentElement;
                    container.insertAdjacentHTML('beforeend', `
                        <div class="text-center mt-4">
                            <button type="button" class="btn btn-outline-primary" id="load-more-btn" data-page="${currentPage + 1}">
                                <i class="fas fa-chevron-down me-2"></i>Cargar más
                            </button>
                        </div>
                    `);
                    // Re-attach event listener
                    setTimeout(() => {
                        const newBtn = document.getElementById('load-more-btn');
                        if (newBtn) {
                            newBtn.addEventListener('click', function() {
                                currentPage = parseInt(this.dataset.page);
                                loadMedia(false);
                            });
                        }
                    }, 100);
                } else {
                    btn.dataset.page = currentPage + 1;
                    btn.innerHTML = '<i class="fas fa-chevron-down me-2"></i>Cargar más';
                    btn.disabled = false;
                }
            } else {
                if (btn) btn.remove();
            }
            
            isLoading = false;
        })
        .catch(error => {
            console.error('Error:', error);
            isLoading = false;
            if (btn) {
                btn.innerHTML = '<i class="fas fa-chevron-down me-2"></i>Cargar más';
                btn.disabled = false;
            }
        });
    }

});

// Funciones globales para los botones inline

// Ver imagen en modal
function viewMedia(id, url, name, type) {
    if(type === 'image') {
        document.getElementById('viewMediaTitle').textContent = name;
        document.getElementById('viewMediaImage').src = url;
        new bootstrap.Modal(document.getElementById('viewMediaModal')).show();
    }
}

// Copiar URL al portapapeles
function copyUrl(url) {
    navigator.clipboard.writeText(url).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'URL Copiada',
            text: 'La URL se copió al portapapeles',
            timer: 1500,
            showConfirmButton: false
        });
    });
}

// Editar nombre para mostrar
function editMediaName(id, currentName, originalName) {
    let displayName = '';
    Swal.fire({
        title: 'Editar Nombre',
        html: `
            <div class="text-start">
                <div class="mb-3">
                    <label class="form-label">Nombre para mostrar</label>
                    <input type="text" id="swal-display-name" class="form-control" value="${currentName || ''}" placeholder="Nombre del archivo">
                    <small class="text-muted">Original: ${originalName}</small>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            // Capturar el valor ya limpio
            const inputValue = document.getElementById('swal-display-name').value.trim();
            
            if (!inputValue) {
                Swal.showValidationMessage('El nombre no puede estar vacío');
                return false;
            }
            
            // Guardar en variable externa
            displayName = inputValue;
            
            const formData = new FormData();
            formData.append('display_name', displayName);
            
            return fetch(`/cms/media/${id}/update-name`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': window.cmsMediaConfig.csrfToken
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if(data.success) {
                    return displayName; // Devolver el nombre para usarlo en el then
                } else {
                    throw new Error(data.message || 'Error al actualizar');
                }
            })
            .catch(error => {
                Swal.showValidationMessage(`Error: ${error.message}`);
                // NO devolver nada aquí - deja que el modal permanezca abierto
            });
        }
    }).then((result) => {
        // Cuando preConfirm retorna un valor, result solo tiene {value: ...}
        // No tiene isConfirmed
        if (result.value) {
            // Actualizar el nombre en la UI sin recargar
            const nameElement = document.getElementById(`media-name-${id}`);
            if (nameElement) {
                // Actualizar el contenido manteniendo el ícono
                nameElement.innerHTML = `<i class="fas fa-edit me-2"></i>${displayName}`;
                // Actualizar el onclick con el nuevo nombre
                nameElement.setAttribute('onclick', `editMediaName(${id}, '${displayName.replace(/'/g, "\\'")}', '${originalName.replace(/'/g, "\\'")}')`);
            }
            
            Swal.fire({
                icon: 'success',
                title: 'Actualizado',
                text: 'Nombre actualizado correctamente',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
}

// Eliminar archivo
function deleteMedia(id, name) {
    Swal.fire({
        title: '¿Eliminar archivo?',
        text: `Se eliminará permanentemente: ${name}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/cms/media/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': window.cmsMediaConfig.csrfToken,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminado',
                        text: 'Archivo eliminado correctamente',
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'Error al eliminar', 'error');
                }
            });
        }
    });
}
