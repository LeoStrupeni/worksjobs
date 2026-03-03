# Actualización: Sistema de Sincronización de Productos Colppy

## Cambios Realizados

### ✅ Sistema Sin Queue:Work
Se ha actualizado todo el sistema de sincronización para **NO usar Jobs ni queue:work**, siguiendo el mismo patrón que se estableció para clientes.

### Archivos Creados/Modificados:

#### 1. **Nuevos Archivos**
- `app/Models/Product.php` - Modelo de productos
- `app/Services/SyncColppyProductsService.php` - Servicio de sincronización
- `app/Console/Commands/SyncColppyProducts.php` - Comando artisan
- `database/migrations/2026_02_27_000001_create_products_table.php` - Tabla de productos

#### 2. **Archivos Modificados**
- `app/Services/ColppyService.php` - Agregados métodos `listarInventario()` y `obtenerItemInventario()`
- `app/Http/Controllers/Api/ApiColppyController.php` - Agregados endpoints de inventario
- `app/Http/Controllers/Api/ApiDataTablesController.php` - Agregados métodos para productos
- `app/Console/Kernel.php` - **IMPORTANTE: Actualizado scheduler**
- `routes/api.php` - Agregadas rutas de productos

## Configuración del Scheduler

### Kernel.php actualizado:
```php
// Sincronizar clientes cada hora (llama directamente al servicio)
$schedule->call(function () {
    $syncService = new \App\Services\SyncColppyClientsService();
    $syncService->syncClients();
})->hourly()
  ->name('sync-colppy-clients')
  ->withoutOverlapping()
  ->onOneServer();

// Sincronizar productos cada 2 horas (llama directamente al servicio)
$schedule->call(function () {
    $syncService = new \App\Services\SyncColppyProductsService();
    $syncService->syncProducts();
})->everyTwoHours()
  ->name('sync-colppy-products')
  ->withoutOverlapping()
  ->onOneServer();
```

## Uso

### 1. Ejecutar Migración
```bash
cd panel
php artisan migrate
```

### 2. Sincronizar Manualmente

#### Opción A: Via Comando Artisan (Recomendado)
```bash
# Sincronizar productos
php artisan colppy:sync-products

# Sincronizar clientes
php artisan colppy:sync-clients
```

#### Opción B: Via API
```bash
# Sincronización inmediata (debug)
POST /api/colppy/sync/products/now

# Ver estadísticas
GET /api/colppy/sync/products/stats
```

### 3. Configurar Scheduler en Windows

**Archivo: `SCHEDULER_WINDOWS.bat`** (ya existe para clientes)
```batch
@echo off
cd /d C:\xampp\htdocs\Proyects\Strupeni_Electronica\panel
php artisan schedule:run
```

**Programar en Windows:**
1. Abrir "Programador de tareas"
2. Crear tarea básica
3. Nombre: "Laravel Scheduler Strupeni"
4. Ejecutar cada: 1 minuto
5. Acción: Iniciar programa
6. Programa: `C:\xampp\htdocs\Proyects\Strupeni_Electronica\panel\SCHEDULER_WINDOWS.bat`

### 4. Verificar Scheduler
```bash
# Ver tareas programadas
php artisan schedule:list

# Ejecutar manualmente el scheduler (para probar)
php artisan schedule:run
```

## Endpoints Disponibles

### API Colppy (Lectura Directa)
- `GET /api/colppy/inventario` - Listar productos desde Colppy
- `GET /api/colppy/inventario/{idItem}` - Obtener producto específico

### Sincronización
- `POST /api/colppy/sync/products` - Verificar sincronización
- `POST /api/colppy/sync/products/now` - Sincronizar inmediatamente (debug)
- `GET /api/colppy/sync/products/stats` - Estadísticas de sincronización

### Productos Locales
- `GET /api/products` - Listar productos locales sincronizados
  - Parámetros: `?search=texto&limit=50`

## Características

✅ **Sin Queue:Work** - No requiere queue worker corriendo  
✅ **Scheduler Automático** - Se sincroniza cada 2 horas automáticamente  
✅ **Comandos Manuales** - Se puede ejecutar cuando se necesite  
✅ **Solo Productos Tipo "P"** - Filtra automáticamente solo productos (no servicios ni kits)  
✅ **Sincronización Inteligente** - Solo actualiza si hay cambios (compara timestamps)  
✅ **Búsquedas Rápidas** - Tabla local permite búsquedas con LIKE  

## Flujo de Sincronización

1. **Automático (Scheduler)**: Cada 2 horas el scheduler ejecuta `SyncColppyProductsService`
2. **Manual (Comando)**: `php artisan colppy:sync-products`
3. **Manual (API)**: `POST /api/colppy/sync/products/now`

## Notas Importantes

- Los **Jobs** (`SyncColppyProductsJob` y `SyncColppyClientsJob`) aún existen pero **NO se usan**
- Se mantienen por si en el futuro se decide usar queue:work
- El scheduler llama **directamente al servicio**, no al Job
- `withoutOverlapping()` evita que se ejecute si ya hay una sincronización corriendo
- `onOneServer()` evita ejecuciones duplicadas en servidores múltiples

## Testing

### Test Rápido de Sincronización
```bash
# 1. Ejecutar sincronización
php artisan colppy:sync-products

# 2. Verificar en la base de datos
# SELECT COUNT(*) FROM products WHERE is_from_colppy = 1;

# 3. Probar API
curl http://localhost/panel/api/products?limit=10
```

## Solución a Problemas Comunes

### El scheduler no se ejecuta
- Verificar que la tarea programada en Windows esté activa
- Ejecutar manualmente: `php artisan schedule:run`
- Ver logs: `storage/logs/laravel.log`

### No se sincronizan productos
- Verificar credenciales Colppy en tabla `configs`
- Ejecutar manualmente para ver errores: `php artisan colppy:sync-products`
- Verificar logs: `storage/logs/laravel.log`

### Diferencia entre productos locales y Colppy
- Es normal si se sincronizó hace tiempo
- Ejecutar: `php artisan colppy:sync-products`
- O esperar a la siguiente ejecución automática (cada 2 horas)

---
**Fecha de actualización:** 27/02/2026
