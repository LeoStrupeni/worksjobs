# Sistema de Gestión de Clientes y Domicilios

> **Actualizado**: 27 de febrero de 2026  
> **Reemplaza**: `SISTEMA_DOMICILIOS_DUAL.md` (desactualizado)

## Descripción

Sistema implementado para manejar clientes de **dos orígenes diferentes**:
- **Clientes locales**: Dados de alta desde el CMS/Panel de administración
- **Clientes sincronizados**: Obtenidos desde Colppy API y sincronizados a la BD local

**⚠️ IMPORTANTE**: Todos los clientes (locales y de Colppy) se almacenan en la **misma tabla `clients`** y se trabaja localmente después de sincronizar.

---

## Arquitectura de Tablas

### Tabla `clients`

Tabla única para **todos** los clientes, independientemente de su origen.

**Campos clave**:
- `id` (PK) - ID numérico secuencial local
- `colppy_id` (STRING, nullable) - ID del cliente en Colppy (ej: "98")
- `is_from_colppy` (BOOLEAN) - `1` = sincronizado desde Colppy, `0` o `NULL` = cliente local
- `first_name`, `last_name`, `nombre_fantasia`
- `type_doc`, `num_doc`
- `email`, `phone1`, `phone2`
- `country`, `state`, `city`, `cp`
- `address_street`, `address_nro`, `address_apartament`, `address_detail`
- `other_obs`
- `created_at`, `updated_at`, `deleted_at`

**Ejemplo de registros**:
```
| id  | colppy_id | is_from_colppy | first_name | last_name |
|-----|-----------|----------------|------------|-----------|
| 1   | NULL      | 0              | Juan       | Pérez     | ← Local
| 2   | NULL      | NULL           | María      | García    | ← Local
| 145 | "98"      | 1              | Carlos     | López     | ← Colppy
| 146 | "124"     | 1              | Ana        | Martínez  | ← Colppy
```

---

### Tabla `clients_address`

Domicilios adicionales para clientes (tabla relacionada con FK).

**Campos**:
- `id` (PK)
- `client_id` (FK → `clients.id`) - **Siempre referencia a `clients.id` local**
- `country`, `state`, `cp`, `city`
- `address_street`, `address_nro`, `address_apartament`, `address_detail`
- `created_at`, `updated_at`, `deleted_at`

**Uso**: 
- Domicilios adicionales para cualquier cliente (local o sincronizado)
- El domicilio principal está en la tabla `clients`
- Relación: `Client::hasMany(Clients_Addres::class, 'client_id')`

---

## Flujo de Sincronización Colppy

### 1. Obtener clientes desde Colppy API

```php
$colppyService = new ColppyService();
$resultado = $colppyService->listarClientes($start, $limit, [], []);
// Retorna clientes con sus datos desde Colppy
```

### 2. Sincronizar a BD local

```php
$syncService = new SyncColppyClientsService();
$syncService->syncClients();
```

**Proceso**:
1. Consulta clientes desde Colppy API (paginado)
2. Por cada cliente:
   - Si existe en BD local (por `colppy_id`): **actualiza** datos
   - Si no existe: **crea** nuevo registro
   - Marca `is_from_colppy = 1`
   - Guarda `colppy_id` original

### 3. Trabajo local

Una vez sincronizados, **todos los clientes se trabajan localmente**:
- Consultas directas a tabla `clients`
- Relaciones con trabajos (`jobs`)
- Domicilios adicionales en `clients_address`
- NO se consulta Colppy API en cada operación

---

## Modos de Operación

Configuración en `configs.colppy_clientes_modo`:

### Modo 'local' (RECOMENDADO) ⭐

```php
Config::where('name', 'colppy_clientes_modo')->value('value') === 'local'
```

**Comportamiento**:
- ✅ Muestra TODOS los clientes (locales + sincronizados)
- ✅ Permite crear clientes locales nuevos
- ✅ Sincronización en background/on-demand
- ✅ Performance óptimo (consultas locales)

**Consulta SQL**:
```sql
SELECT * FROM clients 
ORDER BY id DESC
-- No filtra por is_from_colppy, muestra todos
```

### Modo 'colppy'

**Comportamiento**:
- ⚠️ Muestra SOLO clientes sincronizados desde Colppy
- ❌ NO permite crear clientes (botón deshabilitado)
- ✅ Consultas locales (ya sincronizados)

**Consulta SQL**:
```sql
SELECT * FROM clients WHERE is_from_colppy = 1
```

### Modo 'hibrido' (NO RECOMENDADO)

**Comportamiento**:
- ⚠️ Consulta directa a API Colppy en tiempo real
- ❌ Lento (depende de respuesta de API)
- ❌ Riesgo de timeout
- 📍 Usar solo para troubleshooting

---

## Detección de Origen del Cliente

### Por campo `is_from_colppy`

```php
$client = Client::find($id);

if ($client->is_from_colppy == 1) {
    // Cliente sincronizado desde Colppy
    echo "Colppy ID: " . $client->colppy_id;
} else {
    // Cliente local
    echo "Cliente creado localmente";
}
```

### En consultas

```php
// Solo clientes locales
$locales = Client::where('is_from_colppy', '!=', 1)
                 ->orWhereNull('is_from_colppy')
                 ->get();

// Solo clientes de Colppy
$colppy = Client::where('is_from_colppy', 1)->get();

// Todos los clientes
$todos = Client::all();
```

---

## Controladores y Rutas

### ClientController.php

```php
// Ver listado de clientes (modo según config)
Route::get('/client', [ClientController::class, 'index']);

// Crear cliente local
Route::post('/client', [ClientController::class, 'store']);
// Nota: Solo funciona si modo != 'colppy'

// Editar cliente
Route::put('/client/{id}', [ClientController::class, 'update']);
```

### ApiDataTablesController.php

```php
// DataTable para listado
Route::post('/client/data', [ApiDataTablesController::class, 'getClientsData']);

// Sincronización con Colppy
Route::post('/client/sync-colppy', [ApiDataTablesController::class, 'syncColppyClients']);
Route::post('/client/sync-colppy-now', [ApiDataTablesController::class, 'syncColppyClientsNow']);
```

**Diferencia**:
- `sync-colppy`: Despacha Job (asíncrono si queue activo)
- `sync-colppy-now`: Ejecuta sincrónico inmediato, retorna resultado

---

## Domicilios Adicionales

### Obtener domicilios de un cliente

```php
// En el controller
$client = Client::find($id);
$domicilios = $client->addresses; // Relación hasMany

// O directamente
$domicilios = Clients_Addres::where('client_id', $id)->get();
```

### Crear domicilio adicional

```php
Route::post('/client/address', function(Request $request) {
    $request->validate([
        'client_id' => 'required|exists:clients,id',
        'address_street' => 'required',
        'city' => 'required'
    ]);
    
    Clients_Addres::create([
        'client_id' => $request->client_id,
        'address_street' => $request->address_street,
        'city' => $request->city,
        // ... otros campos
    ]);
});
```

**Importante**: 
- `client_id` SIEMPRE es el ID numérico local de la tabla `clients`
- Funciona igual para clientes locales o sincronizados de Colppy

---

## API Móvil (Técnicos)

### ApiJobController.php

```php
// Obtener clientes (según modo configurado)
GET /api/clients

// Respuesta
{
    "success": true,
    "data": [
        {
            "id": 145,
            "colppy_id": "98",
            "is_from_colppy": 1,
            "first_name": "Carlos",
            "last_name": "López",
            // ...
        }
    ]
}
```

```php
// Obtener domicilios de un cliente
GET /api/client/{id}/addresses

// Funciona con cualquier cliente (local o Colppy)
{
    "success": true,
    "data": [
        {
            "id": 23,
            "client_id": 145,
            "address_street": "San Martín 123",
            "city": "Rosario"
        }
    ]
}
```

---

## Sincronización Programada

### Manual

```bash
# Comando artisan
php artisan colppy:sync-clients

# Vía web (autenticado)
curl -X POST http://localhost/client/sync-colppy-now
```

### Automática (Scheduler)

**Definido en** `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        \App\Jobs\SyncColppyClientsJob::dispatch();
    })->everyMinute()
      ->name('sync-colppy-clients')
      ->withoutOverlapping()
      ->onOneServer();
}
```

**Para activar**:
- **Linux**: Agregar cron job (ver `CONFIGURAR_SCHEDULER.md`)
- **Windows**: Task Scheduler (ver `CONFIGURAR_SCHEDULER.md`)
- **Desarrollo**: `php artisan schedule:run` (ejecutar manualmente)

---

## Ventajas de Esta Arquitectura

✅ **Tabla única**: Simplifica consultas y relaciones
✅ **Performance**: Todo en BD local, sin dependencia de API externa
✅ **Integridad**: Foreign Keys funcionan correctamente
✅ **Escalable**: Fácil agregar más campos o fuentes
✅ **Transparente**: Frontend no necesita saber el origen del cliente
✅ **Consistente**: Mismo modelo `Client` para todos

---

## Restricciones Importantes

### ❌ NO se puede:
- Crear/modificar clientes directamente en Colppy desde nuestro sistema
- Eliminar clientes sincronizados de Colppy (solo soft delete local)
- Modificar campos críticos de clientes Colppy (algunos campos readonly)

### ✅ SÍ se puede:
- Crear clientes locales nuevos (si modo != 'colppy')
- Agregar domicilios adicionales a cualquier cliente
- Sincronizar cambios desde Colppy a BD local
- Trabajar con todos los clientes de forma unificada

---

## Troubleshooting

### Los clientes de Colppy no aparecen

1. Verificar configuración en tabla `configs`:
   ```sql
   SELECT * FROM configs WHERE name LIKE 'colppy%';
   ```

2. Verificar sincronización:
   ```sql
   SELECT COUNT(*) FROM clients WHERE is_from_colppy = 1;
   ```

3. Ejecutar sincronización manual:
   ```bash
   php artisan colppy:sync-clients
   ```

4. Ver logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Error "No se puede crear cliente"

- Verificar modo: `SELECT value FROM configs WHERE name = 'colppy_clientes_modo'`
- Si es 'colppy': Cambiar a 'local' para permitir creación

### Domicilios no se guardan

- Verificar que `client_id` existe en tabla `clients`
- Verificar permisos de usuario (Spatie permissions)
- Ver logs de validación

---

## Migración de Datos

Si necesitas revisar la estructura:

```bash
# Ver migraciones aplicadas
php artisan migrate:status

# Ver tabla clients
DESCRIBE clients;

# Ver tabla clients_address
DESCRIBE clients_address;
```

---

## Modelos Eloquent

### Client.php

```php
class Client extends Model
{
    use SoftDeletes;
    
    protected $table = 'clients';
    
    protected $fillable = [
        'first_name', 'last_name', 'colppy_id', 
        'is_from_colppy', // ... otros campos
    ];
    
    // Relación con domicilios adicionales
    public function addresses()
    {
        return $this->hasMany(Clients_Addres::class, 'client_id');
    }
    
    // Relación con trabajos
    public function jobs()
    {
        return $this->hasMany(Job::class, 'client_id');
    }
}
```

### Clients_Addres.php

```php
class Clients_Addres extends Model
{
    use SoftDeletes;
    
    protected $table = 'clients_address';
    
    protected $fillable = [
        'client_id', 'country', 'state', 'city',
        'address_street', // ... otros campos
    ];
    
    // Relación con cliente
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
```

---

## Conclusión

El sistema actual es una **arquitectura unificada** donde:

1. Todos los clientes se almacenan en `clients`
2. El campo `is_from_colppy` identifica el origen
3. La sincronización es unidireccional: Colppy → BD Local
4. Los domicilios adicionales funcionan igual para todos los clientes
5. El trabajo se realiza localmente para óptimo performance

Esta arquitectura es simple, efectiva y escalable. 🚀

---

**Documentos relacionados**:
- `INTEGRACION_COLPPY.md` - Integración completa con Colppy API
- `CONFIGURACION_COLPPY.md` - Setup inicial de credenciales
- `CONFIGURAR_SCHEDULER.md` - Configurar sincronización automática
