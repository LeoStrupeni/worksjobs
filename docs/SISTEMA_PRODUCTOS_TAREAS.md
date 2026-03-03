# 📦 Sistema de Productos en Tareas

> Sistema para agregar, gestionar y visualizar productos relacionados con tareas (jobs)

**Fecha de creación**: 2 de marzo de 2026  
**Estado**: ✅ **IMPLEMENTADO Y ACTIVO**

---

## 📋 Índice

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Base de Datos](#base-de-datos)
4. [Modelos y Relaciones](#modelos-y-relaciones)
5. [Controladores y Rutas](#controladores-y-rutas)
6. [Vistas y Modales](#vistas-y-modales)
7. [JavaScript y Funciones](#javascript-y-funciones)
8. [Casos de Uso](#casos-de-uso)
9. [Validaciones y Reglas de Negocio](#validaciones-y-reglas-de-negocio)
10. [Troubleshooting](#troubleshooting)

---

## 🎯 Resumen Ejecutivo

### ¿Qué es?

Sistema que permite asociar productos a tareas (jobs) con información histórica (snapshot) de cada producto al momento de agregarlo. Esto garantiza trazabilidad incluso si el producto original es modificado o eliminado.

### ¿Dónde se puede usar?

- **Modal de Crear Tarea**: Al crear una nueva tarea
- **Modal de Editar Tarea**: Al modificar una tarea existente (solo si NO arribó)
- **Modal de Productos Directos**: Desde las cards del home o tabla de tareas (disponible en cualquier estado excepto Cerrado Y Archivado)

### Características Principales

✅ Datos históricos (idcolppy, código, descripción)  
✅ Tipos de unidad: Unidad, Rollo, Metros  
✅ Cantidades decimales (0.01 step)  
✅ Búsqueda en vivo de productos  
✅ Soporte para productos duplicados (mismo producto múltiples veces)  
✅ Identificadores únicos con contador incremental  
✅ Soft deletes para auditoría  

---

## 🏗️ Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────────┐
│                       INTERFAZ DE USUARIO                    │
├────────────────┬────────────────┬───────────────────────────┤
│  Modal Create  │  Modal Edit    │  Modal Add Products       │
│  (Crear tarea) │  (Editar)      │  (Agregar directo)        │
└────────┬───────┴────────┬───────┴───────────┬───────────────┘
         │                │                    │
         └────────────────┴────────────────────┘
                          │
                   jobdetail.js
                   ┌──────┴──────────┐
                   │ Global State:   │
                   │ selectedProducts│
                   │ (array)         │
                   └──────┬──────────┘
                          │
         ┌────────────────┼────────────────┐
         │                │                │
    addProduct    removeProduct    renderProducts
         │                │                │
         └────────────────┴────────────────┘
                          │
              ┌───────────┴───────────┐
              │   AJAX Requests       │
              │   /api/searchvar      │
              │   /jobs/{id}/edit     │
              │   /jobs/{id}/products │
              └───────────┬───────────┘
                          │
         ┌────────────────┴────────────────┐
         │      BACKEND CONTROLLERS         │
         │   JobController                  │
         │   ApiSearchVarController         │
         └────────────────┬────────────────┘
                          │
         ┌────────────────┴────────────────┐
         │           MODELOS                │
         │   Job ← hasMany → JobProduct    │
         │   Product ← belongsTo           │
         └────────────────┬────────────────┘
                          │
              ┌───────────┴──────────┐
              │   TABLA job_products │
              └──────────────────────┘
```

---

## 💾 Base de Datos

### Tabla: `job_products`

**Archivo de migración**: `database/migrations/2026_03_02_000000_create_job_products_table.php`

```php
Schema::create('job_products', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('job_id');
    $table->unsignedBigInteger('product_id')->nullable(); // Nullable por si producto eliminado
    
    // Datos históricos (snapshot)
    $table->string('idcolppy')->nullable();
    $table->string('codigo', 100);
    $table->string('descripcion', 255);
    
    // Datos de la relación
    $table->enum('unit_type', ['Unidad', 'Rollo', 'Metros'])->default('Unidad');
    $table->decimal('quantity', 10, 2);
    
    $table->timestamps();
    $table->softDeletes();
    
    // Foreign key con cascade delete
    $table->foreign('job_id')
        ->references('id')
        ->on('jobs')
        ->onDelete('cascade');
});
```

### Campos Importantes

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `job_id` | FK | Referencia a la tarea |
| `product_id` | FK nullable | ID del producto original (puede ser null si fue eliminado) |
| `idcolppy` | string | ID de Colppy del producto (snapshot histórico) |
| `codigo` | string | Código del producto al momento de agregarlo |
| `descripcion` | string | Descripción del producto al momento de agregarlo |
| `unit_type` | enum | Unidad, Rollo o Metros |
| `quantity` | decimal | Cantidad con 2 decimales |
| `deleted_at` | timestamp | Soft delete para auditoría |

---

## 🔗 Modelos y Relaciones

### JobProduct Model

**Archivo**: `app/Models/JobProduct.php`

```php
class JobProduct extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'job_id',
        'product_id',
        'idcolppy',
        'codigo',
        'descripcion',
        'unit_type',
        'quantity'
    ];
    
    // Relación: pertenece a una tarea
    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }
    
    // Relación: referencia al producto original (puede no existir)
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
```

### Job Model (actualizado)

**Archivo**: `app/Models/Job.php`

```php
// Agregado al modelo existente
public function products()
{
    return $this->hasMany(JobProduct::class, 'job_id');
}
```

---

## 🎮 Controladores y Rutas

### JobController

**Archivo**: `app/Http/Controllers/JobController.php`

#### 1. `store()` - Crear tarea con productos

```php
public function store(Request $request)
{
    // ... validaciones y creación de job ...
    
    // Agregar productos si existen
    if ($request->has('products') && is_array($request->products)) {
        foreach ($request->products as $p) {
            $product = Product::find($p['product_id']);
            JobProduct::create([
                'job_id' => $job->id,
                'product_id' => $product->id,
                'idcolppy' => $product->idcolppy,
                'codigo' => $product->codigo,
                'descripcion' => $product->descripcion,
                'unit_type' => $p['unit_type'],
                'quantity' => $p['quantity']
            ]);
        }
    }
}
```

#### 2. `update()` - Editar tarea con productos

```php
public function update(Request $request, $id)
{
    // ... validaciones y actualización de job ...
    
    // Eliminar productos existentes (soft delete)
    JobProduct::where('job_id', $id)->delete();
    
    // Recrear productos desde el request
    if ($request->has('products') && is_array($request->products)) {
        foreach ($request->products as $p) {
            $product = Product::find($p['product_id']);
            JobProduct::create([
                'job_id' => $id,
                'product_id' => $product->id,
                'idcolppy' => $product->idcolppy,
                'codigo' => $product->codigo,
                'descripcion' => $product->descripcion,
                'unit_type' => $p['unit_type'],
                'quantity' => $p['quantity']
            ]);
        }
    }
}
```

#### 3. `edit()` - Incluir productos en respuesta

```php
public function edit($id)
{
    $data['products'] = JobProduct::where('job_id', $id)
        ->whereNull('deleted_at')
        ->get();
    
    return response()->json($data);
}
```

#### 4. `updateProducts()` - Agregar productos directamente

**Nuevo método** para agregar productos sin editar toda la tarea.

```php
public function updateProducts(Request $request, $id)
{
    try {
        $job = Job::findOrFail($id);
        
        // Validar que NO esté cerrado Y archivado
        if ($job->closed_datetime != null && $job->archived == 1) {
            return redirect()->back()
                ->with('error', 'No se pueden agregar productos a una tarea cerrada y archivada');
        }
        
        // Eliminar productos existentes
        JobProduct::where('job_id', $id)->delete();
        
        // Agregar nuevos productos
        if ($request->has('products') && is_array($request->products)) {
            foreach ($request->products as $p) {
                $product = Product::find($p['product_id']);
                if ($product) {
                    JobProduct::create([
                        'job_id' => $id,
                        'product_id' => $product->id,
                        'idcolppy' => $product->idcolppy,
                        'codigo' => $product->codigo,
                        'descripcion' => $product->descripcion,
                        'unit_type' => $p['unit_type'],
                        'quantity' => $p['quantity']
                    ]);
                }
            }
        }
        
        return redirect()->back()->with('success', 'Productos actualizados correctamente');
    } catch (\Exception $e) {
        Log::error('Error al actualizar productos: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Error al actualizar productos');
    }
}
```

### ApiJobController

**Archivo**: `app/Http/Controllers/Api/ApiJobController.php`

```php
public function show($id)
{
    // ... obtener datos del job ...
    
    // Incluir productos en la respuesta
    $jobData['products'] = JobProduct::where('job_id', $id)
        ->whereNull('deleted_at')
        ->get();
    
    return response()->json($jobData);
}
```

### Rutas

**Archivo**: `routes/web.php`

```php
Route::resource('/jobs', JobController::class);
Route::put('/jobs/{id}/products', [JobController::class, 'updateProducts'])
    ->name('job.products');
```

---

## 🎨 Vistas y Modales

### 1. Modal de Crear (`job/create.blade.php`)

Incluido en `layout.blade.php` (disponible en todas las páginas).

**Estructura**:
```blade
<div class="card">
    <div class="card-body">
        <h6>Productos Relacionados</h6>
        
        <div class="row">
            <!-- Columna 1: Select de productos -->
            <div class="col-md-5">
                <select id="product_id_create" 
                    class="form-control selectpicker searchvar"
                    data-live-search="true">
                    <!-- Opciones -->
                </select>
            </div>
            
            <!-- Columna 2: Tipo de unidad -->
            <div class="col-md-3">
                <select id="unit_type_create">
                    <option value="Unidad">Unidad</option>
                    <option value="Rollo">Rollo</option>
                    <option value="Metros">Metros</option>
                </select>
            </div>
            
            <!-- Columna 3: Cantidad -->
            <div class="col-md-3">
                <input type="number" id="quantity_create" 
                    value="1.00" step="0.01" min="0.01">
            </div>
            
            <!-- Columna 4: Botón agregar -->
            <div class="col-md-1">
                <button onclick="addProductToJob('create')">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
        
        <!-- Lista de productos agregados -->
        <div id="products_list_create"></div>
        
        <!-- Inputs ocultos para el formulario -->
        <div id="products_hidden_create"></div>
    </div>
</div>
```

### 2. Modal de Editar (`job/edit.blade.php`)

Incluido en `jobs.blade.php` y `home.blade.php`.

**Idéntico a create.blade.php** pero con sufijo `_edit` en todos los IDs.

### 3. Modal de Ver (`job/show.blade.php`)

Vista de **solo lectura** de productos.

```blade
<div id="products_show_container" class="d-none">
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Descripción</th>
                <th>Tipo</th>
                <th>Cantidad</th>
            </tr>
        </thead>
        <tbody id="products_show_tbody"></tbody>
    </table>
</div>
```

### 4. Modal de Productos Directos (`job/products.blade.php`)

**Nuevo modal** para agregar productos sin editar toda la tarea.

```blade
<div class="modal" id="addproducts">
    <form id="formaddproducts" method="POST">
        @csrf
        @method('PUT')
        
        <!-- Spinner de carga -->
        <div id="modal-body-addproducts-roller">
            <div class="lds-roller">...</div>
        </div>
        
        <!-- Contenido principal (oculto inicialmente) -->
        <div id="modal-body-addproducts" class="d-none">
            <div class="alert alert-info">
                <strong>Tarea:</strong> 
                <span id="addproducts_task_name"></span>
            </div>
            
            <!-- Formulario idéntico a create/edit -->
            <div id="products_list_add"></div>
            <div id="products_hidden_add"></div>
        </div>
        
        <div id="modal-foot-addproducts" class="d-none">
            <button type="submit">Guardar Productos</button>
        </div>
    </form>
</div>
```

**Incluido en**: `home.blade.php` y `jobs.blade.php`

---

## ⚙️ JavaScript y Funciones

**Archivo**: `public/assets/js/local/jobdetail.js`

### Estado Global

```javascript
var selectedProducts = []; // Array de productos seleccionados
var productUniqueIdCounter = 0; // Contador para IDs únicos
```

### Estructura de Objetos

```javascript
{
    unique_id: 1,              // ID único incremental
    product_id: "25",          // ID del producto (string)
    codigo: "ABC123",          // Código histórico
    descripcion: "Cable UTP",  // Descripción histórica
    unit_type: "Metros",       // Unidad/Rollo/Metros
    quantity: 15.50,           // Cantidad decimal
    mode: "create"             // create/edit/add
}
```

### 1. `addProductToJob(mode)`

Agrega un producto al array `selectedProducts`.

```javascript
function addProductToJob(mode) {
    const productSelect = $(`#product_id_${mode}`);
    const productId = productSelect.val();
    const productText = productSelect.find('option:selected').text();
    const unitType = $(`#unit_type_${mode}`).val();
    const quantity = parseFloat($(`#quantity_${mode}`).val());
    
    // Validaciones
    if (!productId) {
        toastr["warning"]("Debe seleccionar un producto");
        return;
    }
    
    if (quantity <= 0 || isNaN(quantity)) {
        toastr["warning"]("La cantidad debe ser mayor a 0");
        return;
    }
    
    // Verificar duplicados (mismo producto, mismo mode)
    const exists = selectedProducts.some(p => 
        String(p.product_id) === String(productId) && p.mode === mode
    );
    if (exists) {
        toastr["warning"]("Este producto ya está agregado a la tarea");
        return;
    }
    
    // Crear objeto producto
    const product = {
        unique_id: ++productUniqueIdCounter,
        product_id: String(productId),
        codigo: productText.split(' - ')[0],
        descripcion: productText.split(' - ')[1] || productText,
        unit_type: unitType,
        quantity: quantity,
        mode: mode
    };
    
    selectedProducts.push(product);
    renderProductsList(mode);
    
    // Limpiar campos
    productSelect.val('').selectpicker('refresh');
    $(`#unit_type_${mode}`).val('Unidad');
    $(`#quantity_${mode}`).val('1.00');
    
    toastr["success"]("Producto agregado correctamente");
}
```

### 2. `removeProductFromJob(uniqueId, mode)`

Elimina un producto por su ID único.

```javascript
function removeProductFromJob(uniqueId, mode) {
    selectedProducts = selectedProducts.filter(p => p.unique_id !== uniqueId);
    renderProductsList(mode);
    toastr["info"]("Producto eliminado");
}
```

**⚠️ Importante**: Se usa `unique_id` en lugar de `product_id` para permitir el mismo producto múltiples veces.

### 3. `renderProductsList(mode)`

Renderiza la lista visual y los inputs ocultos para el formulario.

```javascript
function renderProductsList(mode) {
    const productsForMode = selectedProducts.filter(p => p.mode === mode);
    const listContainer = $(`#products_list_${mode}`);
    const hiddenContainer = $(`#products_hidden_${mode}`);
    
    // Si no hay productos, limpiar y salir
    if (productsForMode.length === 0) {
        listContainer.empty().removeClass('mt-3');
        hiddenContainer.empty();
        return;
    }
    
    // Renderizar tabla visual
    let html = '<div class="table-responsive"><table class="table table-sm table-hover">';
    html += '<thead><tr><th>Código</th><th>Descripción</th><th>Tipo</th><th>Cantidad</th><th>Acción</th></tr></thead><tbody>';
    
    productsForMode.forEach(product => {
        html += `<tr>
            <td><strong>${product.codigo}</strong></td>
            <td>${product.descripcion}</td>
            <td><span class="badge bg-secondary">${product.unit_type}</span></td>
            <td class="text-end">${parseFloat(product.quantity).toFixed(2)}</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger" 
                    onclick="removeProductFromJob(${product.unique_id}, '${mode}')">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    listContainer.html(html).addClass('mt-3');
    
    // Agregar campos ocultos para envío del formulario
    hiddenContainer.empty();
    productsForMode.forEach((product, index) => {
        hiddenContainer.append(`
            <input type="hidden" name="products[${index}][product_id]" value="${product.product_id}">
            <input type="hidden" name="products[${index}][unit_type]" value="${product.unit_type}">
            <input type="hidden" name="products[${index}][quantity]" value="${product.quantity}">
        `);
    });
}
```

### 4. `loadProductsToEdit(products)`

Carga productos existentes al abrir el modal de edición.

```javascript
function loadProductsToEdit(products) {
    // Limpiar productos anteriores del modo edit
    selectedProducts = selectedProducts.filter(p => p.mode !== 'edit');
    
    if (!products || products.length === 0) {
        renderProductsList('edit');
        return;
    }
    
    products.forEach(product => {
        selectedProducts.push({
            unique_id: ++productUniqueIdCounter,
            product_id: String(product.product_id),
            codigo: product.codigo,
            descripcion: product.descripcion,
            unit_type: product.unit_type,
            quantity: parseFloat(product.quantity),
            mode: 'edit'
        });
    });
    
    renderProductsList('edit');
}
```

### 5. Evento Click - Modal de Productos Directos

```javascript
$('body').on('click', '.addproducts-job', function () {
    const jobId = $(this).data('id');
    const jobName = $(this).data('name');
    
    // Limpiar productos del modo 'add'
    selectedProducts = selectedProducts.filter(p => p.mode !== 'add');
    renderProductsList('add');
    
    // Configurar modal
    $('#formaddproducts').attr('action', app_url + "/jobs/" + jobId + "/products");
    $('#addproducts_task_name').text(jobName);
    
    // Abrir modal con spinner
    $('#modal-body-addproducts-roller').removeClass('d-none');
    $('#modal-body-addproducts').addClass('d-none');
    $('#modal-foot-addproducts').addClass('d-none');
    $('#addproducts').modal('show');
    
    // Cargar productos actuales
    $.ajax({
        url: app_url + '/jobs/' + jobId + '/edit',
        type: 'GET',
        success: function(data) {
            if (data.products && data.products.length > 0) {
                data.products.forEach(product => {
                    selectedProducts.push({
                        unique_id: ++productUniqueIdCounter,
                        product_id: String(product.product_id),
                        codigo: product.codigo,
                        descripcion: product.descripcion,
                        unit_type: product.unit_type,
                        quantity: parseFloat(product.quantity),
                        mode: 'add'
                    });
                });
                renderProductsList('add');
            }
        },
        error: function() {
            toastr["error"]("Error al cargar los productos de la tarea");
        },
        complete: function() {
            // Ocultar spinner y mostrar contenido
            $('#modal-body-addproducts-roller').addClass('d-none');
            $('#modal-body-addproducts').removeClass('d-none');
            $('#modal-foot-addproducts').removeClass('d-none');
        }
    });
});
```

### 6. Limpiar al cerrar modales

```javascript
$('#createjob').on('hidden.bs.modal', function () {
    selectedProducts = selectedProducts.filter(p => p.mode !== 'create');
});

$('#editjob').on('hidden.bs.modal', function () {
    selectedProducts = selectedProducts.filter(p => p.mode !== 'edit');
});

$('#addproducts').on('hidden.bs.modal', function () {
    selectedProducts = selectedProducts.filter(p => p.mode !== 'add');
});
```

---

## 💼 Casos de Uso

### Caso 1: Crear Tarea con Productos

**Flujo**:
1. Usuario hace click en "Nuevo" para crear tarea
2. Llena datos básicos (cliente, domicilio, fecha, descripción)
3. En la sección "Productos Relacionados":
   - Busca producto escribiendo en el select (AJAX en vivo)
   - Selecciona tipo de unidad
   - Ingresa cantidad
   - Click en botón "+"
4. Producto aparece en la lista visual
5. Repite para agregar más productos
6. Click en "Guardar"
7. Backend crea `Job` y múltiples `JobProduct` con datos históricos

### Caso 2: Editar Tarea con Productos

**Flujo**:
1. Usuario hace click en "Editar" en una tarea pendiente
2. Modal se abre con spinner
3. AJAX carga datos incluyendo productos existentes
4. `loadProductsToEdit()` renderiza los productos actuales
5. Usuario puede:
   - Agregar más productos
   - Eliminar productos existentes (click en ícono basura)
   - Modificar datos de la tarea
6. Click en "Guardar"
7. Backend elimina (soft delete) productos anteriores y crea los nuevos

**Restricción**: Solo disponible si tarea NO ha arribado (`arrival_datetime` es null).

### Caso 3: Agregar Productos Directamente

**Ubicaciones**:
- Botón en el footer de las cards del home (ícono de caja)
- Opción en menú dropdown de la tabla de tareas

**Flujo**:
1. Usuario hace click en botón/opción "Agregar Productos"
2. Modal se abre inmediatamente con spinner
3. AJAX carga productos actuales de la tarea
4. Spinner desaparece, muestra formulario con productos cargados
5. Usuario puede:
   - Agregar más productos
   - Eliminar productos
   - Modificar cantidades/tipos
6. Click en "Guardar Productos"
7. Backend actualiza productos sin tocar otros datos de la tarea

**Ventaja**: No necesita ir a "Editar" para modificar solo productos.

**Disponibilidad**:
- ✅ Tarea Pendiente
- ✅ Tarea En Lugar (arribó)
- ✅ Tarea Cerrada (pero NO archivada)
- ❌ Tarea Cerrada Y Archivada

### Caso 4: Ver Productos en Tarea

**Flujo**:
1. Usuario hace click en "Ver Detalles"
2. Modal muestra todos los datos de la tarea
3. Si tiene productos, sección "Productos Relacionados" visible
4. Tabla de solo lectura muestra: Código, Descripción, Tipo, Cantidad
5. Si no tiene productos, sección no se muestra

---

## ✅ Validaciones y Reglas de Negocio

### Validaciones de Frontend (JavaScript)

```javascript
// 1. Producto debe estar seleccionado
if (!productId) {
    toastr["warning"]("Debe seleccionar un producto");
    return;
}

// 2. Cantidad debe ser mayor a 0
if (quantity <= 0 || isNaN(quantity)) {
    toastr["warning"]("La cantidad debe ser mayor a 0");
    return;
}

// 3. No permitir duplicados (mismo producto en mismo modo)
const exists = selectedProducts.some(p => 
    String(p.product_id) === String(productId) && p.mode === mode
);
if (exists) {
    toastr["warning"]("Este producto ya está agregado a la tarea");
    return;
}
```

### Validaciones de Backend (PHP)

```php
// 1. Solo editar productos si tarea NO cerrada Y archivada
if ($job->closed_datetime != null && $job->archived == 1) {
    return redirect()->back()
        ->with('error', 'No se pueden agregar productos a una tarea cerrada y archivada');
}

// 2. Verificar que producto existe antes de insertar
$product = Product::find($p['product_id']);
if ($product) {
    JobProduct::create([...]);
}
```

### Reglas de Visibilidad de Botones

#### Modal de Editar Completo
```php
@if($j->arrival == null && $j->closed == null)
    <!-- Mostrar opción editar -->
@endif
```

#### Botón/Opción de Agregar Productos
```php
@if(in_array('update', $permissions) && !($j->estatus == 'Cerrado' && $j->archived == 1))
    <!-- Mostrar botón agregar productos -->
@endif
```

```javascript
const isClosedAndArchived = val.closed != null && val.archived == 1;
if(permissions.includes('update') && !isClosedAndArchived) {
    // Mostrar opción en dropdown
}
```

### Tipos de Datos

| Campo | Tipo | Min | Max | Step | Default |
|-------|------|-----|-----|------|---------|
| product_id | select | - | - | - | - |
| unit_type | select | - | - | - | "Unidad" |
| quantity | number | 0.01 | - | 0.01 | 1.00 |

---

## 🔍 Troubleshooting

### Problema 1: "No se ven los productos al editar"

**Síntomas**: Al abrir modal de editar, la sección de productos está vacía.

**Causas posibles**:
1. El método `edit()` no incluye productos en la respuesta
2. `loadProductsToEdit()` no se llama en el AJAX success
3. Productos fueron eliminados (soft delete) pero no se filtró `whereNull('deleted_at')`

**Solución**:
```javascript
// Verificar que se llama loadProductsToEdit
success: function(data) {
    // ...
    loadProductsToEdit(data.products);
}
```

```php
// En JobController::edit()
$data['products'] = JobProduct::where('job_id', $id)
    ->whereNull('deleted_at')
    ->get();
```

### Problema 2: "Al eliminar producto se borran todos los duplicados"

**Síntomas**: Si tengo 2 veces el mismo producto, al eliminar uno se borran ambos.

**Causa**: Se está usando `product_id` en lugar de `unique_id` para eliminar.

**Solución**:
```javascript
// ❌ INCORRECTO
function removeProductFromJob(productId, mode) {
    selectedProducts = selectedProducts.filter(p => 
        p.product_id !== productId
    );
}

// ✅ CORRECTO
function removeProductFromJob(uniqueId, mode) {
    selectedProducts = selectedProducts.filter(p => 
        p.unique_id !== uniqueId
    );
}

// En renderProductsList, usar unique_id en el onclick
onclick="removeProductFromJob(${product.unique_id}, '${mode}')"
```

### Problema 3: "Botón de agregar productos no funciona en cards"

**Síntomas**: Click en el botón de caja no hace nada.

**Causas posibles**:
1. Modal `job.products` no incluido en `home.blade.php`
2. Evento no registrado (falta `$('body').on('click', '.addproducts-job')`)
3. Clase CSS incorrecta en el botón

**Solución**:
```blade
{{-- En home.blade.php, dentro de @section('Content') --}}
@include('job.products')
```

```javascript
// Usar delegación de eventos con $('body')
$('body').on('click', '.addproducts-job', function() {
    // ...
});
```

### Problema 4: "Modal se abre lento / se congela"

**Síntomas**: Delay notable entre click y apertura del modal.

**Causa**: Modal se abre DESPUÉS del AJAX, usuario no ve feedback inmediato.

**Solución**:
```javascript
// ✅ Abrir modal INMEDIATAMENTE con spinner
$('#modal-body-addproducts-roller').removeClass('d-none');
$('#modal-body-addproducts').addClass('d-none');
$('#addproducts').modal('show');

// Luego hacer AJAX
$.ajax({
    // ...
    complete: function() {
        // Ocultar spinner y mostrar contenido
        $('#modal-body-addproducts-roller').addClass('d-none');
        $('#modal-body-addproducts').removeClass('d-none');
    }
});
```

### Problema 5: "Selectpicker no tiene estilos / se ve diferente"

**Síntomas**: Select de productos en modal Edit se ve con border azul y fondo blanco, mientras que en Create se ve gris.

**Causa**: CSS personalizado en `home.blade.php` solo para `#modal-body-edit-job .bootstrap-select`.

**Solución**:
```css
/* ❌ NO hacer esto - crea inconsistencia */
#modal-body-edit-job .bootstrap-select > .dropdown-toggle {
    border: 2px solid #0d6efd !important;
    background-color: white !important;
}

/* ✅ Eliminar estilos personalizados, usar defaults de bootstrap-select */
/* Dejar que Bootstrap-select aplique sus estilos por defecto (btn-light) */
```

### Problema 6: "Productos no se guardan al submit"

**Síntomas**: Al guardar tarea, productos no aparecen en base de datos.

**Causas posibles**:
1. Campos ocultos no se generan (`products_hidden_{mode}` vacío)
2. Nombre de inputs incorrecto
3. Backend no procesa array `products`

**Solución**:
```javascript
// Verificar que se crean inputs ocultos
hiddenContainer.append(`
    <input type="hidden" name="products[${index}][product_id]" value="${product.product_id}">
    <input type="hidden" name="products[${index}][unit_type]" value="${product.unit_type}">
    <input type="hidden" name="products[${index}][quantity]" value="${product.quantity}">
`);
```

```php
// En backend, verificar que existe y es array
if ($request->has('products') && is_array($request->products)) {
    foreach ($request->products as $p) {
        // ...
    }
}
```

### Problema 7: "Error al buscar productos rápido"

**Síntomas**: Al escribir rápido en el buscador, se ejecutan múltiples AJAX simultáneos.

**Causa**: No hay cancelación de requests anteriores ni validación de timestamp.

**Solución**: Ya implementado en el código actual con:
- `currentProductRequest.abort()` para cancelar request anterior
- `lastProductSearchTime` para validar solo la búsqueda más reciente
- Debounce de 400ms

### Problema 8: "No se puede limpiar campos de productos en Edit al abrir modal"

**Síntomas**: Al abrir modal de editar, campos de productos (quantity, unit_type) aparecen vacíos en lugar de tener valores por defecto.

**Causa**: El evento que limpia el formulario (`$( form.elements ).each`) también limpia los campos de productos.

**Solución**:
```javascript
$( form.elements ).each(function( index ) {
    var fieldName = $(this).attr('name');
    var fieldId = $(this).attr('id');
    
    // No limpiar campos de productos
    if(fieldName != '_method' && fieldName != '_token' && 
       fieldId != 'quantity_edit' && 
       fieldId != 'unit_type_edit' && 
       fieldId != 'product_id_edit'){
        $(this).val('');
    }
});
```

---

## 📊 Estadísticas y Métricas

### Archivos Modificados/Creados

| Archivo | Acción | Líneas |
|---------|--------|--------|
| `database/migrations/2026_03_02_000000_create_job_products_table.php` | Creado | 35 |
| `app/Models/JobProduct.php` | Creado | 30 |
| `app/Models/Job.php` | Modificado | +5 |
| `app/Http/Controllers/JobController.php` | Modificado | +80 |
| `app/Http/Controllers/Api/ApiJobController.php` | Modificado | +5 |
| `routes/web.php` | Modificado | +1 |
| `resources/views/job/create.blade.php` | Modificado | +50 |
| `resources/views/job/edit.blade.php` | Modificado | +50 |
| `resources/views/job/show.blade.php` | Modificado | +30 |
| `resources/views/job/products.blade.php` | Creado | 75 |
| `resources/views/home.blade.php` | Modificado | +1 |
| `resources/views/jobs.blade.php` | Modificado | +1 |
| `resources/views/home/cards-opcion2.blade.php` | Modificado | +7 |
| `public/assets/js/local/jobdetail.js` | Modificado | +200 |
| `public/assets/js/local/job.js` | Modificado | +10 |

**Total**: 15 archivos, ~580 líneas de código

### Tiempo de Desarrollo

Implementación completa: ~3 horas incluyendo debugging y ajustes visuales.

---

## 🚀 Próximas Mejoras Sugeridas

### Corto Plazo

- [ ] Permitir editar cantidad/tipo de producto sin eliminarlo y volverlo a agregar
- [ ] Mostrar stock disponible del producto al seleccionarlo
- [ ] Agregar validación de stock antes de guardar
- [ ] Exportar listado de productos a PDF/Excel

### Mediano Plazo

- [ ] Implementar en la app móvil (Flutter)
- [ ] Sincronizar productos con Colppy (si API lo permite)
- [ ] Dashboard de productos más usados por técnico
- [ ] Historial de cambios en productos de una tarea

### Largo Plazo

- [ ] Sistema de presupuestos basado en productos
- [ ] Integración con sistema de inventario
- [ ] Alertas automáticas de stock bajo
- [ ] Reportes de consumo por cliente/técnico/período

---

## 📝 Notas Finales

### Consideraciones Importantes

1. **Datos Históricos**: El sistema guarda un snapshot del producto (código, descripción, idcolppy) al momento de agregarlo. Esto garantiza trazabilidad aunque el producto original cambie o se elimine.

2. **Productos Duplicados**: Se permite agregar el mismo producto múltiples veces gracias al `unique_id` incremental.

3. **Soft Deletes**: Los productos eliminados no se borran físicamente, solo se marca `deleted_at`. Esto permite auditoría y recuperación si es necesario.

4. **Modos Independientes**: Los arrays de productos están separados por modo (create/edit/add), evitando conflictos entre modales abiertos simultáneamente.

5. **Performance**: La búsqueda de productos usa AJAX con debounce de 400ms y cancelación de requests anteriores para optimizar rendimiento.

### Contacto y Soporte

Para dudas o problemas con este sistema, consultar este documento primero. Si persiste el problema, revisar la sección [Troubleshooting](#troubleshooting).

---

**Documento creado el**: 2 de marzo de 2026  
**Última actualización**: 2 de marzo de 2026  
**Versión**: 1.0  
**Estado**: ✅ Activo y en producción
