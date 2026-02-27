# API Endpoints - Strupeni Electrónica

> Documentación completa de los endpoints disponibles en el sistema

---

## 📋 Información General

**Base URL**:
- **Web**: `http://localhost` (desarrollo) o `https://strupeni.com` (producción)
- **API Móvil**: `/api/`

**Autenticación**:
- **Web**: Sesión Laravel (cookies)
- **API Móvil**: Laravel Sanctum (Bearer tokens)

---

## 🔐 Autenticación

### Login (Web)

```http
POST /login
Content-Type: application/x-www-form-urlencoded

email=usuario@ejemplo.com&password=contraseña
```

**Respuesta**: Redirect a dashboard

---

### Login (API Móvil)

```http
POST /api/login
Content-Type: application/json

{
  "email": "tecnico@ejemplo.com",
  "password": "contraseña"
}
```

**Respuesta Success**:
```json
{
  "success": true,
  "token": "1|eyJ0eXAiOiJKV1QiLCJh...",
  "user": {
    "id": 5,
    "name": "Juan Técnico",
    "email": "tecnico@ejemplo.com"
  }
}
```

---

### Logout (API Móvil)

```http
POST /api/logout
Authorization: Bearer {token}
```

**Respuesta**:
```json
{
  "success": true,
  "message": "Sesión cerrada"
}
```

---

### Usuario Actual

```http
GET /api/user
Authorization: Bearer {token}
```

**Respuesta**:
```json
{
  "id": 5,
  "name": "Juan Técnico",
  "email": "tecnico@ejemplo.com",
  "roles": ["technician"],
  "permissions": ["view-jobs", "update-jobs"]
}
```

---

## 👥 Clientes

### Listar Clientes (DataTable Web)

```http
POST /client/table
X-CSRF-TOKEN: {token}
Content-Type: application/json

{
  "search": "López",
  "order": "desc",
  "page": 1,
  "limit": 10
}
```

**Respuesta**:
```json
{
  "success": true,
  "data": [
    {
      "id": 145,
      "colppy_id": "98",
      "is_from_colppy": 1,
      "first_name": "Carlos",
      "last_name": "López",
      "email": "carlos@ejemplo.com",
      "phone1": "3411234567",
      "city": "Rosario"
    }
  ],
  "total": 450,
  "page": 1,
  "limit": 10
}
```

---

### Listar Clientes (API Móvil)

```http
GET /api/jobs/clients
Authorization: Bearer {token}
```

**Respuesta**: Misma estructura que DataTable

**
Nota**: Respeta el modo configurado (`colppy_clientes_modo`):
- `'local'`: Todos los clientes
- `'colppy'`: Solo clientes sincronizados
- `'hibrido'`: Consulta directa a Colppy (lento)

---

### Obtener Cliente por ID

```http
GET /client/{id}/edit
Authorization: Sesión web
```

**Respuesta**:
```json
{
  "id": 145,
  "colppy_id": "98",
  "first_name": "Carlos",
  "last_name": "López",
  "type_doc": "DNI",
  "num_doc": "12345678",
  "email": "carlos@ejemplo.com",
  "phone1": "3411234567",
  "country": "Argentina",
  "state": "Santa Fe",
  "city": "Rosario",
  "address_street": "San Martín 123",
  "created_at": "2026-01-15T10:30:00.000000Z",
  "updated_at": "2026-02-20T14:25:00.000000Z"
}
```

---

### Crear Cliente

```http
POST /client
Content-Type: application/x-www-form-urlencoded

first_name=María&last_name=García&type_doc=DNI&num_doc=87654321&email=maria@ejemplo.com&phone1=3419876543
```

**Respuesta**: Redirect a `/client`

**Nota**: Solo funciona si `colppy_clientes_modo != 'colppy'`

---

### Actualizar Cliente

```http
PUT /client/{id}
Content-Type: application/json

{
  "first_name": "María Fernanda",
  "phone1": "3419876543",
  "email": "mariaf@ejemplo.com"
}
```

**Respuesta**: Redirect a `/client`

---

### Eliminar Cliente

```http
DELETE /client/{id}
```

**Respuesta**: Soft delete (marca `deleted_at`)

---

## 📍 Domicilios de Clientes

### Obtener Domicilios de un Cliente (Web)

```http
GET /client/address/{client_id}
Authorization: Sesión web
```

**Respuesta**:
```json
{
  "success": true,
  "data": [
    {
      "id": 23,
      "client_id": 145,
      "country": "Argentina",
      "state": "Santa Fe",
      "city": "Rosario",
      "cp": "2000",
      "address_street": "Belgrano",
      "address_nro": "456",
      "address_apartament": "2B",
      "address_detail": "Entre calles X e Y"
    }
  ]
}
```

---

### Obtener Domicilios (API Móvil)

```http
GET /api/client/address/{client_id}
Authorization: Bearer {token}
```

**Respuesta**: Misma estructura

---

### Crear Domicilio

```http
POST /client/address
Content-Type: application/json

{
  "client_id": 145,
  "country": "Argentina",
  "state": "Santa Fe",
  "city": "Rosario",
  "cp": "2000",
  "address_street": "Sarmiento",
  "address_nro": "789"
}
```

**Respuesta**:
```json
{
  "success": true,
  "message": "Domicilio creado correctamente",
  "data": {
    "id": 24,
    "client_id": 145,
    "address_street": "Sarmiento",
    "address_nro": "789"
  }
}
```

---

### Eliminar Domicilio

```http
DELETE /client/address/{id}
```

**Respuesta**: Soft delete

---

## 📅 Trabajos (Jobs)

### Obtener Trabajos de Hoy (API Móvil)

```http
GET /api/jobs/today
Authorization: Bearer {token}
```

**Respuesta**:
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "client_id": 145,
      "client_name": "Carlos López",
      "client_phone": "3411234567",
      "client_address": "San Martín 123, Rosario",
      "scheduled_date": "2026-02-27",
      "scheduled_time": "14:00:00",
      "status": "pending",
      "priority": "high",
      "description": "Reparación de equipo",
      "technicians": [
        {
          "id": 5,
          "name": "Juan Técnico"
        }
      ]
    }
  ]
}
```

---

### Obtener Trabajos Próximos

```http
GET /api/jobs/upcoming
Authorization: Bearer {token}
```

**Respuesta**: Trabajos de los próximos 7 días (misma estructura que `/today`)

---

### Obtener Trabajos por Rango de Fechas

```http
GET /api/jobs/calendar?start_date=2026-02-27&end_date=2026-03-05
Authorization: Bearer {token}
```

**Respuesta**:
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "scheduled_date": "2026-02-27",
      "client_name": "Carlos López",
      "status": "pending",
      "technicians": ["Juan Técnico"]
    }
  ]
}
```

---

### Obtener Trabajo por ID

```http
GET /api/jobs/{id}
Authorization: Bearer {token}
```

**Respuesta**:
```json
{
  "success": true,
  "data": {
    "id": 123,
    "client_id": 145,
    "client": {
      "id": 145,
      "first_name": "Carlos",
      "last_name": "López",
      "phone1": "3411234567",
      "address_street": "San Martín 123"
    },
    "scheduled_date": "2026-02-27",
    "scheduled_time": "14:00:00",
    "status": "pending",
    "priority": "high",
    "description": "Reparación de equipo",
    "internal_notes": "Cliente preferencial",
    "technicians": [
      {
        "id": 5,
        "name": "Juan Técnico",
        "email": "juan@ejemplo.com"
      }
    ],
    "created_at": "2026-02-20T10:00:00.000000Z",
    "updated_at": "2026-02-20T10:00:00.000000Z"
  }
}
```

---

### Crear Trabajo

```http
POST /api/jobs
Authorization: Bearer {token}
Content-Type: application/json

{
  "client_id": 145,
  "scheduled_date": "2026-02-28",
  "scheduled_time": "10:00",
  "description": "Instalación de equipo nuevo",
  "priority": "medium",
  "technician_ids": [5, 7]
}
```

**Respuesta**:
```json
{
  "success": true,
  "message": "Trabajo creado correctamente",
  "data": {
    "id": 124,
    "client_id": 145,
    "scheduled_date": "2026-02-28",
    "status": "pending"
  }
}
```

---

### Actualizar Trabajo

```http
PUT /api/jobs/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "scheduled_time": "11:00",
  "priority": "high",
  "description": "Instalación urgente"
}
```

---

### Actualizar Técnicos Asignados

```http
PATCH /api/jobs/{id}/technicians
Authorization: Bearer {token}
Content-Type: application/json

{
  "technician_ids": [5, 7, 9]
}
```

---

### Marcar Llegada (Técnico arribó)

```http
POST /api/jobs/{id}/arrival
Authorization: Bearer {token}
```

**Respuesta**:
```json
{
  "success": true,
  "message": "Llegada registrada",
  "data": {
    "id": 123,
    "status": "in_progress",
    "arrival_time": "2026-02-27T14:05:00.000000Z"
  }
}
```

---

### Volver a Pendiente

```http
POST /api/jobs/{id}/back-to-pending
Authorization: Bearer {token}
```

---

### Cerrar Trabajo

```http
POST /api/jobs/{id}/close
Authorization: Bearer {token}
Content-Type: application/json

{
  "completion_notes": "Trabajo completado satisfactoriamente"
}
```

**Respuesta**:
```json
{
  "success": true,
  "message": "Trabajo cerrado",
  "data": {
    "id": 123,
    "status": "completed",
    "completed_at": "2026-02-27T16:30:00.000000Z"
  }
}
```

---

### Eliminar Trabajo

```http
DELETE /api/jobs/{id}
Authorization: Bearer {token}
```

**Respuesta**: Soft delete

---

## 📝 Notas de Trabajos

### Obtener Notas de un Trabajo

```http
GET /api/jobs/{job_id}/notes
Authorization: Bearer {token}
```

**Respuesta**:
```json
{
  "success": true,
  "data": [
    {
      "id": 45,
      "job_id": 123,
      "user_id": 5,
      "user_name": "Juan Técnico",
      "note": "Cliente solicitó cambio de horario",
      "created_at": "2026-02-27T10:15:00.000000Z"
    }
  ]
}
```

---

### Agregar Nota

```http
POST /api/jobs/{job_id}/notes
Authorization: Bearer {token}
Content-Type: application/json

{
  "note": "Equipo requiere repuesto adicional"
}
```

**Respuesta**:
```json
{
  "success": true,
  "message": "Nota agregada",
  "data": {
    "id": 46,
    "job_id": 123,
    "note": "Equipo requiere repuesto adicional"
  }
}
```

---

### Eliminar Nota

```http
DELETE /api/jobs/notes/{note_id}
Authorization: Bearer {token}
```

---

## 📎 Archivos de Trabajos

### Obtener Archivos de un Trabajo

```http
GET /api/jobs/{job_id}/files
Authorization: Bearer {token}
```

**Respuesta**:
```json
{
  "success": true,
  "data": [
    {
      "id": 78,
      "job_id": 123,
      "filename": "foto_equipo.jpg",
      "url": "/storage/jobs/123/foto_equipo.jpg",
      "mimetype": "image/jpeg",
      "size": 524288,
      "uploaded_by": "Juan Técnico",
      "created_at": "2026-02-27T15:20:00.000000Z"
    }
  ]
}
```

---

### Subir Archivos

```http
POST /api/jobs/{job_id}/files
Authorization: Bearer {token}
Content-Type: multipart/form-data

files[]: [archivo1.jpg]
files[]: [archivo2.pdf]
```

**Respuesta**:
```json
{
  "success": true,
  "message": "2 archivos subidos correctamente",
  "data": [
    {
      "id": 79,
      "filename": "archivo1.jpg",
      "url": "/storage/jobs/123/archivo1.jpg"
    },
    {
      "id": 80,
      "filename": "archivo2.pdf",
      "url": "/storage/jobs/123/archivo2.pdf"
    }
  ]
}
```

---

### Eliminar Archivo

```http
DELETE /api/jobs/files/{file_id}
Authorization: Bearer {token}
```

---

## 🔄 Sincronización Colppy

### Obtener Estadísticas de Sincronización

```http
GET /client/sync-stats
```

**Respuesta**:
```json
{
  "total_locales": 50,
  "local_de_colppy": 400,
  "colppy_total": 450,
  "diferencia": 50,
  "modo": "local",
  "ultima_sincronizacion": "2026-02-27T12:00:00.000000Z"
}
```

---

### Sincronizar Clientes (Asíncrono)

```http
POST /client/sync-colppy
X-CSRF-TOKEN: {token}
```

**Respuesta**:
```json
{
  "success": true,
  "message": "Sincronización iniciada en segundo plano"
}
```

**Nota**: Despacha `SyncColppyClientsJob`. Si `QUEUE_CONNECTION=sync`, ejecuta inmediatamente.

---

### Sincronizar Clientes (Síncrono)

```http
POST /client/sync-colppy-now
X-CSRF-TOKEN: {token}
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

**Nota**: Ejecuta sincrónico, bloquea hasta completar. Puede tomar varios minutos.

---

## 🎨 CMS (Sistema de Contenido)

### Obtener Tema Activo (Flutter)

```http
GET /api/flutter/theme
```

**Respuesta**:
```json
{
  "success": true,
  "data": {
    "primary_color": "#1976D2",
    "secondary_color": "#FFC107",
    "background_color": "#FFFFFF",
    "text_color": "#212121",
    "accent_color": "#FF5722",
    "app_bar_color": "#1976D2",
    "button_color": "#1976D2"
  }
}
```

---

### Listar Secciones CMS

```http
GET /cms
Authorization: Sesión web
Roles: admin, editor
```

**Respuesta**: Vista HTML con listado de secciones

---

### Editar Sección

```http
GET /cms/sections/{slug}/edit
Authorization: Sesión web con permisos
```

**Respuesta**: Formulario de edición

---

### Actualizar Sección

```http
POST /cms/sections/{slug}
Content-Type: multipart/form-data

title=Inicio&content=<html>...</html>&images[]=...
```

**Respuesta**: Redirect con mensaje de éxito

---

### Ver Versiones de Sección

```http
GET /cms/sections/{slug}/versions
```

---

### Restaurar Versión

```http
POST /cms/sections/{slug}/restore/{versionId}
```

---

## 📁 Media (Archivos del CMS)

### Listar Media

```http
GET /cms/media
Authorization: Sesión web
```

---

### Subir Archivo

```http
POST /cms/media/upload
Content-Type: multipart/form-data

file=[archivo.jpg]&name=Logo Principal
```

---

### Subir Múltiples Archivos

```http
POST /cms/media/upload-multiple
Content-Type: multipart/form-data

files[]=[archivo1.jpg]&files[]=[archivo2.png]
```

---

### Actualizar Nombre de Media

```http
POST /cms/media/{id}/update-name
Content-Type: application/json

{
  "name": "Nuevo Nombre"
}
```

---

### Eliminar Media

```http
DELETE /cms/media/{id}
```

---

## ⚙️ Configuración

### Ver Configuración API

```http
GET /cms/api-config
Authorization: Sesión web
Roles: admin
```

---

### Actualizar Configuración

```http
POST /cms/api-config
Content-Type: application/json

{
  "url_api_login": "https://login.colppy.com/...",
  "user_api": "usuario@empresa.com",
  "pass_api": "contraseña",
  "id_empresa_api": "98",
  "colppy_clientes_modo": "local"
}
```

---

## 🔍 Búsqueda

### Búsqueda de Variables (Autocompletado)

```http
GET /api/searchvar?search=López
```

o

```http
POST /api/searchvar
Content-Type: application/json

{
  "search": "López",
  "type": "client"
}
```

**Respuesta**:
```json
{
  "success": true,
  "data": [
    {
      "id": 145,
      "label": "Carlos López - DNI 12345678",
      "value": 145
    }
  ]
}
```

---

## 👤 Usuarios y Permisos

### Listar Usuarios (DataTable)

```http
POST /users/table
X-CSRF-TOKEN: {token}
Content-Type: application/json

{
  "search": "Juan",
  "page": 1,
  "limit": 10
}
```

---

### Editar Usuario

```http
GET /users/{id}/edit
```

---

### Listar Roles (DataTable)

```http
POST /roles/table
```

---

### Editar Rol

```http
GET /roles/{id}/edit
```

---

### Obtener Usuarios por Rol

```http
GET /roles/users/{rol_id}
```

---

### Listar Permisos (DataTable)

```http
POST /permission/table
```

---

### Actualizar Permisos de Rol

```http
POST /roles/permission/update
Content-Type: application/json

{
  "rol_id": 3,
  "permissions": [1, 5, 8, 12]
}
```

---

## 🛠️ Utilidades

### Limpiar Caché

```http
GET /clear-cache
```

**Respuesta**: Mensaje de éxito

---

### Test de API Colppy

```http
GET /test
```

**Respuesta**: Página de testing

---

## 📊 Códigos de Estado HTTP

| Código | Significado         | Uso                           |
|--------|---------------------|-------------------------------|
| 200    | OK                  | Operación exitosa             |
| 201    | Created             | Recurso creado correctamente  |
| 400    | Bad Request         | Error en datos enviados       |
| 401    | Unauthorized        | No autenticado                |
| 403    | Forbidden           | Sin permisos                  |
| 404    | Not Found           | Recurso no encontrado         |
| 422    | Unprocessable       | Error de validación           |
| 500    | Internal Server Error | Error del servidor          |

---

## 🔐 Autenticación y Seguridad

### CSRF Token (Web)
Todos los endpoints POST/PUT/DELETE requieren CSRF token en header:
```
X-CSRF-TOKEN: {token}
```

O en formulario:
```html
<input type="hidden" name="_token" value="{{csrf_token()}}">
```

### Bearer Token (API Móvil)
```
Authorization: Bearer {token_from_login}
```

### Permisos (Spatie)
Muchos endpoints verifican permisos:
- `view-clients`
- `create-clients`
- `edit-clients`
- `delete-clients`
- `view-jobs`
- `create-jobs`
- `edit-jobs`
- etc.

---

## 📝 Notas Importantes

1. **Paginación**: Endpoints con DataTable soportan `page` y `limit`
2. **Soft Deletes**: Los DELETE no eliminan físicamente, marcan `deleted_at`
3. **Timestamps**: Todas las fechas en formato ISO 8601 (UTC)
4. **Archivos**: Máximo según `php.ini` (ver `CONFIGURAR_LIMITES_PHP.md`)
5. **Rate Limiting**: APIs públicas pueden tener límite de requests

---

**Documentos relacionados**:
- `INTEGRACION_COLPPY.md` - Detalles de Colppy API
- `FLUJO_SINCRONIZACION.md` - Proceso de sincronización
- `TROUBLESHOOTING.md` - Solución de problemas
