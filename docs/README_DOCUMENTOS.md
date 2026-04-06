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


#### [`SISTEMA_PRODUCTOS_TAREAS.md`](SISTEMA_PRODUCTOS_TAREAS.md) ⭐⭐⭐⭐⭐
**Estado**: **ESENCIAL** - Sistema de productos en tareas (nuevo)

**Contenido**:
- Arquitectura completa del sistema de productos
- Tabla `job_products` y modelo JobProduct
- Datos históricos (snapshot de productos)
- Tres modos de gestión: Create, Edit, Add Products
- Funciones JavaScript (addProductToJob, removeProductFromJob, renderProductsList)
- Sistema de unique_id para duplicados
- Validaciones y reglas de negocio
- Troubleshooting completo con soluciones

**Usar cuando**: 
- Agregar/modificar productos en tareas
- Debugging de productos en jobs
- Extender funcionalidad de productos
- Implementar en app móvil (pendiente)
- Entender flujo de trabajo con productos

**Características**:
- ✅ Datos históricos preservados
- ✅ Soporte para productos duplicados
- ✅ Búsqueda en vivo con AJAX
- ✅ Tres tipos de unidad (Unidad/Rollo/Metros)
- ✅ Soft deletes para auditoría
- ✅ Modal directo sin editar toda la tarea

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

### � Desarrollo y Convenciones

#### [`CONVENCIONES_JAVASCRIPT.md`](CONVENCIONES_JAVASCRIPT.md) ⭐⭐⭐⭐
**Estado**: **ESENCIAL** - Guía de desarrollo frontend (nuevo)

**Contenido**:
- Convenciones de SweetAlert (`type` vs `icon`)
- Estructura de archivos JavaScript
- Manejo de AJAX
- Buenas prácticas de código
- Dónde ubicar funciones JavaScript

**Usar cuando**: 
- Desarrollar nuevas funcionalidades frontend
- Debugging de alertas/notificaciones
- Organizar código JavaScript
- Onboarding de nuevos desarrolladores

**IMPORTANTE**: ⚠️ En este proyecto, SweetAlert usa `type:` NO `icon:`

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

### 📱 Debugging de App Móvil

#### [`TROUBLESHOOTING_APP_RESUMEN.md`](TROUBLESHOOTING_APP_RESUMEN.md) ⭐⭐⭐⭐⭐
**Estado**: **ESENCIAL** - Guía rápida de decisión (NUEVO - 23/03/2026)

**Contenido**:
- Diagnóstico inicial en 30 segundos
- Matriz de decisión (backend vs cliente)
- Soluciones por efectividad
- Comandos rápidos
- Flujo de soporte sugerido

**Usar cuando**: 
- Usuario reporta error en la app móvil
- Necesitas decidir rápido qué documento usar
- Primera respuesta a incidentes

**¡EMPIEZA AQUÍ!** 👈 Si hay un problema con la app móvil


#### [`DEBUGGING_APP_MOVIL.md`](DEBUGGING_APP_MOVIL.md) ⭐⭐⭐⭐⭐
**Estado**: **ESENCIAL** - Problemas del backend (NUEVO - 23/03/2026)

**Contenido**:
- Logging detallado implementado en backend
- Endpoint `/api/health-check` para verificar autenticación
- Script `test-api-mobile.php` para testing automático
- Análisis de logs del servidor
- Verificación de tokens en base de datos
- Diagnóstico de errores SQL/servidor

**Usar cuando**: 
- El error ocurre en TODOS los dispositivos
- Las credenciales del usuario funcionan en tu dispositivo
- Necesitas debuggear el backend/API

**Herramientas incluidas**:
- ✅ Logging con emojis para fácil identificación
- ✅ Health check endpoint
- ✅ Script de test automático (`panel/test-api-mobile.php`)
- ✅ Queries SQL para verificar usuarios/tokens


#### [`DEBUGGING_APP_MOVIL_CLIENTE.md`](DEBUGGING_APP_MOVIL_CLIENTE.md) ⭐⭐⭐⭐⭐
**Estado**: **ESENCIAL** - Problemas del dispositivo (NUEVO - 23/03/2026)

**Contenido**:
- Token corrupto en almacenamiento local (60% de casos)
- Caché corrupto (20%)
- Versión antigua de app (10%)
- Permisos insuficientes (5%)
- Problemas de red (3%)
- Soluciones probadas por efectividad

**Usar cuando**: 
- El error ocurre SOLO en el dispositivo del usuario
- Las credenciales funcionan correctamente en tu dispositivo
- Necesitas instrucciones para el usuario final

**Mejoras técnicas sugeridas**:
- 📝 Implementar logging local en la app
- 📝 Agregar pantalla de debug oculta
- 📝 Manejo de errores mejorado con códigos específicos
- 📝 Auto-recuperación con retry


#### [`GUIA_SOPORTE_USUARIO_FINAL.md`](GUIA_SOPORTE_USUARIO_FINAL.md) ⭐⭐⭐⭐⭐
**Estado**: **ESENCIAL** - Para copiar/pegar al usuario (NUEVO - 23/03/2026)

**Contenido**:
- 6 opciones de comunicación (WhatsApp, Email, Llamada, etc.)
- Mensajes predefinidos listos para enviar
- FAQ con respuestas estándar
- Guías paso a paso para Android e iOS
- Checklist de seguimiento


#### [`DEBUGGING_APP_FLUTTER.md`](DEBUGGING_APP_FLUTTER.md) ⭐⭐⭐⭐⭐
**Estado**: **ESENCIAL** - Sistema de debugging Flutter (NUEVO - 23/03/2026)

**Contenido**:
- Sistema completo de logging local (DebugLogger)
- Pantalla de debug oculta (gesto secreto: tap 5 veces en logo)
- Códigos de error específicos (NO_TOKEN, TIMEOUT, NO_INTERNET, etc.)
- Sistema de retry automático en peticiones HTTP
- NetworkHelper con exponential backoff
- Exportación de logs (compartir por WhatsApp/email)
- Herramientas de diagnóstico integradas

**Usar cuando**: 
- Implementar debugging en la app Flutter
- Agregar logging a nuevos servicios
- Diagnosticar problemas remotamente sin acceso físico al dispositivo
- Verificar estado del token, API, usuario desde el dispositivo

**Componentes implementados**:
- ✅ `lib/utils/debug_logger.dart` - Logging persistente (100 logs)
- ✅ `lib/utils/network_helper.dart` - Peticiones con retry automático
- ✅ `lib/screens/debug_screen.dart` - Pantalla de debug completa
- ✅ `lib/services/job_service.dart` - Integrado con logging y retry
- ✅ `lib/screens/home_screen.dart` - Gesto secreto de acceso

**Gesto de acceso**: Tap 5 veces en 3 segundos en el ícono del rayo (esquina superior izquierda)


#### [`VERSIONADO_FLUTTER.md`](VERSIONADO_FLUTTER.md) ⭐⭐⭐⭐⭐
**Estado**: **ESENCIAL** - Versionado correcto de la app (NUEVO - 23/03/2026)

**Contenido**:
- Formato de versionado: `MAJOR.MINOR.PATCH+BUILD_NUMBER`
- Cuándo incrementar cada parte (feature, bug fix, rediseño)
- Configuración en `pubspec.yaml`
- Ejemplos prácticos con changelog
- Troubleshooting de versionCode duplicado en Play Store
- Mejores prácticas (Git tags, changelog, testing)
- Comandos útiles para cambiar versión

**Usar cuando**: 
- Vas a compilar una nueva versión de la app
- Google Play rechaza la APK por versionCode duplicado
- Necesitas documentar cambios entre versiones
- Quieres verificar qué versión tiene un usuario

**Problema resuelto**: Cada compilación dejaba de generar `1.0.0` → Ahora se versiona correctamente

**Ubicación**: `technician_app/pubspec.yaml` → línea `version: X.Y.Z+N`

**Ejemplos**:
- Bug fix: `1.0.0+1` → `1.0.1+2`
- Nuevo feature: `1.0.1+2` → `1.1.0+3`
- Rediseño: `1.5.0+25` → `2.0.0+26`

**Usar cuando**: 
- Necesitas enviar instrucciones al usuario
- Soporte por WhatsApp/Email/Teléfono
- Comunicación no técnica

**Formato**:
- ✅ Copy/paste listo para WhatsApp
- ✅ Email formal con pasos detallados
- ✅ Script para explicar por teléfono
- ✅ FAQ para respuestas rápidas

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
5. `API_ENDPOINTS.md` - Referencia completa de endpoints
6. `TROUBLESHOOTING_APP_RESUMEN.md` - **¡EMPIEZA AQUÍ si hay error en app móvil!**

### ⭐ IMPORTANTES (Referencias frecuentes)
1. `IMPLEMENTACION_COLPPY.md` - Detalles técnicos
2. `EJEMPLOS_USO_COLPPY.md` - Código de referencia
3. `COLPPY_QUICK_START.md` - Referencia rápida
4. `SISTEMA_CLIENTES_DOMICILIOS.md` - Arquitectura BD
5. `SISTEMA_PRODUCTOS_TAREAS.md` - Sistema de productos
6. `TROUBLESHOOTING.md` - Solución de problemas generales
7. `DEBUGGING_APP_MOVIL.md` - Problemas backend app
8. `DEBUGGING_APP_MOVIL_CLIENTE.md` - Problemas dispositivo
9. `GUIA_SOPORTE_USUARIO_FINAL.md` - Mensajes para usuarios
10. `CONVENCIONES_JAVASCRIPT.md` - Guía de desarrollo frontend
11. `DEBUGGING_APP_FLUTTER.md` - **NUEVO** Sistema de debugging Flutter
12. `VERSIONADO_FLUTTER.md` - **NUEVO** Versionado correcto de la app

### 📚 COMPLEMENTARIOS (Consulta ocasional)
1. `FLUJOS_COLPPY_DIAGRAMA.md` - Visualización
2. `CONFIGURAR_QUEUE_WORKER.md` - Si activas queues
3. `CONFIGURAR_SCHEDULER.md` - Si activas scheduler
4. `SINCRONIZACION_PRODUCTOS_UPDATE.md` - Sincronización productos

### ✅ REVISADOS/ACTUALIZADOS
1. ✅ `SISTEMA_DOMICILIOS_DUAL.md` - **ELIMINADO** (contenía información incorrecta)
2. ✅ `SISTEMA_CLIENTES_DOMICILIOS.md` - **CREADO** (arquitectura real y correcta)
3. ✅ `INTEGRACION_HIBRIDA_COLPPY.md` - **ACTUALIZADO** (disclaimer agregado)
4. ✅ `FLUJO_SINCRONIZACION.md` - **CREADO** (proceso de sincronización detallado)
5. ✅ `API_ENDPOINTS.md` - **CREADO** (85+ endpoints documentados)
6. ✅ `TROUBLESHOOTING.md` - **CREADO** (guía de problemas y soluciones)
7. ✅ `CONVENCIONES_JAVASCRIPT.md` - **CREADO** (guía de desarrollo frontend y SweetAlert)
8. ✅ `SINCRONIZACION_PRODUCTOS_UPDATE.md` - **CREADO** (actualización de sincronización de productos)
9. ✅ `TROUBLESHOOTING_APP_RESUMEN.md` - **CREADO 23/03/2026** (guía rápida debugging app)
10. ✅ `DEBUGGING_APP_MOVIL.md` - **CREADO 23/03/2026** (debugging backend app)
11. ✅ `DEBUGGING_APP_MOVIL_CLIENTE.md` - **CREADO 23/03/2026** (debugging cliente app)
12. ✅ `GUIA_SOPORTE_USUARIO_FINAL.md` - **CREADO 23/03/2026** (mensajes para usuarios)
13. 📝 `SCHEDULER_RESUMEN.md` - Pendiente: Considerar eliminar (redundante)
14. 📝 `MIGRACION_CMS_SECCIONES.md` - Pendiente: Archivar (histórico)

---

## 🎯 RECOMENDACIONES

### ✅ Acciones Completadas (Última actualización: 23/03/2026)
1. ✅ **Eliminado** `SISTEMA_DOMICILIOS_DUAL.md` (contenía arquitectura incorrecta)
2. ✅ **Creado** `SISTEMA_CLIENTES_DOMICILIOS.md` (arquitectura real: single table)
3. ✅ **Actualizado** `INTEGRACION_HIBRIDA_COLPPY.md` (disclaimer agregado - modo híbrido NO recomendado)
4. ✅ **Creado** `FLUJO_SINCRONIZACION.md` (600+ líneas - proceso completo de sync)
5. ✅ **Creado** `API_ENDPOINTS.md` (1000+ líneas - 85+ endpoints documentados)
6. ✅ **Creado** `TROUBLESHOOTING.md` (700+ líneas - 9 categorías de problemas)
7. ✅ **Creado** `CONVENCIONES_JAVASCRIPT.md` (guía completa de desarrollo frontend)
8. ✅ **Actualizado** Agente personalizado (`.github/agents/strupeni-dev.md`) con información correcta
9. ✅ **Creado** `SINCRONIZACION_PRODUCTOS_UPDATE.md` (documentación de sincronización de productos)
10. ✅ **Creado** `TROUBLESHOOTING_APP_RESUMEN.md` (guía rápida debugging app - 23/03/2026)
11. ✅ **Creado** `DEBUGGING_APP_MOVIL.md` (debugging backend app móvil - 23/03/2026)
12. ✅ **Creado** `DEBUGGING_APP_MOVIL_CLIENTE.md` (debugging dispositivo específico - 23/03/2026)
13. ✅ **Creado** `GUIA_SOPORTE_USUARIO_FINAL.md` (mensajes copy/paste para usuarios - 23/03/2026)
14. ✅ **Implementado** Logging detallado en `ApiJobController` (getTodayJobs, getUpcomingJobs, getJobsByDateRange)
15. ✅ **Implementado** Endpoint `/api/health-check` para verificar autenticación
16. ✅ **Creado** Script `panel/test-api-mobile.php` para testing automático de usuarios
17. ✅ **Creado** `DEBUGGING_APP_FLUTTER.md` (sistema completo de debugging Flutter - 23/03/2026)
18. ✅ **Creado** `VERSIONADO_FLUTTER.md` (guía de versionado correcto - 23/03/2026)
19. ✅ **Implementado** Sistema de logging local en app Flutter (`lib/utils/debug_logger.dart`)
20. ✅ **Implementado** Pantalla de debug oculta en Flutter (`lib/screens/debug_screen.dart`)
21. ✅ **Implementado** Códigos de error específicos (`lib/utils/network_helper.dart`)
22. ✅ **Implementado** Auto-retry en peticiones HTTP con exponential backoff
23. ✅ **Implementado** Gesto secreto de debug (tap 5 veces en logo)
24. ✅ **Mejorado** `JobService` con logging y retry automático
25. ✅ **Configurado** Versionado correcto en `pubspec.yaml` con comentarios explicativos

### 📝 Mantenimiento Pendiente (Opcional)
1. **Consolidar**: Fusionar `SCHEDULER_RESUMEN.md` en `CONFIGURAR_SCHEDULER.md`
2. **Archivar**: Mover `MIGRACION_CMS_SECCIONES.md` a `/docs/historico/`

### 💡 Documentación Futura Sugerida
1. **`GUIA_FLUTTER_APP.md`**: Documentar la app móvil de técnicos (modelos, providers, pantallas)
2. **`ARQUITECTURA_BACKEND.md`**: Diagrama y explicación de services, jobs, commands
3. **`GUIA_TESTING.md`**: Convenciones de testing, cómo ejecutar tests, coverage

### 🔧 Mejoras Técnicas Implementadas (App Móvil)
1. ✅ **IMPLEMENTADO** Logging local en la app Flutter (guardar últimos 100 logs)
2. ✅ **IMPLEMENTADO** Pantalla de debug oculta (tap 5 veces en logo del rayo)
3. ✅ **IMPLEMENTADO** Manejo de errores mejorado con códigos específicos (NO_TOKEN, UNAUTHORIZED, TIMEOUT, NO_INTERNET, etc.)
4. ✅ **IMPLEMENTADO** Auto-recuperación con retry automático (hasta 2 reintentos con exponential backoff)
5. 📝 **Pendiente** Sistema de reportes de errores (enviar automáticamente al backend cuando ocurre un error)

**Archivos creados**:
- `technician_app/lib/utils/debug_logger.dart` - Sistema de logging persistente
- `technician_app/lib/utils/network_helper.dart` - Peticiones con retry y códigos de error
- `technician_app/lib/screens/debug_screen.dart` - Pantalla de debug completa (3 pestañas)

**Archivos modificados**:
- `technician_app/lib/services/job_service.dart` - Integrado con logging y retry
- `technician_app/lib/screens/home_screen.dart` - Gesto secreto de acceso
- `technician_app/pubspec.yaml` - Comentarios sobre versionado

**Documentación**:
- `docs/DEBUGGING_APP_FLUTTER.md` - Guía completa del sistema de debugging
- `docs/VERSIONADO_FLUTTER.md` - Guía de versionado correcto

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

### Usuario reporta error en la app móvil ⚠️
**¡PROCESO DE 3 PASOS!**
1. **PRIMERO**: Lee `TROUBLESHOOTING_APP_RESUMEN.md` (30 segundos)
2. **Diagnostica**: ¿Funciona en tu teléfono con sus credenciales?
   - ✅ SÍ funciona → Lee `DEBUGGING_APP_MOVIL_CLIENTE.md` + Envía `GUIA_SOPORTE_USUARIO_FINAL.md`
   - ❌ NO funciona → Lee `DEBUGGING_APP_MOVIL.md` + Ejecuta `test-api-mobile.php`
3. **Resuelve**: Sigue las instrucciones específicas del documento

### Tengo un error general (no app móvil)
1. Buscar en logs: `panel/storage/logs/laravel.log`
2. Consultar: `TROUBLESHOOTING.md`
3. Si es específico de Colppy: Ver sección de troubleshooting en `INTEGRACION_COLPPY.md`

---

**Última actualización**: 23 de marzo de 2026
**Mantenedor**: Equipo de Desarrollo Strupeni Electrónica
