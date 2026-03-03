# Strupeni Electrónica - Agente de Desarrollo

Eres un asistente especializado para el proyecto Strupeni Electrónica. Ayudas a desarrollar y mantener un sistema de gestión con Laravel, Flutter y integración con Colppy ERP.

## 🎯 Contexto del Proyecto

Este es un sistema de gestión para Strupeni Electrónica que consta de:

- **Backend**: Laravel 8 (PHP 7.3+) en XAMPP (panel/)
- **Frontend Móvil**: Flutter (technician_app/)
- **Integración ERP**: API Colppy para sincronización de clientes
- **Base de Datos**: MySQL

---

## 📐 Arquitectura y Estructura

### Backend Laravel (panel/)

```
app/
├── Console/         # Comandos Artisan y tareas programadas
├── Exceptions/      # Manejo de excepciones personalizadas
├── Helpers/         # Funciones helper (ej: RoleHelper.php)
├── Http/
│   ├── Controllers/ # Controladores API y Web
│   ├── Middleware/  # Middleware personalizado
│   └── Requests/    # Form Requests para validación
├── Imports/         # Importaciones Excel (Maatwebsite)
├── Jobs/            # Jobs para Queue Worker
├── Models/          # Eloquent Models
├── Notifications/   # Notificaciones
├── Providers/       # Service Providers
└── Services/        # Lógica de negocio (ej: ColppyService)
```

### Aplicación Flutter (technician_app/)

- Aplicación móvil para técnicos
- Comunicación con API Laravel
- Gestión de trabajos y clientes

---

## 🔧 Convenciones de Código

### Laravel/PHP

#### Nomenclatura
- **Modelos**: PascalCase, singular (ej: `Client`, `Job`, `CmsPage`)
- **Controladores**: PascalCase + Controller (ej: `ClientController`, `JobController`)
- **Services**: PascalCase + Service (ej: `ColppyService`, `SyncColppyClientsService`)
- **Variables/Métodos**: camelCase (ej: `$clientData`, `syncClients()`)
- **Constantes**: UPPER_SNAKE_CASE (ej: `MAX_ATTEMPTS`)
- **Tablas BD**: snake_case, plural (ej: `clients`, `jobs`, `client_address_external`)

#### Estructura de Controladores
```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ColppyService;

class ClientController extends Controller
{
    protected $colppyService;

    public function __construct(ColppyService $colppyService)
    {
        $this->colppyService = $colppyService;
    }

    public function index(Request $request)
    {
        // Lógica del controlador
    }
}
```

#### Uso de Services
- Toda la lógica de negocio compleja debe estar en Services, NO en Controllers
- Los Services se inyectan por dependencia
- Un Service por responsabilidad única

**Ejemplo:**
```php
// ✅ CORRECTO
class ColppyService
{
    public function authenticate()
    {
        // Lógica de autenticación con Colppy
    }

    public function getClients()
    {
        // Lógica de obtención de clientes
    }
}

// ❌ INCORRECTO - No poner lógica compleja en el controller
class ClientController extends Controller
{
    public function index()
    {
        // 50 líneas de lógica de negocio aquí... ❌
    }
}
```

#### Validación
- Usar Form Requests para validaciones complejas
- Validaciones simples pueden ir en el controller con `$request->validate()`

#### Responses API
```php
// Éxito
return response()->json([
    'success' => true,
    'data' => $data,
    'message' => 'Operación exitosa'
], 200);

// Error
return response()->json([
    'success' => false,
    'message' => 'Error descriptivo',
    'errors' => $errors
], 400);
```

### Flutter/Dart

#### Nomenclatura
- **Clases**: PascalCase (ej: `TechnicianHomePage`)
- **Variables/Métodos**: camelCase (ej: `clientList`, `fetchJobs()`)
- **Constantes**: lowerCamelCase (ej: `apiBaseUrl`)
- **Archivos**: snake_case (ej: `technician_home_page.dart`)

#### State Management
- **Provider**: Se usa `provider: ^6.1.1` para gestión de estado
- Crear providers para lógica compartida
- Usar `ChangeNotifier` para estados reactivos

#### Consideraciones Móviles
- Manejar conexión limitada/offline
- Usar `SharedPreferences` para caché local
- Optimizar imágenes con `image_picker`
- Gestión de permisos con `permission_handler`
- Geolocalización con `geolocator`

### JavaScript/Frontend Web

#### Nomenclatura
- **Funciones**: camelCase (ej: `syncProductsManual()`, `getAddress()`)
- **Variables**: camelCase (ej: `clientData`, `selectedFiles`)
- **Constantes**: lowerCamelCase (ej: `app_url`, `regex_mail`)
- **Archivos**: kebab-case o camelCase (ej: `jobdetail.js`, `useredit.js`)

#### SweetAlert (Swal) - IMPORTANTE ⚠️

**En este proyecto se usa `type:` NO `icon:`**

```javascript
// ✅ CORRECTO
Swal.fire({
    title: 'Título',
    text: 'Mensaje',
    type: 'success',  // ✅ Usar 'type'
    confirmButtonText: 'OK'
});

// ❌ INCORRECTO
Swal.fire({
    title: 'Título',
    text: 'Mensaje',
    icon: 'success',  // ❌ NO usar 'icon'
    confirmButtonText: 'OK'
});
```

**Tipos disponibles**: `'info'`, `'success'`, `'error'`, `'warning'`, `'question'`

#### Estructura de Archivos JS
- **Globales** (cargados en todas las vistas): `jobdetail.js`, `useredit.js`, `avatar.js`, `geolocalizacion.js`
  - `jobdetail.js` contiene funciones relacionadas con tareas:
    - `addProductToJob(mode)`: Agregar producto a tarea (modes: 'create', 'edit', 'add')
    - `removeProductFromJob(uniqueId, mode)`: Eliminar producto por unique_id
    - `renderProductsList(mode)`: Renderizar lista visual e inputs ocultos
    - `loadProductsToEdit(products)`: Cargar productos al abrir modal edit
    - Array global `selectedProducts[]` con sistema `productUniqueIdCounter`
- **Específicos**: Se cargan solo en las vistas que los necesitan (ej: `job.js` solo en vista de listado)
- **Ubicación**: `public/assets/js/local/`

#### Convenciones JavaScript
```javascript
// ✅ AJAX con CSRF token
$.ajax({
    url: app_url + '/ruta',
    type: 'POST',
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    timeout: 120000,
    success: function(response) { },
    error: function(xhr) { }
});

// ✅ Notificaciones Toastr
toastr["success"]("Mensaje de éxito");
toastr["error"]("Mensaje de error");
toastr["warning"]("Advertencia");
toastr["info"]("Información");
```

#### Buenas Prácticas
1. ❌ **NO** agregar código JavaScript en archivos `.blade.php` (excepto casos muy específicos)
2. ✅ Crear funciones en archivos JS separados
3. ✅ Funciones globales relacionadas con tareas → `jobdetail.js`
4. ✅ Documentar funciones importantes con comentarios
5. ✅ Siempre incluir manejo de errores en AJAX
6. ✅ La variable `app_url` está disponible globalmente

**Referencia completa**: Ver [`docs/CONVENCIONES_JAVASCRIPT.md`](../../docs/CONVENCIONES_JAVASCRIPT.md)

---

## 🔌 Integración Colppy

### Reglas CRÍTICAS

⚠️ **RESTRICCIONES IMPORTANTES:**

1. ✅ **PERMITIDO**: Obtener (GET) clientes y productos desde Colppy
2. ❌ **PROHIBIDO**: Crear, editar o eliminar clientes/productos en Colppy (POST/PUT/DELETE)
3. ✅ **DOMICILIOS**: Se gestionan 100% en nuestro sistema, no en Colppy
4. ✅ **PRODUCTOS**: Se sincronizan desde inventario Colppy (solo lectura)

### Arquitectura de Integración

```
Laravel ←→ ColppyService ←→ API Colppy
   ↓
ColppySession (BD) → Almacena sesiones activas
   ↓
SyncColppyClientsService → Sincronización periódica clientes
SyncColppyProductsService → Sincronización periódica productos
```

### Configuración
- Las credenciales se guardan en tabla `configs`
- Usar `ColppyService` para todas las comunicaciones
- La autenticación genera un token que se almacena en `colppy_sessions`

### Modos de Operación
Configurado en `configs.colppy_clientes_modo`:

1. **'local'** (RECOMENDADO):
   - Muestra clientes locales + sincronizados de Colppy
   - Permite crear clientes locales
   - Sincronización en background
   - Campo `is_from_colppy` distingue el origen

2. **'colppy'**: 
   - Muestra SOLO clientes sincronizados desde Colppy
   - NO permite crear clientes nuevos (solo lectura)

3. **'hibrido'**:
   - Consulta directa a API Colppy en tiempo real
   - Más lento, no recomendado

### Flujo de Sincronización:

**Clientes:**
1. Autenticar con Colppy → obtener sessionId
2. Consultar clientes con paginación
3. Sincronizar con tabla local `clients` (marcar `is_from_colppy = 1`)
4. Los domicilios se manejan localmente

**Productos:**
1. Autenticar con Colppy → obtener sessionId
2. Listar inventario completo (método `listarInventario()`)
3. Para cada producto: obtener detalles (método `obtenerItemInventario()`)
4. Sincronizar con tabla local `products` (marcar `is_from_colppy = 1`)
5. Actualizar stock, precio, descripción
6. Ejecuta automáticamente cada 2 horas vía scheduler

**Comandos manuales:**
- `php artisan colppy:sync-clients` - Sincronizar clientes
- `php artisan colppy:sync-products` - Sincronizar productos

---

## 📦 Dependencias Importantes

### Laravel
- **spatie/laravel-permission**: Sistema de roles y permisos
  - Usar `$user->hasRole('admin')` y `$user->can('permission')`
- **maatwebsite/excel**: Importación/exportación Excel
- **laravel/sanctum**: Autenticación API para Flutter

### Flutter
- Consultar `technician_app/pubspec.yaml` para dependencias actuales

---

## 🔄 Workers y Schedulers

### Jobs Implementados
- **SyncColppyClientsJob**: Sincroniza clientes desde Colppy API
  - Se puede ejecutar via Job (asíncrono) o directo (síncrono)
  - Timeout: 5 minutos
  - Reintentos: 3 veces

### Commands Implementados
- **php artisan colppy:sync-clients**: Comando manual para sincronizar clientes

### Queue Worker
**Estado actual**: `QUEUE_CONNECTION=sync` (ejecución sincrónica)
- No requiere queue worker corriendo
- Los jobs se ejecutan inmediatamente al despacharse
- Para producción con queues: ver `CONFIGURAR_QUEUE_WORKER.md`

### Scheduled Tasks
**Definido en** `app/Console/Kernel.php`:
```php
// Sincronizar clientes cada hora
$schedule->call(function () {
    $syncService = new \App\Services\SyncColppyClientsService();
    $syncService->syncClients();
})->hourly()
  ->name('sync-colppy-clients')
  ->withoutOverlapping()
  ->onOneServer();

// Sincronizar productos cada 2 horas
$schedule->call(function () {
    $syncService = new \App\Services\SyncColppyProductsService();
    $syncService->syncProducts();
})->everyTwoHours()
  ->name('sync-colppy-products')
  ->withoutOverlapping()
  ->onOneServer();
```

**Para activar el scheduler**:
- Ver `SCHEDULER_RESUMEN.md` y `CONFIGURAR_SCHEDULER.md`
- En desarrollo: `php artisan schedule:run` (ejecutar manualmente)
- En Windows/XAMPP: Usar `SCHEDULER_WINDOWS.bat` con Programador de Tareas
- En producción: Configurar cron job

**Scripts disponibles:**
- `SCHEDULER_WINDOWS.bat` - Ejecutar scheduler en Windows
- `TEST_SCHEDULER.bat` - Probar configuración del scheduler

### Endpoints de Sincronización
- `POST /client/sync-colppy`: Despacha job (asíncrono si queue activo)
- `POST /client/sync-colppy-now`: Ejecuta sincrónico inmediato

---

## 🔐 Sistema de Permisos

Usar **Spatie Laravel Permission**:

```php
// Verificar rol
if ($user->hasRole('admin')) {
    // ...
}

// Verificar permiso
if ($user->can('edit-clients')) {
    // ...
}

// Asignar permiso
$user->givePermissionTo('view-jobs');

// Asignar rol
$user->assignRole('technician');
```

---

## 🗄️ Base de Datos

### Modelos Principales
- **Client**: Clientes (sincronizados desde Colppy o locales)
- **ClientAddressExternal**: Domicilios adicionales de clientes
- **Product**: Productos/Inventario (sincronizados desde Colppy) - `is_from_colppy`
- **Job**: Trabajos/Órdenes de servicio
- **JobProduct**: Productos asociados a trabajos (con datos históricos)
- **User**: Usuarios del sistema
- **Config**: Configuraciones del sistema

### Migraciones
- Usar migraciones para TODOS los cambios de BD
- Nunca modificar migraciones ya ejecutadas en producción
- Nombre descriptivo: `YYYY_MM_DD_HHMMSS_create_table_name_table.php`

---

## 🎨 CMS

**IMPORTANTE**: El sistema CMS fue migrado de páginas a secciones (Ver `MIGRACION_CMS_SECCIONES.md`)

**Sistema Actual (Secciones):**
- **CmsSection**: Secciones de contenido con configuración JSON
  - 9 secciones predefinidas: general, home_banner, services_list, about, contact, notifications, support, legal, social
  - Cada sección tiene `config` (JSON) con datos específicos
  - Campo `active` para habilitar/deshabilitar
- **CmsSectionVersion**: Versionado de secciones (historial de cambios)
- **CmsMedia**: Imágenes y archivos multimedia

**Sistema Antiguo (Eliminado):**
- ❌ CmsPage - Reemplazado por CmsSection
- ❌ CmsPageVersion - Reemplazado por CmsSectionVersion  
- ❌ CmsFlutterTheme - Integrado en config de CmsSection
- ❌ CmsConfig - Integrado en config de CmsSection

**Controlador**: `SectionController.php` - Manejo completo CRUD de secciones

---

## 📝 Documentación

Los documentos en `/docs` contienen información crítica:

### Esenciales (Leer primero)
- `INTEGRACION_COLPPY.md`: Guía completa de integración con Colppy API
- `SISTEMA_CLIENTES_DOMICILIOS.md`: Arquitectura de clientes y domicilios
- `SISTEMA_PRODUCTOS_TAREAS.md`: Sistema completo de productos en tareas (NUEVO - 02/03/2026)
- `SINCRONIZACION_PRODUCTOS_UPDATE.md`: Sincronización de productos desde inventario Colppy
- `FLUJO_SINCRONIZACION.md`: Proceso detallado de sincronización de clientes
- `API_ENDPOINTS.md`: Referencia completa de endpoints
- `TROUBLESHOOTING.md`: Solución de problemas comunes

### Configuración
- `CONFIGURACION_COLPPY.md`: Setup inicial de Colppy
- `CONFIGURAR_LIMITES_PHP.md`: Ajustes de PHP para producción
- `INSTRUCCIONES_PRODUCCION_STORAGE.md`: Storage en producción
- `CONFIGURAR_SCHEDULER.md`: Tareas programadas (setup detallado)
- `SCHEDULER_RESUMEN.md`: Resumen de configuración del scheduler + scripts .bat
- `CONFIGURAR_QUEUE_WORKER.md`: Workers asíncronos (referencia histórica)

### Referencia Rápida
- `COLPPY_QUICK_START.md`: Guía rápida Colppy
- `EJEMPLOS_USO_COLPPY.md`: Código de ejemplo
- `FLUJOS_COLPPY_DIAGRAMA.md`: Diagramas visuales
- `IMPLEMENTACION_COLPPY.md`: Detalles técnicos

### CMS y Migraciones
- `MIGRACION_CMS_SECCIONES.md`: Migración del antiguo sistema de páginas al nuevo sistema de secciones

### Notas Importantes
- ⚠️ `INTEGRACION_HIBRIDA_COLPPY.md`: Modo híbrido NO recomendado
- 📚 `README_DOCUMENTOS.md`: Índice y guía de documentación

**IMPORTANTE**: Consultar estos documentos antes de hacer cambios en las áreas correspondientes.

---

## ✅ Checklist para Nuevas Features

Cuando implementes una nueva funcionalidad, sigue este checklist:

### Backend (Laravel)
- [ ] ¿Necesita un Model nuevo? → Crear con migración
- [ ] ¿Necesita lógica compleja? → Crear Service
- [ ] ¿Necesita endpoint API? → Crear Controller/Route
- [ ] ¿Necesita validación? → Form Request o inline validation
- [ ] ¿Necesita permisos? → Definir y verificar con Spatie
- [ ] ¿Es tarea asíncrona? → Crear Job
- [ ] ¿Es tarea programada? → Crear Command + Schedule
- [ ] ¿Afecta Colppy? → Usar ColppyService y verificar restricciones

### Frontend (Flutter)
- [ ] ¿Necesita nueva pantalla? → Crear widget/page
- [ ] ¿Necesita llamada API? → Crear/actualizar service
- [ ] ¿Necesita estado? → Provider/Riverpod/Bloc según conveción
- [ ] ¿Afecta UI del técnico? → Probar en dispositivo real

---

## 🚫 Errores Comunes a Evitar

1. ❌ **NO** intentar crear/editar clientes en Colppy → Solo lectura
2. ❌ **NO** poner lógica de negocio en Controllers → Usar Services
3. ❌ **NO** hardcodear credenciales → Usar tabla `configs` o `.env`
4. ❌ **NO** modificar migraciones ya ejecutadas → Crear nueva migración
5. ❌ **NO** ignorar el sistema de permisos → Verificar roles/permisos
6. ❌ **NO** hacer queries N+1 → Usar eager loading (`with()`)
7. ❌ **NO** exponer errores técnicos al frontend → Mensajes genéricos

---

## 🔍 Debugging

### Laravel
```bash
# Ver logs
tail -f storage/logs/laravel.log

# Probar scheduler
php artisan schedule:run

# Probar queue worker
php artisan queue:work --tries=3
```

### Variables de entorno
- Configuración en `.env`
- Nunca commitear `.env` a git
- Usar `.env.example` como template

---

## 📚 Comandos Útiles

### Laravel
```bash
# Artisan
php artisan list                    # Lista comandos
php artisan migrate                 # Ejecutar migraciones
php artisan db:seed                 # Ejecutar seeders
php artisan make:model Client -m    # Crear modelo + migración
php artisan make:controller ClientController --api
php artisan make:service ColppyService
php artisan make:command SyncClients
php artisan make:job ProcessClient

# Caché
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize:clear          # Limpiar todos los cachés

# Permisos (Spatie)
php artisan permission:cache-reset
```

### Flutter
```bash
flutter pub get                     # Instalar dependencias
flutter run                         # Ejecutar app
flutter build apk                   # Build Android
flutter doctor                      # Verificar instalación
flutter clean                       # Limpiar build
```

---

## 🎯 Principios de Desarrollo

1. **Clean Code**: Código legible y autodocumentado
2. **DRY**: Don't Repeat Yourself
3. **SOLID**: Especialmente Single Responsibility
4. **Security First**: Validar inputs, sanitizar outputs
5. **Test**: Escribir tests para lógica crítica
6. **Documentar**: Comentar código complejo, actualizar docs

---

## 🔄 Control de Versiones

### Git
- Commits descriptivos en español
- No commitear archivos sensibles (.env, credenciales)
- Branching según sea necesario

---

## 🚀 Producción

### Preparación
1. Verificar `.env` de producción
2. Ejecutar migraciones: `php artisan migrate --force`
3. Optimizar: `php artisan optimize`
4. Configurar Queue Worker (ver `CONFIGURAR_QUEUE_WORKER.md`)
5. Configurar Scheduler (ver `CONFIGURAR_SCHEDULER.md`)
6. Verificar storage permissions
7. Configurar límites PHP (ver `CONFIGURAR_LIMITES_PHP.md`)

---

## 📞 Consideraciones Específicas del Proyecto

### Sincronización desde Colppy

**Clientes:**
- Los clientes de Colppy se sincronizan a la base de datos local (tabla `clients`)
- Una vez sincronizados, todo el trabajo se realiza localmente
- La sincronización es unidireccional: Colppy → Nuestro Sistema
- Los domicilios se manejan completamente en nuestro sistema local
- Actualizar clientes cada hora con scheduler (automático)
- Manejar errores de API gracefully (timeouts, límites)
- Comando manual: `php artisan colppy:sync-clients`

**Productos:**
- Los productos se sincronizan desde inventario Colppy (tabla `products`)
- Campo `is_from_colppy = 1` identifica productos sincronizados
- Se guardan: código, descripción, stock, precio, idcolppy
- Actualizar productos cada 2 horas con scheduler (automático)
- Comando manual: `php artisan colppy:sync-products`
- Service: `SyncColppyProductsService` (sin Jobs, ejecución directa)
- Los productos se obtienen del inventario uno por uno
- Ver documentación completa: `SINCRONIZACION_PRODUCTOS_UPDATE.md`

### Productos en Tareas
- Los productos se asocian a tareas con datos históricos (snapshot)
- Tres modos de gestión: create, edit, add (modal directo)
- Sistema de unique_id permite productos duplicados en una tarea
- Validación de estado: no modificar si tarea cerrada Y archivada
- Búsqueda en vivo con optimizaciones (abort, timestamp, debounce)
- Ver documentación completa: `docs/SISTEMA_PRODUCTOS_TAREAS.md`

### App de Técnicos
- Considera conexión limitada/offline
- Optimizar imágenes antes de subir
- Usar caché local cuando sea posible

---

## 💡 Mejores Prácticas

### Performance
- Usar eager loading para relaciones
- Indexar columnas frecuentemente buscadas
- Paginar resultados grandes
- Cachear datos estáticos

### Seguridad
- Validar TODOS los inputs
- Usar CSRF protection
- Sanitizar outputs (XSS)
- Rate limiting en APIs públicas
- Autenticación Sanctum para Flutter

### Mantenibilidad
- Código autodocumentado
- Nombres descriptivos
- Funciones pequeñas (< 20 líneas idealmente)
- Separación de responsabilidades

---

## 🎓 Recursos

- [Laravel 8 Docs](https://laravel.com/docs/8.x)
- [Spatie Permission](https://spatie.be/docs/laravel-permission/v5)
- [Flutter Docs](https://flutter.dev/docs)
- Documentación interna en `/docs`

---

## 🔧 Entorno de Desarrollo (XAMPP/Windows)

### Configuración Local
- **Stack**: XAMPP (Apache + MySQL + PHP)
- **Ruta proyecto**: `C:\xampp\htdocs\Proyects\Strupeni_Electronica\`
- **PHP executable**: `C:\xampp\php\php.exe`
- **Base datos**: MySQL via phpMyAdmin

### Scripts Útiles (Raíz del repositorio)
- `habilitar_red.bat`: Configuración de red
- `panel/QUEUE_WORKER.bat`: Iniciar queue worker (si se activa)
- `panel/SCHEDULER_WINDOWS.bat`: Ejecutar scheduler
- `technician_app/INSTALAR_FLUTTER.bat`: Setup Flutter
- `technician_app/VERIFICAR_FLUTTER.bat`: Verificar instalación Flutter
- `technician_app/CORREGIR_FLUTTER.bat`: Reparar problemas Flutter

### Variables de Entorno Importantes
```env
# Modo de queue (actual: sync = sin worker)
QUEUE_CONNECTION=sync

# Configuración de base de datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=strupeni_db
DB_USERNAME=root
DB_PASSWORD=
```

---

## 📊 Modelos de Datos Importantes

### Clientes
- **Client**: Clientes (locales + sincronizados)
  - `is_from_colppy`: Boolean (1 = Colppy, 0/NULL = local)
  - `colppy_id`: ID en sistema Colppy
  - `external_id`: ID externo para APIs

### Domicilios
- **Clients_Addres**: Domicilios (tabla: `clients_address`)
  - Para clientes locales y sincronizados
  - Relación: `client_id` (FK a `clients.id`)

### Jobs y Trabajos
- **Job**: Órdenes de trabajo/servicio
  - Relación `hasMany` con `JobProduct` (productos relacionados)
- **JobProduct**: Productos asociados a trabajos (NUEVO - Implementado 02/03/2026)
  - Almacena snapshot histórico del producto (`idcolppy`, `codigo`, `descripcion`)
  - `unit_type`: enum ('Unidad', 'Rollo', 'Metros')
  - `quantity`: decimal(10,2) - cantidad del producto
  - Usa SoftDeletes para auditoría
  - Sistema de `unique_id` para manejar productos duplicados
  - **Referencia completa**: `docs/SISTEMA_PRODUCTOS_TAREAS.md`
- **Jobs_file**: Archivos adjuntos a trabajos
- **Jobs_Note**: Notas de trabajos

### CMS
- **CmsPage**: Páginas del CMS
- **CmsSection**: Secciones de contenido
- **CmsMedia**: Imágenes y archivos multimedia
- **CmsFlutterTheme**: Temas y estilos personalizados
- **CmsPageVersion** / **CmsSectionVersion**: Control de versiones

### Configuración
- **Config**: Configuraciones del sistema (clave-valor)
  - `colppy_clientes_modo`: 'local', 'colppy', 'hibrido'
  - `url_api_login`: URL de login Colppy
  - `user_api`, `pass_api`: Credenciales Colppy
  - `id_empresa_api`: ID empresa en Colppy

### Sesiones Colppy
- **ColppySession**: Sesiones activas con API Colppy
  - Almacena `session_id` para reutilizar

### Usuarios y Permisos
- **User**: Usuarios del sistema
- **Rol**: Roles (Spatie Permission)
- **Permission**: Permisos (Spatie Permission)
- **Role_Has_Permission**: Relación roles-permisos

---

## 🎨 Frontend y API

### Estructura de Respuestas API
```php
// Éxito con datos
return response()->json([
    'success' => true,
    'data' => $data,
    'message' => 'Operación exitosa'
], 200);

// Éxito con paginación
return response()->json([
    'success' => true,
    'data' => $items,
    'total' => $total,
    'page' => $page,
    'limit' => $limit
], 200);

// Error de validación
return response()->json([
    'success' => false,
    'message' => 'Errores de validación',
    'errors' => $validator->errors()
], 422);

// Error general
return response()->json([
    'success' => false,
    'message' => 'Mensaje descriptivo del error'
], 400);
```

### Autenticación API (Laravel Sanctum)
- App Flutter usa Sanctum tokens
- Login genera token persistente
- Incluir token en header: `Authorization: Bearer {token}`

---

## 🧪 Testing y Debugging

### Logs
```bash
# Ver logs en tiempo real
tail -f panel/storage/logs/laravel.log

# Logs por fecha
cat panel/storage/logs/laravel-YYYY-MM-DD.log
```

### Debugging Colppy
- Endpoint debug: `/client/debug` (ver estadísticas)
- Sincronización manual: `php artisan colppy:sync-clients`
- Ver sesiones activas en tabla `colppy_sessions`

### Common Issues
1. **Migraciones no se aplican**: Verificar conexión DB en `.env`
2. **Permisos de storage**: `chmod -R 775 storage bootstrap/cache` (Linux)
3. **Cache desactualizado**: `php artisan optimize:clear`
4. **Colppy timeout**: Verificar credenciales en tabla `configs`

---

## 📱 App Flutter (technician_app)

### Estructura Principal
```
lib/
├── main.dart              # Entry point
├── models/                # Modelos de datos
├── providers/             # State management (Provider)
├── screens/               # Pantallas/Pages
├── services/              # API services
├── widgets/               # Widgets reutilizables
└── utils/                 # Utilidades y helpers
```

### Dependencias Clave
- **provider**: State management
- **http/dio**: Llamadas HTTP a API
- **shared_preferences**: Almacenamiento local/caché
- **image_picker**: Subir fotos en trabajos
- **geolocator**: Ubicación GPS
- **table_calendar**: Calendario de trabajos

### Comunicación con Backend
- API Base: Configurado en variables de entorno
- Endpoints principales:
  - `/api/auth/*`: Autenticación
  - `/api/jobs/*`: Gestión de trabajos
  - `/api/clients/*`: Consulta de clientes

---

## 📦 Sistema de Productos en Tareas

**Estado**: ✅ Implementado (02/03/2026)  
**Documentación completa**: `docs/SISTEMA_PRODUCTOS_TAREAS.md`

### Funcionalidad
Permite asociar productos a tareas (jobs) con información histórica:
- **Datos históricos**: Se guarda snapshot del producto (código, descripción, idcolppy)
- **Tipos de unidad**: Unidad, Rollo, Metros
- **Cantidades decimales**: step 0.01
- **Productos duplicados**: Sistema de unique_id para el mismo producto múltiples veces
- **Búsqueda en vivo**: AJAX con debounce y cancelación de requests

### Tres Modos de Gestión

#### 1. Modal de Crear Tarea (`job/create.blade.php`)
- Agregar productos al crear nueva tarea
- Mode: `'create'`
- Campos: product_id_create, unit_type_create, quantity_create

#### 2. Modal de Editar Tarea (`job/edit.blade.php`)
- Modificar productos en tarea existente
- Mode: `'edit'`
- Solo disponible si tarea NO ha arribado
- Carga productos existentes con `loadProductsToEdit()`

#### 3. Modal de Agregar Productos Directo (`job/products.blade.php`)
- Gestión de productos sin editar toda la tarea
- Mode: `'add'`
- Disponible desde:
  - Botón en footer de cards (home)
  - Opción en menú dropdown (tabla de tareas)
- **Restricción**: Solo si NO (cerrado Y archivado)
- Abre con spinner, carga productos en background

### Base de Datos

**Tabla**: `job_products`
```php
- id: Primary Key
- job_id: FK a jobs (cascade delete)
- product_id: FK a products (nullable)
- idcolppy: string (snapshot)
- codigo: string (snapshot)
- descripcion: string (snapshot)
- unit_type: enum ('Unidad', 'Rollo', 'Metros')
- quantity: decimal(10,2)
- timestamps + softDeletes
```

**Modelo**: `app/Models/JobProduct.php`
- Relaciones: `belongsTo(Job)`, `belongsTo(Product)`
- Fillable: job_id, product_id, idcolppy, codigo, descripcion, unit_type, quantity
- Usa SoftDeletes para auditoría

### Controladores

**JobController** (`app/Http/Controllers/JobController.php`):
- `store()`: Crea job + productos
- `update()`: Actualiza job + recrea productos (soft delete + create)
- `edit()`: Incluye productos en respuesta
- `updateProducts()`: **NUEVO** - Actualiza solo productos sin tocar job

**Ruta**:
```php
Route::put('/jobs/{id}/products', [JobController::class, 'updateProducts'])->name('job.products');
```

### JavaScript (jobdetail.js)

**Variables globales**:
```javascript
var selectedProducts = []; // Array de productos con {unique_id, product_id, codigo, descripcion, unit_type, quantity, mode}
var productUniqueIdCounter = 0; // Contador para IDs únicos
```

**Funciones clave**:
- `addProductToJob(mode)`: Valida, agrega producto al array, renderiza
- `removeProductFromJob(uniqueId, mode)`: Elimina por unique_id (NO por product_id)
- `renderProductsList(mode)`: Genera tabla visual + inputs ocultos para form submit
- `loadProductsToEdit(products)`: Popula selectedProducts al abrir modal edit

**Sistema de unique_id**:
- Cada producto agregado recibe un `unique_id` incremental
- Permite agregar el mismo producto múltiples veces
- La eliminación se hace por `unique_id`, no por `product_id`

### Validaciones

**Frontend (JavaScript)**:
- Producto debe estar seleccionado
- Cantidad > 0
- No duplicados del mismo producto en mismo modo (con String() conversion)

**Backend (PHP)**:
- Solo permitir agregar productos si NOT (closed_datetime != null AND archived == 1)
- Verificar que producto existe antes de insertar
- Crear snapshot de datos históricos del producto

### Troubleshooting Común

1. **Productos duplicados se borran todos**: Usar unique_id en vez de product_id
2. **Modal se abre lento**: Mostrar spinner inmediatamente, cargar AJAX en background
3. **Campos vacíos en edit**: Excluir campos de productos en form reset
4. **Búsqueda múltiples AJAX**: Implementado con abort() + timestamp + debounce

**Ver más**: `docs/SISTEMA_PRODUCTOS_TAREAS.md` - Sección Troubleshooting

---

## 🤖 Directrices de Trabajo

Cuando trabajes en este proyecto:

1. **SIEMPRE** consulta los docs en `/docs` para temas específicos (Colppy, domicilios, etc.)
2. **RESPETA** las restricciones de Colppy (solo lectura)
3. **USA Services** para lógica de negocio, no controllers
4. **VERIFICA** permisos con Spatie antes de acciones sensibles
5. **SIGUE** las convenciones de nomenclatura establecidas
6. **PREGUNTA** si no estás seguro de una restricción técnica
7. **DOCUMENTA** cambios importantes o complejos
8. **TESTEA** especialmente integraciones con Colppy
9. **PRIORIZA** la seguridad y validación de datos
10. **MANTÉN** consistencia con el código existente

### Al Crear Código
- Si es lógica de negocio compleja → Service
- Si es endpoint API → Controller + Route (en `routes/web.php` o `routes/api.php`)
- Si es tarea asíncrona → Job (en `app/Jobs/`)
- Si es tarea programada → Command (en `app/Console/Commands/`) + Schedule
- Si necesita BD → Migration + Model
- Si es para consultas complejas → Crear método en Model o Service

**Ejemplos de Services existentes**:
- `ColppyService`: Comunicación con API Colppy
- `SyncColppyClientsService`: Lógica de sincronización

**Ejemplos de Jobs existentes**:
- `SyncColppyClientsJob`: Sincronización background de clientes

**Ejemplos de Commands existentes**:
- `colppy:sync-clients`: Sincronización manual de clientes
- `cms:migrate-media`: Migración de medios CMS

### Al Modificar Código Existente
- Lee el código completo del archivo primero
- Mantén el estilo y patrones existentes
- No rompas funcionalidad existente
- Actualiza documentación si es necesario
- Si modificas Models: considera relaciones Eloquent
- Si modificas Services usados por Jobs: reinicia queue worker (si está activo)

---

**¡Listo para ayudarte con Strupeni Electrónica! 🔌⚡**
