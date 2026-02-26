# Configuración de Límites PHP para Upload de Archivos

## Problema
Por defecto, PHP tiene límites bajos para subir archivos (2MB típicamente).
Para videos, PDFs grandes y archivos multimedia, necesitas aumentar estos límites.

## Solución para XAMPP/Local

### Opción 1: Editar php.ini (Recomendado)

1. Abrir `C:\xampp\php\php.ini`
2. Buscar y modificar estas líneas:

```ini
upload_max_filesize = 50M
post_max_size = 55M
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
```

3. Reiniciar Apache desde XAMPP Control Panel

### Opción 2: .htaccess (Si no tienes acceso a php.ini)

Crear/editar archivo `.htaccess` en `panel/public/.htaccess`:

```apache
php_value upload_max_filesize 50M
php_value post_max_size 55M
php_value max_execution_time 300
php_value max_input_time 300
php_value memory_limit 256M
```

## Solución para Producción (Linux/cPanel)

### En cPanel:
1. Ir a "Select PHP Version" o "MultiPHP INI Editor"
2. Ajustar los valores:
   - `upload_max_filesize`: 50M
   - `post_max_size`: 55M
   - `max_execution_time`: 300
   - `memory_limit`: 256M

### En servidor con acceso SSH:

Editar `/etc/php/8.x/fpm/php.ini` (reemplaza 8.x con tu versión):

```bash
sudo nano /etc/php/8.2/fpm/php.ini
```

Cambiar los valores mencionados y reiniciar:

```bash
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx  # o apache2
```

## Verificación

Para verificar los límites actuales:

1. Crear archivo `info.php` en `panel/public/`:
```php
<?php phpinfo(); ?>
```

2. Abrir en navegador: `http://localhost/info.php`
3. Buscar `upload_max_filesize` y `post_max_size`
4. ⚠️ **ELIMINAR** el archivo después de verificar (seguridad)

## Tipos de Archivo Soportados

La aplicación ahora acepta:

- **Imágenes:** jpg, jpeg, png, gif, webp, svg, bmp, tiff
- **Videos:** mp4, avi, mov, wmv, flv, mkv, webm
- **Audio:** mp3, wav, ogg, m4a, aac, flac
- **Documentos:** pdf, doc, docx, xls, xlsx, ppt, pptx, txt

**Límite por archivo:** 50MB (configurable en MediaController.php)

## Notas

- `post_max_size` debe ser ligeramente mayor que `upload_max_filesize`
- Si subes múltiples archivos, considera `post_max_size` * cantidad de archivos
- Videos grandes (>50MB) necesitarán aumentar aún más estos límites
