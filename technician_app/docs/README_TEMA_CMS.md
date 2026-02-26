# 🎨 Tema Dinámico CMS - Guía Rápida

## ⚡ Inicio Rápido

### 1. Instalar dependencias

```bash
cd technician_app
flutter pub add provider http shared_preferences
flutter pub get
```

### 2. Configurar URL del API

Edita `lib/config/api_config.dart`:

```dart
// Para emulador Android:
static const String baseUrl = 'http://10.0.2.2/panel/api';

// Para dispositivo físico (cambia por tu IP):
static const String baseUrl = 'http://192.168.1.X/panel/api';
```

### 3. ¡Listo!

Los archivos ya están creados y configurados:
- ✅ `lib/models/cms_theme.dart`
- ✅ `lib/services/cms_theme_service.dart`
- ✅ `lib/providers/theme_provider.dart`
- ✅ `lib/main.dart` (actualizado)
- ✅ `lib/screens/theme_settings_screen.dart` (ejemplo)

## 🧪 Probar

1. **Ejecuta la app:**
   ```bash
   flutter run
   ```

2. **Verifica los logs:**
   ```
   🎨 Obteniendo tema CMS desde: http://...
   ✅ Tema cargado: Tema Strupeni - Azul v1.0.0
   ```

3. **Cambia el tema en el CMS:**
   - Ve a `http://localhost/panel/cms`
   - Edita el tema activo
   - Cambia colores

4. **Recarga en la app:**
   ```dart
   context.read<ThemeProvider>().reloadTheme();
   ```
   
   **¡El tema se actualiza sin reiniciar!** 🎉

## 📖 Uso

### Recargar tema manualmente

```dart
// En cualquier widget
final themeProvider = context.read<ThemeProvider>();
await themeProvider.reloadTheme();
```

### Acceder a colores del CMS

```dart
final colors = context.watch<ThemeProvider>().cmsTheme?.config.colors;

Container(
  color: colors?.primary,
  child: Text('Hola'),
)
```

### Screen de ejemplo

```dart
import 'screens/theme_settings_screen.dart';

// En tu navigation:
Navigator.push(
  context,
  MaterialPageRoute(builder: (_) => ThemeSettingsScreen()),
);
```

## ⚙️ Cómo Funciona

1. **Al iniciar**: `ThemeProvider` descarga el tema del endpoint `/api/flutter/theme`
2. **Se parsea el JSON** y se convierte en `ThemeData` de Flutter
3. **MaterialApp usa el tema** dinámico
4. **Se guarda en caché** para uso offline
5. **Cuando llamas a `reloadTheme()`**, descarga el nuevo tema y **actualiza la UI automáticamente**

## 🔍 Endpoint del API

```
GET /api/flutter/theme

Response:
{
  "name": "Tema Strupeni - Azul",
  "version": "1.0.0",
  "config": {
    "colors": { ... },
    "typography": { ... },
    "spacing": { ... }
  }
}
```

## 🚨 Problemas Comunes

**"No hay tema activo"**
```bash
cd panel
php artisan db:seed --class=CmsContentSeeder
```

**Timeout**
- Verifica la URL en `api_config.dart`
- En emulador Android usa `http://10.0.2.2` en lugar de `localhost`

**Los cambios no aparecen**
- Asegúrate de llamar a `reloadTheme()`
- Verifica que el tema esté activado en el CMS

## 📚 Documentación Completa

Ver `INTEGRACION_CMS_FLUTTER.md` para la documentación detallada.

---

**¡Disfruta de los temas dinámicos!** 🚀
