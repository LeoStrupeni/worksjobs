# Implementación Completa: Funcionalidad de Presupuestos (Budgets/Facturas Colppy)

**Fecha de implementación:** 12 de marzo de 2026  
**Estado:** ✅ Implementado completamente

---

## 📋 RESUMEN DE CAMBIOS

Se ha completado la funcionalidad de asociación entre presupuestos de Colppy y tareas del sistema, incluyendo visualización y gestión completa.

---

## 🎯 Funcionalidades Implementadas

### 1. ✅ Botón "Asociar a tarea" en presupuestos
**Ubicación:** Panel de presupuestos (budgets), menú dropdown de cada presupuesto

**Funcionamiento:**
- Muestra modal con lista de tareas disponibles
- Filtros aplicados:
  - ✅ Solo tareas con status 'Pendiente' o 'En Lugar' (NO 'Cerrado')
  - ✅ Solo tareas SIN presupuesto asociado (`colppy_budget_id IS NULL`)
- Permite selección múltiple con checkboxes
- Incluye "Seleccionar todas" en el encabezado
- Al asociar:
  - Guarda `colppy_budget_id` (ID del presupuesto en Colppy)
  - Guarda `colppy_budget_number` (número visible de factura, ej: "0002-00000123")
  - Actualiza múltiples tareas en una sola operación
  - Recarga automáticamente la tabla de presupuestos

**Validaciones:**
- Verifica que tareas seleccionadas estén disponibles (sin presupuesto y no cerradas)
- Evita asociar tareas ya vinculadas a otros presupuestos
- Muestra mensajes claros de éxito o error

---

### 2. ✅ Modal de tareas asociadas
**Ubicación:** Panel de presupuestos, botón "Ver Tareas Asociadas"

**Funcionamiento:**
- **Si hay 1 tarea:** Abre directamente el modal de detalle de tarea (`#showjob`)
- **Si hay múltiples tareas:** Muestra lista con botones para ver cada una
- Usa el modal existente de "ver detalle" del sistema (no crea modal nuevo)
- Utiliza la función `show_task_full_info()` de `jobdetail.js`
- **Fallback:** Si la función no está disponible, abre en nueva pestaña

---

### 3. ✅ Visualización en cards de tareas (Home)
**Ubicación:** Vista principal (home), tarjetas de tareas

**Implementación:**
- Badge verde debajo de "OT #" cuando hay presupuesto asociado
- Formato: `Factura #[número]` (ej: "Factura #0002-00000123")
- Ícono de factura incluido
- Solo se muestra si `colppy_budget_number` tiene valor

**Apariencia:**
```blade
OT #123
[Badge Estado]
[Badge Verde] Factura #0002-00000123  <-- NUEVO
```

---

### 4. ✅ Visualización en tabla de tareas
**Ubicación:** Vista de tabla de trabajos (/jobs)

**Implementación:**
- Badge compacto debajo del número de OT en la columna "Orden"
- Color verde con ícono de factura
- Formato pequeño para no ocupar mucho espacio
- Tooltip con información al pasar el mouse

**Apariencia:**
```
Orden
┌─────────┐
│ OT #123 │
│ 🧾 0002-│  <-- Badge factura (verde, compacto)
│ 00000123│
└─────────┘
```

---

### 5. ✅ Campo de número de factura en BD
**Nueva migración:** `2026_03_12_100000_add_colppy_budget_number_to_jobs_table.php`

**Campo agregado a tabla `jobs`:**
- `colppy_budget_number` VARCHAR(50) NULLABLE
- Posición: después de `colppy_budget_id`
- Almacena el número visible de factura (ej: "0002-00000123")

**Campos relacionados (ya existentes):**
- `colppy_budget_id`: ID interno del presupuesto en Colppy

---

## 📁 ARCHIVOS MODIFICADOS/CREADOS

### Backend

#### Migraciones
✅ **NUEVO:** `panel/database/migrations/2026_03_12_100000_add_colppy_budget_number_to_jobs_table.php`
- Agrega campo `colppy_budget_number` a tabla `jobs`

#### Controladores
✅ **MODIFICADO:** `panel/app/Http/Controllers/BudgetController.php`
- Método `getAvailableJobs()` - Obtiene tareas disponibles para asociar
- Método `associateJobs()` - Asocia una o varias tareas a un presupuesto

#### Modelos
✅ **MODIFICADO:** `panel/app/Models/Job.php`
- Query `getJobsQuery()` actualizado para incluir:
  - `C.colppy_budget_id`
  - `C.colppy_budget_number`

#### Rutas
✅ **MODIFICADO:** `panel/routes/web.php`
- `POST /budgets/available-jobs` → `BudgetController@getAvailableJobs`
- `POST /budgets/associate-jobs` → `BudgetController@associateJobs`

---

### Frontend

#### JavaScript
✅ **MODIFICADO:** `public/assets/js/local/budget.js`
- Función `asociarTareaExistente()` - Implementación completa del modal
- Función `mostrarModalAsociarTareas()` - Renderiza modal con checkboxes
- Función `guardarAsociacionTareas()` - AJAX para guardar asociaciones
- Función `verTareasAsociadas()` - Implementación con modal de detalle
- Función `abrirModalDetalleTarea()` - Helper para abrir modal existente

✅ **MODIFICADO:** `public/assets/js/local/job.js`
- Función `tableregister()` - Agregado badge de factura en columna "Orden"

---

#### Vistas Blade
✅ **MODIFICADO:** `panel/resources/views/home/cards-opcion2.blade.php`
- Agregado badge de factura debajo de "OT #" en cards
- Condicional: solo si `colppy_budget_number` tiene valor

✅ **SIN CAMBIOS:** `panel/resources/views/budget/show.blade.php`
- Modal de detalle ya existente, funciona correctamente

---

## 🗃️ ESTRUCTURA DE BASE DE DATOS

### Tabla: `jobs`

```sql
-- Campos relacionados con presupuestos Colppy
colppy_budget_id       VARCHAR(50)   NULL    -- ID del presupuesto en Colppy
colppy_budget_number   VARCHAR(50)   NULL    -- Número visible de factura (NUEVO)
```

**Lógica de asociación:**
- Ambos campos se guardan al asociar una tarea a un presupuesto
- `colppy_budget_id`: ID interno para consultas API
- `colppy_budget_number`: Número legible para mostrar al usuario

---

## 🔄 FLUJO DE TRABAJO

### Flujo 1: Asociar tarea a presupuesto

```
1. Usuario ingresa a panel de presupuestos (/budgets)
2. Click en dropdown → "Asociar a Tarea"
3. Se ejecuta función asociarTareaExistente(idFactura)
   ├─ AJAX: Obtiene detalle del presupuesto (nroFactura)
   ├─ AJAX: Obtiene tareas disponibles (filtradas)
   └─ Muestra modal con lista de tareas
4. Usuario selecciona una o varias tareas (checkboxes)
5. Click "Asociar Seleccionadas"
6. Se ejecuta guardarAsociacionTareas()
   └─ AJAX POST /budgets/associate-jobs
      ├─ Validaciones en backend
      ├─ Actualiza jobs.colppy_budget_id
      ├─ Actualiza jobs.colppy_budget_number
      └─ Retorna respuesta
7. Mensaje de éxito y recarga de tabla
```

### Flujo 2: Ver tareas asociadas

```
1. Usuario ingresa a panel de presupuestos
2. Click en dropdown → "Ver Tareas Asociadas"
3. Se ejecuta función verTareasAsociadas(idsTareas)
   ├─ Si 1 tarea: abrirModalDetalleTarea(jobId)
   │  └─ Llama a show_task_full_info(jobId) [jobdetail.js]
   │     └─ Abre modal #showjob con detalle completo
   └─ Si múltiples: muestra lista con botones
      └─ Click en tarea → abrirModalDetalleTarea(jobId)
```

---

## 🧪 TESTING Y VALIDACIÓN

### Pasos para probar

#### 1. Ejecutar migración
```bash
cd panel
php artisan migrate
```

Verificar que el campo `colppy_budget_number` existe:
```sql
DESCRIBE jobs;
-- Debe aparecer colppy_budget_number VARCHAR(50) NULL
```

#### 2. Probar asociación de tareas

**En panel de presupuestos:**
1. Ir a `/budgets`
2. Localizar un presupuesto (ej: Factura #0002-00000123)
3. Click en menú (⋮) → "Asociar a Tarea"
4. Verificar que solo aparecen tareas:
   - Status "Pendiente" o "En Lugar"
   - Sin presupuesto asociado
5. Seleccionar una o varias tareas
6. Click "Asociar Seleccionadas"
7. Verificar mensaje de éxito

**Verificar en BD:**
```sql
SELECT id, colppy_budget_id, colppy_budget_number, closed_datetime, archived
FROM jobs
WHERE colppy_budget_id IS NOT NULL;
```

#### 3. Probar visualización en cards (Home)

1. Ir a `/` o `/home`
2. Localizar una tarea asociada a presupuesto
3. Verificar badge verde debajo de "OT #"
4. Formato esperado: "🧾 Factura #0002-00000123"

#### 4. Probar visualización en tabla

1. Ir a `/jobs`
2. Localizar tarea asociada en la columna "Orden"
3. Verificar badge compacto debajo del número OT

#### 5. Probar ver tareas asociadas

**Caso 1 tarea:**
1. Volver a `/budgets`
2. Presupuesto con 1 tarea → Click "Ver Tareas Asociadas"
3. Debe abrir modal de detalle de tarea directamente

**Caso múltiples tareas:**
1. Asociar otra tarea al mismo presupuesto
2. Click "Ver Tareas Asociadas"
3. Debe mostrar lista con botones
4. Click en cualquier tarea → abre modal de detalle

---

## 🚨 VALIDACIONES Y RESTRICCIONES

### Backend (BudgetController)

**getAvailableJobs():**
- ✅ Solo tareas NO eliminadas (`whereNull('deleted_at')`)
- ✅ Sin presupuesto asociado (`whereNull('colppy_budget_id')`)
- ✅ NO cerradas (`whereNull('closed_datetime')`)

**associateJobs():**
- ✅ Validación de campos requeridos (budget_id, budget_number, job_ids[])
- ✅ Verificación de existencia de tareas en BD
- ✅ Control de tareas no disponibles (ya asociadas o cerradas)
- ✅ Actualización atómica con timestamps

### Frontend (budget.js)

**Validaciones en modal:**
- ✅ Requiere al menos 1 tarea seleccionada
- ✅ Manejo de errores AJAX
- ✅ Loading states (spinners)
- ✅ Mensajes descriptivos al usuario

---

## 🎨 ESTILOS Y UI

### SweetAlert (Versión antigua)
Recuerda que este proyecto usa **versión antigua de SweetAlert**:
- ✅ Usar `type:` en lugar de `icon:`
- ✅ Validar con `result.value === true` en lugar de `result.isConfirmed`

### Badges en Cards
```blade
<span class="badge bg-success rounded-pill px-3 py-2" style="font-size: 0.7rem;">
  <i class="fas fa-file-invoice me-1"></i>Factura #0002-00000123
</span>
```

### Badges en Tabla
```javascript
<span class="badge bg-success rounded-pill px-2 py-1" style="font-size: 0.7rem;" title="Factura Colppy asociada">
  <i class="fas fa-file-invoice me-1"></i>0002-00000123
</span>
```

---

## 📝 NOTAS IMPORTANTES

### ⚠️ Restricciones de Colppy
- **SOLO LECTURA:** No se pueden crear/editar presupuestos en Colppy desde nuestro sistema
- **PERMITIDO:** Obtener (GET) presupuestos y productos
- **ASOCIACIÓN:** Es local, solo vincula el ID y número de factura con tareas

### 🔄 Sincronización
- Los presupuestos se consultan en tiempo real desde Colppy
- No hay caché, siempre datos actualizados
- Las tareas asociadas se almacenan localmente

### 🎯 Status de tareas válidos
Para asociar a presupuesto:
- ✅ "Pendiente" (arrival_datetime IS NULL, closed_datetime IS NULL)
- ✅ "En Lugar" (arrival_datetime IS NOT NULL, closed_datetime IS NULL)
- ❌ "Cerrado" (closed_datetime IS NOT NULL) - NO PERMITIDO

---

## 🔧 TROUBLESHOOTING

### Problema: Modal de asociar no muestra tareas

**Causas posibles:**
1. Todas las tareas ya tienen presupuesto asociado
2. Todas las tareas están cerradas
3. No hay tareas en el sistema

**Solución:**
- Crear tareas nuevas sin presupuesto
- Verificar query en `getAvailableJobs()`
- Revisar consola del navegador (F12) para errores AJAX

---

### Problema: No se guarda la asociación

**Causas posibles:**
1. Tarea ya asociada a otro presupuesto
2. Tarea cerrada entre la consulta y el guardado
3. Error de validación en backend

**Solución:**
- Revisar logs Laravel: `storage/logs/laravel.log`
- Verificar respuesta AJAX en consola
- Verificar permisos de BD

---

### Problema: No se ve el badge de factura en cards

**Causas posibles:**
1. Campo `colppy_budget_number` no se guardó
2. Query no incluye el campo
3. Caché de vista

**Solución:**
```bash
# Verificar query en Job.php
php artisan tinker
> Job::getJobsQuery()->where('id', 123)->first();

# Limpiar caché
php artisan view:clear
php artisan route:clear
```

---

### Problema: Modal de detalle de tarea no abre

**Causas posibles:**
1. Función `show_task_full_info()` no está definida
2. Modal `#showjob` no existe en la vista
3. Script `jobdetail.js` no cargado

**Solución:**
- Verificar que `jobdetail.js` está incluido en layout
- Verificar que vista incluye `@include('job.show')`
- Usar fallback de abrir en nueva pestaña

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

- [x] Migración de campo `colppy_budget_number` creada
- [x] Métodos en `BudgetController` implementados
- [x] Rutas agregadas en `web.php`
- [x] Query en `Job.php` actualizado
- [x] Función `asociarTareaExistente()` implementada
- [x] Modal de asociar con checkboxes funcional
- [x] Función `verTareasAsociadas()` con modal de detalle
- [x] Badge de factura en cards de home
- [x] Badge de factura en tabla de trabajos
- [x] Validaciones backend completas
- [x] Manejo de errores frontend
- [x] Documentación completa

---

## 🚀 PRÓXIMOS PASOS (FUTURO)

Funcionalidades que podrían agregarse:

1. **Desasociar tarea de presupuesto:**
   - Botón para quitar asociación
   - Solo si tarea no está cerrada

2. **Historial de asociaciones:**
   - Tabla de auditoría
   - Ver quién asoció y cuándo

3. **Notificaciones:**
   - Avisar a técnicos cuando se asocia presupuesto a su tarea
   - Email o notificación push

4. **Reportes:**
   - Tareas con/sin presupuesto
   - Presupuestos asociados en período

5. **Filtros en tabla de tareas:**
   - Filtrar por presupuesto asociado
   - Buscar por número de factura

---

## 📞 SOPORTE

Si hay problemas con la implementación:

1. Revisar logs: `panel/storage/logs/laravel.log`
2. Verificar consola del navegador (F12)
3. Verificar BD: campos `colppy_budget_id` y `colppy_budget_number`
4. Revisar documentación de Colppy: `docs/INTEGRACION_COLPPY.md`

---

**Documentación creada:** 12/03/2026  
**Última actualización:** 12/03/2026  
**Versión:** 1.0.0
