@echo off
REM ======================================================
REM Script de prueba del Laravel Scheduler
REM Ejecuta el scheduler manualmente para verificar
REM ======================================================

echo.
echo ======================================
echo   PRUEBA DEL LARAVEL SCHEDULER
echo ======================================
echo.

cd /d "%~dp0"

echo [1/3] Verificando tareas programadas...
php artisan schedule:list
echo.

echo [2/3] Ejecutando scheduler manualmente...
php artisan schedule:run
echo.

echo [3/3] Verificando logs...
if exist "storage\logs\scheduler.log" (
    echo Ultimas 10 lineas del log:
    powershell -Command "Get-Content storage\logs\scheduler.log -Tail 10"
) else (
    echo No se encuentra el archivo de log aun.
)

echo.
echo ======================================
echo   PRUEBA COMPLETADA
echo ======================================
echo.
echo Para ver el estado de la sincronizacion:
echo   - Ve a http://localhost/client
echo   - Haz clic en "Estadisticas"
echo.
pause
