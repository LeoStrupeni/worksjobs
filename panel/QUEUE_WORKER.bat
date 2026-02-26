@echo off
title Laravel Queue Worker - Strupeni Electronica
color 0A

echo ========================================
echo  Laravel Queue Worker
echo  Strupeni Electronica
echo ========================================
echo.
echo Iniciando worker...
echo Presiona Ctrl+C para detener
echo.

cd /d C:\xampp\htdocs\Proyects\Strupeni_Electronica\panel

:loop
php artisan queue:work --tries=3 --timeout=300 --sleep=3
echo.
echo [%date% %time%] Worker detenido, reiniciando en 5 segundos...
timeout /t 5 /nobreak > nul
goto loop
