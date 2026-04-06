# Sistema de Debugging - App Flutter Técnicos

**Fecha**: 23/03/2026  
**Implementado**: ✅ Completo  
**Ubicación**: `technician_app/lib/utils/` y `technician_app/lib/screens/`

---

## 📋 Índice

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Componentes Implementados](#componentes-implementados)
3. [Cómo Usar el Sistema de Debug](#cómo-usar-el-sistema-de-debug)
4. [Acceder a la Pantalla de Debug](#acceder-a-la-pantalla-de-debug)
5. [Características de la Pantalla de Debug](#características-de-la-pantalla-de-debug)
6. [Códigos de Error Específicos](#códigos-de-error-específicos)
7. [Sistema de Retry Automático](#sistema-de-retry-automático)
8. [Integración en tu Código](#integración-en-tu-código)
9. [Troubleshooting](#troubleshooting)

---

## Resumen Ejecutivo

Se implementó un **sistema completo de debugging** para la app móvil de técnicos que permite:

✅ **Logging local** persistente (últimos 100 logs guardados en el dispositivo)  
✅ **Pantalla de debug oculta** (acceso con gesto secreto)  
✅ **Códigos de error específicos** (NO_TOKEN, TIMEOUT, NO_INTERNET, etc.)  
✅ **Auto-retry automático** en peticiones HTTP (hasta 2 reintentos)  
✅ **Exportar logs** (compartir por WhatsApp, email, etc.)  
✅ **Herramientas de diagnóstico** (probar endpoints, forzar logout, etc.)

**Objetivo**: Facilitar el soporte remoto sin necesidad de acceso físico al dispositivo del técnico.

---

## Componentes Implementados

### 1. **DebugLogger** (`lib/utils/debug_logger.dart`)

Sistema de logging local que guarda los últimos 100 logs en `SharedPreferences`.

**Características**:
- ✅ Singleton (acceso global: `DebugLogger.instance`)
- ✅ 4 niveles de log: `info`, `warning`, `error`, `success`
- ✅ Categorización de logs (NETWORK, AUTH, JOBS, etc.)
- ✅ Persistencia entre sesiones
- ✅ Exportación como texto
- ✅ Filtros por nivel y categoría

**Uso básico**:
```dart
await DebugLogger.instance.info('Mensaje informativo');
await DebugLogger.instance.warning('Advertencia');
await DebugLogger.instance.error('Error crítico');
await DebugLogger.instance.success('Operación exitosa');

// Con categoría y datos adicionales
await DebugLogger.instance.network(
  'GET /api/jobs/today',
  data: {'status': 200, 'time': '150ms'},
);
```

---

### 2. **NetworkHelper** (`lib/utils/network_helper.dart`)

Utilidad para peticiones HTTP con retry automático y códigos de error específicos.

**Características**:
- ✅ Retry automático (hasta 2 reintentos por defecto)
- ✅ Exponential backoff (500ms, 1s, 2s...)
- ✅ Códigos de error específicos
- ✅ Logging automático de todas las peticiones
- ✅ Timeout configurable (30s por defecto)

**Uso básico**:
```dart
final result = await NetworkHelper.getWithRetry(
  Uri.parse('https://api.strupeni.com/jobs/today'),
  headers: {'Authorization': 'Bearer $token'},
  maxRetries: 2,
  logCategory: 'JOBS',
);

if (result.success) {
  final response = result.data as http.Response;
  // Procesar respuesta...
} else {
  print('Error: ${result.errorCode} - ${result.userMessage}');
}
```

---

### 3. **DebugScreen** (`lib/screens/debug_screen.dart`)

Pantalla de debug completa con 3 pestañas.

**Pestañas**:
1. **Logs**: Ver todos los logs, filtrar, exportar, limpiar
2. **Sistema**: Info del usuario, token, API, dispositivo
3. **Tools**: Probar endpoints, forzar logout, generar logs de prueba

---

### 4. **JobService Mejorado** (`lib/services/job_service.dart`)

El `JobService` fue actualizado para usar:
- ✅ `DebugLogger` para logging automático
- ✅ `NetworkHelper` para peticiones con retry
- ✅ Códigos de error específicos
- ✅ Mensajes user-friendly

**Mejoras implementadas en**:
- `getTodayJobs()`
- `getUpcomingJobs()`

---

### 5. **Gesto Secreto en HomeScreen** (`lib/screens/home_screen.dart`)

Gesto secreto para abrir la pantalla de debug:

**Cómo acceder**: Tap **5 veces** en 3 segundos en el **ícono del rayo** (esquina superior izquierda)

**Feedback**:
- Muestra contador de taps (1/5, 2/5, etc.)
- Al llegar a 5, abre la pantalla de debug
- Se resetea si pasan más de 3 segundos

---

## Cómo Usar el Sistema de Debug

### Para Desarrolladores

1. **Inicializar el logger** (ya está en `home_screen.dart`):
   ```dart
   @override
   void initState() {
     super.initState();
     DebugLogger.instance.initialize();
   }
   ```

2. **Agregar logs en tu código**:
   ```dart
   await DebugLogger.instance.info('Usuario logueado', category: 'AUTH');
   await DebugLogger.instance.network('GET /api/jobs → HTTP 200');
   await DebugLogger.instance.error('Token expirado', category: 'AUTH');
   ```

3. **Usar NetworkHelper para peticiones HTTP**:
   ```dart
   final result = await NetworkHelper.getWithRetry(
     Uri.parse(url),
     headers: headers,
     maxRetries: 2,
   );
   ```

### Para Técnicos (Soporte Remoto)

1. **Acceder a debug**: Tap 5 veces en el logo (rayo)
2. **Ver logs**: Pestaña "Logs" → Filtrar por nivel o categoría
3. **Ver info del sistema**: Pestaña "Sistema" → Token, usuario, API
4. **Probar endpoints**: Pestaña "Tools" → "Probar Health Check", etc.
5. **Exportar logs**: Botón "Exportar" → Compartir por WhatsApp/email

---

## Acceder a la Pantalla de Debug

### Método 1: Gesto Secreto (RECOMENDADO)

1. Abrir la app (pantalla principal)
2. **Tap 5 veces rápido** en el **ícono del rayo** (esquina superior izquierda)
3. Verás un contador: "Debug: Tap 1/5", "Debug: Tap 2/5", etc.
4. Al llegar a 5 taps, se abre la pantalla de debug

**Nota**: Tienes **3 segundos** entre tap y tap, si no se resetea el contador.

### Método 2: Programático (Solo para desarrollo)

```dart
Navigator.push(
  context,
  MaterialPageRoute(
    builder: (context) => const DebugScreen(),
  ),
);
```

---

## Características de la Pantalla de Debug

### Pestaña 1: Logs

**Funciones**:
- ✅ Ver todos los logs en orden cronológico (más reciente primero)
- ✅ Estadísticas: Total, Info, Warning, Error, Success
- ✅ Filtros por nivel: Todos, Info, Warning, Error, Success
- ✅ Filtro por categoría: NETWORK, AUTH, JOBS, etc.
- ✅ Expandir log para ver datos adicionales
- ✅ **Exportar logs**: Genera archivo `.txt` y lo comparte
- ✅ **Copiar logs**: Copia al portapapeles
- ✅ **Limpiar logs**: Eliminar todos los logs

**Formato de log**:
```
✅ 15:32:45 [JOBS] 3 citas de hoy obtenidas
   Data: {count: 3}
```

### Pestaña 2: Sistema

**Información mostrada**:
- **Usuario**: Nombre, email, ID, estado de autenticación
- **Token**: Estado, primeros 20 caracteres, tamaño
- **API**: Base URL, endpoints configurados
- **Dispositivo**: Plataforma, versión del OS
- **Storage**: Cantidad de logs almacenados

**Utilidad**: Verificar si el problema es de token, configuración API incorrecta, etc.

### Pestaña 3: Tools

**Herramientas disponibles**:
- ✅ **Probar Health Check**: Verifica que la autenticación funcione
- ✅ **Probar /jobs/today**: Llama al endpoint de citas de hoy
- ✅ **Probar /jobs/upcoming**: Llama al endpoint de próximas citas
- ✅ **Forzar Logout**: Cierra sesión y vuelve al login
- ✅ **Generar Logs de Prueba**: Crea logs de ejemplo para testear

**Utilidad**: Probar endpoints manualmente para diagnosticar problemas.

---

## Códigos de Error Específicos

El sistema usa códigos de error estandarizados para facilitar el diagnóstico.

### Códigos Implementados

| Código | Descripción | Mensaje User-Friendly |
|--------|-------------|----------------------|
| `NO_TOKEN` | Usuario no autenticado | "No has iniciado sesión. Por favor, inicia sesión nuevamente." |
| `TOKEN_EXPIRED` | Token expirado | "Tu sesión ha expirado. Por favor, inicia sesión nuevamente." |
| `NO_INTERNET` | Sin conexión a internet | "Sin conexión a internet. Verifica tu conexión e intenta de nuevo." |
| `TIMEOUT` | Petición tardó demasiado | "La petición tardó demasiado. Verifica tu conexión e intenta de nuevo." |
| `SERVER_ERROR` | Error en el servidor (HTTP 500+) | "Error en el servidor. Intenta de nuevo más tarde." |
| `NOT_FOUND` | Recurso no encontrado (HTTP 404) | "No se encontró el recurso solicitado." |
| `FORBIDDEN` | Sin permisos (HTTP 403) | "No tienes permisos para realizar esta acción." |
| `UNAUTHORIZED` | No autorizado (HTTP 401) | "No autorizado. Por favor, inicia sesión nuevamente." |
| `BAD_REQUEST` | Petición inválida (HTTP 400) | "Petición inválida. Verifica los datos e intenta de nuevo." |
| `UNKNOWN` | Error desconocido | "Error desconocido. Intenta de nuevo." |

### Uso en el Código

**Retornar error con código**:
```dart
return {
  'success': false,
  'errorCode': ApiErrorCode.NO_INTERNET,
  'message': ApiErrorCode.getMessage(ApiErrorCode.NO_INTERNET),
};
```

**Verificar error específico**:
```dart
final result = await jobService.getTodayJobs();

if (!result['success']) {
  if (result['errorCode'] == ApiErrorCode.NO_TOKEN) {
    // Redirigir al login
  } else if (result['errorCode'] == ApiErrorCode.NO_INTERNET) {
    // Mostrar mensaje de sin conexión
  } else {
    // Error genérico
  }
}
```

---

## Sistema de Retry Automático

El `NetworkHelper` reintenta automáticamente las peticiones fallidas.

### Configuración por Defecto

- **Máximo de reintentos**: 2 (total 3 intentos)
- **Timeout**: 30 segundos por intento
- **Delay inicial**: 500ms
- **Exponential backoff**: Se duplica en cada reintento (500ms → 1s → 2s)

### ¿Cuándo se reintenta?

✅ **SÍ se reintenta**:
- Timeout (sin respuesta del servidor)
- Sin conexión a internet (SocketException)
- Error del servidor (HTTP 500, 502, 503, 504)
- Error desconocido

❌ **NO se reintenta** (falla inmediatamente):
- Bad Request (HTTP 400)
- Unauthorized (HTTP 401)
- Forbidden (HTTP 403)
- Not Found (HTTP 404)

**Razón**: Estos errores no se solucionan reintentando.

### Ejemplo de Flujo con Retry

```
Intento 1: GET /api/jobs/today
   → Timeout (30s)
   → Log: "⚠️ GET /jobs/today failed with TIMEOUT, retrying in 500ms..."
   → Espera 500ms

Intento 2: GET /api/jobs/today
   → Sin conexión (SocketException)
   → Log: "⚠️ GET /jobs/today failed with NO_INTERNET, retrying in 1000ms..."
   → Espera 1s

Intento 3: GET /api/jobs/today
   → HTTP 200 ✅
   → Log: "✅ GET /jobs/today successful (attempt 3)"
   → Retorna respuesta
```

### Configurar Retry Personalizado

```dart
final result = await NetworkHelper.getWithRetry(
  Uri.parse(url),
  headers: headers,
  maxRetries: 3,              // Más reintentos
  timeout: Duration(seconds: 60), // Más tiempo por intento
  logCategory: 'CUSTOM',
);
```

---

## Integración en tu Código

### Migrar de http a NetworkHelper

**ANTES** (sin retry):
```dart
final response = await http.get(
  Uri.parse(url),
  headers: headers,
);

if (response.statusCode == 200) {
  // Procesar...
} else {
  return {'success': false, 'message': 'Error'};
}
```

**DESPUÉS** (con retry y logging):
```dart
final result = await NetworkHelper.getWithRetry(
  Uri.parse(url),
  headers: headers,
  maxRetries: 2,
  logCategory: 'JOBS',
);

if (result.success) {
  final response = result.data as http.Response;
  final data = jsonDecode(response.body);
  // Procesar...
} else {
  return {
    'success': false,
    'errorCode': result.errorCode,
    'message': result.userMessage,
  };
}
```

### Agregar Logging a Métodos Existentes

**Patrón recomendado**:
```dart
Future<Map<String, dynamic>> miMetodo() async {
  await DebugLogger.instance.info('📝 Iniciando miMetodo...', category: 'MI_CATEGORIA');
  
  try {
    // Lógica...
    
    await DebugLogger.instance.success('✅ miMetodo completado', category: 'MI_CATEGORIA');
    return {'success': true, ...};
  } catch (e, stackTrace) {
    await DebugLogger.instance.error(
      '❌ Error en miMetodo: $e',
      category: 'MI_CATEGORIA',
      data: {'error': e.toString(), 'stackTrace': stackTrace.toString()},
    );
    return {'success': false, 'message': e.toString()};
  }
}
```

---

## Troubleshooting

### Problema 1: Los logs no se guardan

**Causa**: El logger no fue inicializado.

**Solución**:
```dart
@override
void initState() {
  super.initState();
  DebugLogger.instance.initialize(); // ✅ Agregar esto
}
```

---

### Problema 2: No puedo abrir la pantalla de debug

**Causa**: Tap muy lento o en lugar equivocado.

**Solución**:
1. Tap en el **ícono del rayo** (esquina superior izquierda)
2. Tap **5 veces** en menos de 3 segundos
3. Verás un contador: "Debug: Tap 1/5", "Debug: Tap 2/5", etc.

---

### Problema 3: Los logs se pierden al reiniciar la app

**Causa**: No debería pasar (se guardan en SharedPreferences).

**Solución**:
1. Verificar que `await DebugLogger.instance.initialize()` se llame al iniciar
2. Los logs deberían persistir

Si se siguen perdiendo:
```dart
// Forzar guardado manual si es necesario
await DebugLogger.instance._saveLogs();
```

---

### Problema 4: El retry no funciona

**Causa**: El error es de tipo "no reintentable" (401, 403, 404, 400).

**Solución**: Estos errores **NO se reintentan** porque no se solucionan reintentando.

---

### Problema 5: Timeout muy corto

**Causa**: Conexión lenta + timeout de 30s.

**Solución**:
```dart
final result = await NetworkHelper.getWithRetry(
  Uri.parse(url),
  headers: headers,
  timeout: Duration(seconds: 60), // ✅ Aumentar a 60s
  maxRetries: 2,
);
```

---

## Ejemplos de Uso Completos

### Ejemplo 1: Agregar Logging a un Servicio

```dart
class MyService {
  Future<Map<String, dynamic>> fetchData() async {
    await DebugLogger.instance.info('🔄 Obteniendo datos...', category: 'MY_SERVICE');
    
    try {
      final token = await AuthService().getToken();
      
      if (token == null) {
        await DebugLogger.instance.error('❌ No hay token', category: 'MY_SERVICE');
        return {
          'success': false,
          'errorCode': ApiErrorCode.NO_TOKEN,
          'message': ApiErrorCode.getMessage(ApiErrorCode.NO_TOKEN),
        };
      }
      
      final result = await NetworkHelper.getWithRetry(
        Uri.parse('https://api.strupeni.com/data'),
        headers: {'Authorization': 'Bearer $token'},
        maxRetries: 2,
        logCategory: 'MY_SERVICE',
      );
      
      if (result.success) {
        await DebugLogger.instance.success('✅ Datos obtenidos', category: 'MY_SERVICE');
        return {'success': true, 'data': result.data};
      }
      
      return {
        'success': false,
        'errorCode': result.errorCode,
        'message': result.userMessage,
      };
    } catch (e, stackTrace) {
      await DebugLogger.instance.error(
        '❌ Exception: $e',
        category: 'MY_SERVICE',
        data: {'error': e.toString(), 'stackTrace': stackTrace.toString()},
      );
      return {'success': false, 'errorCode': ApiErrorCode.UNKNOWN};
    }
  }
}
```

### Ejemplo 2: Mostrar Errores al Usuario

```dart
final result = await myService.fetchData();

if (!result['success']) {
  final errorCode = result['errorCode'];
  final message = result['message'] ?? 'Error desconocido';
  
  // Mostrar mensaje específico según el código
  if (errorCode == ApiErrorCode.NO_TOKEN || errorCode == ApiErrorCode.UNAUTHORIZED) {
    // Redirigir al login
    Navigator.pushReplacement(context, MaterialPageRoute(
      builder: (context) => LoginScreen(),
    ));
  } else if (errorCode == ApiErrorCode.NO_INTERNET) {
    // Mostrar mensaje de sin conexión
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Sin Conexión'),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.pop(context);
              myService.fetchData(); // Reintentar
            },
            child: Text('Reintentar'),
          ),
        ],
      ),
    );
  } else {
    // Error genérico
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message)),
    );
  }
} else {
  // Éxito
  final data = result['data'];
  // Procesar...
}
```

---

## Resumen de Archivos Creados/Modificados

### Archivos NUEVOS ✨

1. `lib/utils/debug_logger.dart` - Sistema de logging
2. `lib/utils/network_helper.dart` - Retry automático + códigos de error
3. `lib/screens/debug_screen.dart` - Pantalla de debug

### Archivos MODIFICADOS 🔧

1. `lib/services/job_service.dart` - Integrado con logger y network helper
2. `lib/screens/home_screen.dart` - Gesto secreto para abrir debug
3. `pubspec.yaml` - Comentado sobre versionado (ver `VERSIONADO_FLUTTER.md`)

---

## Próximos Pasos (Opcionales)

### Mejoras Futuras

1. **Push notifications de errores**: Enviar push cuando se detecte un error crítico
2. **Envío automático de logs**: Subir logs al servidor cuando hay errores
3. **Crash reporting**: Integrar con Firebase Crashlytics
4. **Métricas de performance**: Medir tiempo de respuesta de endpoints
5. **Modo offline**: Cachear respuestas y trabajar sin conexión

---

## Soporte

Para dudas o problemas con el sistema de debugging, contactar al equipo de desarrollo.

**Documentos relacionados**:
- `VERSIONADO_FLUTTER.md` - Cómo versionar la app
- `DEBUGGING_APP_MOVIL_CLIENTE.md` - Debugging para técnicos (usuario final)
- `TROUBLESHOOTING_APP_RESUMEN.md` - Matriz de decisión para problemas

---

**Última actualización**: 23/03/2026  
**Autor**: GitHub Copilot con Claude Sonnet 4.5
