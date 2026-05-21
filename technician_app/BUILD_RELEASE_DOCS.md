# 🚀 Build Release - Sistema de Compilación Automática

Sistema automatizado para compilar la app **Strupeni Técnicos** generando dos versiones simultáneamente:
- **Versión Usuario** (MINOR par) → Sin funcionalidad de debug
- **Versión Debug** (MINOR impar) → Con pantalla de logs para desarrollo

---

## 📋 Requisitos Previos

### Esenciales
- ✅ Flutter SDK instalado y en el PATH
- ✅ Android SDK configurado
- ✅ Python 3.x instalado (para BUILD_RELEASE.py)

### Verificar instalación
```bash
flutter --version
python --version
```

---

## 🎯 ¿Cómo funciona?

### Sistema de Versionado

El script utiliza el estándar **Semantic Versioning** con una convención especial:

```
MAJOR.MINOR.PATCH+BUILD_NUMBER
  │     │     │        │
  │     │     │        └─ Autoincrementa (+1 por cada compilación)
  │     │     └────────── Bug fixes / Cambios menores
  │     └──────────────── IMPAR = Debug, PAR = Usuario
  └────────────────────── Cambios incompatibles
```

### Lógica de Versiones

Cuando ingresas una versión Usuario (MINOR par), el script:
1. Valida que MINOR sea par
2. Incrementa BUILD_NUMBER automáticamente (+1)
3. Genera **dos APKs** con el **mismo BUILD_NUMBER**:

| Versión | MINOR | BUILD | Debug | Destinatarios |
|---------|-------|-------|-------|---------------|
| `1.2.0+8` | 2 (par) | +8 | ❌ NO | Técnicos finales |
| `1.3.0+8` | 3 (impar) | +8 | ✅ SÍ | Desarrollo/Testing |

**Importante:** Ambas versiones comparten el mismo BUILD_NUMBER, solo cambia el MINOR.

**Archivos generados:**
- `strupeni-tecnicos-v1.2.0.apk` → Usuario (nombre limpio)
- `strupeni-tecnicos-v1.3.0-DEBUG.apk` → Debug (con sufijo -DEBUG)

---

## 🛠️ Uso del Script

### Opción 1: Script Python (Recomendado)

```bash
cd technician_app/
python BUILD_RELEASE.py
```

También admite modos directos para automatizar por flavor:

```bash
python BUILD_RELEASE.py --mode run-dev
python BUILD_RELEASE.py --mode run-qa
python BUILD_RELEASE.py --mode run-prod
python BUILD_RELEASE.py --mode build-prod
```

**Ventajas:**
- ✅ Multiplataforma (Windows, Linux, macOS)
- ✅ Manejo robusto de errores
- ✅ Salida con colores en terminal
- ✅ Validación exhaustiva

### Opción 2: Script BAT (Solo Windows)

```bash
cd technician_app/
BUILD_RELEASE.bat
```

Con argumentos (Windows):

```bash
BUILD_RELEASE.bat --mode run-dev
BUILD_RELEASE.bat --mode run-qa
BUILD_RELEASE.bat --mode run-prod
BUILD_RELEASE.bat --mode build-prod
```

**Ventajas:**
- ✅ No requiere Python
- ✅ Doble clic para ejecutar
- ✅ Abre automáticamente la carpeta de resultados

---

## 📝 Flujo de Trabajo

### Paso a paso:

1. **Ejecutar el script**
   ```bash
   python BUILD_RELEASE.py
   ```

2. **El script muestra la versión actual**
   ```
   Versión actual: 1.0.7
   Build number:   7
   MAJOR: 1, MINOR: 0, PATCH: 7
   ```

3. **Ingresar la nueva versión USUARIO**
   ```
   Nueva versión: 1.2.0
   ```
   ⚠️ **IMPORTANTE:** El MINOR debe ser PAR (0, 2, 4, 6, 8...)

4. **El script calcula automáticamente:**
   ```
   ✅ Versión validada correctamente
   
   📦 Versiones a generar:
      • Usuario: 1.2.0+8 (sin debug)
      • Debug:   1.3.0+8 (con debug)
   ```
   
   ⚠️ **NOTA:** El BUILD_NUMBER (+8) es el mismo para ambas versiones, solo cambia el MINOR.

5. **Confirmar compilación**
   ```
   ¿Continuar con la compilación? (S/N): S
   ```

6. **El script ejecuta:**
   - ✅ Actualiza `pubspec.yaml` a versión Usuario
   - ✅ Comenta el bloque de debug en `home_screen.dart`
   - ✅ Compila APK Usuario
   - ✅ Restaura el código de debug
   - ✅ Actualiza `pubspec.yaml` a versión Debug
   - ✅ Compila APK Debug
   - ✅ Genera resumen de compilación

7. **Resultado final:**
   ```
   📦 Archivos generados:
   
   1️⃣  strupeni-tecnicos-v1.2.0.apk
       └─ Para distribución a técnicos (SIN debug)
   
   2️⃣  strupeni-tecnicos-v1.3.0-DEBUG.apk
       └─ Para desarrollo y testing (CON debug)
   
   📍 Ubicaciones:
      • build/app/outputs/flutter-apk/ (última compilación)
      • VersionApk/ (historial de todas las versiones)
   ```

---

## 🔒 Funcionalidad de Debug

### ¿Qué hace el debug?

La funcionalidad de debug permite a los desarrolladores ver **logs detallados** de la app en tiempo real.

### ¿Cómo se accede?

1. **Versión DEBUG:** Tocar 5 veces seguidas el logo ⚡ en el AppBar
2. **Versión USUARIO:** ❌ No disponible (código comentado)

### Pantalla de Debug

Muestra:
- 📝 Todos los logs de la aplicación
- 🔍 Filtros por nivel (INFO, WARNING, ERROR, SUCCESS)
- 📂 Filtros por categoría (AUTH, JOBS, BUDGETS, etc.)
- 💾 Exportar logs a archivo
- 🗑️ Limpiar logs

### Código del Gesto Secreto

```dart
// En lib/screens/home_screen.dart

// START_DEBUG_FEATURE
// Logo con gesto secreto de debug (5 taps para abrir pantalla de logs)
leading: GestureDetector(
  onTap: _handleDebugTap,
  child: const Padding(
    padding: EdgeInsets.all(8.0),
    child: Icon(Icons.electrical_services, color: Colors.white, size: 32),
  ),
),
// END_DEBUG_FEATURE
```

El script:
- **Versión Usuario:** Comenta todo el bloque entre `START_DEBUG_FEATURE` y `END_DEBUG_FEATURE`
- **Versión Debug:** Descomenta el bloque (funcionalidad activa)

---

## 📦 Archivos Generados

### Nomenclatura de APKs

```
strupeni-tecnicos-v{VERSION}.apk           (Usuario)
strupeni-tecnicos-v{VERSION}-DEBUG.apk     (Debug)
                     │            │
                     │            └─ Sufijo -DEBUG para diferenciar
                     └──────────────── Versión semántica MAJOR.MINOR.PATCH
```

**Ejemplos:**
- `strupeni-tecnicos-v1.2.0.apk` → Usuario (limpio, para distribución)
- `strupeni-tecnicos-v1.3.0-DEBUG.apk` → Debug (con sufijo)

### Archivo de Resumen

Se genera automáticamente: `BUILD_SUMMARY_{VERSION}.txt` en la carpeta `VersionApk/`

```
═══════════════════════════════════════════════════════════════
  RESUMEN DE COMPILACIÓN - Strupeni Técnicos App
═══════════════════════════════════════════════════════════════

Fecha: 2026-04-08 15:30:45

VERSIONES GENERADAS:

  1. VERSIÓN USUARIO (Producción):
     • Versión: 1.2.0+8
     • Archivo: strupeni-tecnicos-v1.2.0.apk
     • Debug: DESACTIVADO
     • Destinatarios: Técnicos finales

  2. VERSIÓN DEBUG (Desarrollo):
     • Versión: 1.3.0+8
     • Archivo: strupeni-tecnicos-v1.3.0-DEBUG.apk
     • Debug: ACTIVADO (gesto secreto 5 taps en logo)
     • Destinatarios: Desarrolladores / Testing

VERSIÓN ANTERIOR: 1.0.7+7

═══════════════════════════════════════════════════════════════
```

---

## ⚠️ Reglas Importantes

### ✅ Hacer

- ✅ Siempre ingresar versión Usuario con MINOR **PAR**
- ✅ Incrementar PATCH para bug fixes (1.2.0 → 1.2.1)
- ✅ Incrementar MINOR para nuevas features (1.2.0 → 1.4.0)
- ✅ Probar ambas versiones antes de distribuir
- ✅ Distribuir versión USUARIO a técnicos finales
- ✅ Guardar las versiones DEBUG para testing interno

### ❌ No Hacer

- ❌ Ingresar versión con MINOR impar (el script lo rechazará)
- ❌ Modificar manualmente `pubspec.yaml` antes de compilar
- ❌ Distribuir versión DEBUG a usuarios finales
- ❌ Saltar números de BUILD (el script los maneja automáticamente)

---

## 🔧 Troubleshooting

### Error: "Python no está instalado"

**Solución:**
1. Descargar Python desde: https://python.org/downloads/
2. Durante la instalación, marcar "Add Python to PATH"
3. Reiniciar terminal

### Error: "Flutter no está instalado"

**Solución:**
```bash
# Verificar instalación
flutter doctor

# Si falta PATH, agregarlo a las variables de entorno
```

### Error: "Falló la compilación"

**Causas comunes:**
- ❌ Android SDK no configurado
- ❌ Dependencias desactualizadas
- ❌ Errores de código en la app

**Solución:**
```bash
# Limpiar caché
flutter clean

# Actualizar dependencias
flutter pub get

# Verificar errores
flutter analyze
```

### El script restaura archivos automáticamente

Si algo sale mal durante la compilación:
- ✅ `pubspec.yaml` se restaura a la versión anterior
- ✅ `home_screen.dart` se restaura al estado original
- ✅ Se guardan backups en `build_backup/`

---

## 📁 Estructura de Archivos

```
technician_app/
├── BUILD_RELEASE.py          # Script principal (Python)
├── BUILD_RELEASE.bat         # Launcher para Windows
├── BUILD_RELEASE_DOCS.md     # Este archivo
├── VersionApk/               # 📁 Historial de todas las versiones
│   ├── strupeni-tecnicos-v*.apk       # APKs Usuario
│   ├── strupeni-tecnicos-v*-DEBUG.apk # APKs Debug
│   └── BUILD_SUMMARY_*.txt            # Resúmenes
├── disable_debug.bat         # [Obsoleto] Usar BUILD_RELEASE.py
├── enable_debug.bat          # [Obsoleto] Usar BUILD_RELEASE.py
├── pubspec.yaml              # Versionado automático
├── lib/
│   └── screens/
│       └── home_screen.dart  # Contiene bloque debug marcado
└── build/
    └── app/
        └── outputs/
            └── flutter-apk/
                ├── strupeni-tecnicos-v*.apk  # Última compilación
                └── app-release.apk           # APK genérico de Flutter
```

---

## 📚 Ejemplos de Uso

## 🧪 Ejecución por Flavor

Para usar configuraciones predefinidas sin pasar `--dart-define` manualmente:

```bash
# Desarrollo (reintento de cola más frecuente)
flutter run --flavor dev -t lib/main_dev.dart

# QA
flutter run --flavor qa -t lib/main_qa.dart

# Producción
flutter run --flavor prod -t lib/main_prod.dart
flutter build apk --flavor prod -t lib/main_prod.dart --release
```

Valores de cola configurados:
- `dev`: retry cada 30s, batch 5
- `qa`: retry cada 60s, batch 5
- `prod`: retry cada 120s, batch 5

El script `BUILD_RELEASE.py` ya compila usando flavor `prod`.

### Caso 1: Release de producción con nueva feature

**Situación:** Agregaste presupuestos a la app

```bash
$ python BUILD_RELEASE.py

Versión actual: 1.0.7
Build number:   7

Nueva versión: 1.2.0  ✅ (MINOR=2, par)

✅ Versión validada
📦 Generando:
   • Usuario: 1.2.0+8 (sin debug)
   • Debug:   1.3.0+8 (con debug)
```

**Resultado:**
- Distribuyes `v1.2.0-USUARIO.apk` a los técnicos
- Guardas `v1.3.0-DEBUG.apk` para tu testing
- Ambas versiones quedan guardadas en `VersionApk/` para historial

---

### Caso 2: Bug fix rápido

**Situación:** Corrección de error en la última versión

```bash
$ python BUILD_RELEASE.py

Versión actual: 1.3.0
Build number:   9

Nueva versión: 1.2.1  ✅ (MINOR=2, par, PATCH+1)

✅ Versión validada
📦 Generando:
   • Usuario: 1.2.1+10 (sin debug)
   • Debug:   1.3.1+10 (con debug)
```

---

### Caso 3: Error intencional

**Situación:** Intentas versión con MINOR impar

```bash
$ python BUILD_RELEASE.py

Versión actual: 1.3.0
Build number:   9

Nueva versión: 1.3.0  ❌

❌ ERROR: El MINOR debe ser PAR para versión de usuario
   Ingresaste: 1.3.0 (MINOR=3)
   Ejemplos válidos: 1.0.0, 1.2.1, 2.4.3
```

---

## 🎓 Convenciones del Proyecto

### Semantic Versioning Extendido

| Componente | Cuándo incrementar | Ejemplo |
|------------|-------------------|---------|
| **MAJOR** | Cambios incompatibles (breaking changes) | `1.x.x` → `2.0.0` |
| **MINOR (par)** | Nueva feature Usuario | `1.0.x` → `1.2.0` |
| **MINOR (impar)** | Nueva feature Debug (automático) | `1.2.x` → `1.3.0` |
| **PATCH** | Bug fixes | `1.2.0` → `1.2.1` |
| **BUILD** | Cada compilación (automático, mismo para Usuario y Debug) | `1.2.0+8` = `1.3.0+8` |

### Nomenclatura de Archivos

- ✅ `strupeni-tecnicos-v1.2.0.apk` → Producción (Usuario)
- ✅ `strupeni-tecnicos-v1.3.0-DEBUG.apk` → Desarrollo (Debug)
- ❌ `app-release.apk` → Evitar nombre genérico

### Gestión de Versiones

- 📁 **VersionApk/**: Historial completo de todas las compilaciones
  - Cada compilación genera 2 APKs + 1 TXT de resumen
  - Nunca se sobrescriben versiones anteriores
  - Ideal para revertir a versiones previas
  
- 📁 **build/app/outputs/flutter-apk/**: Última compilación únicamente
  - Se sobrescribe en cada nuevo build
  - Ubicación estándar de Flutter

---

## 🚀 Flujo de Trabajo Recomendado

### Para cada nuevo Release:

1. **Desarrollo**
   - Trabajar con versión DEBUG activa
   - Probar funcionalidades con pantalla de logs

2. **Pre-Release**
   - Ejecutar `python BUILD_RELEASE.py`
   - Ingresar nueva versión Usuario (MINOR par)

3. **Testing**
   - Instalar versión DEBUG en dispositivo de prueba
   - Verificar logs y comportamiento

4. **Producción**
   - Distribuir versión USUARIO a técnicos
   - Guardar ambas versiones en repositorio

5. **Post-Release**
   - Mantener versión DEBUG para soporte
   - Documentar cambios en changelog

---

## 📞 Soporte

**Creado por:** strupeni-dev Agent  
**Proyecto:** Strupeni Electrónica  
**Fecha:** Abril 2026  

**Documentación relacionada:**
- [`docs/README_DOCUMENTOS.md`](../docs/README_DOCUMENTOS.md) - Índice de documentación
- [`docs/API_ENDPOINTS.md`](../docs/API_ENDPOINTS.md) - Endpoints del backend
- [`pubspec.yaml`](pubspec.yaml) - Configuración de versiones

---

**¡Listo para compilar! 🎉**
