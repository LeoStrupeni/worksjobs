# Technician App

App móvil para técnicos de Strupeni Electrónica. Permite gestionar citas, marcar llegadas, cerrar trabajos y añadir notas.

## Características

- ✅ Autenticación con Laravel Sanctum
- ✅ Vista de citas del día
- ✅ Próximas citas
- ✅ Calendario de citas
- ✅ Marcar llegada con GPS
- ✅ Cerrar citas con observaciones
- ✅ Añadir y ver notas
- ✅ Ver archivos adjuntos

## Requisitos Previos

1. **Flutter SDK** (versión 3.0 o superior)
   - Descargar desde: https://flutter.dev/docs/get-started/install
   
2. **Android Studio** o **VS Code** con extensiones de Flutter

3. **Backend Laravel** configurado con:
   - Laravel Sanctum habilitado
   - Endpoints API creados
   - Base de datos configurada

## Instalación

### 1. Instalar Flutter

**Windows:**
```bash
# Descargar Flutter SDK desde https://docs.flutter.dev/get-started/install/windows
# Extraer en C:\src\flutter
# Añadir al PATH: C:\src\flutter\bin

# Verificar instalación
flutter doctor
```

### 2. Configurar el Proyecto

```bash
cd c:\xampp\htdocs\Proyects\Strupeni_Electronica\technician_app

# Instalar dependencias
flutter pub get

# Verificar dispositivos conectados
flutter devices
```

### 3. Configurar la URL del Backend

Editar el archivo `lib/config/api_config.dart` y actualizar la URL base:

```dart
static const String baseUrl = 'http://TU_IP:TU_PUERTO/api';
```

**Importante:** 
- Para emulador Android: usar `http://10.0.2.2:80/api` (si tu XAMPP está en puerto 80)
- Para dispositivo físico: usar la IP de tu PC (ej: `http://192.168.1.100:80/api`)

### 4. Ejecutar la App

```bash
# En emulador
flutter run

# En dispositivo físico (Debug)
flutter run

# Compilar APK para producción
flutter build apk --release
```

El APK se generará en: `build/app/outputs/flutter-apk/app-release.apk`

## Estructura del Proyecto

```
lib/
├── main.dart                 # Punto de entrada
├── config/
│   └── api_config.dart      # Configuración API
├── models/                  # Modelos de datos
│   ├── user.dart
│   ├── job.dart
│   └── note.dart
├── providers/               # State Management
│   ├── auth_provider.dart
│   └── job_provider.dart
├── services/               # Servicios API
│   ├── api_service.dart
│   ├── auth_service.dart
│   └── job_service.dart
├── screens/                # Pantallas
│   ├── login_screen.dart
│   ├── home_screen.dart
│   ├── calendar_screen.dart
│   └── job_detail_screen.dart
└── widgets/                # Componentes reutilizables
    ├── job_card.dart
    └── custom_button.dart
```

## Uso

### Login
1. Abrir la app
2. Ingresar email y contraseña
3. Presionar "Iniciar Sesión"

### Ver Citas
- **Hoy:** Muestra todas las citas del día actual
- **Próximas:** Muestra las próximas citas programadas
- **Calendario:** Vista de calendario con todas las citas

### Gestionar Cita
1. Seleccionar una cita
2. Ver detalles del cliente y descripción
3. Acciones disponibles:
   - **Marcar Llegada:** Registra tu llegada con ubicación GPS
   - **Añadir Nota:** Agregar observaciones durante el trabajo
   - **Cerrar Cita:** Finalizar el trabajo con observaciones finales

## Configuración del Backend (Laravel)

Asegúrate de que tu backend tenga:

1. **CORS habilitado** en `config/cors.php`:
```php
'paths' => ['api/*'],
'allowed_origins' => ['*'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
```

2. **Sanctum configurado** en `config/sanctum.php`:
```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost')),
```

3. **Migraciones ejecutadas**:
```bash
php artisan migrate
```

## Troubleshooting

### Error de conexión
- Verificar que XAMPP esté corriendo
- Verificar la URL en `api_config.dart`
- Verificar que el firewall permita conexiones

### Error de autenticación
- Verificar que Sanctum esté configurado
- Verificar credenciales de usuario en la base de datos

### Error de GPS
- Dar permisos de ubicación a la app
- Verificar que el GPS esté habilitado

## Próximas Funcionalidades

- [ ] Subir fotos desde la app
- [ ] Modo offline
- [ ] Notificaciones push
- [ ] Firma digital del cliente
- [ ] Escaneo de códigos QR

## Licencia

Propietario - Strupeni Electrónica
