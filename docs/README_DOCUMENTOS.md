# 📚 Guía de Documentación del Proyecto

> Resumen de todos los documentos disponibles y su estado de relevancia

---

## ✅ DOCUMENTOS ACTIVOS Y RELEVANTES

### 🔌 Integración Colppy

#### [`INTEGRACION_COLPPY.md`](INTEGRACION_COLPPY.md) ⭐⭐⭐⭐⭐
**Estado**: **ESENCIAL** - Documento maestro

**Contenido**:
- Arquitectura completa de integración
- Flujos de autenticación
- Estructura de payloads
- Restricciones (solo lectura)
- Ejemplos de uso

**Usar cuando**: Cualquier trabajo relacionado con Colppy API


#### [`FLUJO_SINCRONIZACION.md`](FLUJO_SINCRONIZACION.md) ⭐⭐⭐⭐⭐
**Estado**: **ESENCIAL** - Proceso de sincronización

**Contenido**:
- Flujo completo paso a paso
- Arquitectura del sistema
- Métodos de ejecución (manual, automático, programado)
- Manejo de errores y reintentos
- Troubleshooting específico de sincronización

**Usar cuando**: Trabajar con sincronización de clientes, debugging


#### [`CONFIGURACION_COLPPY.md`](CONFIGURACION_COLPPY.md) ⭐⭐⭐⭐
**Estado**: **ACTIVO** - Setup inicial

**Contenido**:
- Configuración inicial de credenciales
- Setup de tabla configs
- Primeros pasos

**Usar cuando**: Configurar Colppy por primera vez o troubleshooting


#### [`COLPPY_QUICK_START.md`](COLPPY_QUICK_START.md) ⭐⭐⭐
**Estado**: **ACTIVO** - Referencia rápida

**Contenido**:
- Guía rápida de uso
- Ejemplos concisos

**Usar cuando**: Necesitas recordar algo rápido sin leer el doc completo


#### [`EJEMPLOS_USO_COLPPY.md`](EJEMPLOS_USO_COLPPY.md) ⭐⭐⭐
**Estado**: **ACTIVO** - Código de referencia

**Contenido**:
- Ejemplos prácticos de código
- Casos de uso específicos

**Usar cuando**: Implementar nuevas features que usen Colppy


#### [`FLUJOS_COLPPY_DIAGRAMA.md`](FLUJOS_COLPPY_DIAGRAMA.md) ⭐⭐⭐
**Estado**: **ACTIVO** - Visualización

**Contenido**:
- Diagramas de flujo
- Visualización de procesos

**Usar cuando**: Entender visualmente los flujos


#### [`IMPLEMENTACION_COLPPY.md`](IMPLEMENTACION_COLPPY.md) ⭐⭐⭐⭐
**Estado**: **ACTIVO** - Documentación de implementación

**Contenido**:
- Detalles de implementación técnica
- Decisiones de arquitectura

**Usar cuando**: Modificar o extender la integración existente

---

### 🗄️ Arquitectura de Datos

#### [`SISTEMA_CLIENTES_DOMICILIOS.md`](SISTEMA_CLIENTES_DOMICILIOS.md) ⭐⭐⭐⭐⭐
**Estado**: **ESENCIAL** - Arquitectura actualizada (nuevo)

**Contenido**:
- Estructura real de tablas `clients` y `clients_address`
- Modos de operación ('local', 'colppy', 'hibrido')
- Flujo de sincronización
- Detección de origen de clientes
- Controladores y API

**Usar cuando**: Trabajar con clientes, domicilios, entender la BD

**Nota**: ✅ Reemplaza el antiguo `SISTEMA_DOMICILIOS_DUAL.md` (desactualizado)

---

### 📡 API y Endpoints

#### [`API_ENDPOINTS.md`](API_ENDPOINTS.md) ⭐⭐⭐⭐⭐
**Estado**: **ESENCIAL** - Referencia completa (nuevo)

**Contenido**:
- Todos los endpoints disponibles (web y móvil)
- Autenticación (sesión web y Sanctum)
- Request/Response de cada endpoint
- Códigos de estado HTTP
- Ejemplos cURL y código

**Usar cuando**: 
- Implementar llamadas desde Flutter
- Crear nuevos endpoints
- Debugging de API
- Documentar para frontend

---

### 🔧 Troubleshooting

#### [`TROUBLESHOOTING.md`](TROUBLESHOOTING.md) ⭐⭐⭐⭐⭐
**Estado**: **ESENCIAL** - Solución de problemas (nuevo)

**Contenido**:
- Problemas comunes organizados por categoría
- Diagnóstico paso a paso
- Soluciones probadas
- Comandos útiles
- Cuándo contactar soporte

**Categorías**:
- Autenticación y sesiones
- Integración Colppy
- Clientes y domicilios  
- Trabajos (Jobs)
- Archivos y storage
- Base de datos
- Configuración
- App móvil Flutter
- Performance

**Usar cuando**: Tienes un error o problema

---

### ⚙️ Configuración del Sistema

#### [`CONFIGURAR_LIMITES_PHP.md`](CONFIGURAR_LIMITES_PHP.md) ⭐⭐⭐⭐
**Estado**: **ACTIVO** - Configuración de servidor

**Contenido**:
- Ajustes de `php.ini`
- Límites de memoria, upload, execution time
- Configuraciones para producción

**Usar cuando**: 
- Errores de timeout
- Problemas de subida de archivos
- Deploy a producción


#### [`INSTRUCCIONES_PRODUCCION_STORAGE.md`](INSTRUCCIONES_PRODUCCION_STORAGE.md) ⭐⭐⭐⭐
**Estado**: **ACTIVO** - Deploy a producción

**Contenido**:
- Configuración de storage en producción
- Permisos de archivos
- Symlinks

**Usar cuando**: Deploy o problemas con storage/uploads

---

### 🔄 Workers y Schedulers

#### [`CONFIGURAR_QUEUE_WORKER.md`](CONFIGURAR_QUEUE_WORKER.md) ⭐⭐⭐
**Estado**: **REFERENCIA** - No usado actualmente

**Contenido**:
- Setup de queue workers con Supervisor/systemd
- Configuraciones Windows/Linux
- Jobs asíncronos

**Estado actual del proyecto**:
- ✅ Jobs implementados: `SyncColppyClientsJob`
- ⚠️ `QUEUE_CONNECTION=sync` → No requiere worker
- 💡 Útil si en el futuro se cambia a queues asíncronas

**Usar cuando**: 
- Quieres activar queues asíncronas
- Producción con carga alta requiera background processing


#### [`CONFIGURAR_SCHEDULER.md`](CONFIGURAR_SCHEDULER.md) ⭐⭐⭐
**Estado**: **REFERENCIA** - Probablemente no usado

**Contenido**:
- Setup de cron jobs
- Task Scheduler de Windows
- Comandos programados

**Estado actual del proyecto**:
- ✅ Scheduler definido en `Kernel.php` (sync cada minuto)
- ⚠️ Probablemente no está corriendo en desarrollo
- 💡 Necesario activar para sincronización automática

**Usar cuando**:
- Quieres sincronización automática periódica
- Deploy a producción con tareas programadas


#### [`SCHEDULER_RESUMEN.md`](SCHEDULER_RESUMEN.md) ⭐⭐
**Estado**: **REFERENCIA** - Resumen

**Contenido**:
- Resumen rápido del scheduler
- Duplica info de CONFIGURAR_SCHEDULER.md

**Recomendación**: Probablemente redundante, preferir `CONFIGURAR_SCHEDULER.md`

---

### 🏗️ CMS y Migraciones

#### [`MIGRACION_CMS_SECCIONES.md`](MIGRACION_CMS_SECCIONES.md) ⭐⭐
**Estado**: **HISTÓRICO** - Proceso de migración

**Contenido**:
- Proceso de migración de CMS
- Probablemente ya ejecutado

**Usar cuando**: Referencia histórica o si hay rollback

---

### ⚠️ DOCUMENTOS DESACTUALIZADOS

#### [`SISTEMA_DOMICILIOS_DUAL.md`](SISTEMA_DOMICILIOS_DUAL.md) ❌
**Estado**: **DESACTUALIZADO** - NO corresponde a la realidad

**Problema identificado**:
- Describe sistema dual con `clients_address` y `clients_address_external`
- **REALIDAD**: Los clientes se sincronizan de Colppy a BD local
- Todo el trabajo se hace localmente
- Domicilios se manejan en el sistema local

**Recomendación**: ⚠️ **ACTUALIZAR O ELIMINAR**


#### [`INTEGRACION_HIBRIDA_COLPPY.md`](INTEGRACION_HIBRIDA_COLPPY.md) ⚠️
**Estado**: **CUESTIONABLE** - Modo híbrido no recomendado

**Contenido**:
- Documentación del modo "híbrido"
- Consultas directas en tiempo real

**Estado actual**:
- Modo híbrido existe en código pero NO es recomendado
- Modo 'local' es el preferido

**Recomendación**: Mantener como referencia pero marcar claramente que no es recomendado

---

## 📊 RESUMEN POR PRIORIDAD

### 🔥 CRÍTICOS (Leer primero)
1. `INTEGRACION_COLPPY.md` - Documento maestro
2. `CONFIGURACION_COLPPY.md` - Setup inicial
3. `CONFIGURAR_LIMITES_PHP.md` - Configuración servidor
4. `INSTRUCCIONES_PRODUCCION_STORAGE.md` - Deploy producción

### ⭐ IMPORTANTES (Referencias frecuentes)
1. `IMPLEMENTACION_COLPPY.md` - Detalles técnicos
2. `EJEMPLOS_USO_COLPPY.md` - Código de referencia
3. `COLPPY_QUICK_START.md` - Referencia rápida

### 📚 COMPLEMENTARIOS (Consulta ocasional)
1. `FLUJOS_COLPPY_DIAGRAMA.md` - Visualización
2. `CONFIGURAR_QUEUE_WORKER.md` - Si activas queues
3. `CONFIGURAR_SCHEDULER.md` - Si activas scheduler

### ✅ REVISADOS/ACTUALIZADOS
1. ✅ `SISTEMA_DOMICILIOS_DUAL.md` - **ELIMINADO** (contenía información incorrecta)
2. ✅ `SISTEMA_CLIENTES_DOMICILIOS.md` - **CREADO** (arquitectura real y correcta)
3. ✅ `INTEGRACION_HIBRIDA_COLPPY.md` - **ACTUALIZADO** (disclaimer agregado)
4. ✅ `FLUJO_SINCRONIZACION.md` - **CREADO** (proceso de sincronización detallado)
5. ✅ `API_ENDPOINTS.md` - **CREADO** (85+ endpoints documentados)
6. ✅ `TROUBLESHOOTING.md` - **CREADO** (guía de problemas y soluciones)
7. 📝 `SCHEDULER_RESUMEN.md` - Pendiente: Considerar eliminar (redundante)
8. 📝 `MIGRACION_CMS_SECCIONES.md` - Pendiente: Archivar (histórico)

---

## 🎯 RECOMENDACIONES

### ✅ Acciones Completadas (27/02/2026)
1. ✅ **Eliminado** `SISTEMA_DOMICILIOS_DUAL.md` (contenía arquitectura incorrecta)
2. ✅ **Creado** `SISTEMA_CLIENTES_DOMICILIOS.md` (arquitectura real: single table)
3. ✅ **Actualizado** `INTEGRACION_HIBRIDA_COLPPY.md` (disclaimer agregado - modo híbrido NO recomendado)
4. ✅ **Creado** `FLUJO_SINCRONIZACION.md` (600+ líneas - proceso completo de sync)
5. ✅ **Creado** `API_ENDPOINTS.md` (1000+ líneas - 85+ endpoints documentados)
6. ✅ **Creado** `TROUBLESHOOTING.md` (700+ líneas - 9 categorías de problemas)
7. ✅ **Actualizado** Agente personalizado (`.github/agents/strupeni-dev.md`) con información correcta

### 📝 Mantenimiento Pendiente (Opcional)
1. **Consolidar**: Fusionar `SCHEDULER_RESUMEN.md` en `CONFIGURAR_SCHEDULER.md`
2. **Archivar**: Mover `MIGRACION_CMS_SECCIONES.md` a `/docs/historico/`

### 💡 Documentación Futura Sugerida
1. **`GUIA_FLUTTER_APP.md`**: Documentar la app móvil de técnicos (modelos, providers, pantallas)
2. **`ARQUITECTURA_BACKEND.md`**: Diagrama y explicación de services, jobs, commands

---

## 💡 Cómo Usar Esta Guía

### Soy nuevo en el proyecto
**Orden de lectura recomendado**:
1. Este documento (`README_DOCUMENTOS.md`) ← Estás aquí
2. `INTEGRACION_COLPPY.md` - Entender la integración principal
3. `CONFIGURACION_COLPPY.md` - Ver cómo está configurado
4. Revisar agente personalizado: `../.github/agents/strupeni-dev.md`

### Voy a trabajar con Colppy
1. `INTEGRACION_COLPPY.md` - Documento base
2. `EJEMPLOS_USO_COLPPY.md` - Ejemplos de código
3. `ColppyService.php` - Ver implementación actual

### Voy a hacer deploy a producción
1. `CONFIGURAR_LIMITES_PHP.md` - Configurar PHP
2. `INSTRUCCIONES_PRODUCCION_STORAGE.md` - Storage y permisos
3. Opcionalmente: `CONFIGURAR_QUEUE_WORKER.md` y `CONFIGURAR_SCHEDULER.md`

### Tengo un error
1. Buscar en logs: `panel/storage/logs/laravel.log`
2. Ver troubleshooting en el agente
3. Consultar doc relevante según el área del error

---

**Última actualización**: 27 de febrero de 2026
**Mantenedor**: Equipo de Desarrollo Strupeni Electrónica
