@echo off
echo ========================================
echo INSTALADOR DE FLUTTER PARA WINDOWS
echo ========================================
echo.

echo [1/4] Descargando Flutter SDK...
echo Por favor descarga Flutter desde:
echo https://docs.flutter.dev/get-started/install/windows
echo.
echo O directamente desde:
echo https://storage.googleapis.com/flutter_infra_release/releases/stable/windows/flutter_windows_3.16.0-stable.zip
echo.

echo [2/4] Instrucciones de instalacion:
echo 1. Descarga el archivo ZIP
echo 2. Extrae en C:\src\flutter (crea la carpeta si no existe)
echo 3. NO extraigas dentro de Program Files o carpetas con permisos especiales
echo.

echo [3/4] Configurar PATH:
echo 1. Presiona Windows + R
echo 2. Escribe: sysdm.cpl
echo 3. Ve a "Opciones avanzadas" ^> "Variables de entorno"
echo 4. En "Variables del sistema", busca "Path" y haz clic en "Editar"
echo 5. Haz clic en "Nuevo" y agrega: C:\src\flutter\bin
echo 6. Haz clic en "Aceptar" en todas las ventanas
echo.

echo [4/4] Verificar instalacion:
echo 1. Cierra y abre una nueva terminal
echo 2. Ejecuta: flutter doctor
echo.

echo ========================================
echo IMPORTANTE:
echo ========================================
echo - Necesitas tener Git instalado (https://git-scm.com/download/win)
echo - Necesitas Android Studio para el emulador (opcional)
echo - Reinicia la terminal despues de configurar PATH
echo.

pause
