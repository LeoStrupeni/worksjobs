function geosuccess(pos) {
    const crd = pos.coords;
    // console.log(`✅ Geolocalización obtenida - Lat: ${crd.latitude}, Lng: ${crd.longitude}`);

    $('input[name="latitude"]').val(crd.latitude);
    $('input[name="longitude"]').val(crd.longitude);
    $('input[name="jsongeolocation"]').val(JSON.stringify(pos));
    
    // Mostrar indicador visual de que se obtuvo la ubicación
    showGeoStatus('success', 'Ubicación obtenida correctamente');
}

function geoerror(err) {
    // console.warn(`❌ Error de geolocalización (${err.code}): ${err.message}`);
    
    // Limpiar campos de ubicación
    $('input[name="latitude"]').val('');
    $('input[name="longitude"]').val('');
    $('input[name="jsongeolocation"]').val('');
    
    let mensaje = '';
    switch(err.code) {
        case err.PERMISSION_DENIED:
            mensaje = "⚠️ Permiso de ubicación denegado. Por favor, habilite la ubicación en su navegador para registrar la posición GPS.";
            break;
        case err.POSITION_UNAVAILABLE:
            mensaje = "⚠️ Ubicación no disponible. Verifique que el GPS esté habilitado.";
            break;
        case err.TIMEOUT:
            mensaje = "⚠️ Tiempo de espera agotado al obtener la ubicación.";
            break;
        default:
            mensaje = "⚠️ Error desconocido al obtener la ubicación.";
    }
    
    showGeoStatus('error', mensaje);
}

function showGeoStatus(type, message) {
    // Eliminar mensajes anteriores
    $('.geo-status-alert').remove();
    
    // Crear alerta según el tipo
    const alertClass = type === 'success' ? 'alert-success' : 'alert-warning';
    const icon = type === 'success' ? '<i class="fas fa-map-marker-alt me-2"></i>' : '<i class="fas fa-exclamation-triangle me-2"></i>';
    
    const alert = `
        <div class="alert ${alertClass} alert-dismissible fade show geo-status-alert" role="alert">
            ${icon}${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Insertar en modales activos
    $('.modal.show .modal-body').prepend(alert);
    
    // Auto-ocultar después de 5 segundos si es éxito
    if (type === 'success') {
        setTimeout(() => {
            $('.geo-status-alert').fadeOut(300, function() { $(this).remove(); });
        }, 5000);
    }
}

function getGeolocation() {
    if (navigator.geolocation) {
        // console.log('🔍 Solicitando geolocalización...');
        navigator.geolocation.getCurrentPosition(geosuccess, geoerror, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        });
    } else {
        // console.error('❌ Geolocalización no soportada por este navegador');
        showGeoStatus('error', 'Su navegador no soporta geolocalización.');
    }
}