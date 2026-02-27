# Troubleshooting - Guía de Solución de Problemas

> Problemas comunes y sus soluciones en Strupeni Electrónica

---

## 📋 Índice Temático

- [🔐 Autenticación y Sesiones](#autenticación-y-sesiones)
- [🔌 Integración Colppy](#integración-colppy)
- [👥 Problemas con Clientes](#problemas-con-clientes)
- [📅 Trabajos (Jobs)](#trabajos-jobs)
- [📁 Archivos y Storage](#archivos-y-storage)
- [🗄️ Base de Datos](#base-de-datos)
- [⚙️ Configuración y Entorno](#configuración-y-entorno)
- [📱 App Móvil Flutter](#app-móvil-flutter)
- [🚀 Performance](#performance)

---

## 🔐 Autenticación y Sesiones

### Problema: "CSRF token mismatch"

**Síntomas**:
```
419 | PAGE EXPIRED
The page has expired due to inactivity.
```

**Causas comunes**:
1. Sesión expirada
2. Token CSRF incorrecto o ausente
3. Caché de navegador desactualizado

**Soluciones**:

```bash
# 1. Limpiar caché de Laravel
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# 2. Regenerar clave de aplicación
php artisan key:generate

# 3. Verificar .env
```

En el frontend, asegurarse de incluir el token:
```javascript
// En AJAX
headers: {
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
}
```

---

### Problema: "Unauthenticated" en API Móvil

**Síntomas**:
```json
{
  "message": "Unauthenticated."
}
```

**Causas**:
1. Token no enviado o inválido
2. Token expirado
3. Usuario eliminado

**Soluciones**:

```dart
// Flutter - Verificar header
headers: {
  'Authorization': 'Bearer $token',
  'Accept': 'application/json'
}
```

```bash
# Laravel - Verificar tokens en BD
SELECT * FROM personal_access_tokens WHERE tokenable_id = {user_id};

# Limpiar tokens antiguos
DELETE FROM personal_access_tokens WHERE last_used_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

---

### Problema: No puedo hacer login

**Verificaciones**:

```bash
# 1. Verificar usuario existe
php artisan tinker
>>> User::where('email', 'usuario@ejemplo.com')->first()

# 2. Si no existe, crear:
>>> User::create([
    'name' => 'Admin',
    'email' => 'admin@ejemplo.com',
    'password' => bcrypt('contraseña')
]);

# 3. Verificar contraseña
>>> Hash::check('contraseña', $user->password)
```

---

## 🔌 Integración Colppy

### Problema: "Error al conectar con Colppy"

**Síntomas**:
- Timeout al sincronizar
- Error 401/403 de Colppy
- "Credenciales inválidas"

**Diagnóstico**:

```sql
-- 1. Verificar credenciales configuradas
SELECT * FROM configs WHERE name LIKE '%api%';
```

**Soluciones**:

```bash
# 1. Probar autenticación manualmente
php artisan tinker
>>> $service = new \App\Services\ColppyService();
>>> $session = $service->obtenerClaveSesion();
>>> dd($session);

# 2. Limpiar sesiones antiguas
>>> DB::table('colppy_sessions')->truncate();

# 3. Verificar conectividad
curl -v https://login.colppy.com/
```

**Errores comunes de Colppy**:

| Error | Causa | Solución |
|-------|-------|----------|
| "Usuario o contraseña incorrectos" | Credenciales mal configuradas | Actualizar en tabla `configs` |
| "Empresa no encontrada" | `id_empresa_api` incorrecto | Verificar ID de empresa en Colppy |
| "Sesión expirada" | Token antiguo | Eliminar sesiones: `DELETE FROM colppy_sessions` |
| Timeout | API Colppy lenta | Aumentar timeout en `ColppyService` |

---

### Problema: Sincronización se queda colgada

**Síntomas**:
- Botón de sincronización no responde
- Job nunca termina
- Logs no muestran progreso

**Diagnóstico**:

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Verificar jobs en cola
php artisan queue:failed
```

```sql
-- Ver jobs pendientes
SELECT * FROM jobs;

-- Ver última sincronización
SELECT MAX(updated_at) FROM clients WHERE is_from_colppy = 1;
```

**Soluciones**:

```bash
# 1. Reintentar job fallido
php artisan queue:retry {id}

# 2. Limpiar jobs colgados
php artisan queue:flush

# 3. Ejecutar sincronización manual
php artisan colppy:sync-clients

# 4. Si QUEUE_CONNECTION=database, reiniciar worker
php artisan queue:restart
```

---

### Problema: Clientes duplicados después de sincronizar

**Causa**: `colppy_id` no está indexado o hay inconsistencias

**Diagnóstico**:

```sql
-- Buscar duplicados
SELECT colppy_id, COUNT(*) as total
FROM clients
WHERE is_from_colppy = 1
GROUP BY colppy_id
HAVING total > 1;
```

**Solución**:

```sql
-- Eliminar duplicados (mantener el más reciente)
DELETE c1 FROM clients c1
INNER JOIN clients c2
WHERE c1.colppy_id = c2.colppy_id
  AND c1.is_from_colppy = 1
  AND c2.is_from_colppy = 1
  AND c1.id < c2.id;

-- Crear índice si no existe
CREATE INDEX idx_colppy_id ON clients(colppy_id);
```

---

## 👥 Problemas con Clientes

### Problema: No puedo crear clientes

**Síntomas**:
- Botón "Nuevo Cliente" deshabilitado
- Error al guardar cliente

**Causas**:
1. Modo Colppy está activo
2. Usuario sin permisos
3. Validación fallando

**Soluciones**:

```sql
-- 1. Verificar modo
SELECT * FROM configs WHERE name = 'colppy_clientes_modo';
-- Si value = 'colppy', cambiar a 'local'
UPDATE configs SET value = 'local' WHERE name = 'colppy_clientes_modo';

-- 2. Verificar permisos del usuario
```

```bash
php artisan tinker
>>> $user = User::find({id});
>>> $user->getAllPermissions();
>>> $user->givePermissionTo('create-clients');
```

---

### Problema: Clientes de Colppy no aparecen

**Diagnóstico**:

```sql
-- Verificar si hay clientes sincronizados
SELECT COUNT(*) FROM clients WHERE is_from_colppy = 1;

-- Verificar modo configurado
SELECT value FROM configs WHERE name = 'colppy_clientes_modo';
```

**Soluciones**:

```bash
# 1. Ejecutar sincronización manual
php artisan colppy:sync-clients

# 2. Verificar logs de sincronización
tail -n 100 storage/logs/laravel.log | grep -i colppy

# 3. Probar conexión a Colppy
```

---

### Problema: "Cliente no encontrado" al editar

**Causa**: Cliente fue eliminado (soft delete)

**Solución**:

```sql
-- Ver clientes eliminados
SELECT * FROM clients WHERE deleted_at IS NOT NULL AND id = {id};

-- Restaurar si es necesario
UPDATE clients SET deleted_at = NULL WHERE id = {id};
```

---

## 📅 Trabajos (Jobs)

### Problema: Trabajos no aparecen en el calendario

**Diagnóstico**:

```sql
-- Verificar si existen trabajos
SELECT COUNT(*) FROM jobs;

-- Verificar trabajos de hoy
SELECT * FROM jobs WHERE scheduled_date = CURDATE();

-- Verificar asignación de técnicos
SELECT j.*, u.name 
FROM jobs j
LEFT JOIN user_has_jobs uhj ON uhj.job_id = j.id
LEFT JOIN users u ON u.id = uhj.user_id;
```

**Soluciones**:

1. Verificar filtros en frontend (fechas, técnicos)
2. Verificar permisos del usuario
3. Limpiar caché del navegador

---

### Problema: No puedo subir archivos a trabajos

**Síntomas**:
- Error "File too large"
- "Upload failed"
- Timeout

**Verificar límites PHP**:

```bash
php -i | grep -i upload
php -i | grep -i post_max
php -i | grep -i max_execution
```

**Solución**: Ver `CONFIGURAR_LIMITES_PHP.md`

```ini
; php.ini
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
memory_limit = 256M
```

Reiniciar Apache/PHP-FPM después de cambios.

---

### Problema: Estado del trabajo no se actualiza

**Diagnóstico**:

```sql
-- Ver estado actual
SELECT id, status, arrival_time, completed_at 
FROM jobs 
WHERE id = {id};
```

**Estados posibles**:
- `pending`: Pendiente
- `in_progress`: En progreso (técnico arribó)
- `completed`: Completado
- `cancelled`: Cancelado

**Solución**: Verificar transiciones de estado en código

---

## 📁 Archivos y Storage

### Problema: "Storage link not found"

**Síntomas**:
- Imágenes no cargan
- Error 404 en `/storage/...`

**Solución**:

```bash
# Crear symlink
php artisan storage:link

# Verificar que existe
ls -la public/storage

# Si no funciona en Windows:
mklink /D "C:\xampp\htdocs\Proyects\Strupeni_Electronica\panel\public\storage" "C:\xampp\htdocs\Proyects\Strupeni_Electronica\panel\storage\app\public"
```

---

### Problema: "Permission denied" al subir archivos

**Síntomas**:
- Error 500 al subir
- "failed to open stream: Permission denied"

**Solución Linux**:

```bash
# Dar permisos a storage
cd panel/
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# O si usas usuario específico:
chown -R tu_usuario:www-data storage bootstrap/cache
```

**Solución Windows**:
- Clic derecho en carpeta `storage` → Propiedades → Seguridad
- Dar permisos completos a usuario actual y IUSR

---

### Problema: Archivos se pierden después de deploy

**Causa**: `storage/app/public` no es persistente

**Solución**: Ver `INSTRUCCIONES_PRODUCCION_STORAGE.md`

Considerar usar:
- Storage en servidor separado
- AWS S3 / DigitalOcean Spaces
- NFS compartido

---

## 🗄️ Base de Datos

### Problema: "Table doesn't exist"

**Síntomas**:
```
SQLSTATE[42S02]: Base table or view not found
```

**Solución**:

```bash
# Ejecutar migraciones
php artisan migrate

# Ver estado de migraciones
php artisan migrate:status

# Si hay migraciones pendientes
php artisan migrate --force
```

---

### Problema: "Duplicate entry" al crear registro

**Causa**: Registro ya existe (unique constraint)

**Diagnóstico**:

```sql
-- Ver índices únicos de una tabla
SHOW INDEX FROM clients WHERE Non_unique = 0;
```

**Solución**:
- Verificar datos antes de insertar
- Usar `updateOrCreate()` en vez de `create()`

```php
Client::updateOrCreate(
    ['email' => 'cliente@ejemplo.com'],
    ['first_name' => 'Nombre', 'last_name' => 'Apellido']
);
```

---

### Problema: Query muy lenta

**Diagnóstico**:

```bash
# Activar query log
php artisan tinker
>>> DB::enableQueryLog();
>>> // ejecutar operación
>>> DB::getQueryLog();
```

**Soluciones**:

```sql
-- 1. Verificar índices
SHOW INDEX FROM clients;

-- 2. Analizar query lenta
EXPLAIN SELECT * FROM clients WHERE email LIKE '%ejemplo%';

-- 3. Crear índices necesarios
CREATE INDEX idx_email ON clients(email);
CREATE INDEX idx_scheduled_date ON jobs(scheduled_date);
```

---

### Problema: "Too many connections"

**Causa**: Límite de conexiones MySQL alcanzado

**Solución**:

```sql
-- Ver conexiones actuales
SHOW PROCESSLIST;

-- Ver límite
SHOW VARIABLES LIKE 'max_connections';

-- Aumentar límite (temporal)
SET GLOBAL max_connections = 200;
```

Permanente en `my.cnf` / `my.ini`:
```ini
[mysqld]
max_connections = 200
```

---

## ⚙️ Configuración y Entorno

### Problema: Cambios en `.env` no surten efecto

**Causa**: Caché de configuración

**Solución**:

```bash
# Limpiar caché de config
php artisan config:clear

# En producción, volver a cachear
php artisan config:cache
```

---

### Problema: "Class not found"

**Síntomas**:
```
Class 'App\Services\ColppyService' not found
```

**Solución**:

```bash
# Regenerar autoload
composer dump-autoload

# Limpiar caché de clases
php artisan clear-compiled
php artisan optimize:clear
```

---

### Problema: Error 500 sin detalles

**Diagnosis**:

```bash
# Ver logs
tail -f storage/logs/laravel.log

# Activar debug en .env (solo desarrollo!)
APP_DEBUG=true

# Ver errores de Apache/PHP
tail -f /var/log/apache2/error.log  # Linux
```

**En Windows XAMPP**:
```
C:\xampp\apache\logs\error.log
C:\xampp\php\logs\php_error_log
```

---

### Problema: Scheduler no ejecuta tareas

**Diagnóstico**:

```bash
# Ejecutar manualmente
php artisan schedule:run

# Ver tareas programadas
php artisan schedule:list
```

**Linux - Verificar cron**:

```bash
# Editar crontab
crontab -e

# Debe contener:
* * * * * cd /path/to/panel && php artisan schedule:run >> /dev/null 2>&1

# Ver logs de cron
grep CRON /var/log/syslog
```

**Windows - Verificar Task Scheduler**:
1. Abrir "Programador de tareas"
2. Buscar tarea "Laravel Scheduler"
3. Verificar que esté habilitada y ejecutándose

---

## 📱 App Móvil Flutter

### Problema: "Connection refused" desde app

**Causas**:
1. Backend no accesible desde dispositivo
2. URL incorrecta
3. Firewall bloqueando

**Soluciones**:

```dart
// Verificar URL base en Flutter
const String apiBaseUrl = 'http://192.168.1.100'; // IP local
// NO usar 'localhost' en dispositivo físico!
```

```bash
# Verificar que Apache escucha en todas las interfaces
# En httpd.conf:
Listen 0.0.0.0:80

# Verificar firewall permite conexiones
netsh advfirewall firewall add rule name="Apache" dir=in action=allow protocol=TCP localport=80
```

---

### Problema: Imágenes no cargan en app

**Causas**:
1. URL relativa en vez de absoluta
2. Storage link no creado
3. CORS (si API en dominio diferente)

**Solución**:

```php
// Laravel - Retornar URL completa
'image_url' => asset('storage/' . $media->path)
// o
'image_url' => url('storage/' . $media->path)
```

```dart
// Flutter - Usar URL completa
Image.network('$apiBaseUrl/storage/path/to/image.jpg')
```

---

### Problema: Token expira muy rápido

**Solución**:

```php
// config/sanctum.php
'expiration' => 525600, // 1 año en minutos

// O null para tokens que no expiren
'expiration' => null,
```

---

## 🚀 Performance

### Problema: Panel de admin muy lento

**Diagnóstico**:

```bash
# Instalar debugbar (solo desarrollo)
composer require barryvdh/laravel-debugbar --dev
```

**Optimizaciones**:

```bash
# 1. Cachear configuración
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 2. Optimizar composer
composer install --optimize-autoloader --no-dev

# 3. Usar eager loading
```

```php
// ❌ N+1 problem
$jobs = Job::all();
foreach ($jobs as $job) {
    echo $job->client->name; // Query por cada iteración
}

// ✅ Eager loading
$jobs = Job::with('client')->get();
foreach ($jobs as $job) {
    echo $job->client->name; // Un solo query
}
```

---

### Problema: API Colppy muy lenta

**Soluciones**:

1. **Usar sincronización local** (modo recomendado)
   ```sql
   UPDATE configs SET value = 'local' WHERE name = 'colppy_clientes_modo';
   ```

2. **Reducir paginación**:
   ```php
   // En SyncColppyClientsService
   $limit = 50; // En vez de 100
   ```

3. **Cachear sesiones** (ya implementado):
   ```php
   // ColppyService reutiliza claveSesion automáticamente
   ```

---

### Problema: Base de datos alcanza tamaño máximo

**Diagnóstico**:

```sql
-- Ver tamaño de tablas
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Size (MB)"
FROM information_schema.TABLES
WHERE table_schema = "strupeni_db"
ORDER BY (data_length + index_length) DESC;
```

**Soluciones**:

```sql
-- Limpiar logs antiguos
DELETE FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Limpiar jobs fallidos antiguos
DELETE FROM failed_jobs WHERE failed_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Limpiar sesiones inactivas
DELETE FROM sessions WHERE last_activity < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY));

-- Optimizar tablas
OPTIMIZE TABLE clients, jobs, jobs_file;
```

---

## 🆘 Comandos de Emergencia

### Resetear todo (desarrollo)

```bash
# ⚠️ CUIDADO: Esto elimina todo!
php artisan migrate:fresh --seed
php artisan storage:link
php artisan optimize:clear
```

---

### Modo mantenimiento

```bash
# Activar
php artisan down --message="Mantenimiento programado" --retry=60

# Desactivar
php artisan up
```

---

### Backup de emergencia

```bash
# Base de datos
mysqldump -u root -p strupeni_db > backup_$(date +%Y%m%d).sql

# Archivos importantes
tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/app/public
```

---

## 📞 Cuándo Contactar Soporte

Si después de seguir estas guías el problema persiste:

1. **Recopilar información**:
   - Logs completos (`storage/logs/laravel.log`)
   - Pasos para reproducir el error
   - Screenshots si aplica
   - Versión de PHP, Laravel, MySQL

2. **Información del entorno**:
   ```bash
   php -v
   php artisan --version
   mysql --version
   ```

3. **Estado del sistema**:
   ```bash
   php artisan about
   ```

---

**Documentos relacionados**:
- `INTEGRACION_COLPPY.md` - Problemas específicos de Colppy
- `CONFIGURAR_LIMITES_PHP.md` - Configuración de PHP
- `INSTRUCCIONES_PRODUCCION_STORAGE.md` - Storage en producción
- `FLUJO_SINCRONIZACION.md` - Sincronización de clientes
