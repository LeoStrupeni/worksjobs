# 🚀 Quick Start - Integración Colppy

## 2 Minutos para Empezar

### Paso 1: Configurar Credenciales (1 min)

Abre: `http://localhost/panel/cms/api-config`

Completa estos campos (ya habían sido configurados antes):
```
URL API Login: https://login.colppy.com/lib/frontera2/service.php
Usuario Dev API: tu_email@colppy.com
Contraseña Dev API: tu_password_colppy
ID Empresa API: 98
```

**Guarda** ✅

---

### Paso 2: Probar Backend (1 min)

1. **Login** en la app para obtener un token
   
2. **Abre Postman/Thunder Client** y haz este request:

```http
POST http://localhost/panel/api/colppy/session
Authorization: Bearer {TOKEN_DEL_PASO_1}
Content-Type: application/json
```

**Debe retornar:**
```json
{
  "success": true,
  "data": {
    "claveSesion": "xyz123...",
    "usuario": "tu_email@colppy.com",
    "idEmpresa": "98"
  }
}
```

Si ves esto ✅, ¡va perfecto!

---

### Paso 3: Usar en Flutter

En tu pantalla de clientes:

```dart
import 'package:technician_app/services/colppy_service.dart';

// En tu código
final token = 'TOKEN_DE_AUTENTICACION';

final resultado = await ColppyService.listarClientes(token);

if (resultado['success']) {
  final clientes = resultado['data']; // Es una lista
  print('Clientes: ${clientes.length}');
}
```

---

## ¿Qué se hizo?

✅ **Servicio simple**: Sin tabla en BD, usa SESSION de Laravel  
✅ **API REST**: 5 endpoints lista

1. **Login** en la app para obtener un token
   
2. **Abre Postman/Thunder Client** y haz este request:

```http
POST http://localhost/panel/api/colppy/session
Authorization: Bearer {TOKEN_DEL_PASO_1}
Content-Type: application/json
```

**Debe retornar:**
```json
{
  "success": true,
  "data": {
    "claveSesion": "xyz123...",
    "usuario": "tu_email@colppy.com",
    "idEmpresa": "98"
  }
}
```

Si ves esto ✅, ¡va perfecto!

---

### Paso 4: Usar en Flutter (1 min)

En tu pantalla de clientes:

```dart
import 'package:technician_app/services/colppy_service.dart';

// En tu código
final token = 'TOKEN_DE_AUTENTICACION';

final resultado = await ColppyService.listarClientes(token);

if (resultado['success']) {
  final clientes = resultado['data']; // Es una lista
  print('Clientes: ${clientes.length}');
}
```

---

## ¿Qué acabas de hacer?

✅ **Backend**: Servicio que conecta con Colppy  
✅ **Base de datos**: Tabla para guardar sesiones  
✅ **API REST**: Endpoints para obtener clientes  
✅ **Frontend**: Servicio Flutter listo para usar  

---

## 📚 Documentación Completa

Después del quick start, lee:

1. [INTEGRACION_COLPPY.md](INTEGRACION_COLPPY.md) - Guía técnica completa
2. [CONFIGURACION_COLPPY.md](CONFIGURACION_COLPPY.md) - Checklist de setup
3. [EJEMPLOS_USO_COLPPY.md](EJEMPLOS_USO_COLPPY.md) - Ejemplos de código
4. [FLUJOS_COLPPY_DIAGRAMA.md](FLUJOS_COLPPY_DIAGRAMA.md) - Diagramas de flujo

---

## ⚠️ Problemas Comunes

### "Configuración de Colppy incompleta"
→ Asegurate de rellenar todos los campos en `/cms/api-config`

### "Table colppy_sessions doesn't exist"
→ Ejecuta: `php artisan migrate`

### Error 401
→ El token ha expirado, haz login de nuevo

### Timeout
→ Verifica tu conexión a internet y la URL de Colppy

---

## 🔗 Endpoints Disponibles

```
GET    /api/colppy/clientes               # Listar
GET    /api/colppy/clientes/{id}          # Detalle
POST   /api/colppy/session                # Sesión
POST   /api/colppy/call                   # Llamada genérica
POST   /api/colppy/invalidate-session     # Invalidar
```

Todos requieren: `Authorization: Bearer {token}`

---

¡Listo! Ya puedes conectar tu app con Colppy 🎉
