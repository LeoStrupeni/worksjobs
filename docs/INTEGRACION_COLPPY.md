# Integración API Colppy - Guía Completa

## 📋 Resumen General

Este documento explica cómo funciona la integración con la API de Colppy para obtener clientes y sus datos.

### Restricciones Actuales
- ✅ **Lectura (GET)**: Obtener clientes y sus datos desde Colppy
- ❌ **Escritura (POST/PUT/DELETE)**: Dar de alta, editar o eliminar clientes está **BLOQUEADO**
- ✅ **Domicilios**: Se manejan completamente desde nuestro sistema

---

## 🔧 Arquitectura Técnica

### Backend (Laravel)

```
┌─────────────────────────┐
│   Configuración (BD)    │
│  - url_api_login        │
│  - user_dev_api         │
│  - pass_dev_api         │
│  - user_api             │
│  - pass_api             │
│  - id_empresa_api       │
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────────────────┐
│      ColppyService (Servicio)       │
│  - obtenerClaveSesion()             │
│  - listarClientes()                 │
│  - obtenerCliente()                 │
│  - hacerLlamada()                   │
│  - invalidarSesion()                │
└────────────┬──────────────────────┘
             │
             ▼
┌─────────────────────────────────────┐
│  ApiColppyController (Controlador)  │
│  - POST /api/colppy/session         │
│  - GET /api/colppy/clientes         │
│  - GET /api/colppy/clientes/{id}    │
│  - POST /api/colppy/call            │
│  - POST /api/colppy/invalidate-...  │
└────────────┬──────────────────────┘
             │
             ▼
        ┌─────────────┐
        │   BD Local  │
        │  - Sesiones │
        │  - Dominios │
        │  - etc.     │
        └─────────────┘
```

### Mobile App (Flutter)

```
┌────────────────────────────────────────┐
│   ColppyService (Servicio)             │
│  - obtenerSesion()                     │
│  - listarClientes()                    │
│  - obtenerCliente()                    │
│  - hacerLlamada()                      │
│  - invalidarSesion()                   │
│                                        │
│  + Caché local con SharedPreferences   │
└────────────┬─────────────────────────┘
             │
             ▼
      ┌────────────┐
      │   Backend  │
      │   Laravel  │
      │  /api/...  │
      └────────────┘
```

---

## 🔐 Flujo de Autenticación Colppy

### 1. Obtener Clave de Sesión

**Paso 1**: El usuario inicia sesión en la app (autenticación local)
```
    App/Web  →  Backend  →  Genera token Sanctum
```

**Paso 2**: Cuando se necesita acceder a clientes de Colppy:
```
    App/Web  →  POST /api/colppy/session  →  ColppyService
                                              │
                                              ├─ Verificar sesión en caché (BD)
                                              │
                                              ├─ Si no existe o expiró:
                                              │  └─ POST https://login.colppy.com/...
                                              │     ├─ Enviar credenciales (MD5)
                                              │     └─ Recibir claveSesion
                                              │
                                              ├─ Guardar sesión en BD
                                              │
                                              └─ Retornar claveSesion
```

### 2. Usar la Clave de Sesión

```
ColppyService.hacerLlamada(
  provision: "Cliente",
  operacion: "listar_cliente",
  parameters: {
    sesion: {
      usuario: "user@colppy.com",
      claveSesion: "xxx" ← Obtenida en paso anterior
    },
    idEmpresa: "98",
    start: 0,
    limit: 100
  }
)
```

---

## 📚 Estructura de Payloads

### Iniciar Sesión

**Request** (Colppy Login):
```json
{
  "auth": {
    "usuario": "user@colppy.com",
    "password": "hash_md5_contraseña"
  },
  "service": {
    "provision": "Usuario",
    "operacion": "iniciar_sesion"
  },
  "parameters": {
    "usuario": "user@colppy.com",
    "password": "hash_md5_contraseña"
  }
}
```

**Response** (Colppy):
```json
{
  "exito": true,
  "datos": [
    {
      "claveSesion": "b5a97564ad59e624a6ba545ecd3ca112"
    }
  ]
}
```

### Listar Clientes

**Request** (Backend):
```json
{
  "auth": {
    "usuario": "user@colppy.com",
    "password": "hash_md5_contraseña"
  },
  "service": {
    "provision": "Cliente",
    "operacion": "listar_cliente"
  },
  "parameters": {
    "sesion": {
      "usuario": "user@colppy.com",
      "claveSesion": "b5a97564ad59e624a6ba545ecd3ca112"
    },
    "idEmpresa": "98",
    "start": 0,
    "limit": 100,
    "filter": [
      {
        "field": "Activo",
        "op": "=",
        "value": "1"
      },
      {
        "field": "CUIT",
        "op": "=",
        "value": "30-69224359-1"
      }
    ],
    "order": [
      {
        "field": "NombreFantasia",
        "dir": "asc"
      }
    ]
  }
}
```

**Response** (Colppy):
```json
{
  "exito": true,
  "datos": [
    {
      "idCliente": "1",
      "RazonSocial": "Empresa A",
      "NombreFantasia": "Cliente A",
      "CUIT": "30-69224359-1",
      "Activo": "1"
    },
    {
      "idCliente": "2",
      "RazonSocial": "Empresa B",
      "NombreFantasia": "Cliente B",
      "CUIT": "30-69224360-2",
      "Activo": "1"
    }
  ]
}
```

---

## 📱 Uso en Flutter

### 1. Obtener Sesión

```dart
// En el servicio o proveedor
final resultado = await ColppyService.obtenerSesion(authToken);

if (resultado['success']) {
  final claveSesion = resultado['claveSesion'];
  final usuario = resultado['usuario'];
  final idEmpresa = resultado['idEmpresa'];
  // Usar estos datos...
}
```

### 2. Listar Clientes

```dart
// Con filtros opcionales
final resultado = await ColppyService.listarClientes(
  authToken,
  start: 0,
  limit: 50,
  filters: [
    {
      'field': 'Activo',
      'op': '=',
      'value': '1'
    }
  ],
  order: [
    {
      'field': 'NombreFantasia',
      'dir': 'asc'
    }
  ],
);

if (resultado['success']) {
  final clientesResponse = ColppyClientesResponse.fromJson(resultado['data']);
  print('Total clientes: ${clientesResponse.total}');
  
  for (final cliente in clientesResponse.clientes) {
    print('${cliente.nombreMostrar} - ${cliente.cuit}');
  }
}
```

### 3. Obtener Cliente Individual

```dart
final resultado = await ColppyService.obtenerCliente(authToken, '123');

if (resultado['success']) {
  final cliente = ColppyCliente.fromJson(resultado['data']);
  print(cliente.nombreMostrar);
}
```

### 4. Llamada Genérica

```dart
final resultado = await ColppyService.hacerLlamada(
  authToken,
  provision: 'OtraProvision',
  operacion: 'algunaOperacion',
  parameters: {
    'param1': 'valor1',
    'param2': 'valor2'
  },
);
```

---

## 🖥️ Uso en Laravel (Backend)

### 1. Obtener Clave de Sesión

```php
use App\Services\ColppyService;

$colppy = new ColppyService();
$resultado = $colppy->obtenerClaveSesion();

if ($resultado['success']) {
    $claveSesion = $resultado['claveSesion'];
    // Usar la clave...
} else {
    $error = $resultado['mensaje'];
}
```

### 2. Listar Clientes

```php
$resultado = $colppy->listarClientes(
    start: 0,
    limit: 100,
    filtros: [
        [
            'field' => 'Activo',
            'op' => '=',
            'value' => '1'
        ]
    ],
    orden: [
        [
            'field' => 'NombreFantasia',
            'dir' => 'asc'
        ]
    ]
);

if ($resultado['success']) {
    $clientes = $resultado['datos'];
} else {
    Log::error('Error listando clientes: ' . $resultado['mensaje']);
}
```

### 3. Hacer Llamada Genérica

```php
$resultado = $colppy->hacerLlamada(
    provision: 'Cliente',
    operacion: 'listar_cliente',
    parameters: [
        'idEmpresa' => '98',
        'start' => 0,
        'limit' => 50
    ]
);
```

---

## 🔄 Caché de Sesiones

### Backend (Laravel)

Las sesiones se guardan en la tabla `colppy_sessions`:

```sql
CREATE TABLE colppy_sessions (
    id BIGINT PRIMARY KEY,
    usuario VARCHAR(255),
    clave_sesion TEXT,
    id_empresa VARCHAR(255),
    se_vence_en DATETIME,
    activa BOOLEAN,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_usuario_empresa (usuario, id_empresa),
    INDEX idx_vencimiento (se_vence_en)
);
```

**Ventajas**:
- Evita llamadas repetidas a Colppy
- Mayor velocidad
- Menos carga en la API externa

**Tiempo de caché**: Por defecto 1 hora (ajustable en `ColppyService::obtenerClaveSesion()`)

### App (Flutter)

Las sesiones se guardan en `SharedPreferences`:

**Estructura**:
```
colppy_session_{usuario}_{idEmpresa}: {
    "claveSesion": "xxx",
    "usuario": "user@colppy.com",
    "idEmpresa": "98"
}
colppy_session_expiry_{usuario}_{idEmpresa}: timestamp_expiración
```

---

## 🚀 Endpoints de la API

### Base URL
```
https://tecnicos.strupeni.com.ar/api
(o http://localhost/panel/api en desarrollo)
```

### Endpoints

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/colppy/session` | Obtener/renovar sesión |
| `GET` | `/colppy/clientes` | Listar clientes |
| `GET` | `/colppy/clientes/{idCliente}` | Obtener cliente |
| `POST` | `/colppy/call` | Llamada genérica |
| `POST` | `/colppy/invalidate-session` | Invalidar sesión |

**Todos requieren**: `Authorization: Bearer {token}`

---

## ⚠️ Consideraciones Importantes

### 1. Credenciales de Colppy
- Se almacenan en la tabla `configs` de Laravel
- Usuario y contraseña se envían en formato **MD5**
- **No almacenar credenciales en el código**

### 2. Tiempo de Expiración de Sesión
- Actualmente configurado en **1 hora**
- ⚠️ **Necesita confirmación**: ¿Cuál es el tiempo real de expiración en Colppy?
- Ajustar en: `panel/app/Services/ColppyService.php` línea ~73

### 3. Rate Limiting
- Considerar implementar rate limiting en los endpoints
- Colppy puede tener límites de llamadas

### 4. Manejo de Errores
- Todos los servicios retornan estructura uniforme: `['success' => bool, 'data' => [...], 'message' => '...']`
- Logging completo en `/storage/logs/` para debugging

### 5. Filtros de Colppy
- Los filtros siguen estructura específica de Colppy
- Campos disponibles: consultae documentación oficial de Colppy

---

## 📝 Ejemplos de Uso Completo

### Flutter - Cargar Lista de Clientes

```dart
class ClientesScreen extends StatefulWidget {
  @override
  State<ClientesScreen> createState() => _ClientesScreenState();
}

class _ClientesScreenState extends State<ClientesScreen> {
  List<ColppyCliente> clientes = [];
  bool cargando = false;
  String error = '';

  @override
  void initState() {
    super.initState();
    _cargarClientes();
  }

  Future<void> _cargarClientes() async {
    setState(() {
      cargando = true;
      error = '';
    });

    // Obtener token del servicio de autenticación
    final token = await AuthService.getToken();

    final resultado = await ColppyService.listarClientes(
      token!,
      start: 0,
      limit: 100,
      filters: [
        {'field': 'Activo', 'op': '=', 'value': '1'}
      ],
      order: [
        {'field': 'NombreFantasia', 'dir': 'asc'}
      ],
    );

    setState(() {
      cargando = false;
      if (resultado['success']) {
        final response = ColppyClientesResponse.fromJson(resultado['data']);
        clientes = response.clientes;
      } else {
        error = resultado['message'] ?? 'Error desconocido';
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    if (cargando) {
      return const Center(child: CircularProgressIndicator());
    }

    if (error.isNotEmpty) {
      return Center(child: Text('Error: $error'));
    }

    return ListView.builder(
      itemCount: clientes.length,
      itemBuilder: (context, index) {
        final cliente = clientes[index];
        return ListTile(
          title: Text(cliente.nombreMostrar),
          subtitle: Text(cliente.cuit ?? 'Sin CUIT'),
          trailing: Icon(
            cliente.activo ? Icons.check_circle : Icons.cancel,
            color: cliente.activo ? Colors.green : Colors.red,
          ),
        );
      },
    );
  }
}
```

### Laravel - API Endpoint Personalizado

```php
// routes/api.php
Route::middleware('auth:sanctum')->get('/mis-clientes-colppy', function (Request $request) {
    $colppy = new ColppyService();
    
    // Obtener clientes
    $resultado = $colppy->listarClientes(
        start: 0,
        limit: 100,
        filtros: [
            ['field' => 'Activo', 'op' => '=', 'value' => '1']
        ]
    );

    if ($resultado['success']) {
        return response()->json([
            'success' => true,
            'clientes' => $resultado['datos']
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => $resultado['mensaje']
    ], 400);
});
```

---

## 🐛 Troubleshooting

### Error: "Configuración de Colppy incompleta"
**Solución**: Verificar que todos los campos estén configurados en: `/cms/api-config`

### Error: "Error de conexión con la API de Colppy"
**Solución**: 
- Verificar URL de Colppy en configuración
- Verificar credenciales
- Verificar conectividad

### Sesión expira muy rápido
**Solución**: Aumentar tiempo de caché en `ColppyService::obtenerClaveSesion()`

### SharedPreferences lleno en Flutter
**Solución**: Implementar limpieza periódica de sesiones expiradas

---

## 📞 Próximos Pasos

1. ✅ **Completado**: Integración básica de Colppy
2. ⏳ **Pendiente**: Implementación de domicilios
3. ⏳ **Pendiente**: Búsqueda avanzada de clientes
4. ⏳ **Pendiente**: Sincronización de datos

---

**Última actualización**: 16 de febrero de 2026
