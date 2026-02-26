# Configuración del Laravel Scheduler para Sincronización de Colppy

Este documento explica cómo configurar el Laravel Scheduler para ejecutar automáticamente la sincronización de clientes de Colppy.

## 📊 ¿Qué hace?

El scheduler ejecuta automáticamente la sincronización de clientes desde Colppy **cada hora**, manteniendo la base de datos local actualizada sin intervención manual.

**Configuración actual:**
- **Frecuencia:** Cada hora (`:00` de cada hora)
- **Job:** `SyncColppyClientsJob`
- **Protección:** Sin solapamiento (no ejecuta si ya hay una sincronización en curso)
- **Servidor único:** Solo se ejecuta en un servidor (útil para múltiples instancias)

---

## 🪟 Configuración en Windows (XAMPP)

### Opción 1: Programador de Tareas de Windows (Recomendado)

1. **Abrir el Programador de Tareas:**
   - Presiona `Win + R`
   - Escribe `taskschd.msc` y presiona Enter

2. **Crear nueva tarea:**
   - Clic derecho en "Biblioteca del Programador de Tareas"
   - Selecciona "Crear tarea básica..."

3. **Configurar la tarea:**
   - **Nombre:** Laravel Scheduler - Strupeni
   - **Descripción:** Ejecuta el Laravel Scheduler cada minuto para tareas programadas

4. **Desencadenador:**
   - Selecciona "Diariamente"
   - Hora de inicio: 00:00
   - Repetir cada: **1 minuto**
   - Durante: **1 día**
   - **IMPORTANTE:** Marca "Repetir la tarea indefinidamente"

5. **Acción:**
   - Acción: "Iniciar un programa"
   - Programa/script: Ruta completa al archivo `.bat`
     ```
     C:\xampp\htdocs\Proyects\Strupeni_Electronica\panel\SCHEDULER_WINDOWS.bat
     ```
   - Iniciar en: Directorio del panel
     ```
     C:\xampp\htdocs\Proyects\Strupeni_Electronica\panel
     ```

6. **Condiciones (IMPORTANTE):**
   - ✅ **DESMARCAR** "Iniciar la tarea solo si el equipo está conectado a la corriente alterna"
   - ✅ **DESMARCAR** "Detener si el equipo deja de estar conectado a la corriente alterna"
   - Esto evita que se detenga en laptops con batería

7. **Configuración:**
   - ✅ Marcar "Permitir que la tarea se ejecute a petición"
   - ✅ Marcar "Ejecutar la tarea lo antes posible después de perder un inicio programado"
   - Duración: Sin límite

8. **Guardar y probar:**
   - Haz clic derecho en la tarea creada
   - Selecciona "Ejecutar"
   - Verifica el log: `storage/logs/scheduler.log`

### Opción 2: Script manual (Solo para pruebas)

Ejecuta manualmente el scheduler:
```cmd
cd C:\xampp\htdocs\Proyects\Strupeni_Electronica\panel
SCHEDULER_WINDOWS.bat
```

---

## 🐧 Configuración en Linux (Producción)

### 1. Editar el crontab

Abre el editor de crontab:
```bash
crontab -e
```

### 2. Agregar la línea del cron

Agrega esta línea al final del archivo:
```bash
* * * * * cd /var/www/strupeni/panel && php artisan schedule:run >> /dev/null 2>&1
```

**Explicación:**
- `* * * * *` - Ejecutar cada minuto
- `cd /var/www/strupeni/panel` - Cambiar al directorio del proyecto
- `php artisan schedule:run` - Ejecutar el scheduler de Laravel
- `>> /dev/null 2>&1` - Redirigir salida a null (o puedes guardar logs)

### 3. Alternativa con logs

Si quieres guardar logs:
```bash
* * * * * cd /var/www/strupeni/panel && php artisan schedule:run >> storage/logs/scheduler.log 2>&1
```

### 4. Verificar que el cron está activo

```bash
crontab -l
```

---

## 🔧 Modificar la Frecuencia

Si quieres cambiar la frecuencia de sincronización, edita `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Opciones de frecuencia:
    
    // Cada 30 minutos
    $schedule->call(function () {
        \App\Jobs\SyncColppyClientsJob::dispatch();
    })->everyThirtyMinutes();
    
    // Cada hora (CONFIGURACIÓN ACTUAL)
    $schedule->call(function () {
        \App\Jobs\SyncColppyClientsJob::dispatch();
    })->hourly();
    
    // Cada 2 horas
    $schedule->call(function () {
        \App\Jobs\SyncColppyClientsJob::dispatch();
    })->everyTwoHours();
    
    // Cada día a las 2 AM
    $schedule->call(function () {
        \App\Jobs\SyncColppyClientsJob::dispatch();
    })->dailyAt('02:00');
    
    // Cada 15 minutos
    $schedule->call(function () {
        \App\Jobs\SyncColppyClientsJob::dispatch();
    })->everyFifteenMinutes();
}
```

---

## 📝 Verificación

### 1. Verificar que el scheduler funciona

Ejecuta manualmente:
```bash
php artisan schedule:run
```

Deberías ver:
```
Running scheduled command: ...
```

### 2. Ver logs de sincronización

Los logs de la sincronización están en:
```
storage/logs/laravel-YYYY-MM-DD.log
```

Busca entradas con:
```
[INICIO] Sincronizando clientes desde Colppy
[FIN] Sincronización completada
```

### 3. Ver logs del scheduler (Windows)

```
storage/logs/scheduler.log
```

### 4. Probar la sincronización manual

Desde el panel web:
- Ve a Clientes
- Haz clic en "Sincronizar Ahora"
- Verifica que aparezcan los nuevos clientes

---

## ⚙️ Configuración Adicional

### Variables de entorno importantes

En el archivo `.env`:

```ini
# Cola de trabajos (usar 'sync' para ejecución inmediata o 'database' para cola)
QUEUE_CONNECTION=sync

# Límite de tiempo de ejecución (segundos)
MAX_EXECUTION_TIME=300

# Activar/desactivar debug
APP_DEBUG=false  # En producción usar false
```

### Deshabilitar sincronización automática

Si quieres deshabilitar temporalmente la sincronización automática:

1. **Opción 1:** Comenta la línea en `app/Console/Kernel.php`:
   ```php
   protected function schedule(Schedule $schedule)
   {
       // $schedule->call(...)->hourly();  // Comentado
   }
   ```

2. **Opción 2 (Windows):** Deshabilita la tarea en el Programador de Tareas

3. **Opción 3 (Linux):** Comenta la línea del crontab:
   ```bash
   # * * * * * cd /var/www/...
   ```

---

## 🐛 Solución de Problemas

### El scheduler no se ejecuta

1. **Verifica que el cron/tarea programada esté activa:**
   - Windows: Abre el Programador de Tareas
   - Linux: `crontab -l`

2. **Verifica permisos (Linux):**
   ```bash
   chmod +x artisan
   ```

3. **Verifica la ruta de PHP:**
   ```bash
   which php  # Linux
   where php  # Windows
   ```

### La sincronización falla

1. **Verifica las credenciales de Colppy** en `.env`:
   ```ini
   COLPPY_API_URL=https://api.colppy.com
   COLPPY_CLIENT_ID=tu_client_id
   COLPPY_CLIENT_SECRET=tu_client_secret
   ```

2. **Verifica los logs:**
   ```bash
   tail -f storage/logs/laravel-*.log
   ```

3. **Prueba la sincronización manual:**
   ```bash
   php artisan tinker
   >>> \App\Jobs\SyncColppyClientsJob::dispatch();
   ```

### Sincronizaciones múltiples simultáneas

Si ves múltiples sincronizaciones ejecutándose al mismo tiempo:

- Verifica que solo hay **UNA** entrada en el crontab
- Verifica que la opción `withoutOverlapping()` esté en el schedule
- Limpia cachés: `php artisan cache:clear`

---

## 📊 Monitoreo

### Ver última ejecución del scheduler

```bash
php artisan schedule:list
```

### Ver tareas programadas

```bash
php artisan schedule:list
```

Salida:
```
0 * * * * sync-colppy-clients ......... Next Due: 1 hour from now
```

### Ver estadísticas de sincronización

Desde el panel web:
- Ve a Clientes
- Haz clic en "Estadísticas"
- Verás: Local vs Colppy, diferencias detectadas

---

## 🚀 Resumen

**Pasos rápidos para configurar:**

### Windows (XAMPP):
1. Abre el Programador de Tareas (`taskschd.msc`)
2. Crea tarea que ejecute `SCHEDULER_WINDOWS.bat` cada minuto
3. Verifica logs en `storage/logs/scheduler.log`

### Linux (Producción):
1. Edita crontab: `crontab -e`
2. Agrega: `* * * * * cd /ruta/panel && php artisan schedule:run`
3. Verifica: `crontab -l`

**¡Listo!** Los clientes se sincronizarán automáticamente cada hora. ✅
