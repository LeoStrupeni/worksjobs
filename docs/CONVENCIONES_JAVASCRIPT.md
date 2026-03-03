# Convenciones de JavaScript - Proyecto Strupeni Electrónica

## SweetAlert (Swal)

### Uso de la propiedad `type` (NO `icon`)

En este proyecto se utiliza una versión específica de SweetAlert que requiere la propiedad `type` en lugar de `icon` para especificar el tipo de alerta.

**✅ CORRECTO:**
```javascript
Swal.fire({
    title: 'Título',
    text: 'Mensaje',
    type: 'success',  // ✅ Usar 'type'
    confirmButtonText: 'OK'
});
```

**❌ INCORRECTO:**
```javascript
Swal.fire({
    title: 'Título',
    text: 'Mensaje',
    icon: 'success',  // ❌ NO usar 'icon'
    confirmButtonText: 'OK'
});
```

### Tipos disponibles:
- `type: 'info'` - Para mensajes informativos o de carga
- `type: 'success'` - Para operaciones exitosas
- `type: 'error'` - Para errores
- `type: 'warning'` - Para advertencias
- `type: 'question'` - Para confirmaciones

### Ejemplos de uso en el proyecto:

#### Alerta de carga
```javascript
Swal.fire({
    title: 'Procesando...',
    html: 'Por favor espere...',
    type: 'info',
    allowOutsideClick: false,
    showConfirmButton: false,
    willOpen: () => {
        Swal.showLoading();
    }
});
```

#### Alerta de éxito
```javascript
Swal.fire({
    title: '¡Operación exitosa!',
    text: 'Los datos se guardaron correctamente',
    type: 'success',
    confirmButtonText: 'Cerrar'
});
```

#### Alerta de error
```javascript
Swal.fire({
    title: 'Error',
    text: 'Ocurrió un problema. Intente nuevamente.',
    type: 'error',
    confirmButtonText: 'Cerrar'
});
```

#### Confirmación
```javascript
Swal.fire({
    title: '¿Está seguro?',
    text: 'Esta acción no se puede revertir',
    type: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, continuar',
    cancelButtonText: 'Cancelar'
}).then((result) => {
    if (result.value) {
        // Acción confirmada
    }
});
```

## Toastr

### Uso de notificaciones toast
```javascript
toastr["success"]("Operación exitosa");
toastr["error"]("Ocurrió un error");
toastr["warning"]("Advertencia");
toastr["info"]("Información");
```

## JavaScript en archivos Blade

**⚠️ IMPORTANTE:** No agregar código JavaScript directamente en archivos `.blade.php` excepto en casos muy específicos.

**✅ CORRECTO:** Crear funciones en archivos JavaScript separados:
- `public/assets/js/local/jobdetail.js` - Funciones globales relacionadas con tareas
- `public/assets/js/local/job.js` - Funciones específicas de la vista de tareas
- Otros archivos según corresponda

**❌ EVITAR:** Código JavaScript embebido en archivos blade
```blade
{{-- NO HACER ESTO --}}
<script>
function miFuncion() {
    // código
}
</script>
```

## Estructura de archivos JavaScript

### Archivos globales (cargados en todas las vistas)
Estos se cargan en `panel/resources/views/Layout/script.blade.php`:
- `avatar.js`
- `useredit.js`
- `jobdetail.js` - **Usar este archivo para funciones relacionadas con tareas que necesiten estar disponibles globalmente**
- `geolocalizacion.js`

### Archivos específicos por página
Se cargan solo en las vistas que los necesitan mediante `@section('script_by_page')`:
- `job.js` - Solo en la vista de listado de tareas

## AJAX

### Configuración de headers
```javascript
$.ajax({
    url: app_url + '/ruta',
    type: 'POST',
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    data: { /* datos */ },
    success: function(response) {
        // Manejar respuesta exitosa
    },
    error: function(xhr) {
        // Manejar error
    }
});
```

**Nota:** La variable `app_url` está disponible globalmente y contiene la URL base de la aplicación.

## Buenas prácticas

1. **Funciones globales:** Colocar en archivos que se cargan en todas las vistas
2. **Funciones específicas:** Mantener en archivos que solo se cargan donde se necesitan
3. **Comentarios:** Documentar funciones importantes con JSDoc
4. **Manejo de errores:** Siempre incluir manejo de errores en llamadas AJAX
5. **Timeouts:** Establecer timeouts apropiados para operaciones largas (ejemplo: 120000 ms para sincronizaciones)

---
*Última actualización: 27 de febrero de 2026*
