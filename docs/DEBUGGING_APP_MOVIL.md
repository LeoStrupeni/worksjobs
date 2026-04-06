# 🐛 Debugging de App Móvil - Errores Remotos

## 📋 Problema

Usuario reporta: **"Error: Error al obtener citas"** en la app móvil.

No tenemos acceso físico al dispositivo, por lo que necesitamos debugging remoto.

---

## 🔀 **IMPORTANTE: Diagnóstico Inicial**

Antes de continuar, determinar si el problema es del **backend** o del **cliente**:

### ✅ **Paso 0: Prueba Rápida**
Intentá loguearte con las credenciales del usuario en **TU dispositivo**:

**Si funciona en tu dispositivo:**
→ El problema es **específico del dispositivo del usuario**  
→ **Seguí la guía:** [`DEBUGGING_APP_MOVIL_CLIENTE.md`](DEBUGGING_APP_MOVIL_CLIENTE.md)

**Si NO funciona en ningún dispositivo:**
→ El problema es del **backend** (servidor/base de datos)  
→ **Continuá con este documento** ⬇️

---

## 🔧 Soluciones Implementadas

### ✅ 1. Logging Detallado en Backend

Se agregó logging extensivo en:
- `ApiJobController::getTodayJobs()`
- `ApiJobController::getUpcomingJobs()`
- `ApiJobController::getJobsByDateRange()`

**Qué hace:**
- ✅ Registra usuario que hace la petición
- ✅ Registra cantidad de trabajos encontrados
- ✅ Captura excepciones con stack trace completo
- ✅ Identifica si el usuario está autenticado

### ✅ 2. Endpoint de Health Check

**Nuevo endpoint:** `GET /api/health-check`

**Requiere:** Token Sanctum válido

**Respuesta exitosa:**
```json
{
  "success": true,
  "message": "Autenticación válida",
  "authenticated": true,
  "data": {
    "user_id": 5,
    "name": "Juan Técnico",
    "email": "juan@example.com",
    "roles": ["technician"],
    "permissions_count": 8,
    "estatus": 1,
    "token_name": "mobile-app",
    "token_created_at": "2026-03-20 10:30:00"
  }
}
```

**Respuesta con error:**
```json
{
  "success": false,
  "message": "No autenticado",
  "authenticated": false
}
```

---

## 📝 Pasos para Debugging Remoto

### **PASO 1: Verificar los Logs del Servidor**

**Ubicación del log:**
```bash
panel/storage/logs/laravel.log
```

**Qué buscar:**
```log
# Buscar logs del usuario afectado
📱 getTodayJobs - Usuario: 5 - juan@example.com
✅ getTodayJobs - 3 trabajos encontrados para juan@example.com

# O errores
❌ getTodayJobs - Usuario no autenticado
❌ getTodayJobs - Exception: ...
```

**Comando para ver logs en tiempo real:**
```bash
# En Linux/Mac
tail -f panel/storage/logs/laravel.log

# En Windows (CMD)
Get-Content panel\storage\logs\laravel.log -Wait -Tail 50

# O simplemente abrir el archivo y ver las últimas líneas
```

**Comando para buscar logs específicos de un usuario:**
```bash
# Linux/Mac
grep "getTodayJobs" panel/storage/logs/laravel.log | tail -20

# Windows PowerShell
Select-String -Path "panel\storage\logs\laravel.log" -Pattern "getTodayJobs" | Select-Object -Last 20
```

---

### **PASO 2: Probar el Endpoint de Health Check**

Pedile al usuario que intente cargar las citas, y luego vos ejecutá esto en el servidor:

**Opción A: Desde el servidor (CURL)**

```bash
# Reemplazar TOKEN_DEL_USUARIO con el token real
curl -X GET https://tecnicos.strupeni.com.ar/api/health-check \
  -H "Authorization: Bearer TOKEN_DEL_USUARIO" \
  -H "Accept: application/json"
```

**Opción B: Desde Postman / Insomnia**

1. Método: `GET`
2. URL: `https://tecnicos.strupeni.com.ar/api/health-check`
3. Headers:
   - `Authorization: Bearer {token_del_usuario}`
   - `Accept: application/json`

**Opción C: Desde PHP (crear archivo test)**

Crear `panel/test-health.php`:
```php
<?php
$token = 'TOKEN_DEL_USUARIO'; // Obtener de la base de datos

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://tecnicos.strupeni.com.ar/api/health-check');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response:\n";
print_r(json_decode($response, true));
```

Ejecutar:
```bash
php panel/test-health.php
```

---

### **PASO 3: Verificar Token en Base de Datos**

**Consulta SQL:**
```sql
-- Ver tokens del usuario específico
SELECT 
    pat.id,
    pat.name as token_name,
    pat.tokenable_id as user_id,
    u.name as user_name,
    u.email,
    u.estatus,
    pat.created_at,
    pat.last_used_at,
    DATEDIFF(NOW(), pat.created_at) as dias_desde_creacion
FROM personal_access_tokens pat
JOIN users u ON pat.tokenable_id = u.id
WHERE u.email = 'email@del.usuario'
ORDER BY pat.created_at DESC;

-- Ver si el token fue usado recientemente
SELECT 
    last_used_at,
    TIMESTAMPDIFF(HOUR, last_used_at, NOW()) as horas_sin_uso
FROM personal_access_tokens
WHERE tokenable_id = 5  -- ID del usuario
ORDER BY created_at DESC
LIMIT 1;
```

**Problemas comunes:**
- ✅ Token no existe → Usuario debe volver a hacer login
- ✅ `last_used_at` es NULL → Token nunca fue usado
- ✅ `last_used_at` muy antiguo → Posible problema de conectividad
- ✅ Usuario con `estatus = 0` → Usuario desactivado

---

### **PASO 4: Verificar Estado del Usuario**

```sql
-- Verificar usuario completo
SELECT 
    u.id,
    u.name,
    u.email,
    u.estatus,
    u.deleted_at,
    GROUP_CONCAT(r.name) as roles
FROM users u
LEFT JOIN model_has_roles mhr ON u.id = mhr.model_id
LEFT JOIN roles r ON mhr.role_id = r.id
WHERE u.email = 'email@del.usuario'
GROUP BY u.id;

-- Verificar permisos del usuario
SELECT 
    p.name as permission_name
FROM users u
JOIN model_has_permissions mhp ON u.id = mhp.model_id
JOIN permissions p ON mhp.permission_id = p.id
WHERE u.email = 'email@del.usuario'
ORDER BY p.name;
```

---

### **PASO 5: Probar Endpoint Directo de Citas**

```bash
# Probar getTodayJobs
curl -X GET https://tecnicos.strupeni.com.ar/api/jobs/today \
  -H "Authorization: Bearer TOKEN_DEL_USUARIO" \
  -H "Accept: application/json"

# Probar getUpcomingJobs
curl -X GET https://tecnicos.strupeni.com.ar/api/jobs/upcoming \
  -H "Authorization: Bearer TOKEN_DEL_USUARIO" \
  -H "Accept: application/json"
```

---

## 🔍 Diagnóstico por Síntomas

### **Error: "No autenticado"**

**Causa:** Token expiró o es inválido

**Solución:**
1. Verificar token en BD (PASO 3)
2. Pedirle al usuario que cierre sesión y vuelva a iniciar
3. Verificar que el token se esté enviando correctamente en la app

**SQL para forzar logout del usuario:**
```sql
-- Eliminar todos los tokens del usuario
DELETE FROM personal_access_tokens 
WHERE tokenable_id = 5;  -- ID del usuario
```

Luego pedirle que vuelva a hacer login.

---

### **Error: "Error al obtener citas" (genérico)**

**Causa:** Excepción en el backend

**Solución:**
1. Ver logs del servidor (PASO 1)
2. Buscar el stack trace de la excepción
3. Verificar consulta SQL en el log

**Posibles causas:**
- ❌ Error en query SQL (tabla no existe, columna incorrecta)
- ❌ Base de datos inaccesible
- ❌ Error al obtener permisos (Spatie)
- ❌ Memoria PHP insuficiente (muchos trabajos)

---

### **Error: Carga infinita (spinner eterno)**

**Causa:** Timeout de red o respuesta lenta

**Solución:**
1. Verificar logs para ver si la petición llega al servidor
2. Medir tiempo de respuesta:

```bash
# Medir tiempo de respuesta
time curl -X GET https://tecnicos.strupeni.com.ar/api/jobs/today \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json"
```

3. Si tarda más de 30 segundos → optimizar query
4. Verificar cantidad de trabajos:

```sql
-- Ver cantidad de trabajos del día
SELECT COUNT(*) as total_jobs_today
FROM jobs
WHERE DATE(visit_datetime) = CURDATE()
  AND deleted_at IS NULL;

-- Ver trabajos abiertos antiguos (pueden ser muchos)
SELECT COUNT(*) as old_open_jobs
FROM jobs
WHERE visit_datetime < CURDATE()
  AND closed_datetime IS NULL
  AND deleted_at IS NULL;
```

Si hay **demasiados trabajos antiguos abiertos**, considerar:
- Cerrar trabajos antiguos manualmente
- Agregar paginación a la app
- Limitar a últimos 30 días

---

## 🚀 Próximos Pasos y Mejoras

### **1. Mejorar manejo de errores en Flutter**

Modificar `job_service.dart` para capturar más detalles:

```dart
// En getTodayJobs (línea 15-60)
catch (e) {
  // En vez de solo: 'Error al obtener citas'
  print('❌ getTodayJobs: Exception: $e');
  
  // Devolver más info
  return {
    'success': false, 
    'message': 'Error al obtener citas',
    'error_detail': e.toString()  // ← AGREGAR ESTO
  };
}
```

Y mostrar el error en la UI (para debug).

---

### **2. Crear pantalla de debug en la app**

Agregar una pantalla oculta (ej: tap 5 veces en el logo) que muestre:
- ✅ Usuario actual
- ✅ Token (primeros/últimos 10 caracteres)
- ✅ Última petición exitosa
- ✅ Último error recibido
- ✅ URL del servidor
- ✅ Botón para probar health-check

---

### **3. Implementar sistema de reportes de errores**

Cuando ocurre un error, enviar automáticamente al backend:

```dart
// Nuevo endpoint en Laravel
POST /api/error-report
{
  "user_id": 5,
  "screen": "TodayJobsScreen",
  "error_message": "Error al obtener citas",
  "error_detail": "...",
  "timestamp": "2026-03-23 15:30:00",
  "device_info": {
    "platform": "android",
    "version": "12",
    "app_version": "1.2.3"
  }
}
```

Guardar en tabla `app_error_logs` para revisión posterior.

---

### **4. Agregar retry automático en la app**

```dart
// Si falla, reintentar 1 vez después de 2 segundos
if (result['success'] == false) {
  await Future.delayed(Duration(seconds: 2));
  result = await getTodayJobs(); // Reintentar
}
```

---

## 📋 Checklist de Debugging

Cuando un usuario reporte error:

- [ ] 1. Verificar logs del servidor (`laravel.log`)
- [ ] 2. Probar endpoint de health-check con el token del usuario
- [ ] 3. Verificar token en base de datos
- [ ] 4. Verificar estado del usuario (activo, permisos)
- [ ] 5. Probar endpoint directo de citas
- [ ] 6. Medir tiempo de respuesta
- [ ] 7. Verificar cantidad de trabajos a devolver
- [ ] 8. Si nada funciona: pedirle que haga logout/login nuevamente

---

## 🔧 Comandos Rápidos

```bash
# Ver últimos logs
tail -50 panel/storage/logs/laravel.log

# Buscar errores de usuario específico
grep "juan@example.com" panel/storage/logs/laravel.log

# Ver tokens activos
mysql -u root -p strupeni_db -e "SELECT tokenable_id, name, created_at, last_used_at FROM personal_access_tokens ORDER BY created_at DESC LIMIT 10;"

# Eliminar tokens viejos (más de 30 días sin usar)
mysql -u root -p strupeni_db -e "DELETE FROM personal_access_tokens WHERE last_used_at < DATE_SUB(NOW(), INTERVAL 30 DAY);"
```

---

## 📞 Contacto para Soporte

Si necesitas ayuda adicional:
1. Recolectar logs del servidor
2. Ejecutar health-check del usuario
3. Captura de pantalla del error
4. Email del usuario afectado

**Recuerda:** Con los nuevos logs, deberías tener mucha más información para diagnosticar el problema remotamente.
