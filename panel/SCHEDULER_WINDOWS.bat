@echo off
REM ======================================================
REM Laravel Scheduler para Windows (XAMPP)
REM Este script ejecuta el scheduler de Laravel
REM ======================================================

cd /d "%~dp0"

REM Ejecutar el scheduler de Laravel
php artisan schedule:run >> storage/logs/scheduler.log 2>&1

REM Este script debe ejecutarse cada minuto usando el Programador de Tareas de Windows
REM Ver instrucciones en CONFIGURAR_SCHEDULER.md
