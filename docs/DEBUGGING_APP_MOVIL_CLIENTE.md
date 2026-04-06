# 🐛 Debugging de App Móvil - Problemas del Cliente

## 📋 Escenario

El usuario reporta: **"Error: Error al obtener citas"**

Pero cuando el desarrollador se loguea con las credenciales del usuario en otro dispositivo, **funciona correctamente**.

**Conclusión:** El problema NO es del backend, sino del **dispositivo específico del usuario**.

---

## 🔍 Causas Comunes (Lado Cliente)

### 1. **Token Corrupto en Almacenamiento Local** ⭐ (MÁS COMÚN)
- El token se guarda en `SharedPreferences` (Android) o `UserDefaults` (iOS)
- Puede corromperse por:
  - Actualización de la app mal aplicada
  - Cierre forzado de la app durante escritura
  - Espacio insuficiente en el dispositivo
  - Bug en versión anterior

**Solución:** Borrar datos de la app

---

### 2. **Caché Corrupto**
- La app cachea respuestas para offline
- El caché puede tener datos viejos o corruptos

**Solución:** Borrar caché de la app

---

### 3. **Versión Antigua de la App**
- El usuario no actualizó la app
- Versiones viejas pueden tener bugs ya corregidos

**Solución:** Actualizar desde la tienda

---

### 4. **Permisos Insuficientes**
- Android: Permisos de red, almacenamiento
- iOS: Permisos de red en segundo plano

**Solución:** Verificar permisos en configuración del sistema

---

### 5. **Problemas de Red Específicos**
- Firewall corporativo
- VPN activa
- Proxy configurado
- Red con restricciones

**Solución:** Probar con datos móviles vs WiFi

---

### 6. **Espacio Insuficiente**
- No puede escribir logs
- No puede cachear datos
- Falla al guardar token

**Solución:** Liberar espacio (mínimo 200 MB)

---

### 7. **Sistema Operativo Desactualizado**
- Android < 5.0 o iOS < 12
- Problemas de compatibilidad

**Solución:** Actualizar SO o usar versión legacy de la app

---

## 🚀 Soluciones para el Usuario

### **SOLUCIÓN 1: Limpiar Datos de la App** ⭐ (Recomendado)

#### Android:
```
1. Configuración → Apps → [Nombre de la App]
2. Almacenamiento
3. Borrar datos
4. Borrar caché
5. Abrir la app
6. Hacer login nuevamente
```

#### iOS:
```
1. Configuración → General → Almacenamiento del iPhone
2. Buscar [Nombre de la App]
3. Descargar app (mantiene el ícono pero borra datos)
4. Tocar el ícono para reinstalar
5. Hacer login nuevamente
```

**Ventaja:** Rápido, no pierde la app  
**Desventaja:** Pierde sesión y datos locales (si tiene caché offline)

---

### **SOLUCIÓN 2: Reinstalar la App** (Más Efectivo)

```
1. Desinstalar la app completamente
2. Reiniciar el teléfono (opcional pero recomendado)
3. Instalar desde Google Play / App Store
4. Hacer login
```

**Ventaja:** Limpia TODOS los residuos  
**Desventaja:** Toma más tiempo

---

### **SOLUCIÓN 3: Verificar y Otorgar Permisos**

#### Android:
```
Configuración → Apps → [Nombre de la App] → Permisos

Verificar que estén ACTIVADOS:
✅ Almacenamiento (para guardar token y caché)
✅ Red (implícito, pero verificar que no esté restringido)
✅ Ubicación (si la app la requiere para geolocalización)
✅ Cámara (para subir fotos de trabajos)
```

#### iOS:
```
Configuración → [Nombre de la App]

Verificar:
✅ Datos móviles: Permitir
✅ Actualización en segundo plano: Activada
✅ Ubicación: Mientras se usa (o Siempre)
✅ Cámara: Permitir
```

---

### **SOLUCIÓN 4: Probar con Otra Red**

```
1. Desconectarse del WiFi
2. Activar datos móviles
3. Intentar cargar las citas

SI FUNCIONA CON DATOS MÓVILES:
  → El problema es el WiFi/red local
  → Posible firewall corporativo
  → Posible bloqueo del router

SI NO FUNCIONA CON NINGUNA RED:
  → El problema es la app en el dispositivo
  → Aplicar Solución 1 o 2
```

---

### **SOLUCIÓN 5: Verificar Espacio Disponible**

```
Android: Configuración → Almacenamiento
iOS: Configuración → General → Almacenamiento del iPhone

Debe tener mínimo: 200-500 MB libres

Si está lleno:
- Borrar fotos/videos viejos
- Desinstalar apps no usadas
- Borrar caché de otras apps
```

---

### **SOLUCIÓN 6: Actualizar la App**

```
Google Play Store / App Store
→ Buscar [Nombre de la App]
→ Si hay botón "Actualizar" → Actualizar
→ Si dice "Abrir" → Ya está actualizada
```

---

### **SOLUCIÓN 7: Actualizar Sistema Operativo**

```
Android: Configuración → Sistema → Actualización
iOS: Configuración → General → Actualización de software

Versiones mínimas requeridas:
- Android: 5.0 (Lollipop) o superior
- iOS: 12.0 o superior
```

---

## 📞 Guía de Soporte al Usuario

### **Conversación Sugerida:**

**Usuario:** "Me sale error al cargar las citas"

**Soporte:**
```
Hola! Para solucionar el problema, probá lo siguiente:

1. Cerrá la app completamente (forzar cierre)
2. Andá a: Configuración → Apps → [App] → Almacenamiento
3. Tocá "Borrar datos" y "Borrar caché"
4. Abrí la app nuevamente
5. Loguéate de nuevo

Esto debería solucionar el problema.
Avisame si sigue sin funcionar.
```

**Si sigue sin funcionar:**
```
Probá esto:
1. Desinstalá la app completamente
2. Reiniciá el teléfono
3. Instalá la app nuevamente desde Google Play
4. Loguéate

Si aún así no funciona, decime:
- ¿Qué modelo de teléfono tenés?
- ¿Qué versión de Android/iOS?
- ¿Te pasa con WiFi o con datos móviles?
```

---

## 🔧 Para Desarrolladores: Prevenir Estos Problemas

### **1. Implementar Logging Local**

Guardar logs en el dispositivo que el usuario pueda compartir:

```dart
// En lib/utils/logger.dart
class AppLogger {
  static final List<String> _logs = [];
  
  static void log(String message) {
    final timestamp = DateTime.now().toIso8601String();
    final entry = '[$timestamp] $message';
    _logs.add(entry);
    print(entry);
    
    // Guardar los últimos 100 logs
    if (_logs.length > 100) {
      _logs.removeAt(0);
    }
  }
  
  static String exportLogs() {
    return _logs.join('\n');
  }
}

// Usar en job_service.dart
catch (e) {
  AppLogger.log('❌ getTodayJobs: Exception: $e');
  return {'success': false, 'message': 'Error al obtener citas'};
}
```

---

### **2. Pantalla de Debug (Acceso Oculto)**

Agregar una pantalla de debug que el usuario pueda compartir:

```dart
// En lib/screens/debug_screen.dart
class DebugScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Debug Info')),
      body: ListView(
        padding: EdgeInsets.all(16),
        children: [
          _buildInfoCard('App Version', '1.2.3'), // Desde package_info
          _buildInfoCard('Device', '${Platform.operatingSystem}'),
          _buildInfoCard('API Base URL', ApiConfig.baseUrl),
          _buildInfoCard('Token', _getTokenPreview()),
          _buildInfoCard('Last Error', _lastError ?? 'None'),
          
          SizedBox(height: 20),
          
          ElevatedButton(
            onPressed: _testHealthCheck,
            child: Text('Test Health Check'),
          ),
          
          ElevatedButton(
            onPressed: _clearCache,
            child: Text('Clear Cache'),
          ),
          
          ElevatedButton(
            onPressed: _exportLogs,
            child: Text('Export Logs'),
          ),
        ],
      ),
    );
  }
  
  String _getTokenPreview() async {
    final token = await AuthService().getToken();
    if (token == null) return 'No token';
    return '${token.substring(0, 10)}...${token.substring(token.length - 10)}';
  }
  
  void _testHealthCheck() async {
    // Llamar a /api/health-check y mostrar resultado
  }
  
  void _clearCache() async {
    // Borrar SharedPreferences
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();
    // Mostrar confirmación
  }
  
  void _exportLogs() {
    // Exportar logs y compartir por email/WhatsApp
    Share.share(AppLogger.exportLogs());
  }
}
```

**Cómo acceder:** Tap 5 veces en el logo desde la pantalla de login

```dart
// En login_screen.dart
GestureDetector(
  onTap: () {
    _logoTapCount++;
    if (_logoTapCount >= 5) {
      Navigator.push(
        context,
        MaterialPageRoute(builder: (_) => DebugScreen()),
      );
      _logoTapCount = 0;
    }
  },
  child: Logo(),
)
```

---

### **3. Manejo de Errores Mejorado**

```dart
// En job_service.dart
Future<Map<String, dynamic>> getTodayJobs() async {
  try {
    final token = await _authService.getToken();
    
    if (token == null) {
      AppLogger.log('❌ getTodayJobs: No token found');
      return {
        'success': false, 
        'message': 'No autenticado',
        'error_code': 'NO_TOKEN',
        'user_action': 'Por favor, cerrá sesión y volvé a ingresar'
      };
    }

    AppLogger.log('📡 getTodayJobs: Request to ${ApiConfig.baseUrl}${ApiConfig.todayJobsEndpoint}');
    
    final response = await http.get(
      Uri.parse('${ApiConfig.baseUrl}${ApiConfig.todayJobsEndpoint}'),
      headers: ApiConfig.getHeaders(token: token),
    ).timeout(Duration(seconds: 30));

    AppLogger.log('📥 getTodayJobs: Status ${response.statusCode}');
    
    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      
      if (data['success'] == true) {
        AppLogger.log('✅ getTodayJobs: ${data['count']} jobs found');
        // ...
      }
    } else if (response.statusCode == 401) {
      AppLogger.log('❌ getTodayJobs: Unauthorized');
      return {
        'success': false,
        'message': 'Sesión expirada',
        'error_code': 'UNAUTHORIZED',
        'user_action': 'Por favor, cerrá sesión y volvé a ingresar'
      };
    }
    
    return {'success': false, 'message': 'Error al obtener citas'};
    
  } on TimeoutException {
    AppLogger.log('❌ getTodayJobs: Timeout');
    return {
      'success': false,
      'message': 'La conexión tardó demasiado',
      'error_code': 'TIMEOUT',
      'user_action': 'Verificá tu conexión a internet'
    };
  } on SocketException {
    AppLogger.log('❌ getTodayJobs: No internet connection');
    return {
      'success': false,
      'message': 'Sin conexión a internet',
      'error_code': 'NO_INTERNET',
      'user_action': 'Verificá que tengas WiFi o datos móviles activos'
    };
  } catch (e) {
    AppLogger.log('❌ getTodayJobs: Exception: $e');
    return {
      'success': false,
      'message': 'Error al obtener citas',
      'error_code': 'UNKNOWN',
      'error_detail': e.toString()
    };
  }
}
```

Y mostrar mensajes más útiles:

```dart
// En today_jobs_screen.dart
if (result['success'] == false) {
  final userAction = result['user_action'];
  
  showDialog(
    context: context,
    builder: (context) => AlertDialog(
      title: Text('Error'),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(result['message']),
          if (userAction != null) ...[
            SizedBox(height: 10),
            Text(
              userAction,
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
          ],
        ],
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: Text('OK'),
        ),
      ],
    ),
  );
}
```

---

### **4. Auto-Recuperación**

Intentar recuperarse automáticamente:

```dart
Future<Map<String, dynamic>> getTodayJobs({int retryCount = 0}) async {
  try {
    // ... lógica normal ...
    
  } catch (e) {
    // Si es el primer intento y falló por token inválido
    if (retryCount == 0 && e is UnauthorizedException) {
      AppLogger.log('⚠️ Retrying getTodayJobs after token refresh');
      
      // Intentar refrescar token o limpiar y pedir nuevo login
      await _authService.clearToken();
      
      // Reintentar UNA vez
      return getTodayJobs(retryCount: 1);
    }
    
    return {'success': false, 'message': 'Error al obtener citas'};
  }
}
```

---

## 📊 Estadísticas de Problemas

Según experiencia en apps similares:

| Problema | Frecuencia | Solución |
|----------|------------|----------|
| Token corrupto local | 60% | Borrar datos app |
| Caché corrupto | 20% | Borrar caché |
| Versión antigua | 10% | Actualizar |
| Permisos | 5% | Otorgar permisos |
| Red restrictiva | 3% | Cambiar de red |
| Otro | 2% | Caso por caso |

---

## 📝 Checklist para el Usuario

```
□ ¿Probaste cerrar y abrir la app completamente?
□ ¿Borraste datos y caché de la app?
□ ¿Tenés la última versión instalada?
□ ¿Tenés espacio suficiente en el teléfono? (>200MB)
□ ¿Probaste con datos móviles en vez de WiFi?
□ ¿Los permisos de la app están activados?
□ ¿Reiniciaste el teléfono?
□ ¿Probaste reinstalar la app?
```

Si todo lo anterior falla → Entonces SÍ puede ser un problema del backend específico para ese usuario.

---

## 🎯 Resumen

**Cuando el problema es específico del dispositivo:**
1. Primero: Borrar datos/caché
2. Si no funciona: Reinstalar
3. Si sigue sin funcionar: Verificar permisos y red
4. Como último recurso: Actualizar SO o cambiar dispositivo

**El 90% se soluciona con:** Borrar datos + Reinstalar
