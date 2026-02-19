/**
 * Alertas Personalizadas - Strupeni Electrónica
 * Funciones reutilizables de SweetAlert2 con el branding de la empresa
 */

// ========== ALERTA DE GUARDANDO (LOADING) ==========
function showSavingAlert() {
    return Swal.fire({
        imageUrl: app_url + '/assets/media/Logo2.gif',
        imageWidth: 400,
        imageHeight: 300,
        imageAlt: 'Guardando',
        showConfirmButton: false,
        allowOutsideClick: false,
        allowEscapeKey: false,
        background: '#00274e',
        onOpen: () => {
            const popup = Swal.getPopup();
            if (popup) {
                popup.style.borderRadius = '30px';                
                // Quitar margin de la imagen
                const image = popup.querySelector('.swal2-image');
                if (image) {
                    image.style.margin = '0';
                }
            }
        }
    });
}

// ========== ALERTA DE ÉXITO ==========
function showSuccessAlert(title = 'Guardado exitosamente', text = 'Los cambios se guardaron correctamente', timer = 2000) {
    return Swal.fire({
        icon: 'success',
        title: title,
        text: text,
        timer: timer,
        showConfirmButton: false,
        customClass: {
            popup: 'custom-success-alert'
        }
    });
}

// ========== ALERTA DE ERROR ==========
function showErrorAlert(title = 'Error al guardar', text = 'Ocurrió un error al guardar los cambios. Por favor intenta nuevamente.') {
    return Swal.fire({
        icon: 'error',
        title: title,
        text: text,
        confirmButtonColor: '#00274e'
    });
}

// ========== ALERTA DE CONFIRMACIÓN ==========
function showConfirmAlert(title = '¿Estás seguro?', text = 'Esta acción no se puede deshacer', confirmText = 'Sí, confirmar', cancelText = 'Cancelar') {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#00274e',
        cancelButtonColor: '#d33',
        confirmButtonText: confirmText,
        cancelButtonText: cancelText
    });
}

// ========== ALERTA DE INFORMACIÓN ==========
function showInfoAlert(title = 'Información', text = '') {
    return Swal.fire({
        icon: 'info',
        title: title,
        text: text,
        confirmButtonColor: '#00274e'
    });
}

// ========== ALERTA DE CARGANDO GENÉRICA ==========
function showLoadingAlert(title = 'Cargando...', text = 'Por favor espera') {
    return Swal.fire({
        title: title,
        text: text,
        imageUrl: app_url + '/assets/media/Cargando.gif',
        imageWidth: 100,
        imageHeight: 100,
        imageAlt: 'Cargando',
        showConfirmButton: false,
        allowOutsideClick: false,
        allowEscapeKey: false,
    });
}

// ========== CERRAR ALERTA ACTUAL ==========
function closeSwal() {
    Swal.close();
}
