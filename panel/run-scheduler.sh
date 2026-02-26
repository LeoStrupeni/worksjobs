#!/bin/bash
# Script para ejecutar Laravel Scheduler con logs
cd /home/u939320192/domains/tecnicos.strupeni.com.ar/panel

# Registrar inicio
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Ejecutando scheduler..." >> storage/logs/cron-scheduler.log

# Ejecutar scheduler
php artisan schedule:run >> storage/logs/cron-scheduler.log 2>&1

# Registrar fin
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Scheduler completado" >> storage/logs/cron-scheduler.log
echo "---" >> storage/logs/cron-scheduler.log
