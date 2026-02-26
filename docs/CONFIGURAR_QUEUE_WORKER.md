# Configuración del Queue Worker y Task Scheduler

## Problema
Los jobs en cola (como `SyncColppyClientsJob`) necesitan un proceso que los ejecute. Laravel usa queues para procesar tareas en segundo plano.

## Soluciones por Entorno

---

## 🪟 DESARROLLO (Windows/XAMPP)

### Opción 1: Ejecutar manualmente durante desarrollo
Abre una terminal en la carpeta del panel y ejecuta:
```bash
cd C:\xampp\htdocs\Proyects\Strupeni_Electronica\panel
php artisan queue:work --tries=3
```
**Nota:** Dejar esta ventana abierta mientras trabajas. Se procesan los jobs automáticamente.

### Opción 2: Script Batch automático
Crea un archivo `QUEUE_WORKER.bat` en la raíz del panel:
```batch
@echo off
cd /d C:\xampp\htdocs\Proyects\Strupeni_Electronica\panel
:loop
php artisan queue:work --tries=3 --timeout=300 --sleep=3
timeout /t 5 /nobreak
goto loop
```
Ejecuta este archivo para mantener el worker corriendo.

### Opción 3: Task Scheduler de Windows
1. Abre "Programador de tareas" de Windows
2. Crear tarea básica → Nombre: "Laravel Queue Worker"
3. Desencadenador: Al iniciar sesión
4. Acción: Iniciar programa
   - Programa: `C:\xampp\php\php.exe`
   - Argumentos: `artisan queue:work --tries=3`
   - Iniciar en: `C:\xampp\htdocs\Proyects\Strupeni_Electronica\panel`

---

## 🐧 PRODUCCIÓN (Linux/Ubuntu)

### Opción 1: Supervisor (RECOMENDADO)

#### 1. Instalar Supervisor
```bash
sudo apt-get install supervisor
```

#### 2. Crear archivo de configuración
```bash
sudo nano /etc/supervisor/conf.d/strupeni-queue.conf
```

Agregar este contenido (ajusta las rutas según tu servidor):
```ini
[program:strupeni-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/strupeni/panel/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/strupeni/panel/storage/logs/queue-worker.log
stopwaitsecs=3600
```

#### 3. Activar y iniciar
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start strupeni-queue-worker:*
```

#### 4. Comandos útiles
```bash
# Ver estado
sudo supervisorctl status

# Reiniciar workers
sudo supervisorctl restart strupeni-queue-worker:*

# Detener workers
sudo supervisorctl stop strupeni-queue-worker:*

# Ver logs
tail -f /var/www/strupeni/panel/storage/logs/queue-worker.log
```

### Opción 2: Systemd Service

#### 1. Crear archivo de servicio
```bash
sudo nano /etc/systemd/system/strupeni-queue.service
```

Contenido:
```ini
[Unit]
Description=Strupeni Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/strupeni/panel
ExecStart=/usr/bin/php /var/www/strupeni/panel/artisan queue:work --sleep=3 --tries=3
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

#### 2. Activar y iniciar
```bash
sudo systemctl enable strupeni-queue.service
sudo systemctl start strupeni-queue.service
```

#### 3. Comandos útiles
```bash
# Ver estado
sudo systemctl status strupeni-queue.service

# Reiniciar
sudo systemctl restart strupeni-queue.service

# Ver logs
sudo journalctl -u strupeni-queue.service -f
```

---

## 📅 TASK SCHEDULER (Comandos Programados)

Si en el futuro agregas comandos programados en `app/Console/Kernel.php`, necesitas configurar el cron:

### Linux
```bash
# Editar crontab
crontab -e

# Agregar esta línea (ajusta la ruta):
* * * * * cd /var/www/strupeni/panel && php artisan schedule:run >> /dev/null 2>&1
```

### Windows (Task Scheduler)
1. Crear tarea básica → "Laravel Scheduler"
2. Desencadenador: Repetir cada 1 minuto
3. Acción: `php.exe artisan schedule:run`

---

## 🔍 Verificar que funciona

### Método 1: Monitorear la base de datos
```sql
-- Ver jobs pendientes
SELECT * FROM jobs;

-- Ver jobs fallidos
SELECT * FROM failed_jobs;
```

### Método 2: Usar los endpoints de debug
Una vez configurado el worker, usa los botones en la vista de clientes:
- **Estadísticas**: Ver si se están sincronizando
- **Sincronizar Ahora**: Para debug inmediato (bypasea la queue)

### Método 3: Logs de Laravel
```bash
tail -f storage/logs/laravel.log
```

---

## ⚙️ Configuración de Queue

Verifica tu archivo `.env`:
```env
# Para desarrollo (sincrónico, sin queue)
QUEUE_CONNECTION=sync

# Para producción (con queue worker)
QUEUE_CONNECTION=database
```

Si cambias a `database`, asegúrate de tener las tablas:
```bash
php artisan queue:table
php artisan queue:failed-table
php artisan migrate
```

---

## 🚨 Solución de Problemas

### El worker no procesa jobs
1. Verifica que el worker esté corriendo: `ps aux | grep queue:work`
2. Revisa los logs: `tail -f storage/logs/laravel.log`
3. Verifica la tabla `jobs`: `SELECT * FROM jobs;`

### Worker se detiene después de actualizar código
**Importante:** Después de actualizar código en producción, debes reiniciar el worker:
```bash
# Con Supervisor
sudo supervisorctl restart strupeni-queue-worker:*

# Con Systemd
sudo systemctl restart strupeni-queue.service

# O usar el comando de Laravel
php artisan queue:restart
```

### Jobs fallan constantemente
```bash
# Ver jobs fallidos
php artisan queue:failed

# Reintentar un job específico
php artisan queue:retry {id}

# Reintentar todos los fallidos
php artisan queue:retry all
```

---

## 📝 Recomendaciones

1. **Desarrollo**: Usa `QUEUE_CONNECTION=sync` para debug inmediato
2. **Producción**: Usa `QUEUE_CONNECTION=database` con Supervisor
3. **Siempre** reinicia el worker después de actualizar código
4. Monitorea los logs regularmente
5. Usa el endpoint `/client/sync-colppy-now` para debug sin queue

---

## 🎯 Para tu caso específico (Colppy Sync)

### Desarrollo (ahora mismo)
```bash
# Terminal 1: Servidor web (si no usas Apache)
php artisan serve

# Terminal 2: Queue worker
php artisan queue:work --tries=3
```

### Producción
1. Instala Supervisor siguiendo la "Opción 1" de Linux
2. Configura el servicio con las rutas de tu servidor
3. Reinicia después de cada deploy: `php artisan queue:restart`

### Alternativa: Sin Queue
Si prefieres no usar queue worker, puedes:
1. Cambiar `.env`: `QUEUE_CONNECTION=sync`
2. Usar siempre el endpoint `/client/sync-colppy-now` (sincrónico)
3. Programar un cron que llame ese endpoint cada hora
