# 📋 Instrucciones para Configurar Storage en Producción

## 🎯 Problema resuelto en local

El proyecto tiene una estructura **no estándar** de Laravel:
- **Carpeta pública web**: `/public/` (raíz del proyecto)
- **Laravel**: `/panel/` (subcarpeta)
- **Storage**: `/panel/storage/app/public/`

Por defecto, Laravel espera que `public/` esté dentro de la carpeta de Laravel, pero aquí están separadas.

## ✅ Solución implementada (Local)

Se creó un **junction** (symlink en Windows) que conecta:

```
/public/storage  →  /panel/storage/app/public
```

Este junction permite que las URLs como:
```
http://localhost/storage/cms-media/imagen.png
```

Apunten realmente a:
```
/panel/storage/app/public/cms-media/imagen.png
```

---

## 🚀 Pasos para Producción (Servidor Linux)

### 1. **Conectarse al servidor vía SSH**

```bash
ssh usuario@tu-servidor.com
cd /ruta/completa/a/Strupeni_Electronica
```

### 2. **Verificar la estructura de carpetas**

```bash
ls -la
# Deberías ver:
# - panel/          (Laravel)
# - public/         (DocumentRoot de Apache/Nginx)
# - technician_app/ (Flutter)
```

### 3. **Crear el symlink en Linux**

```bash
# Desde la raíz del proyecto
ln -s panel/storage/app/public public/storage
```

O con rutas absolutas:

```bash
# Reemplaza /var/www/html/Strupeni_Electronica con tu ruta real
ln -s /var/www/html/Strupeni_Electronica/panel/storage/app/public \
      /var/www/html/Strupeni_Electronica/public/storage
```

### 4. **Verificar que el symlink funciona**

```bash
ls -la public/storage
# Deberías ver: storage -> ../panel/storage/app/public

# Verificar acceso a las imágenes
ls -la public/storage/cms-media/
```

### 5. **Configurar permisos correctos**

```bash
# Dar permisos al storage completo
chmod -R 775 panel/storage
chown -R www-data:www-data panel/storage

# Verificar que Apache/Nginx pueda leer el symlink
chmod 755 public/storage
```

### 6. **Configurar .env en producción**

```env
APP_URL=https://tudominio.com
FILESYSTEM_DISK=public
```

### 7. **Subir las imágenes existentes**

Las imágenes deben estar en:
```
/panel/storage/app/public/cms-media/
```

Transferir con SFTP o:
```bash
scp -r local/panel/storage/app/public/cms-media/* \
    usuario@servidor:/ruta/panel/storage/app/public/cms-media/
```

---

## 🔍 Verificación en Producción

### Probar acceso HTTP:
```bash
curl -I https://tudominio.com/storage/cms-media/test-image.png
# Debe devolver: HTTP/2 200
```

### Si obtienes 404:
1. Verificar que el symlink existe: `ls -la public/storage`
2. Verificar permisos: `ls -la panel/storage/app/public`
3. Revisar configuración de Apache/Nginx para seguir symlinks

---

## ⚙️ Configuración Apache (si aplica)

En el VirtualHost o `.htaccess`:

```apache
<Directory /var/www/html/Strupeni_Electronica/public>
    Options +FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

## ⚙️ Configuración Nginx (si aplica)

```nginx
server {
    root /var/www/html/Strupeni_Electronica/public;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Los symlinks funcionan automáticamente en Nginx
}
```

---

## 📂 Estructura final esperada

```
/var/www/html/Strupeni_Electronica/
├── panel/
│   ├── storage/
│   │   └── app/
│   │       └── public/
│   │           └── cms-media/          ← Archivos físicos aquí
│   │               ├── imagen1.png
│   │               └── imagen2.png
│   └── ...
├── public/
│   ├── storage -> ../panel/storage/app/public  ← Symlink
│   ├── index.php
│   └── assets/
└── ...
```

---

## ✅ Checklist Final

- [ ] SSH al servidor
- [ ] Crear symlink `public/storage → panel/storage/app/public`
- [ ] Configurar permisos 775 en `panel/storage`
- [ ] Subir imágenes a `panel/storage/app/public/cms-media/`
- [ ] Configurar `APP_URL` correcto en `.env`
- [ ] Verificar Apache/Nginx permite FollowSymLinks
- [ ] Probar acceso HTTP: `https://tudominio.com/storage/cms-media/test.png`
- [ ] Verificar modal de librería de medios carga imágenes
- [ ] Probar seleccionar y guardar imagen desde el CMS

---

## 🆘 Troubleshooting

### Error: "Symlink not allowed" o "Permission denied"
```bash
# En el servidor, habilitar symlinks en Apache
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Error: Las imágenes no cargan (403 Forbidden)
```bash
# Ajustar permisos
sudo chown -R www-data:www-data panel/storage
sudo chmod -R 755 panel/storage/app/public
```

### El symlink no funciona
```bash
# Eliminar y recrear
rm public/storage
ln -sfn /ruta/absoluta/panel/storage/app/public public/storage
```

---

**Fecha de creación**: 9 febrero 2026  
**Ambiente testeado**: Windows (local) con junction, Linux (producción) con symlink
