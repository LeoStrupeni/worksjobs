# INSTALAR ANDROID COMMAND LINE TOOLS

## Paso 1: Descargar Command Line Tools

**OPCIÓN A - Link Directo (Más fácil):**

Descarga directamente desde aquí:
https://dl.google.com/android/repository/commandlinetools-win-11076708_latest.zip

**OPCIÓN B - Desde la página oficial:**

1. Ve a: https://developer.android.com/studio
2. Baja hasta el final de la página
3. Busca la sección "Command line tools only"
4. Haz clic en "Download" bajo "Windows"
5. Acepta los términos y descarga el ZIP

## Paso 2: Instalar

1. Crea la carpeta: C:\Android\cmdline-tools
2. Extrae el ZIP
3. Dentro del ZIP hay una carpeta "cmdline-tools"
4. Mueve todo su contenido a: C:\Android\cmdline-tools\latest\

Estructura final:
```
C:\Android\
  └─ cmdline-tools\
      └─ latest\
          ├─ bin\
          ├─ lib\
          └─ ...
```

## Paso 2.5: Instalar Java JDK (REQUERIDO)

Android SDK necesita Java. Instálalo así:

**OPCIÓN A - Descargar Java JDK:**

1. Ve a: https://www.oracle.com/java/technologies/downloads/#jdk21-windows
2. Descarga: "Windows x64 Installer" (jdk-21_windows-x64_bin.exe)
3. Ejecuta el instalador
4. Deja la ruta por defecto: C:\Program Files\Java\jdk-21

**OPCIÓN B - Con Chocolatey (más rápido si lo tienes):**

```bash
choco install openjdk
```

**Después de instalar, REINICIA la terminal/VSCode**

## Paso 3: Instalar SDK

Abre PowerShell como Administrador y ejecuta:

```powershell
cd C:\Android\cmdline-tools\latest\bin
.\sdkmanager.bat "platform-tools" "platforms;android-34" "build-tools;34.0.0"
```

## Paso 4: Configurar Flutter

```bash
flutter config --android-sdk C:\Android
```

## Paso 5: Aceptar licencias

```bash
flutter doctor --android-licenses
```
(Presiona 'y' para aceptar todas)

## Paso 6: Generar APK

```bash
cd c:\xampp\htdocs\Proyects\Strupeni_Electronica\technician_app
flutter build apk --release
```

El APK estará en: build\app\outputs\flutter-apk\app-release.apk
