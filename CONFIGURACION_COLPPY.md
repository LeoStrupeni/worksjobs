# 🔧 Configuración Colppy - Checklist de Implementación

## ✅ Paso 1: Configurar Credenciales de Colppy

Accede a: `http://localhost/panel/cms/api-config`

Rellena los siguientes campos:

### Configuración de Acceso a API
- **URL API Login**: `https://login.colppy.com/lib/frontera2/service.php`

### Configuración de Desarrollo (Staging)
- **Usuario Dev API**: `tu_usuario_dev@colppy.com`
- **Contraseña Dev API**: `tu_contraseña_dev` (se convierte a MD5 automáticamente)

### Configuración de Producción
- **Usuario API**: `tu_usuario_produccion@colppy.com`
- **Contraseña API**: `tu_contraseña_produccion` (se convierte a MD5 automáticamente)

### Parámetros Generales
- **ID Empresa API**: `98` (número de empresa en Colppy)

---

## ✅ Paso 2: Ejecutar Migraciones

En la consola (dentro de `panel/`):

```bash
# Crear tabla colppy_sessions
php artisan migrate

# Verificar que la tabla se creó
php artisan migrate:status
```

---

## ✅ Paso 3: Verificar Rutas API

Las siguientes rutas estarán disponibles (requieren autenticación Sanctum):

```bash
# Obtener sesión
POST /api/colppy/session
Headers: Authorization: Bearer {token}
Body: {} (vacío)

# Listar clientes
GET /api/colppy/clientes?start=0&limit=100
GET /api/colppy/clientes?filters={"field":"Activo","op":"=","value":"1"}

# Obtener cliente específico
GET /api/colppy/clientes/123
Headers: Authorization: Bearer {token}

# Llamada genérica
POST /api/colppy/call
Headers: Authorization: Bearer {token}
Body: {
  "provision": "Cliente",
  "operacion": "listar_cliente",
  "parameters": { ... }
}

# Invalidar sesión
POST /api/colppy/invalidate-session
Headers: Authorization: Bearer {token}
Body: {}
```

---

## ✅ Paso 4: Probar con Postman/Thunder Client

### Test 1: Obtener Sesión

```http
POST https://tecnicos.strupeni.com.ar/api/colppy/session
Content-Type: application/json
Authorization: Bearer {tu_token_sanctum}
```

**Respuesta esperada**:
```json
{
  "success": true,
  "data": {
    "claveSesion": "b5a97564ad59e624a6ba545ecd3ca112",
    "usuario": "tu_usuario@colppy.com",
    "idEmpresa": "98"
  }
}
```

### Test 2: Listar Clientes

```http
GET https://tecnicos.strupeni.com.ar/api/colppy/clientes?start=0&limit=10
Content-Type: application/json
Authorization: Bearer {tu_token_sanctum}
```

**Respuesta esperada**:
```json
{
  "success": true,
  "data": [
    {
      "idCliente": "1",
      "RazonSocial": "Empresa A",
      "NombreFantasia": "Cliente A",
      "CUIT": "30-69224359-1",
      "Activo": "1"
    },
    ...
  ],
  "metadata": { ... }
}
```

---

## ✅ Paso 5: Verificar Logs

Después de ejecutar los tests, verificar logs en:

```
storage/logs/laravel-{fecha}.log
```

Buscar errores como:
- `"Configuración de Colppy incompleta"`
- `"Error de conexión con la API de Colppy"`

---

## 📱 Paso 6: Usar en la App Flutter

### 6.1 Actualizar api_config.dart

Ya está hecho, pero verifica que tenga:

```dart
static const String colppySessionEndpoint = '/colppy/session';
static const String colppyClientesEndpoint = '/colppy/clientes';
```

### 6.2 Usar ColppyService

```dart
import 'package:technician_app/services/colppy_service.dart';
import 'package:technician_app/models/colppy_cliente.dart';

// En tu código
final token = await AuthService.getToken();
final resultado = await ColppyService.listarClientes(token!);

if (resultado['success']) {
  final clientes = ColppyClientesResponse.fromJson(resultado['data']);
  print('Clientes encontrados: ${clientes.clientes.length}');
}
```

---

## 🔍 Paso 7: Verificar Tabla colppy_sessions

Después de las pruebas, la tabla debe tener registros:

```sql
SELECT * FROM colppy_sessions WHERE activa = 1;
```

Cada registro contiene:
- `usuario`: Usuario de Colppy
- `clave_sesion`: La clave obtenida
- `id_empresa`: ID de empresa
- `se_vence_en`: Cuándo expira (1 hora por defecto)
- `activa`: Si la sesión sigue siendo válida

---

## ⚙️ Configuración Adicional

### Cambiar Tiempo de Expiración de Sesión

Editar: `panel/app/Services/ColppyService.php`

Buscar la línea:
```php
'se_vence_en' => now()->addHour(),
```

Cambiar según necesidad:
```php
'se_vence_en' => now()->addMinutes(30),  // 30 minutos
'se_vence_en' => now()->addHours(2),    // 2 horas
```

### Limpiar Sesiones Expiradas

Se puede ejecutar un comando artisan programado:

```bash
# Ejecutar manualmente
php artisan tinker
ColppyService::limpiarSesionesExpiradas()

# O en un Schedule (kernel.php)
$schedule->call(function () {
    \App\Services\ColppyService::limpiarSesionesExpiradas();
})->hourly();
```

---

## 📋 Checklist Final

- [ ] Credenciales configuradas en `/cms/api-config`
- [ ] Migraciones ejecutadas (`colppy_sessions` existe)
- [ ] Rutas API agregadas a `routes/api.php`
- [ ] ColppyService creado en `app/Services/`
- [ ] ApiColppyController creado en `app/Http/Controllers/Api/`
- [ ] ColppyService creado en Flutter
- [ ] ColppyCliente modelo creado en Flutter
- [ ] api_config.dart actualizado con endpoints
- [ ] Test exitoso en Postman/Thunder Client
- [ ] Logs verificados (sin errores)

---

## 🆘 Troubleshoot Rápido

### ❌ "No tienes permiso para acceder a la configuración de API"
→ Asegurate de tener rol CMS con permisos de lectura/escritura

### ❌ "SQLSTATE[42S02]: Table 'colppy_sessions' doesn't exist"
→ Ejecutar: `php artisan migrate`

### ❌ "Configuración de Colppy incompleta"
→ Rellenar todos los campos en `/cms/api-config`

### ❌ "Error de conexión con la API de Colppy"
→ Verificar:
- URL es correcta
- Credenciales son correctas
- Conexión a internet
- Firewall no bloquea la conexión

### ❌ Error 401/403 en Flutter
→ Verificar que el token Sanctum es válido y no ha expirado

---

**Apuntes importantes**:
1. ⚠️ El tiempo de expiración de sesión en Colppy aún no se conoce con precisión
2. ⚠️ Implementar manejo robusto de errores en la app
3. ⚠️ Considerar rate limiting en el backend
4. ⚠️ No hardcodear credenciales en el código

---

**Última actualización**: 16 de febrero de 2026
