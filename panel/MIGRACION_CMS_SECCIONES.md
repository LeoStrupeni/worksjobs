# MIGRACIÓN A NUEVO SISTEMA CMS CON SECCIONES

## ✅ ARCHIVOS CREADOS

### Migraciones:
1. `2026_02_09_000001_create_cms_sections_table.php` - Tabla principal de secciones
2. `2026_02_09_000002_create_cms_section_versions_table.php` - Versionado de secciones
3. `2026_02_09_000003_drop_old_cms_tables.php` - Elimina tablas viejas

### Modelos:
1. `app/Models/CmsSection.php` - Modelo de secciones con cast JSON
2. `app/Models/CmsSectionVersion.php` - Modelo de versiones

### Controladores:
1. `app/Http/Controllers/SectionController.php` - Manejo completo de secciones

### Seeders:
1. `database/seeders/CmsSectionsSeeder.php` - Crea 9 secciones con configs completas

### Vistas:
1. `resources/views/cms/sections/index.blade.php` - Vista principal CMS

---

## 🚀 PASOS PARA MIGRAR

### 1. **Hacer Backup de la Base de Datos** (IMPORTANTE)
```bash
# Desde phpMyAdmin o línea de comandos
mysqldump -u u939320192_jobDev -p u939320192_jobDev > backup_antes_migracion.sql
```

### 2. **Ejecutar Migraciones**
```bash
cd panel
php artisan migrate
```

Esto creará:
- ✅ `cms_sections` (9 secciones)
- ✅ `cms_section_versions` (historial)
- ❌ DROP `cms_pages`, `cms_page_versions`, `cms_flutter_themes`, `cms_configs`

### 3. **Ejecutar Seeder**
```bash
php artisan db:seed --class=CmsSectionsSeeder
```

Esto creará las 9 secciones:
1. ✅ **general** - Configuración global
2. ✅ **header** - Migra datos de cms_configs (logo, redes)
3. ✅ **carousel** - Carrusel de imágenes
4. ✅ **historia** - Nuestra historia
5. ✅ **servicios** - Carrusel de servicios
6. ✅ **banner** - Banner empresa
7. ✅ **instagram** - Feed Instagram
8. ✅ **footer** - Pie de página
9. ✅ **flutter_theme** - Tema app móvil

### 4. **Verificar**
```bash
# Revisar que las tablas existen
php artisan tinker
>>> \App\Models\CmsSection::count()  # Debe retornar 9
>>> \App\Models\CmsSection::pluck('name', 'slug')
```

### 5. **Acceder al CMS**
Ir a: http://localhost/panel/cms

Deberías ver 9 cards con las secciones configurables.

---

## 📊 ESTRUCTURA DE DATOS

### Tabla `cms_sections`:
```sql
id, name, slug, config (JSON), order, is_active, created_at, updated_at
```

### Ejemplo de `config` JSON para Header:
```json
{
  "logo": "/assets/media/logos/logo.png",
  "logo_alt": "Strupeni Electrónica",
  "background_color": "#ffffff",
  "text_color": "#1f2937",
  "text_hover_color": "#667eea",
  "menu": [...],
  "social": {
    "facebook": {"url": "...", "active": true, "color": "#1877f2"},
    "instagram": {"url": "...", "active": true, "color": "#e4405f"},
    "linkedin": {"url": "...", "active": true, "color": "#0a66c2"}
  }
}
```

---

## 🔥 DATOS MIGRADOS AUTOMÁTICAMENTE

El seeder **migra automáticamente** estos datos de `cms_configs`:
- ✅ `header.logo` → `header.config.logo`
- ✅ `header.facebook_url` → `header.config.social.facebook.url`
- ✅ `header.instagram_url` → `header.config.social.instagram.url`
- ✅ `header.linkedin_url` → `header.config.social.linkedin.url`

---

## ⚠️ TABLAS ELIMINADAS

Estas tablas se **eliminarán permanentemente**:
- ❌ `cms_pages`
- ❌ `cms_page_versions`
- ❌ `cms_flutter_themes`
- ❌ `cms_configs`

**Todos los datos útiles ya fueron migrados al JSON de las secciones.**

---

## 🛑 SI ALGO SALE MAL

### Revertir migración:
```bash
php artisan migrate:rollback --step=3
```

### Restaurar desde backup:
```bash
mysql -u u939320192_jobDev -p u939320192_jobDev < backup_antes_migracion.sql
```

---

## 📝 PRÓXIMOS PASOS

Después de migrar exitosamente:

1. ✅ Crear vistas de edición para cada sección
2. ✅ Formularios específicos por tipo de sección
3. ✅ Vista de versiones con diff visual
4. ✅ Actualizar `home.blade.php` para usar `$sections` en lugar de `$configs`
5. ✅ Renderizar las 7 secciones del PDF en el frontend

---

## 📞 SOPORTE

Si algo no funciona correctamente:
1. Revisar logs: `storage/logs/laravel.log`
2. Verificar permisos de base de datos
3. Comprobar que todas las migraciones se ejecutaron
