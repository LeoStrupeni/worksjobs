# Flujo de Sincronización Colppy

> Documento técnico sobre el proceso de sincronización de clientes desde Colppy API

---

## 📋 Resumen Ejecutivo

La sincronización es el proceso mediante el cual los clientes de Colppy se copian a la base de datos local, permitiendo trabajar con ellos sin depender de la API externa.

**Características clave**:
- ✅ Unidireccional: Colppy → Sistema Local (solo lectura desde Colppy)
- ✅ Incremental: Actualiza existentes, crea nuevos
- ✅ Programable: Manual, automática o por evento
- ✅ Robusto: Manejo de errores, reintentos, logging

---

## 🏗️ Arquitectura del Sistema

```
┌─────────────────┐
│   Colppy API    │
│  (Fuente)       │
└────────┬────────┘
         │ HTTPS
         │ (Solo lectura)
         ▼
┌────────────────────────────────┐
│   ColppyService.php            │
│  - obtenerClaveSesion()        │
│  - listarClientes()            │
│  - manejar paginación          │
└────────────┬───────────────────┘
             │
             ▼
┌────────────────────────────────┐
│  SyncColppyClientsService.php  │
│  - Lógica de sincronización    │
│  - Crear/actualizar registros  │
│  - Manejo de errores           │
└────────────┬───────────────────┘
             │
             ▼
┌────────────────────────────────┐
│   Base de Datos Local          │
│   Tabla: clients               │
│   - is_from_colppy = 1         │
│   - colppy_id (ID original)    │
└────────────────────────────────┘
```

---

## 🔄 Proceso Completo Paso a Paso

### 1️⃣ Autenticación con Colppy

```php
// ColppyService::obtenerClaveSesion()

1. Verificar si existe sesión activa en tabla `colppy_sessions`
2. Si existe y es válida: reutilizar
3. Si no existe o expiró:
   a. Hash MD5 de la contraseña
   b. POST a https://login.colppy.com/...
   c. Enviar credenciales (user, password_md5)
   d. Recibir claveSesion
   e. Guardar en tabla `colppy_sessions`
4. Retornar claveSesion para usar en siguientes llamadas
```

**Resultado**: `claveSesion` (token de autenticación)

---

### 2️⃣ Consultar Clientes desde Colppy

```php
// ColppyService::listarClientes($start, $limit, $filters, $orders)

1. Obtener claveSesion (paso anterior)
2. Preparar payload:
   {
     "provision": "Cliente",
     "operacion": "listar_cliente",
     "parameters": {
       "sesion": { usuario, claveSesion },
       "idEmpresa": "98",
       "start": 0,
       "limit": 100
     }
   }
3. POST a Colppy API
4. Recibir respuesta paginada:
   {
     "total": 450,
     "results": [
       { "id": "98", "nombre": "Carlos López", ... },
       { "id": "124", "nombre": "Ana Martínez", ... },
       ...
     ]
   }
5. Retornar clientes + total
```

**Resultado**: Array de clientes con sus datos completos

---

### 3️⃣ Sincronizar a Base de Datos Local

```php
// SyncColppyClientsService::syncClients()

INICIO
│
├─ Inicializar contadores:
│  - $nuevos = 0
│  - $actualizados = 0
│  - $errores = 0
│
├─ BUCLE PAGINADO:
│  │
│  ├─ start = 0, limit = 100
│  │
│  └─ MIENTRAS haya más clientes:
│     │
│     ├─ Obtener página actual de Colppy
│     │  → ColppyService::listarClientes(start, limit)
│     │
│     ├─ Por cada cliente en resultado:
│     │  │
│     │  ├─ Buscar en BD local por colppy_id:
│     │  │  → $existe = Client::where('colppy_id', $id)->first()
│     │  │
│     │  ├─ SI EXISTE:
│     │  │  ├─ Actualizar datos:
│     │  │  │  - first_name, last_name
│     │  │  │  - email, phone
│     │  │  │  - dirección, ciudad, etc.
│     │  │  │  - is_from_colppy = 1
│     │  │  └─ $actualizados++
│     │  │
│     │  └─ SI NO EXISTE:
│     │     ├─ Crear nuevo registro:
│     │     │  - Todos los datos de Colppy
│     │     │  - is_from_colppy = 1
│     │     │  - colppy_id = ID original
│     │     └─ $nuevos++
│     │
│     ├─ start += limit (pasar a siguiente página)
│     │
│     └─ REPETIR hasta que start >= total
│
└─ RETORNAR resultado:
   {
     "success": true,
     "nuevos": 15,
     "actualizados": 435,
     "errores": 0,
     "total": 450
   }

FIN
```

**Resultado**: Clientes en BD local sincronizados

---

## 📊 Estructura de Datos

### Tabla `clients`

```sql
CREATE TABLE `clients` (
  `id` bigint UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `colppy_id` varchar(255) NULL,         -- ID en Colppy
  `is_from_colppy` tinyint(1) DEFAULT 0, -- Bandera de origen
  `first_name` varchar(255),
  `last_name` varchar(255),
  `nombre_fantasia` varchar(255) NULL,
  `type_doc` varchar(50),
  `num_doc` varchar(50),
  `email` varchar(255) NULL,
  `phone1` varchar(50) NULL,
  `phone2` varchar(50) NULL,
  `country` varchar(100) NULL,
  `state` varchar(100) NULL,
  `city` varchar(100) NULL,
  `cp` varchar(20) NULL,
  `address_street` varchar(255) NULL,
  `address_nro` varchar(20) NULL,
  `address_apartament` varchar(50) NULL,
  `address_detail` text NULL,
  `other_obs` text NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  `deleted_at` timestamp NULL,
  
  INDEX `idx_colppy_id` (`colppy_id`),
  INDEX `idx_is_from_colppy` (`is_from_colppy`)
);
```

### Tabla `colppy_sessions`

```sql
CREATE TABLE `colppy_sessions` (
  `id` bigint UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  `session_id` text NOT NULL,  -- claveSesion de Colppy
  `user` varchar(255),          -- Usuario Colppy
  `expires_at` timestamp NULL,  -- Expiración estimada
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL
);
```

---

## 🚀 Métodos de Ejecución

### 1. Manual via Comando Artisan

```bash
# En terminal/consola
cd panel/
php artisan colppy:sync-clients
```

**Salida**:
```
=== INICIANDO SINCRONIZACIÓN DE CLIENTES COLPPY ===

Sincronizando clientes...

=== SINCRONIZACIÓN COMPLETADA ===

✓ Clientes nuevos: 15
✓ Clientes actualizados: 435
✓ Total procesados: 450
```

**Cuándo usar**: Sincronización puntual, mantenimiento, testing

---

### 2. Via Endpoint Web (Sincrónico)

```http
POST /client/sync-colppy-now
Header: X-CSRF-TOKEN
```

**Respuesta**:
```json
{
  "success": true,
  "data": {
    "nuevos": 15,
    "actualizados": 435,
    "errores": 0,
    "total": 450
  },
  "message": "Sincronización completada"
}
```

**Cuándo usar**: Botón de sincronización manual en panel admin

---

### 3. Via Job (Asíncrono)

```http
POST /client/sync-colppy
Header: X-CSRF-TOKEN
```

**Comportamiento**:
- Si `QUEUE_CONNECTION=sync`: Ejecuta inmediatamente
- Si `QUEUE_CONNECTION=database`: Encola para procesar en background

**Respuesta inmediata**:
```json
{
  "success": true,
  "message": "Sincronización iniciada en segundo plano"
}
```

**Cuándo usar**: Sincronización desde UI sin bloquear

---

### 4. Programado (Scheduler)

```php
// app/Console/Kernel.php
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

**Activación**:
```bash
# Linux (cron)
* * * * * cd /path/to/panel && php artisan schedule:run >> /dev/null 2>&1

# Windows (Task Scheduler)
# Ver CONFIGURAR_SCHEDULER.md
```

**Cuándo usar**: Sincronización automática periódica en producción

---

## ⚙️ Configuración

### Credenciales Colppy

Tabla `configs`:

```sql
INSERT INTO configs (name, value) VALUES
('url_api_login', 'https://login.colppy.com/...'),
('user_api', 'usuario@empresa.com'),
('pass_api', 'contraseña_colppy'),
('id_empresa_api', '98');
```

### Modo de Clientes

```sql
INSERT INTO configs (name, value) VALUES
('colppy_clientes_modo', 'local');
```

Opciones:
- `'local'`: Todos los clientes (locales + sincronizados) ⭐ RECOMENDADO
- `'colppy'`: Solo clientes sincronizados desde Colppy
- `'hibrido'`: Consulta directa en tiempo real (NO RECOMENDADO)

---

## 🛡️ Manejo de Errores

### Reintentos Automáticos

```php
// SyncColppyClientsJob
public $tries = 3;        // 3 intentos
public $timeout = 300;    // 5 minutos máximo
```

### Errores Comunes

#### 1. Error de Autenticación

**Causa**: Credenciales incorrectas o expiradas

**Solución**:
```sql
-- Verificar credenciales
SELECT * FROM configs WHERE name LIKE '%api%';

-- Eliminar sesiones antiguas
DELETE FROM colppy_sessions;
```

#### 2. Timeout de API

**Causa**: Colppy API lenta o no responde

**Solución**:
- Reducir `limit` en paginación (de 100 a 50)
- Aumentar `timeout` en Job
- Verificar conectividad de red

#### 3. Duplicados

**Causa**: Fallo en detección por `colppy_id`

**Solución**:
```sql
-- Buscar duplicados
SELECT colppy_id, COUNT(*) 
FROM clients 
WHERE is_from_colppy = 1 
GROUP BY colppy_id 
HAVING COUNT(*) > 1;

-- Eliminar duplicados (quedarse con el más reciente)
-- Ver logs para identificar causa raíz
```

---

## 📊 Monitoreo y Logs

### Ver Progreso en Logs

```bash
tail -f panel/storage/logs/laravel.log
```

**Logs típicos**:
```
[2026-02-27 14:30:00] local.INFO: Iniciando sincronización Colppy
[2026-02-27 14:30:05] local.INFO: Página 1/5: 100 clientes obtenidos
[2026-02-27 14:30:10] local.INFO: Página 2/5: 100 clientes obtenidos
...
[2026-02-27 14:30:45] local.INFO: Sincronización completada: 15 nuevos, 435 actualizados
```

### Verificar Resultados

```sql
-- Total de clientes sincronizados
SELECT COUNT(*) FROM clients WHERE is_from_colppy = 1;

-- Últimos sincronizados
SELECT id, colppy_id, first_name, last_name, updated_at 
FROM clients 
WHERE is_from_colppy = 1 
ORDER BY updated_at DESC 
LIMIT 10;

-- Comparar con Colppy
-- (Debe coincidir con total reportado por API)
```

---

## 🔧 Troubleshooting

### La sincronización no se ejecuta automáticamente

1. **Verificar scheduler activo**:
   ```bash
   # Ejecutar manualmente
   php artisan schedule:run
   
   # Verificar que esté en cron/Task Scheduler
   ```

2. **Verificar queue worker** (si QUEUE_CONNECTION=database):
   ```bash
   # Ver si el worker está corriendo
   ps aux | grep queue:work
   
   # Iniciar worker
   php artisan queue:work --tries=3
   ```

### Sincronización se queda "colgada"

1. **Ver jobs fallidos**:
   ```bash
   php artisan queue:failed
   ```

2. **Reintentar job fallido**:
   ```bash
   php artisan queue:retry {id}
   ```

3. **Limpiar jobs colgados**:
   ```sql
   -- Ver jobs en cola
   SELECT * FROM jobs;
   
   -- Si hay jobs antiguos, limpiar
   DELETE FROM jobs WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR);
   ```

### Clientes no se actualizan

1. **Verificar que colppy_id coincida**:
   ```sql
   SELECT id, colppy_id, first_name, updated_at 
   FROM clients 
   WHERE colppy_id = '98';
   ```

2. **Forzar actualización**:
   ```bash
   # Ejecutar sincronización con logs verbose
   php artisan colppy:sync-clients -v
   ```

---

## 📈 Performance y Optimización

### Paginación Óptima

```php
// Ajustar según performance de Colppy API
$limit = 100; // 100 es buen balance

// Si hay timeouts, reducir:
$limit = 50;

// Si la API es muy rápida, aumentar:
$limit = 200;
```

### Índices de Base de Datos

```sql
-- Verificar índices
SHOW INDEX FROM clients;

-- Crear si no existen
CREATE INDEX idx_colppy_id ON clients(colppy_id);
CREATE INDEX idx_is_from_colppy ON clients(is_from_colppy);
```

### Caché de Sesiones

```php
// ColppyService reutiliza sesiones automáticamente
// Duración típica: 1-2 horas
// Se guarda en tabla colppy_sessions
```

---

## 🎯 Mejores Prácticas

### ✅ DO (Hacer)

- Ejecutar sincronización en horarios de baja carga
- Monitorear logs después de cada sincronización
- Mantener credenciales actualizadas en `configs`
- Usar modo 'local' con sincronización periódica
- Configurar alertas para jobs fallidos

### ❌ DON'T (No hacer)

- No sincronizar cada minuto en producción (muy agresivo)
- No usar modo 'hibrido' en producción (lento)
- No modificar clientes de Colppy directamente en Colppy
- No eliminar registros con `is_from_colppy = 1` sin soft delete
- No hardcodear credenciales en código

---

## 📝 Resumen

1. **Autenticación**: Obtener claveSesión de Colppy
2. **Consulta**: Paginar clientes desde API
3. **Sincronización**: Crear o actualizar en BD local
4. **Resultado**: Clientes disponibles localmente
5. **Trabajo**: Operaciones locales sin depender de API

**Ventajas**:
- ⚡ Performance óptimo
- 🔒 Sin dependencia de disponibilidad de Colppy
- 🔄 Datos siempre disponibles
- 📊 Relaciones y consultas complejas eficientes

---

**Documentos relacionados**:
- `INTEGRACION_COLPPY.md` - Detalles de la API
- `SISTEMA_CLIENTES_DOMICILIOS.md` - Arquitectura de datos
- `CONFIGURAR_SCHEDULER.md` - Automatizar sincronización
- `TROUBLESHOOTING.md` - Solución de problemas
