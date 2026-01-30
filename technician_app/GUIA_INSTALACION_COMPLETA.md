# 🚀 GUÍA COMPLETA DE INSTALACIÓN - APP TÉCNICOS STRUPENI

## ✅ PARTE 1: INSTALAR FLUTTER (Windows)

### Opción A: Instalación Manual (Recomendada)

1. **Descargar Flutter:**
   - Ve a: https://docs.flutter.dev/get-started/install/windows
   - O descarga directo: https://storage.googleapis.com/flutter_infra_release/releases/stable/windows/flutter_windows_3.16.0-stable.zip

2. **Extraer Flutter:**
   ```
   - Crea la carpeta: C:\src\flutter
   - Extrae el ZIP ahí (NO en Program Files)
   - Resultado final: C:\src\flutter\bin\flutter.bat
   ```

3. **Configurar PATH (Variable de Entorno):**
   ```
   a) Presiona Windows + R
   b) Escribe: sysdm.cpl
   c) Pestaña "Opciones avanzadas" > "Variables de entorno"
   d) En "Variables del sistema", busca "Path"
   e) Clic en "Editar" > "Nuevo"
   f) Agrega: C:\src\flutter\bin
   g) Clic en "Aceptar" en todas las ventanas
   h) REINICIA la terminal/VSCode
   ```

4. **Verificar instalación:**
   ```bash
   # Abre una NUEVA terminal y ejecuta:
   flutter doctor
   ```

### Opción B: Usando Chocolatey (Más rápido)

Si tienes Chocolatey instalado:
```bash
choco install flutter
```

---

## ✅ PARTE 2: INSTALAR DEPENDENCIAS

### Git (Requerido)

Si no tienes Git:
```bash
# Descarga desde:
https://git-scm.com/download/win

# O con Chocolatey:
choco install git
```

### Android Studio (Para emulador Android)

1. **Descargar:**
   - https://developer.android.com/studio

2. **Instalar:**
   - Instala Android Studio
   - Durante la instalación, marca:
     ✅ Android SDK
     ✅ Android SDK Platform
     ✅ Android Virtual Device

3. **Configurar en Flutter:**
   ```bash
   flutter doctor --android-licenses
   # Acepta todas las licencias (presiona 'y' para todas)
   ```

4. **Crear Emulador (Opcional):**
   - Abre Android Studio
   - Tools > Device Manager
   - Create Device
   - Selecciona un dispositivo (ej: Pixel 5)
   - Selecciona una imagen del sistema (ej: API 33)

---

## ✅ PARTE 3: CONFIGURAR EL PROYECTO

### 1. Instalar dependencias del proyecto:

```bash
cd c:\xampp\htdocs\Proyects\Strupeni_Electronica\technician_app
flutter pub get
```

### 2. Configurar la URL del Backend:

Edita: `lib/config/api_config.dart` (línea 5)

**Para emulador Android:**
```dart
static const String baseUrl = 'http://10.0.2.2/panel/api';
```

**Para dispositivo físico (WiFi misma red):**
```dart
// Reemplaza XXX por tu IP local
static const String baseUrl = 'http://192.168.1.XXX/panel/api';
```

**Para encontrar tu IP local:**
```bash
ipconfig
# Busca "Adaptador de LAN inalámbrica Wi-Fi"
# Anota la "Dirección IPv4" (ej: 192.168.1.100)
```

### 3. Habilitar CORS en Laravel:

Edita: `panel/config/cors.php`
```php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],  // ← Cambiar esto
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
```

### 4. Verificar que XAMPP esté corriendo:
- ✅ Apache corriendo
- ✅ MySQL corriendo

---

## ✅ PARTE 4: EJECUTAR LA APP

### Verificar dispositivos disponibles:
```bash
flutter devices
```

### Ejecutar en emulador Android:
```bash
# Primero inicia el emulador desde Android Studio
# Luego:
flutter run
```

### Ejecutar en dispositivo físico:
```bash
# 1. Habilita "Opciones de desarrollador" en tu Android:
#    - Ve a Ajustes > Acerca del teléfono
#    - Toca 7 veces en "Número de compilación"
#
# 2. Habilita "Depuración USB":
#    - Ajustes > Sistema > Opciones de desarrollador
#    - Activa "Depuración USB"
#
# 3. Conecta el cable USB
# 4. Autoriza la depuración en el teléfono
# 5. Ejecuta:
flutter run
```

### Compilar APK para instalar:
```bash
# APK de depuración (más rápido):
flutter build apk --debug

# APK de producción (optimizado):
flutter build apk --release

# El APK estará en:
# build/app/outputs/flutter-apk/app-release.apk
```

---

## 🔧 SOLUCIÓN DE PROBLEMAS

### Error: "flutter: command not found"
✅ Solución: Reinicia la terminal después de configurar PATH

### Error de conexión en emulador
✅ Solución: Usa `http://10.0.2.2` no `localhost` ni `127.0.0.1`

### Error de conexión en dispositivo físico
✅ Solución: 
- Verifica que estés en la misma red WiFi
- Usa la IP local de tu PC (ipconfig)
- Verifica firewall de Windows

### Error CORS
✅ Solución: Habilita CORS en `panel/config/cors.php`

### Error "Cleartext HTTP traffic not permitted"
✅ Ya está configurado en AndroidManifest.xml

### Error de permisos GPS
✅ Ya están configurados, pero debes aceptarlos en la app

---

## 📋 CHECKLIST RÁPIDO

- [ ] Flutter instalado (`flutter --version` funciona)
- [ ] Git instalado
- [ ] Android Studio instalado (opcional, para emulador)
- [ ] Dependencias instaladas (`flutter pub get`)
- [ ] URL configurada en `api_config.dart`
- [ ] CORS habilitado en Laravel
- [ ] XAMPP corriendo
- [ ] Emulador o dispositivo conectado
- [ ] `flutter run` ejecutado

---

## 🎯 COMANDOS ÚTILES

```bash
# Ver versión de Flutter
flutter --version

# Ver estado del sistema
flutter doctor

# Ver dispositivos conectados
flutter devices

# Limpiar proyecto
flutter clean

# Reinstalar dependencias
flutter pub get

# Ejecutar app
flutter run

# Ejecutar con logs detallados
flutter run --verbose

# Hot reload (mientras la app corre)
# Presiona 'r' en la terminal

# Hot restart (mientras la app corre)
# Presiona 'R' en la terminal

# Salir de la app
# Presiona 'q' en la terminal
```

---

## 📱 PROBAR LA APP

### Credenciales de prueba:
Usa las credenciales de un usuario existente en tu base de datos.

### Flujo de prueba:
1. Login
2. Ver citas de hoy
3. Ver próximas citas
4. Abrir calendario
5. Seleccionar una cita
6. Marcar llegada
7. Añadir nota
8. Cerrar cita

---

## 💡 SIGUIENTE PASO

Una vez instalado Flutter, ejecuta en la terminal:

```bash
cd c:\xampp\htdocs\Proyects\Strupeni_Electronica\technician_app
flutter pub get
flutter doctor
```

Y me compartes el resultado para continuar con la configuración 😊
