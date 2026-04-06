# Versionado de la App Flutter - Strupeni Técnicos

**Fecha**: 23/03/2026  
**Ubicación**: `technician_app/pubspec.yaml`

---

## 📋 Índice

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [¿Por Qué Necesito Versionar?](#por-qué-necesito-versionar)
3. [Formato de Versionado en Flutter](#formato-de-versionado-en-flutter)
4. [Cómo Cambiar la Versión](#cómo-cambiar-la-versión)
5. [Cuándo Incrementar Cada Parte](#cuándo-incrementar-cada-parte)
6. [Ejemplos Prácticos](#ejemplos-prácticos)
7. [Verificar la Versión de una App Compilada](#verificar-la-versión-de-una-app-compilada)
8. [Troubleshooting](#troubleshooting)
9. [Mejores Prácticas](#mejores-prácticas)

---

## Resumen Ejecutivo

**Problema**: Cada compilación genera versión `1.0.0` sin importar los cambios.

**Solución**: Cambiar manualmente la versión en `pubspec.yaml` **ANTES** de compilar.

**Ubicación**: `technician_app/pubspec.yaml` → línea `version: X.Y.Z+N`

**Formato**: `MAJOR.MINOR.PATCH+BUILD_NUMBER`
- **Ejemplo**: `1.2.3+15`
  - Versión visible al usuario: `1.2.3`
  - Número interno de build: `15`

---

## ¿Por Qué Necesito Versionar?

### Problemas sin Versionado Correcto

❌ **Google Play Store rechaza la APK**: Si intentas subir una APK con el mismo `versionCode` que una anterior, Google Play la rechaza.

❌ **No sabes qué versión tiene un usuario**: Si todos tienen "1.0.0", no puedes saber si tienen la última versión.

❌ **Dificulta el soporte**: No puedes decir "actualiza a la 1.2.0 para solucionar el problema".

❌ **No puedes hacer updates incrementales**: Android requiere que el `versionCode` siempre aumente.

### Beneficios con Versionado Correcto

✅ **Publicación en Play Store**: Cada build tiene un `versionCode` único e incremental.

✅ **Soporte más fácil**: "¿Qué versión tienes?" → "1.3.5" → Sabes exactamente qué código tiene.

✅ **Changelog claro**: Puedes comunicar qué cambió en cada versión.

✅ **Updates automáticos**: Play Store puede notificar a usuarios que hay una nueva versión.

✅ **Pruebas organizadas**: "Esta versión 1.4.0-beta tiene X bug, corregir en 1.4.1".

---

## Formato de Versionado en Flutter

En Flutter, la versión se define en `pubspec.yaml` con este formato:

```yaml
version: MAJOR.MINOR.PATCH+BUILD_NUMBER
```

### Parte 1: Versión Semántica (MAJOR.MINOR.PATCH)

Esta es la versión **visible al usuario**.

**Componentes**:
- **MAJOR**: Cambios incompatibles / rediseño completo
- **MINOR**: Nuevas funcionalidades compatibles
- **PATCH**: Bug fixes / correcciones pequeñas

**Ejemplo**: `1.2.3`
- `1` = versión mayor
- `2` = 2 releases de funcionalidades desde 1.0.0
- `3` = 3 parches desde 1.2.0

### Parte 2: Build Number (+BUILD_NUMBER)

Este es el **número interno de compilación**.

- **Android**: Se convierte en `versionCode` (entero incremental)
- **iOS**: Se convierte en `CFBundleVersion`
- **Debe incrementarse en CADA compilación** (aunque no cambies la versión visible)

**Ejemplo**: `1.2.3+15`
- Versión visible: `1.2.3`
- Build number: `15` (es la compilación número 15)

---

## Cómo Cambiar la Versión

### Paso 1: Abrir pubspec.yaml

```bash
cd technician_app
notepad pubspec.yaml  # o tu editor preferido
```

### Paso 2: Buscar la línea "version"

```yaml
version: 1.0.0+1
```

### Paso 3: Cambiar según el tipo de cambio

**Ejemplo - Nuevo feature**:
```yaml
# ANTES
version: 1.0.0+1

# DESPUÉS (incrementar MINOR y BUILD_NUMBER)
version: 1.1.0+2
```

**Ejemplo - Bug fix**:
```yaml
# ANTES
version: 1.1.0+2

# DESPUÉS (incrementar PATCH y BUILD_NUMBER)
version: 1.1.1+3
```

**Ejemplo - Rediseño completo**:
```yaml
# ANTES
version: 1.5.2+20

# DESPUÉS (incrementar MAJOR, resetear MINOR y PATCH, incrementar BUILD_NUMBER)
version: 2.0.0+21
```

### Paso 4: Guardar y compilar

```bash
flutter clean
flutter pub get
flutter build apk --release  # o flutter build appbundle
```

**IMPORTANTE**: ¡La versión ya está en la APK compilada!

---

## Cuándo Incrementar Cada Parte

### MAJOR (el primer número)

**Incrementar cuando**:
- ✅ Rediseño completo de la UI
- ✅ Cambios incompatibles (se elimina funcionalidad)
- ✅ Nuevo flujo de autenticación (rompe versiones anteriores)
- ✅ Migración a nueva arquitectura

**Ejemplo**: `1.5.0` → `2.0.0`

**Mensaje al usuario**: "Nueva versión mayor con cambios importantes"

---

### MINOR (el segundo número)

**Incrementar cuando**:
- ✅ Nuevo feature/funcionalidad
- ✅ Nueva pantalla
- ✅ Nueva API integrada
- ✅ Mejoras de performance visibles

**Ejemplo**: `1.2.0` → `1.3.0`

**Mensaje al usuario**: "Nueva funcionalidad: Sistema de productos en tareas"

---

### PATCH (el tercer número)

**Incrementar cuando**:
- ✅ Bug fix
- ✅ Corrección de crash
- ✅ Mejora de texto/wording
- ✅ Ajustes visuales menores

**Ejemplo**: `1.3.0` → `1.3.1`

**Mensaje al usuario**: "Correcciones de errores menores"

---

### BUILD_NUMBER (el número después del +)

**Incrementar SIEMPRE** en cada compilación.

**Ejemplo**: `1.3.1+10` → `1.3.1+11` (aunque no cambies la versión visible)

**Razón**: Android/iOS requieren que siempre aumente.

---

## Ejemplos Prácticos

### Escenario 1: Primera Versión

```yaml
version: 1.0.0+1
```
- Primera compilación
- Versión visible: `1.0.0`
- Build number: `1`

---

### Escenario 2: Corrección de Bug (Sin nueva funcionalidad)

```yaml
# Versión actual
version: 1.0.0+1

# Cambias a
version: 1.0.1+2
```
- Incrementaste PATCH (bug fix)
- Incrementaste BUILD_NUMBER (compilación 2)

**Changelog**:
- `v1.0.1` - Corrección: Error al cargar citas

---

### Escenario 3: Nuevo Feature (Sistema de Productos)

```yaml
# Versión actual
version: 1.0.1+2

# Cambias a
version: 1.1.0+3
```
- Incrementaste MINOR (nueva funcionalidad)
- Reseteaste PATCH a 0
- Incrementaste BUILD_NUMBER (compilación 3)

**Changelog**:
- `v1.1.0` - Nuevo: Sistema de productos en tareas
- `v1.1.0` - Mejora: Pantalla de debug oculta

---

### Escenario 4: Más Bugs Encontrados

```yaml
# Versión actual
version: 1.1.0+3

# Bug fix 1
version: 1.1.1+4

# Bug fix 2
version: 1.1.2+5

# Bug fix 3
version: 1.1.3+6
```

---

### Escenario 5: Otro Nuevo Feature

```yaml
# Versión actual
version: 1.1.3+6

# Nuevo feature: Notificaciones push
version: 1.2.0+7
```

---

### Escenario 6: Rediseño Completo (v2.0)

```yaml
# Versión actual
version: 1.5.2+25

# Rediseño completo de la app
version: 2.0.0+26
```

---

## Verificar la Versión de una App Compilada

### Android (APK/AAB)

**Método 1: Desde el código**

Agregar en cualquier pantalla (ej: login):
```dart
import 'package:package_info_plus/package_info_plus.dart';

// En initState o similar
PackageInfo packageInfo = await PackageInfo.fromPlatform();
String version = packageInfo.version;        // "1.2.3"
String buildNumber = packageInfo.buildNumber; // "15"

print('Versión: $version ($buildNumber)');
```

**Método 2: Desde Android Studio**

1. `Build` → `Build Bundle(s) / APK(s)` → `Build APK`
2. Abrir el APK con un extractor (WinRAR, 7zip)
3. Buscar `AndroidManifest.xml` → Ver `android:versionName` y `android:versionCode`

**Método 3: Desde el dispositivo**

1. Instalar la APK
2. `Configuración` → `Apps` → `Strupeni Técnicos`
3. Ver "Versión"

---

### iOS (IPA)

**Método 1: Desde Xcode**

1. Abrir `ios/Runner.xcworkspace` en Xcode
2. Seleccionar target "Runner"
3. General → Identity → Ver `Version` y `Build`

**Método 2: Desde el dispositivo**

1. `Ajustes` → `General` → `Información` → `[Nombre App]`
2. Ver "Versión"

---

## Troubleshooting

### Problema 1: "Cada compilación sigue siendo 1.0.0"

**Causa**: No cambiaste la versión en `pubspec.yaml` antes de compilar.

**Solución**:
1. Abrir `pubspec.yaml`
2. Cambiar línea `version: X.Y.Z+N`
3. Guardar
4. Compilar

---

### Problema 2: "Google Play rechaza mi APK - versionCode duplicado"

**Causa**: El `BUILD_NUMBER` (número después del `+`) no se incrementó.

**Solución**:
```yaml
# ANTES
version: 1.2.0+5

# DESPUÉS (incrementar +1 al build number)
version: 1.2.0+6
```

**IMPORTANTE**: El build number NUNCA debe repetirse.

---

### Problema 3: "Olvidé cambiar la versión antes de compilar"

**Causa**: Ya compilaste con versión incorrecta.

**Solución**:
1. Cambiar versión en `pubspec.yaml`
2. Hacer `flutter clean`
3. Compilar nuevamente

**Nota**: La APK anterior ya tiene la versión equivocada. No la uses.

---

### Problema 4: "No sé qué número usar"

**Causa**: No hay convención establecida.

**Solución**:
- **Para la primera vez**: Usar `1.0.0+1`
- **Cada release público**: Incrementar MINOR o MAJOR según sea feature o rediseño
- **Cada bug fix**: Incrementar PATCH
- **Cada compilación** (incluso para testing): Incrementar BUILD_NUMBER

---

### Problema 5: "Quiero hacer versiones de testing (beta)"

**Solución**: Usar convención pre-release en el nombre:
```yaml
version: 1.2.0-beta.1+10
version: 1.2.0-rc.1+11   # Release Candidate
version: 1.2.0+12        # Versión final
```

Flutter acepta este formato, pero el `-beta.1` no aparece en Android/iOS por defecto.

---

## Mejores Prácticas

### ✅ 1. Cambiar la Versión ANTES de Compilar

```bash
# 1. Cambiar pubspec.yaml
version: 1.3.0+15

# 2. Compilar
flutter build apk --release
```

**NO** compilar primero y luego cambiar la versión (ya es tarde).

---

### ✅ 2. Incrementar SIEMPRE el Build Number

Aunque solo cambies un color, `+N` debe aumentar.

```yaml
# Versión actual
version: 1.2.0+10

# Cambio menor (color de botón)
version: 1.2.0+11  # ✅ CORRECTO (build number aumentó)

# ❌ INCORRECTO
version: 1.2.0+10  # Error: build number no cambió
```

---

### ✅ 3. Documentar los Cambios (Changelog)

Crear archivo `CHANGELOG.md`:
```markdown
# Changelog

## [1.3.0] - 2026-03-23
### Agregado
- Sistema de debug oculto (tap 5 veces en el logo)
- Logging local (últimos 100 logs)
- Retry automático en peticiones HTTP

### Corregido
- Error al cargar citas sin conexión

## [1.2.0] - 2026-03-15
### Agregado
- Sistema de productos en tareas
```

---

### ✅ 4. Usar Git Tags para Versiones

```bash
git tag v1.3.0
git push origin v1.3.0
```

Esto te permite volver a cualquier versión específica.

---

### ✅ 5. Probar Antes de Publicar

```bash
# Compilar versión de debug primero
flutter build apk --debug

# Probar en dispositivo real
flutter install

# Si todo OK, compilar release
flutter build apk --release
```

---

### ✅ 6. Convención de Commit Messages

```bash
git commit -m "feat: Sistema de debug con logging local (v1.3.0)"
git commit -m "fix: Corrección de timeout en getJobs (v1.2.1)"
git commit -m "refactor: Migración a NetworkHelper (v1.2.0)"
```

---

## Resumen Rápido (Cheatsheet)

| Acción | Versión Actual | Nueva Versión |
|--------|----------------|---------------|
| Primera compilación | - | `1.0.0+1` |
| Bug fix | `1.0.0+1` | `1.0.1+2` |
| Nuevo feature | `1.0.1+2` | `1.1.0+3` |
| Otro feature | `1.1.0+3` | `1.2.0+4` |
| Bug fix | `1.2.0+4` | `1.2.1+5` |
| Rediseño completo | `1.2.1+5` | `2.0.0+6` |
| Compilación testing | `1.2.1+5` | `1.2.1+6` (solo build) |

---

## Comandos Útiles

### Ver versión actual

```bash
grep "version:" pubspec.yaml
```

### Cambiar versión con sed (Linux/Mac)

```bash
sed -i 's/version: .*/version: 1.3.0+15/' pubspec.yaml
```

### Cambiar versión con PowerShell (Windows)

```powershell
(Get-Content pubspec.yaml) -replace 'version: .*', 'version: 1.3.0+15' | Set-Content pubspec.yaml
```

### Compilar con versión específica (override)

```bash
flutter build apk --build-name=1.3.0 --build-number=15
```

**Nota**: Esto NO cambia `pubspec.yaml`, solo la compilación actual.

---

## Flujo Completo Recomendado

### Paso 1: Feature Completo

```bash
git commit -m "feat: Sistema de productos en tareas"
```

### Paso 2: Actualizar Versión

```yaml
# pubspec.yaml
version: 1.3.0+15  # Cambiar aquí
```

```bash
git add pubspec.yaml
git commit -m "chore: bump version to 1.3.0+15"
```

### Paso 3: Crear Tag

```bash
git tag v1.3.0
git push origin main
git push origin v1.3.0
```

### Paso 4: Compilar

```bash
flutter clean
flutter pub get
flutter build appbundle --release  # Para Play Store
# o
flutter build apk --release  # Para distribución directa
```

### Paso 5: Documentar

```markdown
# CHANGELOG.md
## [1.3.0] - 2026-03-23
### Agregado
- Sistema de productos en tareas con búsqueda en vivo
- Validación de estado (no editar si cerrado Y archivado)
- Sistema de unique_id para productos duplicados
```

### Paso 6: Distribuir

- Subir a Play Store / TestFlight
- O enviar APK directamente a técnicos

---

## Dependencia Recomendada: package_info_plus

Para mostrar la versión dentro de la app:

### Instalación

```yaml
# pubspec.yaml
dependencies:
  package_info_plus: ^5.0.1
```

```bash
flutter pub get
```

### Uso

```dart
import 'package:package_info_plus/package_info_plus.dart';

Future<void> showVersionInfo(BuildContext context) async {
  final packageInfo = await PackageInfo.fromPlatform();
  
  showDialog(
    context: context,
    builder: (context) => AlertDialog(
      title: Text('Información de la App'),
      content: Text(
        'Versión: ${packageInfo.version}\n'
        'Build: ${packageInfo.buildNumber}\n'
        'Nombre: ${packageInfo.appName}',
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: Text('Cerrar'),
        ),
      ],
    ),
  );
}
```

Agregar en `Settings` o `About`:
```dart
ListTile(
  title: Text('Versión'),
  subtitle: FutureBuilder<PackageInfo>(
    future: PackageInfo.fromPlatform(),
    builder: (context, snapshot) {
      if (!snapshot.hasData) return Text('Cargando...');
      return Text('${snapshot.data!.version} (${snapshot.data!.buildNumber})');
    },
  ),
),
```

---

## Recursos Adicionales

- [Flutter Versioning Documentation](https://flutter.dev/docs/deployment/android#versioning-the-app)
- [Semantic Versioning Spec](https://semver.org/)
- [Google Play Version Codes](https://developer.android.com/studio/publish/versioning)

---

## Resumen Final

1. ✅ **Cambiar `version:` en `pubspec.yaml` ANTES de compilar**
2. ✅ **Incrementar SIEMPRE el build number (+N)**
3. ✅ **Documentar cambios en CHANGELOG.md**
4. ✅ **Usar Git tags para releases**
5. ✅ **Probar antes de publicar**

**Fórmula rápida**:
- Bug fix → PATCH+1, BUILD+1
- Feature → MINOR+1, PATCH=0, BUILD+1
- Rediseño → MAJOR+1, MINOR=0, PATCH=0, BUILD+1
- Testing → BUILD+1 (sin cambiar versión visible)

---

**Última actualización**: 23/03/2026  
**Autor**: GitHub Copilot con Claude Sonnet 4.5
