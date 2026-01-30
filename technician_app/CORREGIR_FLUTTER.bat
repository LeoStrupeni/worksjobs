@echo off
echo ========================================
echo CORRIGIENDO INSTALACION DE FLUTTER
echo ========================================
echo.

echo Moviendo archivos de C:\src\flutter\flutter a C:\src\flutter...
echo.

xcopy "C:\src\flutter\flutter\*" "C:\src\flutter\" /E /H /Y /I

echo.
echo Eliminando carpeta duplicada...
rd /s /q "C:\src\flutter\flutter"

echo.
echo ========================================
echo LISTO! Flutter corregido
echo ========================================
echo.
echo Ahora configura el PATH con:
echo C:\src\flutter\bin
echo.
echo Cierra esta ventana y ejecuta en una NUEVA terminal:
echo flutter --version
echo.

pause
