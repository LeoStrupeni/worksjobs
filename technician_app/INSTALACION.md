# 📱 App Móvil para Técnicos - Strupeni Electrónica

## ✅ Backend Laravel Configurado

He creado toda la API necesaria para la app móvil:

### Archivos Creados/Modificados:

**Backend (Laravel):**
- ✅ [panel/routes/api.php](panel/routes/api.php) - Rutas API configuradas
- ✅ [panel/app/Http/Controllers/Api/ApiAuthController.php](panel/app/Http/Controllers/Api/ApiAuthController.php) - Autenticación
- ✅ [panel/app/Http/Controllers/Api/ApiJobController.php](panel/app/Http/Controllers/Api/ApiJobController.php) - Gestión de citas
- ✅ [panel/app/Models/Jobs_Note.php](panel/app/Models/Jobs_Note.php) - Relación con User añadida

### Endpoints API Disponibles:

```
POST   /api/login                    - Login
POST   /api/logout                   - Logout
GET    /api/user                     - Datos del usuario

GET    /api/jobs/today               - Citas del día
GET    /api/jobs/upcoming            - Próximas citas
GET    /api/jobs/calendar            - Citas por rango de fechas
GET    /api/jobs/{id}                - Detalle de cita
POST   /api/jobs/{id}/arrival        - Marcar llegada
POST   /api/jobs/{id}/close          - Cerrar cita
POST   /api/jobs/{id}/notes          - Añadir nota
GET    /api/jobs/{id}/notes          - Obtener notas
GET    /api/jobs/{id}/files          - Obtener archivos
```

---

## 📱 App Flutter Creada

### Estructura del Proyecto:

```
technician_app/
├── lib/
│   ├── main.dart                    - App principal
│   ├── config/
│   │   └── api_config.dart         - Configuración API
│   ├── models/
│   │   ├── user.dart               - Modelo Usuario
│   │   ├── job.dart                - Modelo Cita
│   │   ├── note.dart               - Modelo Nota
│   │   └── job_file.dart           - Modelo Archivo
│   ├── providers/
│   │   ├── auth_provider.dart      - State Management Auth
│   │   └── job_provider.dart       - State Management Jobs
│   ├── services/
│   │   ├── auth_service.dart       - Servicio Autenticación
│   │   └── job_service.dart        - Servicio Citas
│   ├── screens/
│   │   ├── login_screen.dart       - Pantalla Login
│   │   ├── home_screen.dart        - Pantalla Principal
│   │   ├── today_jobs_screen.dart  - Citas de Hoy
│   │   ├── upcoming_jobs_screen.dart - Próximas Citas
│   │   ├── calendar_screen.dart    - Calendario
│   │   └── job_detail_screen.dart  - Detalle de Cita
│   └── widgets/
│       └── job_card.dart           - Componente Tarjeta
├── android/                        - Configuración Android
├── pubspec.yaml                    - Dependencias
└── README.md                       - Documentación
```

---

## 🚀 Cómo Instalar y Ejecutar

### 1. Instalar Flutter

**Windows:**
```bash
# Descargar desde: https://docs.flutter.dev/get-started/install/windows
# Extraer en C:\src\flutter
# Añadir al PATH: C:\src\flutter\bin
```

Verificar instalación:
```bash
flutter doctor
```

### 2. Configurar el Proyecto

```bash
cd c:\xampp\htdocs\Proyects\Strupeni_Electronica\technician_app

# Instalar dependencias
flutter pub get
```

### 3. Configurar la URL del Backend

Editar `lib/config/api_config.dart` línea 5:

```dart
// Para emulador Android:
static const String baseUrl = 'http://10.0.2.2/panel/api';

// Para dispositivo físico (cambiar por la IP de tu PC):
// static const String baseUrl = 'http://192.168.1.XXX/panel/api';
```

### 4. Configurar CORS en Laravel

Editar `panel/config/cors.php`:
```php
'paths' => ['api/*'],
'allowed_origins' => ['*'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
```

### 5. Ejecutar la App

```bash
# Ver dispositivos disponibles
flutter devices

# Ejecutar en emulador/dispositivo
flutter run

# Compilar APK para producción
flutter build apk --release
```

El APK estará en: `build/app/outputs/flutter-apk/app-release.apk`

---

## 📲 Funcionalidades Implementadas

### ✅ Autenticación
- Login con email y contraseña
- Tokens con Laravel Sanctum
- Logout
- Sesión persistente

### ✅ Gestión de Citas
- **Vista Hoy:** Citas del día actual
- **Vista Próximas:** Citas futuras
- **Vista Calendario:** Calendario interactivo con todas las citas

### ✅ Detalle de Cita
- Información del cliente
- Descripción del trabajo
- Marcar llegada (con GPS)
- Cerrar cita con observaciones (con GPS)
- Añadir notas durante el trabajo
- Ver historial completo

### ✅ Características Técnicas
- State Management con Provider
- Almacenamiento local con SharedPreferences
- Geolocalización con Geolocator
- Calendario con TableCalendar
- UI Material Design 3
- Manejo de errores y loading states

---

## 🔧 Próximos Pasos

1. **Instalar Flutter** siguiendo la guía oficial
2. **Ejecutar** `flutter pub get` en el directorio de la app
3. **Configurar** la URL del backend en `api_config.dart`
4. **Habilitar CORS** en Laravel
5. **Ejecutar** `flutter run` con un emulador o dispositivo conectado

---

## 📝 Notas Importantes

### Para Emulador Android:
- Usar `http://10.0.2.2` para acceder a localhost
- Asegurarse que XAMPP esté corriendo

### Para Dispositivo Físico:
- PC y dispositivo deben estar en la misma red WiFi
- Usar la IP local de tu PC (ej: `192.168.1.100`)
- Verificar firewall de Windows

### Permisos Android:
- ✅ GPS (para marcar llegada/cierre)
- ✅ Internet
- ✅ Tráfico HTTP no encriptado (cleartext)

---

## 🎯 Mejoras Futuras Sugeridas

- [ ] Subir fotos desde la cámara
- [ ] Modo offline con sincronización
- [ ] Notificaciones push
- [ ] Firma digital del cliente
- [ ] Escaneo de códigos QR
- [ ] Reportes PDF
- [ ] Chat en tiempo real

---

## 💡 Soporte

Si tienes problemas durante la instalación o ejecución:

1. Verificar que Flutter esté instalado: `flutter doctor`
2. Verificar que XAMPP esté corriendo
3. Verificar la URL en `api_config.dart`
4. Verificar que CORS esté habilitado en Laravel
5. Revisar logs con `flutter run --verbose`

---

**Desarrollado para Strupeni Electrónica** 🔧⚡
