# ✅ Configuración Completada - Laravel Scheduler + Cron

## 📋 Resumen de lo Configurado

### 1. Laravel Scheduler (app/Console/Kernel.php)
```php
protected function schedule(Schedule $schedule)
{
    // Sincronizar clientes de Colppy cada hora
    $schedule->call(function () {
        \App\Jobs\SyncColppyClientsJob::dispatch();
    })->hourly()
      ->name('sync-colppy-clients')
      ->withoutOverlapping()
      ->onOneServer();
}
```

**Características:**
- ✅ Ejecución automática cada hora (en punto: 13:00, 14:00, 15:00...)
- ✅ Protección contra ejecuciones simultáneas (`withoutOverlapping`)
- ✅ Solo se ejecuta en un servidor (`onOneServer`)
- ✅ Nombre identificador: `sync-colppy-clients`

### 2. Archivos Creados

| Archivo | Descripción |
|---------|-------------|
| `SCHEDULER_WINDOWS.bat` | Script para ejecutar el scheduler en Windows |
| `TEST_SCHEDULER.bat` | Script de prueba para verificar configuración |
| `CONFIGURAR_SCHEDULER.md` | Documentación completa con instrucciones |

### 3. Comando Artisan Disponible

```bash
php artisan colppy:sync-clients
```

Este comando sincroniza manualmente los clientes de Colppy.

---

## 🚀 Pasos Siguientes (IMPORTANTE)

### Para XAMPP en Windows (Desarrollo):

1. **Abre el Programador de Tareas de Windows:**
   ```
   Presiona Win + R → escribe "taskschd.msc" → Enter
   ```

2. **Crear nueva tarea:**
   - Clic derecho → "Crear tarea básica..."
   - Nombre: `Laravel Scheduler - Strupeni`

3. **Configurar desencadenador:**
   - Tipo: Diariamente
   - Hora: 00:00
   - **Repetir cada: 1 minuto**
   - Durante: 1 día
   - ✅ Marcar "Repetir indefinidamente"

4. **Configurar acción:**
   - Programa/script:
     ```
     C:\xampp\htdocs\Proyects\Strupeni_Electronica\panel\SCHEDULER_WINDOWS.bat
     ```
   - Iniciar en:
     ```
     C:\xampp\htdocs\Proyects\Strupeni_Electronica\panel
     ```

5. **Ajustar condiciones:**
   - ❌ DESMARCAR "Iniciar solo si está conectado a la corriente"
   - ❌ DESMARCAR "Detener si deja de estar conectado"

6. **Guardar y probar:**
   - Clic derecho en la tarea → "Ejecutar"
   - Verifica logs: `storage/logs/scheduler.log`

### Para Linux/Producción:

1. **Editar crontab:**
   ```bash
   crontab -e
   ```

2. **Agregar línea:**
   ```bash
   * * * * * cd /var/www/strupeni/panel && php artisan schedule:run >> /dev/null 2>&1
   ```

3. **Verificar:**
   ```bash
   crontab -l
   ```

---

## 🧪 Probar la Configuración

### Opción 1: Script de Prueba (Windows)
```cmd
TEST_SCHEDULER.bat
```

### Opción 2: Comandos Manuales

**Ver tareas programadas:**
```bash
php artisan schedule:list
```

Salida esperada:
```
+---------+-----------+---------------------+----------------------------+
| Command | Interval  | Description         | Next Due                   |
+---------+-----------+---------------------+----------------------------+
|         | 0 * * * * | sync-colppy-clients | 2026-02-26 13:00:00 -03:00 |
+---------+-----------+---------------------+----------------------------+
```

**Ejecutar sincronización manualmente:**
```bash
php artisan colppy:sync-clients
```

**Ejecutar el scheduler manualmente:**
```bash
php artisan schedule:run
```

---

## 📊 Verificar que Funciona

### 1. Desde el Panel Web

- Ve a http://localhost/client
- Haz clic en "Estadísticas"
- Deberías ver:
  - Local Colppy: X
  - Colppy Total: X
  - Badge verde: "Sincronizado correctamente"

### 2. Desde los Logs

**Ver última sincronización:**
```bash
tail -f storage/logs/laravel-*.log | grep "Sincronizando clientes"
```

**Ver log del scheduler (Windows):**
```bash
type storage\logs\scheduler.log
```

### 3. Verificar en Base de Datos

```sql
SELECT COUNT(*) as total_colppy 
FROM clients 
WHERE is_from_colppy = 1;
```

Debería coincidir con el total de Colppy.

---

## ⚙️ Cambiar la Frecuencia

Edita `app/Console/Kernel.php`:

```php
// Cada 30 minutos
->everyThirtyMinutes()

// Cada hora (ACTUAL)
->hourly()

// Cada 2 horas
->everyTwoHours()

// Cada 15 minutos
->everyFifteenMinutes()

// Diariamente a las 2 AM
->dailyAt('02:00')

// De lunes a viernes a las 9 AM
->weekdays()->dailyAt('09:00')
```

Después de cambiar:
```bash
php artisan schedule:list
```

---

## 🐛 Solución de Problemas

### "No scheduled commands are ready to run"

✅ **Normal.** El scheduler solo ejecuta tareas cuando llega su hora programada.

Para forzar ejecución inmediata:
```bash
php artisan colppy:sync-clients
```

### El cron/tarea no se ejecuta

**Windows:**
1. Verifica que la tarea existe: Programador de Tareas
2. Verifica que está habilitada
3. Verifica la ruta del .bat
4. Ejecuta manualmente la tarea (clic derecho → Ejecutar)

**Linux:**
1. Verifica crontab: `crontab -l`
2. Verifica permisos: `chmod +x artisan`
3. Verifica logs de cron: `grep CRON /var/log/syslog`

### La sincronización falla

1. **Verifica credenciales Colppy** en `.env`:
   ```ini
   COLPPY_API_URL=https://api.colppy.com
   COLPPY_CLIENT_ID=tu_client_id
   COLPPY_CLIENT_SECRET=tu_client_secret
   ```

2. **Prueba manualmente:**
   ```bash
   php artisan colppy:sync-clients
   ```

3. **Revisa logs:**
   ```bash
   tail -100 storage/logs/laravel-*.log
   ```

---

## 📝 Comandos Útiles

```bash
# Ver tareas programadas
php artisan schedule:list

# Ejecutar scheduler manualmente
php artisan schedule:run

# Sincronizar clientes manualmente
php artisan colppy:sync-clients

# Ver logs en tiempo real
tail -f storage/logs/laravel-*.log

# Limpiar cachés
php artisan cache:clear
php artisan config:clear
```

---

## ✨ Resultado Final

Una vez configurado el cron/tarea programada:

1. ✅ Los clientes se sincronizarán **automáticamente cada hora**
2. ✅ No necesitas intervención manual
3. ✅ Los nuevos clientes de Colppy aparecerán en el panel
4. ✅ Los cambios se actualizarán automáticamente
5. ✅ Puedes ver estadísticas en tiempo real desde el panel

**¡La sincronización automática está lista!** 🎉

---

## 📚 Documentación

- **Configuración completa:** `CONFIGURAR_SCHEDULER.md`
- **Laravel Scheduler:** https://laravel.com/docs/scheduling
- **Cron expressions:** https://crontab.guru/
