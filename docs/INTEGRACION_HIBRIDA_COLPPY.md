# Integración Configurable con Colppy

> ⚠️ **ADVERTENCIA - MODO HÍBRIDO NO RECOMENDADO**  
> El modo "híbrido" con consultas en tiempo real a Colppy API **NO es la configuración recomendada** para uso en producción.  
> Causa problemas de performance, timeouts y dependencia de conectividad.  
> **RECOMENDACIÓN**: Usar modo **'local'** con sincronización periódica (ver `INTEGRACION_COLPPY.md` y `SISTEMA_CLIENTES_DOMICILIOS.md`).

---

## 📋 Resumen

El sistema ahora tiene un **modo configurable** para gestión de clientes, permitiendo elegir entre tres opciones:

- **🏠 Local**: Solo base de datos local (por defecto)
- **☁️ API**: Solo clientes desde Colppy
- **🔄 Híbrido**: Combina base de datos local + Colppy

## ⚙️ Configuración

### Dónde configurar

Ingresa al **CMS → Configuración API**:

```
/cms/api-config
```

Encontrarás la sección **"Configuración de Clientes (Colppy)"** con un desplegable que permite elegir el modo.

### Opciones Disponibles

#### 1. 🏠 Local (Recomendado por defecto)
**Usa este modo si:**
- No tienes cuenta de Colppy
- No necesitas integración externa
- Quieres máxima velocidad
- Solo usas tu base de datos

**Comportamiento:**
- ✅ Solo consulta base de datos local
- ✅ CRUD completo (crear, editar, eliminar)
- ✅ Sin dependencias externas
- ✅ Funciona sin internet

#### 2. ☁️ API (Solo Colppy)
**Usa este modo si:**
- Migraste completamente a Colppy
- No necesitas clientes locales
- Colppy es tu fuente única de verdad

**Comportamiento:**
- ✅ Solo consulta API de Colppy
- ❌ No editar/eliminar (read-only)
- ⚠️ Requiere conexión a internet
- ⚠️ Requiere credenciales configuradas

#### 3. 🔄 Híbrido (Transición)
**Usa este modo si:**
- Estás migrando de local a Colppy
- Necesitas ambos orígenes temporalmente
- Quieres comparar datos

**Comportamiento:**
- ✅ Consulta BD local + Colppy
- ✅ Clientes locales: CRUD completo
- ❌ Clientes Colppy: Solo lectura
- 📊 Muestra ambos en la misma tabla

## 🔧 Requisitos por Modo

### Local
```
✓ Base de datos MySQL corriendo
```

### API o Híbrido
```
✓ Base de datos MySQL corriendo
✓ Credenciales Colppy configuradas:
  - URL API Login
  - Usuario Dev API
  - Contraseña Dev API
  - Usuario API
  - Contraseña API
  - ID Empresa API
```

## 💾 Estructura de Datos

### Tabla: `configs`

El modo se almacena en:

```sql
SELECT * FROM configs WHERE name = 'colppy_clientes_modo';
```

Valores posibles: `'local'` | `'api'` | `'hibrido'`

## 🎯 Comportamiento por Modo

### Tabla de Clientes

| Acción | Local | API | Híbrido |
|--------|-------|-----|---------|
| Ver clientes locales | ✅ | ❌ | ✅ |
| Ver clientes Colppy | ❌ | ✅ | ✅ |
| Crear nuevos | ✅ | ❌ | ✅ (solo locales) |
| Editar locales | ✅ | ❌ | ✅ |
| Editar Colppy | ❌ | ❌ | ❌ |
| Eliminar locales | ✅ | ❌ | ✅ |
| Eliminar Colppy | ❌ | ❌ | ❌ |

### Identificación en Tabla

**Clientes Locales:**
- ID numérico: `1`, `2`, `3`...
- Botones: Ver, Editar, Eliminar

**Clientes Colppy:**
- ID con prefijo: `colppy_1`, `colppy_2`...
- Indicador: 🌥️ "Desde Colppy"
- Botones: Solo Ver
- Sección adicional en detalle: **Dirección Fiscal**

## 📊 Mensajes Informativos

### Modo Local
```
Mostrando registros del 1 al 10 de un total de 25
```

### Modo API
```
Mostrando registros del 1 al 10 de un total de 74 (Solo Colppy)
```

### Modo Híbrido
```
Mostrando registros del 1 al 20 de un total de 99 (Locales: 25 | Colppy: 74)
```

Si Colppy falla en modo híbrido:
```
Mostrando registros del 1 al 10 de un total de 25 (Solo locales - Colppy no disponible)
```

## 🛡️ Tolerancia a Fallos

### Modo Local
- ✅ Siempre funciona (no depende de Colppy)

### Modo API
- ⚠️ Si Colppy falla → Muestra error, no datos
- ⚠️ Requiere internet activo

### Modo Híbrido
- ✅ Si Colppy falla → Muestra solo locales
- ✅ Degrada gracefully
- ✅ Nunca deja la app inoperante

## 🔄 Cambiar de Modo

1. Ir a **CMS → Configuración API**
2. Scroll hasta **"Configuración de Clientes (Colppy)"**
3. Seleccionar modo deseado
4. Click en **"Guardar Configuración"**
5. ✅ El cambio es inmediato (próxima consulta usará nuevo modo)

**⚠️ IMPORTANTE**: No requiere reiniciar servidor ni limpiar cache.

## 📁 Archivos Modificados

### Backend
- `app/Http/Controllers/Api/ApiDataTablesController.php`
  - `getClientsDataTable()` - Router principal
  - `getClientsFromLocalOnly()` - Modo local
  - `getClientsFromColppyOnly()` - Modo API
  - `getClientsHibrido()` - Modo híbrido

- `app/Http/Controllers/ApiConfigController.php`
  - Agregado campo `colppy_clientes_modo`

- `app/Models/Config.php`
  - Modelo existente (sin cambios)

### Base de Datos
- `database/migrations/2026_02_16_000000_add_colppy_configs_to_configs_table.php`
  - Migración para agregar configuración

### Frontend
- `resources/views/cms/api-config/index.blade.php`
  - Nueva sección de configuración

- `public/assets/js/local/client.js`
  - Detección y manejo de clientes Colppy

- `resources/views/client/show.blade.php`
  - Sección de dirección fiscal

## 🚀 Instalación/Migración

### 1. Ejecutar migración
```bash
cd panel
php artisan migrate
```

### 2. Verificar configuración
El modo por defecto es `local`, pero puedes cambiarlo desde el CMS.

### 3. Si quieres usar API o Híbrido
Configurar credenciales en **CMS → Configuración API**:
- URL API Login
- Usuario Dev API  
- Contraseña Dev API
- Usuario API
- Contraseña API
- ID Empresa API

## 💡 Casos de Uso Recomendados

### Startup / Empresa Nueva
```
Modo: Local
- Máxima simplicidad
- Sin costos adicionales
- No requiere Colppy
```

### Usando Colppy Exclusivamente
```
Modo: API
- Colppy como fuente única
- Sincronización automática
- Datos centralizados
```

### Migración en Proceso
```
Fase 1: Local (estado actual)
Fase 2: Híbrido (durante migración)
Fase 3: API (después de migración completa)
```

### Testing / Comparación
```
Modo: Híbrido
- Comparar datos entre sistemas
- Validar migración
- Detectar discrepancias
```

## 🔍 Troubleshooting

### "No aparecen clientes de Colppy en modo API"
1. Verificar credenciales en Configuración API
2. Revisar logs: `storage/logs/laravel-YYYY-MM-DD.log`
3. Buscar: `MODO CLIENTES` y `Colppy`

### "Modo híbrido solo muestra locales"
1. Normal si Colppy no está configurado
2. Verificar mensaje: "Solo locales - Colppy no disponible"
3. Configurar credenciales si es necesario

### "Error al cambiar de modo"
1. Verificar permisos en CMS (update)
2. Revisar que el valor sea: `local`, `api` o `hibrido`
3. No usar mayúsculas ni espacios

## ✅ Ventajas de Este Sistema

- ✅ **Flexible**: Cambia de modo sin código
- ✅ **Robusto**: Nunca se rompe por Colppy
- ✅ **Gradual**: Permite migración por fases
- ✅ **Sin Código**: Todo desde interfaz web
- ✅ **Auditable**: Cambios registrados en BD
- ✅ **Reversible**: Volver atrás es inmediato
