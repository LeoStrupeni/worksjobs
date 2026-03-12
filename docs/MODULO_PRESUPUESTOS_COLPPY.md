# Módulo de Presupuestos desde Colppy

## 📋 Descripción

Módulo completo para visualizar presupuestos borradores desde la API de Colppy. Los datos **NO se almacenan en la base de datos local**, solo se consultan y muestran visualmente.

## 🎯 Características

- **Consulta directa a Colppy API**: Usa `listar_facturasventa` para obtener presupuestos
- **Filtrado automático**: Solo muestra presupuestos borradores (nroFactura >= "0002-00000000")
- **Sin persistencia local**: Los datos no se guardan en BD, solo visualización en tiempo real
- **Paginación**: Compatible con el sistema de paginación existente del proyecto
- **Búsqueda**: Permite buscar por número de factura
- **Ordenamiento**: Columnas ordenables (fecha, nro, cliente, total, estado)
- **Estados visuales**: Badges de colores según el estado del presupuesto

## 📁 Archivos Creados/Modificados

### Nuevos Archivos

1. **Controlador**
   - `panel/app/Http/Controllers/PresupuestoController.php`
   - Maneja la vista y la obtención de datos vía AJAX

2. **Vista**
   - `panel/resources/views/presupuestos.blade.php`
   - Tabla responsiva con DataTable personalizado

3. **JavaScript**
   - `public/assets/js/local/presupuesto.js`
   - Lógica de renderizado de la tabla y formato de datos

### Archivos Modificados

1. **ColppyService**
   - `panel/app/Services/ColppyService.php`
   - Agregado método `listarFacturasVenta()` (líneas ~689-770)

2. **Rutas**
   - `panel/routes/web.php`
   - Agregado import de `PresupuestoController`
   - Agregadas rutas:
     - `GET /presupuestos` → Vista principal
     - `POST /presupuestos/table` → AJAX DataTable

3. **Header**
   - `panel/resources/views/Layout/header.blade.php`
   - Agregado ítem "Presupuestos" en menú Administración

4. **Offcanvas**
   - `panel/resources/views/Layout/header/offcanvas.blade.php`
   - Agregado ítem "Presupuestos" en menú lateral móvil

## 🔧 Implementación Técnica

### ColppyService - Método listarFacturasVenta()

```php
public function listarFacturasVenta(
    int $start = 0,
    int $limit = 50,
    array $filtros = [],
    array $orden = []
): array
```

**Parámetros:**
- `$start`: Inicio de paginación (calculado como `($page - 1) * $limit`)
- `$limit`: Cantidad de registros por página
- `$filtros`: Array de filtros para Colppy (field, op, value)
- `$orden`: Array de ordenamiento (field, dir)

**Payload a Colppy:**
```php
[
    'provision' => 'FacturaVenta',
    'operacion' => 'listar_facturasventa',
    'parameters' => [
        'sesion' => [...],
        'idEmpresa' => '...',
        'start' => 0,
        'limit' => 50,
        'filter' => [
            [
                'field' => 'nroFactura',
                'op' => '>=',
                'value' => '0002-00000000'
            ]
        ],
        'order' => [...]
    ]
]
```

### Controlador - PresupuestoController

**Métodos:**

1. `index()` - Vista principal
   - Verifica autenticación
   - Renderiza vista `presupuestos.blade.php`

2. `getPresupuestosDataTable(Request $request)` - AJAX DataTable
   - Recibe: page, limit, order, search
   - Construye filtros para Colppy
   - Llama a `ColppyService::listarFacturasVenta()`
   - Formatea respuesta para tableAjaxLocal.js
   - Retorna JSON compatible con la tabla

**Formato de Respuesta:**
```json
{
    "totales": 150,
    "filtrados": 150,
    "paginastotal": 15,
    "datos": [
        {
            "idFactura": "123",
            "nroFactura": "0002-00000045",
            "fechaFactura": "2025-03-10",
            "idCliente": "456",
            "nombreCliente": "Cliente Ejemplo SA",
            "totalFactura": "15000.00",
            "descripcion": "Presupuesto de productos",
            "idEstadoFactura": "1",
            "estadoDescripcion": "Borrador"
        }
    ],
    "infototal": "Mostrando registros del 1 al 10 de un total de 150",
    "roluser": "...",
    "permissions": [...]
}
```

### Vista - presupuestos.blade.php

**Estructura:**
- Extends del layout principal
- Header con título e ícono
- Alert informativo sobre origen de datos
- Selectores de límite y búsqueda
- Tabla con 6 columnas:
  1. Nro. Factura
  2. Fecha
  3. Cliente
  4. Total (formateado con $)
  5. Descripción
  6. Estado (badge con color)
- Estados de carga (roller, error, sin datos)
- Paginación

**Columnas Ordenables:**
- `nroFactura`
- `fechaFactura`
- `nombreCliente`
- `totalFactura`
- `estadoDescripcion`

### JavaScript - presupuesto.js

**Funciones:**

1. `$(document).ready()` - Inicialización
   - Configura CSRF token
   - Llama a `callregister()` inicial

2. `tableregister(data, page, callpaginas, url_query)` - Renderizado
   - Recorre `data.datos`
   - Formatea fechas, totales, descripciones
   - Genera badges de estado con clases CSS
   - Inserta filas en `#table_body`

3. `formatNumber(num)` - Formato de moneda
   - Convierte a 2 decimales
   - Agrega separadores de miles

4. `getBadgeEstado(idEstado, descripcion)` - Badge HTML
   - Retorna span con clase según idEstado
   - Colores: Borrador (gris), Facturado (verde), Anulado (rojo), etc.

## 🔐 Permisos

El módulo usa el permiso de **jobs** para controlar el acceso:

```php
@if (in_array('read',Session::get('user')['permissions']['jobs']))
    <li><a href="{{ route('presupuestos.index') }}">Presupuestos</a></li>
@endif
```

**Razón:** Los presupuestos están relacionados con las tareas/trabajos del sistema.

## 🎨 Estilos CSS

**Estados de Presupuesto (Badges):**
```css
.badge-estado-1 { background-color: #6c757d; color: white; } /* Borrador */
.badge-estado-2 { background-color: #28a745; color: white; } /* Facturado */
.badge-estado-3 { background-color: #dc3545; color: white; } /* Anulado */
.badge-estado-4 { background-color: #ffc107; color: black; } /* Pendiente */
.badge-estado-5 { background-color: #17a2b8; color: white; } /* Pagado */
```

## 🚀 Uso

### Acceso al Módulo

1. **Desde Menu Principal (Desktop):**
   - Administración → Presupuestos

2. **Desde Menu Lateral (Móvil):**
   - Abrir offcanvas → Administración → Presupuestos

### URL Directa

```
http://tudominio.com/presupuestos
```

## 📊 Flujo de Datos

```
[Usuario] 
   ↓ (clic en Presupuestos)
[PresupuestoController::index()]
   ↓ (renderiza vista)
[presupuestos.blade.php]
   ↓ (JavaScript: callregister)
[AJAX POST /presupuestos/table]
   ↓
[PresupuestoController::getPresupuestosDataTable()]
   ↓
[ColppyService::listarFacturasVenta()]
   ↓ (autenticación)
[ColppyService::obtenerClaveSesion()]
   ↓ (API Colppy)
[POST a Colppy API - listar_facturasventa]
   ↓ (respuesta JSON)
[Formateo de datos en Controller]
   ↓ (JSON response)
[JavaScript: tableregister()]
   ↓ (renderizado HTML)
[Tabla visible en navegador]
```

## 🔍 Filtros de Colppy

### Filtro Obligatorio (Presupuestos Borradores)

```php
$filtros = [
    [
        'field' => 'nroFactura',
        'op' => '>=',
        'value' => '0002-00000000'
    ]
];
```

**Explicación:** 
Según la convención de Colppy, los presupuestos borradores tienen números de factura que comienzan con "0002-". Este filtro asegura que solo se muestren presupuestos en estado borrador.

### Filtro de Búsqueda (Opcional)

```php
if (!empty($search)) {
    $filtros[] = [
        'field' => 'nroFactura',
        'op' => 'like',
        'value' => '%' . $search . '%'
    ];
}
```

## ⚠️ Consideraciones Importantes

1. **Sin Persistencia Local:**
   - Los presupuestos NO se guardan en la base de datos local
   - Cada consulta es en tiempo real a Colppy
   - Puede haber latencia dependiendo de la respuesta de la API

2. **Autenticación Colppy:**
   - Usa el sistema de sesiones de `ColppyService`
   - La clave de sesión se guarda en `Session::get('colppy_clave_sesion')`
   - Si la sesión expira, se renueva automáticamente

3. **Límites de Paginación:**
   - Colppy tiene límites en la cantidad de registros por request
   - El servicio valida que `limit <= 50` y `start >= 0`

4. **Búsqueda Limitada:**
   - La búsqueda solo funciona por `nroFactura` debido a las limitaciones de la API de Colppy
   - No se puede buscar por nombre de cliente o descripción directamente

5. **Performance:**
   - Para grandes volúmenes de datos, considerar implementar caché
   - Actualmente cada request va directo a Colppy API

## 🐛 Troubleshooting

### Error: "No se pudo obtener claveSesion"
**Solución:** Verificar credenciales de Colppy en tabla `configs`

### Error: "Error de conexión con la API de Colppy"
**Solución:** 
- Verificar conectividad de red
- Revisar URL de API en configuración
- Verificar certificados SSL (actualmente deshabilitado con `verify => false`)

### No se muestran datos
**Solución:**
- Verificar que existan presupuestos borradores en Colppy
- Revisar filtros aplicados (nroFactura >= 0002-00000000)
- Verificar logs en `storage/logs/laravel.log`

### Tabla no se actualiza
**Solución:**
- Limpiar caché del navegador
- Verificar consola de JavaScript para errores
- Verificar que el archivo `presupuesto.js` se esté cargando

## 📝 Logs

El módulo registra logs en:
```
storage/logs/laravel.log
```

**Eventos Logueados:**
- Errores al obtener presupuestos desde Colppy
- Excepciones en el controlador
- Respuestas de la API de Colppy (con Log::info en ColppyService)

## 🔄 Extensiones Futuras

Posibles mejoras al módulo:

1. **Ver Detalle de Presupuesto:**
   - Agregar modal o página para ver items del presupuesto
   - Usar operación `obtener_facturaventa` de Colppy

2. **Exportar a Excel/PDF:**
   - Implementar exportación de listado

3. **Filtros Avanzados:**
   - Por rango de fechas
   - Por cliente
   - Por total (mayor/menor que)

4. **Sincronización Opcional:**
   - Permitir guardar presupuestos localmente para consulta rápida
   - Botón "Sincronizar" similar al de clientes

5. **Conversión a Presupuesto Local:**
   - Botón para "importar" un presupuesto de Colppy
   - Crear una tarea (Job) basada en el presupuesto

## 📚 Referencias

- **Documentación API Colppy:** Ver archivo adjunto del usuario
- **ColppyService Existente:** `panel/app/Services/ColppyService.php`
- **Sistema de DataTables:** `public/assets/js/tableAjaxLocal.js`
- **Documentación Interna:** `docs/INTEGRACION_COLPPY.md`

## ✅ Checklist de Implementación

- [x] Método `listarFacturasVenta()` en ColppyService
- [x] Controlador `PresupuestoController` creado
- [x] Rutas agregadas en `web.php`
- [x] Vista `presupuestos.blade.php` creada
- [x] JavaScript `presupuesto.js` creado
- [x] Ítem de menú en header
- [x] Ítem de menú en offcanvas
- [x] Documentación README creada

---

**Fecha de Implementación:** 11 de marzo de 2026  
**Implementado por:** GitHub Copilot (Claude Sonnet 4.5)  
**Proyecto:** Strupeni Electrónica - Sistema de Gestión
