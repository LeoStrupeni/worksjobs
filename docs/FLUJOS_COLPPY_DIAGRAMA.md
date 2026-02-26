# 🔄 Flujos de Integración Colppy

## Diagrama 1: Flujo de Obtención de Clientes

```
┌─────────────────────────────────────────────────────────────────┐
│                      OBTENER CLIENTES                           │
└─────────────────────────────────────────────────────────────────┘

                        ┌─────────────────┐
                        │   Flutter App   │
                        │  Usuario Login  │
                        └────────┬────────┘
                                 │
                                 │ 1. Presiona "Ver Clientes"
                                 ▼
                        ┌─────────────────────┐
                        │  ColppyService      │
                        │ .listarClientes()   │
                        └────────┬────────────┘
                                 │
                                 │ 2. Verifica token Sanctum
                                 ▼
                        ┌──────────────────────────────┐
                        │  Backend Laravel             │
                        │  POST /api/colppy/session    │
                        └────────┬─────────────────────┘
                                 │
               ┌─────────────────┼──────────────────┐
               │                 │                  │
               ▼                 ▼                  ▼
         ¿Sesión en BD?    ¿Sesión válida?   ¿Está en caché?
         y vigente?        (no expirada)
               │                 │                  │
            ✅ SÍ            ❌ NO              ✅ SÍ
               │                 │                  │
               │                 │                  ▼
               │                 │       ┌─────────────────────┐
               │                 │       │ Retornar Caché      │
               │                 │       │ (2-3ms)             │
               │                 │       └─────────────────────┘
               │                 │
               │                 │ 3️⃣ Obtener clave nueva
               │                 ▼
               │    ┌──────────────────────────────────────────┐
               │    │   API Colppy                             │
               │    │   POST /lib/frontera2/service.php        │
               │    │   {                                      │
               │    │     auth: {...},                         │
               │    │     service: {                           │
               │    │       provision: "Usuario",              │
               │    │       operacion: "iniciar_sesion"        │
               │    │     }                                    │
               │    │   }                                      │
               │    └─────────────┬──────────────────────────┘
               │                  │
               │                  │ Valida credenciales MD5
               │                  ▼
               │    ┌──────────────────────────────┐
               │    │ Retorna claveSesion          │
               │    │ {                            │
               │    │   "exito": true,             │
               │    │   "datos": [{                │
               │    │     "claveSesion": "xyz..."  │
               │    │   }]                         │
               │    │ }                            │
               │    └─────────────┬────────────────┘
               │                  │
               │ 4️⃣ Guardar sesión en colppy_sessions
               │                  │
               ▼                  ▼
         ┌────────────────────────────────┐
         │   ColppyService                │
         │   Obtiene claveSesion          │
         └────────┬───────────────────────┘
                  │
                  │ 5️⃣ GET /api/colppy/clientes
                  │    + claveSesion
                  │
                  ▼
         ┌────────────────────────────────┐
         │   API Colppy                   │
         │   listar_cliente()             │
         │   (con claveSesion)            │
         └────────┬───────────────────────┘
                  │
                  │ Retorna lista de clientes
                  ▼
         ┌────────────────────────────────┐
         │   Backend Laravel              │
         │   Respuesta JSON               │
         └────────┬───────────────────────┘
                  │
                  │ 6️⃣ Flutter recibe
                  ▼
         ┌──────────────────────────────────┐
         │   ColppyService                  │
         │   fromJson() → ColppyCliente[]   │
         └────────┬─────────────────────────┘
                  │
                  │ 7️⃣ Actualiza UI
                  ▼
         ┌──────────────────────────────────┐
         │   Pantalla de Clientes           │
         │   - Muestra lista                │
         │   - Paginación habilitada        │
         │   - Búsqueda disponible          │
         └──────────────────────────────────┘
```

---

## Diagrama 2: Flujo de Caché de Sesiones

```
┌────────────────────────────────────────────────────────────────────┐
│             GESTIÓN DE SESIONES (Backend + Frontend)               │
└────────────────────────────────────────────────────────────────────┘

BACKEND (Laravel)
━━━━━━━━━━━━━━━━

    ┌──────────────────────────────────────┐
    │  Solicitud de Sesión                 │
    │  POST /api/colppy/session            │
    └────────┬─────────────────────────────┘
             │
             ▼
    ┌──────────────────────────────────────┐
    │  ColppyService                       │
    │  .obtenerClaveSesion()               │
    └────────┬─────────────────────────────┘
             │
    ┌────────┴────────┐
    │                 │
    ▼                 ▼
¿Existe en BD?    ¿Es válida?
    │                 │
    ├─✅ SÍ ──────────┤
    │     (ambas SÍ)  │
    │                 │
    │              ┌──▼──────────────┐
    │              │ Retornar sesión │
    │              │ existente        │
    │              └─────────────────┘
    │
    ├─❌ NO
    │
    ▼
┌─────────────────────────────────────┐
│  POST Colppy Login                  │
│  Obtener nueva claveSesion          │
└────────┬────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────┐
│  Guardar en tabla colppy_sessions   │
│  - usuario                          │
│  - clave_sesion                     │
│  - id_empresa                       │
│  - se_vence_en (now + 1 hora)       │
│  - activa = true                    │
└────────┬────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────┐
│  Limpieza de sesiones expiradas     │
│  DELETE ... WHERE se_vence_en < NOW │
└─────────────────────────────────────┘

FRONTEND (Flutter)
━━━━━━━━━━━━━━━━

┌──────────────────────────────────────┐
│  ColppyService                       │
│  .obtenerSesion(token)               │
└────────┬─────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────┐
│  ¿Hay sesión en SharedPreferences?   │
│  colppy_session_{user}_{empresa}     │
└────────┬─────────────────────────────┘
         │
    ┌────┴────┐
    ▼         ▼
   SÍ         NO
    │         │
    │         ▼
    │    ┌──────────────────────────┐
    │    │  POST /api/colppy/session│
    │    │  al Backend              │
    │    └────────┬─────────────────┘
    │             │
    │             ▼
    │    ┌──────────────────────────┐
    │    │  Guardar en caché local  │
    │    │  SharedPreferences       │
    │    │  (expira en 1 hora)      │
    │    └────────┬─────────────────┘
    │             │
    ▼             ▼
┌─────────────────────────────────────┐
│  Usar claveSesion para próximas     │
│  llamadas a Colppy                  │
│  (evita request adicional)          │
└─────────────────────────────────────┘

Tabla de Tiempos
━━━━━━━━━━━━━━
Backend:
│ Primera solicitud    : +500ms   (incluye request a Colppy)
│ Solicitud con caché  : +50ms    (lectura de BD)

Frontend:
│ Primera solicitud    : +600ms   (HTTP + caché)
│ Solicitud con caché  : +2ms     (lectura local)
```

---

## Diagrama 3: Estructura de Datos

```
┌─────────────────────────────────────────────────────────────┐
│                   MODELOS DE DATOS                          │
└─────────────────────────────────────────────────────────────┘

DATABASE: colppy_sessions
━━━━━━━━━━━━━━━━━━━━━━━━━
┌────┬────────────┬──────────────┬──────────┬─────────────┬───────┐
│ id │  usuario   │ clave_sesion │ id_emps  │ se_vence_en │activa │
├────┼────────────┼──────────────┼──────────┼─────────────┼───────┤
│ 1  │user@col.c  │ b5a97564...  │   98     │ 2026-02-16  │  1    │
│ 2  │user2@col.c │ a3f3bdef...  │   98     │ 2026-02-15  │  0    │
└────┴────────────┴──────────────┴──────────┴─────────────┴───────┘

FLUTTER: SharedPreferences
━━━━━━━━━━━━━━━━━━━━━━━━━━
Key: colppy_session_user@col.c_98
Val: {
  "claveSesion": "b5a97564...",
  "usuario": "user@col.c",
  "idEmpresa": "98"
}

Key: colppy_session_expiry_colppy_session_user@col.c_98
Val: 1708098432100 (timestamp ms)

MODELO: ColppyCliente
━━━━━━━━━━━━━━━━━━━
ColppyCliente {
  String idCliente         // ID único en Colppy
  String razonSocial       // Nombre oficial
  String? nombreFantasia   // Nombre comercial
  String? cuit             // CUIT/DNI
  bool activo              // Está activo?
  Map<String, dynamic> datosAdicionales  // Otros campos
}

MODELO: ColppyClientesResponse
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ColppyClientesResponse {
  List<ColppyCliente> clientes
  int total               // Total de registros
  int start               // Índice de inicio
  int limit               // Límite por página
}
```

---

## Diagrama 4: Flujo de Validación de Credenciales

```
┌────────────────────────────────────────────────────────┐
│        VALIDACIÓN DE CREDENCIALES (MD5)                │
└────────────────────────────────────────────────────────┘

Contraseña Original: "Alma2024"

         ▼

    md5()  ← Función de hash

         ▼

Hash MD5: "5f4dcc3b5aa765d61d8327deb882cf99"

         │
         ├─────────────────────────────────┬─────────────┐
         │                                 │             │
         ▼                                 ▼             ▼
    
    BACKEND              FRONTEND           COLPPY
    Laravel              Flutter            API
    
    ▼                                       ▼
  ┌──────────────────┐            ┌──────────────────┐
  │ Recibe:          │            │ Recibe:          │
  │ password: "xyz"  │            │ password: "xyz"  │
  │                  │            │                  │
  │ md5(password) ?= │            │ md5(password) ?= │
  │ config.md5       │            │ config.md5       │
  │                  │            │                  │
  │ ✅ Válida        │            │ ✅ Válida        │
  └──────────────────┘            └──────────────────┘

SEGURIDAD:
━━━━━━━━
❌ Nunca enviar contraseña en texto plano
✅ Siempre enviar en MD5 (o mejor, SHA256/bcrypt)
✅ Usar HTTPS en producción
✅ Guardar en .env, no en código
```

---

## Diagrama 5: Casos de Error

```
┌─────────────────────────────────────────────────────────────┐
│              MANEJO DE ERRORES Y RECUPERACIÓN               │
└─────────────────────────────────────────────────────────────┘

Error 1: Credenciales Inválidas
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  ┌─────────────────────────────┐
  │ Usuario ingresa contraseña  │
  │ incorrecta en login         │
  └────────────┬────────────────┘
               │
               ▼
      ┌────────────────────┐
      │ Colppy rechaza     │
      │ credenciales       │
      └────────┬───────────┘
               │
               ▼
      ┌─────────────────────────────┐
      │ Backend retorna:            │
      │ {                           │
      │   success: false,           │
      │   message: "Credenciales    │
      │             inválidas"      │
      │ }                           │
      └────────┬────────────────────┘
               │
               ▼
      Flutter muestra:
      "⚠️ Usuario o contraseña inválidos"
      
      Usuario intenta de nuevo ↺

Error 2: Sesión Expirada
━━━━━━━━━━━━━━━━━━━━━━━
  ┌────────────────────────────────┐
  │ Sesión en BD expiró            │
  │ (se_vence_en < NOW)            │
  └────────┬───────────────────────┘
           │
           ▼
  ┌────────────────────────────────┐
  │ SELECT ... obtenerValida()     │
  │ Retorna NULL                   │
  └────────┬───────────────────────┘
           │
           ▼
  ┌────────────────────────────────┐
  │ Solicitar nueva sesión a       │
  │ Colppy (transparente para app) │
  └────────┬───────────────────────┘
           │
           ▼
      Usuario NO notificado
      Funciona automáticamente ✅

Error 3: Timeout en la Red
━━━━━━━━━━━━━━━━━━━━━━━━━
  ┌──────────────────────────────┐
  │ Timeout en HTTP              │
  │ (> 30 segundos)              │
  └────────┬─────────────────────┘
           │
           ▼
  ┌──────────────────────────────┐
  │ TimeoutException lanzado     │
  └────────┬─────────────────────┘
           │
           ▼
      Flutter muestra:
      "⏱️ Tiempo de espera agotado"
      [Reintentar]

Error 4: Conexión a Internet Perdida
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  ┌──────────────────────────────┐
  │ SocketException              │
  └────────┬─────────────────────┘
           │
           ▼
      Flutter muestra:
      "📡 Sin conexión a internet"
      [Reintentar cuando haya conexión]

Tabla de Códigos de Estado HTTP
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
200 ✅ OK - Éxito
400 ❌ Bad Request - Parámetros inválidos
401 ❌ Unauthorized - Token inválido/expirado
403 ❌ Forbidden - Sin permisos
404 ❌ Not Found - Recurso no existe
500 ❌ Server Error - Error interno
```

---

**Última actualización**: 16 de febrero de 2026
