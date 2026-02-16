# Integración Flutter con CMS - Temas Dinámicos

## 📱 Descripción General

El sistema permite que la aplicación Flutter obtenga y aplique temas de forma dinámica desde el CMS sin necesidad de recompilar la aplicación. Los cambios de tema se aplican mediante **Hot Reload** automático.

---

## 🚀 Instalación

### 1. Dependencias Requeridas

Agrega las siguientes dependencias al `pubspec.yaml`:

```yaml
dependencies:
  flutter:
    sdk: flutter
  provider: ^6.0.5
  http: ^1.1.0
  shared_preferences: ^2.2.2
```

Ejecuta:
```bash
flutter pub get
```

### 2. Archivos Creados

Los siguientes archivos han sido creados automáticamente:

```
lib/
├── models/
│   └── cms_theme.dart          # Modelo de datos del tema
├── services/
│   └── cms_theme_service.dart  # Servicio para consumir API
├── providers/
│   └── theme_provider.dart     # Provider para manejo de estado
└── main.dart                    # Actualizado con integración
```

---

## 🔧 Configuración

### Actualizar URL de la API

Edita `lib/config/api_config.dart`:

```dart
// Para desarrollo local (emulador Android):
static const String baseUrl = 'http://10.0.2.2/panel/api';

// Para desarrollo local (dispositivo físico):
static const String baseUrl = 'http://TU_IP_LOCAL/panel/api';

// Para producción:
static const String baseUrl = 'https://tecnicos.strupeni.com.ar/api';
```

**Importante:** El endpoint `/flutter/theme` se agrega automáticamente al baseUrl.

---

## 📖 Uso Básico

### Carga Automática del Tema

El tema se carga automáticamente al iniciar la app:

```dart
// Ya está configurado en main.dart
MultiProvider(
  providers: [
    ChangeNotifierProvider(create: (_) => ThemeProvider()..loadTheme()),
    // ... otros providers
  ],
  // ...
)
```

### Recargar Tema Manualmente

Para actualizar el tema sin reiniciar la app:

```dart
// En cualquier widget
context.read<ThemeProvider>().reloadTheme();
```

### Ejemplo de Botón para Recargar Tema

```dart
ElevatedButton(
  onPressed: () async {
    final themeProvider = context.read<ThemeProvider>();
    await themeProvider.reloadTheme();
    
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Tema actualizado')),
    );
  },
  child: Text('Actualizar Tema'),
)
```

---

## 🎨 Estructura del Tema JSON

El CMS devuelve el tema en este formato:

```json
{
  "name": "Tema Strupeni - Azul",
  "version": "1.0.0",
  "config": {
    "colors": {
      "primary": "#007bff",
      "secondary": "#6c757d",
      "accent": "#28a745",
      "background": "#ffffff",
      "surface": "#f8f9fa",
      "error": "#dc3545",
      "success": "#28a745",
      "warning": "#ffc107",
      "info": "#17a2b8",
      "textPrimary": "#212529",
      "textSecondary": "#6c757d",
      "textOnPrimary": "#ffffff"
    },
    "typography": {
      "fontFamily": "Roboto",
      "fontSize": {
        "headline1": 96,
        "headline2": 60,
        "body1": 16,
        "button": 14
      }
    },
    "spacing": {
      "xs": 4,
      "sm": 8,
      "md": 16,
      "lg": 24
    },
    "buttons": {
      "height": 48,
      "borderRadius": 8,
      "elevation": 2
    },
    "cards": {
      "borderRadius": 12,
      "elevation": 2,
      "padding": 16
    }
  }
}
```

---

## 🔄 Hot Reload sin Recompilación

### ¿Cómo Funciona?

1. **Cambias el tema en el CMS** (colores, tamaños, etc.)
2. **En la app Flutter**, llamas a `reloadTheme()`
3. **El tema se actualiza INSTANTÁNEAMENTE** sin rebuild

### Ejemplo de Implementación

```dart
class SettingsScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Configuración')),
      body: Consumer<ThemeProvider>(
        builder: (context, themeProvider, _) {
          return ListView(
            children: [
              ListTile(
                title: Text('Tema Actual'),
                subtitle: Text(
                  themeProvider.cmsTheme?.name ?? 'Tema por defecto'
                ),
              ),
              ListTile(
                title: Text('Versión'),
                subtitle: Text(
                  themeProvider.cmsTheme?.version ?? 'N/A'
                ),
              ),
              ElevatedButton.icon(
                icon: Icon(Icons.refresh),
                label: Text('Actualizar Tema'),
                onPressed: themeProvider.isLoading 
                  ? null 
                  : () => themeProvider.reloadTheme(),
              ),
              if (themeProvider.isLoading)
                LinearProgressIndicator(),
            ],
          );
        },
      ),
    );
  }
}
```

---

## 🎯 Acceso a Colores del Tema

### En Widgets

```dart
// Obtener el tema actual
final theme = Theme.of(context);

// Usar colores del CMS
Container(
  color: theme.primaryColor,
  child: Text(
    'Hola',
    style: TextStyle(color: theme.colorScheme.onPrimary),
  ),
)
```

### Acceso Directo al CMS Theme

```dart
final themeProvider = context.watch<ThemeProvider>();
final colors = themeProvider.cmsTheme?.config.colors;

if (colors != null) {
  Container(
    color: colors.accent,
    child: Text('Color de acento del CMS'),
  )
}
```

---

## 📦 Caché Local

El tema se guarda automáticamente en `SharedPreferences` para:
- **Uso offline**: Si no hay conexión, usa el último tema descargado
- **Carga rápida**: Muestra el tema anterior mientras descarga el nuevo
- **Fallback**: Si falla la descarga, usa el tema en caché

---

## 🔍 Debug y Logs

El sistema imprime logs útiles:

```
🎨 Obteniendo tema CMS desde: http://...
📡 Response status: 200
✅ Tema cargado: Tema Strupeni - Azul v1.0.0
```

En caso de error:
```
❌ Error al obtener tema: 404
⚠️ Usando tema en caché
```

---

## 🧪 Pruebas

### Probar la Integración

1. **Inicia la app Flutter**
   ```bash
   flutter run
   ```

2. **Verifica los logs**
   - Deberías ver: `✅ Tema cargado: ...`

3. **Cambia colores en el CMS**
   - Ve a `/cms` → Temas Flutter
   - Edita el tema activo
   - Cambia el color primary de `#007bff` a `#ff0000` (rojo)
   - Guarda y activa

4. **En la app, recarga el tema**
   - Llama a `reloadTheme()`
   - **Los colores cambian INSTANTÁNEAMENTE** 🎉

### Sin conexión

1. Desconecta internet
2. Reinicia la app
3. Debería cargar el tema en caché
4. Los logs mostrarán: `📦 Tema cargado desde caché`

---

## 🚨 Solución de Problemas

### Error: "No hay tema activo en el CMS"

**Causa:** No existe un tema marcado como "activo" en la base de datos.

**Solución:**
```bash
cd panel
php artisan db:seed --class=CmsContentSeeder
```

### Error: "Timeout al obtener tema"

**Causa:** La URL del API es incorrecta o el servidor no responde.

**Solución:**
1. Verifica la URL en `api_config.dart`
2. Prueba el endpoint manualmente:
   ```bash
   curl http://TU_URL/panel/api/flutter/theme
   ```

### Los cambios no se reflejan

**Causa:** El tema no se está recargando.

**Solución:**
1. Llama explícitamente a `reloadTheme()`
2. Verifica que el tema esté marcado como activo en el CMS
3. Revisa los logs de la consola

### Error de parsing JSON

**Causa:** El formato del JSON del tema no es válido.

**Solución:**
1. Ve al CMS → Editar Tema
2. Valida el JSON con un validador online
3. Asegúrate de que todos los campos requeridos existen

---

## 📝 Notas Importantes

1. **Sin rebuild requerido**: Los cambios se aplican con hot reload
2. **Funcionamiento offline**: El último tema se guarda en caché
3. **Fallback automático**: Si falla, usa el tema por defecto
4. **Versión del tema**: Úsala para detectar actualizaciones
5. **Material 3**: El tema usa Material Design 3 por defecto

---

## 🔗 Endpoints del API

### Obtener Tema Activo

```
GET /api/flutter/theme
```

**Response exitoso (200):**
```json
{
  "name": "Tema Strupeni - Azul",
  "version": "1.0.0",
  "config": { ... }
}
```

**Sin tema activo (404):**
```json
{
  "error": "No hay tema activo configurado"
}
```

---

## 🎓 Próximos Pasos

1. ✅ Implementar verificación automática de actualizaciones
2. ✅ Agregar soporte para temas claros/oscuros
3. ✅ Permitir múltiples temas y selección del usuario
4. ✅ Agregar animaciones en el cambio de tema

---

## 📞 Soporte

Si tienes problemas con la integración:
- Revisa los logs en la consola de Flutter
- Verifica que el endpoint retorne JSON válido
- Asegúrate de que las dependencias estén instaladas

---

**¡Listo!** Tu app Flutter ahora puede actualizar su tema dinámicamente desde el CMS sin necesidad de recompilar. 🚀
