<?php

use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiColppyController;
use App\Http\Controllers\Api\ApiDataTablesController;
use App\Http\Controllers\Api\ApiJobController;
use App\Http\Controllers\Api\ApiSearchVarController;
use App\Http\Controllers\CmsController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\SectionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Rutas públicas (sin autenticación)
Route::post('/login', [ApiAuthController::class, 'login']);

// DEBUG TEMPORAL - Ver permisos de usuario
Route::get('/debug-user-permissions', [ApiAuthController::class, 'debugUserPermissions']);

// API pública para tema Flutter
Route::get('/flutter/theme', [CmsController::class, 'getActiveTheme']);

// Rutas usadas por Web y App (sin autenticación Sanctum pero con sesión)
Route::get('/searchvar', [ApiSearchVarController::class, 'searchvar']);
Route::post('/searchvar', [ApiSearchVarController::class, 'searchvar']);

// Buscar cliente por colppy_id
Route::post('/clients/by-colppy-id', [ApiSearchVarController::class, 'getClientByColppyId']);

// Buscar productos por array de colppy_ids
Route::post('/products/by-colppy-ids', [ApiSearchVarController::class, 'getProductsByColppyIds']);

// Buscar cliente por colppy_id
Route::post('/clients/by-colppy-id', [ApiSearchVarController::class, 'getClientByColppyId']);

// Buscar productos por array de colppy_ids
Route::post('/products/by-colppy-ids', [ApiSearchVarController::class, 'getProductsByColppyIds']);

Route::prefix('budgets')->group(function () {
    Route::get('/{idFactura}/pdf/view', [\App\Http\Controllers\Api\ApiBudgetController::class, 'viewPdf']);
    Route::get('/{idFactura}/pdf/preview', [\App\Http\Controllers\Api\ApiBudgetController::class, 'previewHtml']);
});

// Rutas protegidas (requieren autenticación Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Usuario autenticado con roles y permisos
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        $roles = $user->getRoleNames()->toArray();
        $permissions = $user->getAllPermissions()->pluck('name')->values()->toArray();
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'imagen' => $user->imagen,
            'roles' => $roles,
            'permissions' => $permissions
        ]);
    });
    Route::post('/logout', [ApiAuthController::class, 'logout']);
    
    // Health check - Verificar estado de autenticación
    Route::get('/health-check', [ApiAuthController::class, 'healthCheck']);
    
    // Jobs/Citas endpoints
    Route::prefix('jobs')->group(function () {
        // Lectura (GET) - ApiJobController
        Route::get('/today', [ApiJobController::class, 'getTodayJobs']);
        Route::get('/upcoming', [ApiJobController::class, 'getUpcomingJobs']);
        Route::get('/calendar', [ApiJobController::class, 'getJobsByDateRange']);
        Route::get('/clients', [ApiJobController::class, 'getClients']);
        Route::get('/products', [ApiJobController::class, 'getProducts']);
        Route::get('/{id}', [ApiJobController::class, 'show']);
        Route::get('/{id}/notes', [ApiJobController::class, 'getNotes']);
        Route::get('/{id}/files', [ApiJobController::class, 'getFiles']);
        
        // Escritura (POST/PUT/DELETE) - JobController (lógica original de la web)
        Route::post('/', [JobController::class, 'store']);
        Route::put('/{id}', [JobController::class, 'update']);
        Route::patch('/{id}/technicians', [ApiJobController::class, 'updateTechnicians']);
        Route::delete('/{id}', [JobController::class, 'destroy']);
        Route::post('/{id}/arrival', [JobController::class, 'markarrival']);
        Route::post('/{id}/back-to-pending', [JobController::class, 'backarrival']);
        Route::post('/{id}/close', [JobController::class, 'closed']);
        Route::post('/{id}/notes', [JobController::class, 'addnote']);
        Route::delete('/notes/{id}', [JobController::class, 'destroynote']);
        Route::delete('/notes/{noteId}/delete', [ApiJobController::class, 'deleteNote']);
        Route::post('/{id}/files', [JobController::class, 'onlyaddfiles']);
        Route::delete('/files/{id}', [JobController::class, 'destroyfile']);
        Route::delete('/files/{fileId}/delete', [ApiJobController::class, 'deleteFile']);
        
        // PDF Generation
        Route::post('/{id}/generate-pdf', [JobController::class, 'generatePDF']);
    });
    
    // Clientes endpoints
    Route::prefix('client')->group(function () {
        Route::get('/address/{id}', [ApiJobController::class, 'getClientAddresses']);
        Route::post('/address', [ApiJobController::class, 'createClientAddress']);
    });

    // Colppy API endpoints
    Route::prefix('colppy')->group(function () {
        Route::get('/clientes', [ApiColppyController::class, 'listarClientes']);
        Route::get('/clientes/{idCliente}', [ApiColppyController::class, 'obtenerCliente']);
        Route::get('/inventario', [ApiColppyController::class, 'listarInventario']);
        Route::get('/inventario/{idItem}', [ApiColppyController::class, 'obtenerItemInventario']);
        Route::post('/call', [ApiColppyController::class, 'hacerLlamada']);
        Route::post('/invalidate-session', [ApiColppyController::class, 'invalidarSesion']);
        
        // Sincronización
        Route::post('/sync/products', [ApiDataTablesController::class, 'syncColppyProducts']);
        Route::post('/sync/products/now', [ApiDataTablesController::class, 'syncColppyProductsNow']);
        Route::get('/sync/products/stats', [ApiDataTablesController::class, 'getProductSyncStats']);
    });

    // Productos endpoints
    Route::prefix('products')->group(function () {
        Route::get('/', [ApiDataTablesController::class, 'getProducts']);
    });

    // Presupuestos endpoints (NUEVO)
    Route::prefix('budgets')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ApiBudgetController::class, 'index']);
        Route::get('/available-jobs', [\App\Http\Controllers\Api\ApiBudgetController::class, 'getAvailableJobs']);
        
        // Rutas de PDF (más específicas primero)
        Route::get('/{idFactura}/pdf', [\App\Http\Controllers\Api\ApiBudgetController::class, 'downloadPdf']);
        Route::get('/{idFactura}/jobs', [\App\Http\Controllers\Api\ApiBudgetController::class, 'getAssociatedJobs']);
        Route::post('/{idFactura}/create-job', [\App\Http\Controllers\Api\ApiBudgetController::class, 'createJobFromBudget']);
        
        Route::get('/{idFactura}', [\App\Http\Controllers\Api\ApiBudgetController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\ApiBudgetController::class, 'store']);
        Route::put('/{idFactura}', [\App\Http\Controllers\Api\ApiBudgetController::class, 'update']);
        Route::post('/{idFactura}/associate-jobs', [\App\Http\Controllers\Api\ApiBudgetController::class, 'associateJobsToBudget']);
    });
    
    // Productos y Servicios para presupuestos (NUEVO)
    Route::get('/products-services', [\App\Http\Controllers\Api\ApiBudgetController::class, 'getProductsAndServices']);
    
    // Creación de clientes desde app (NUEVO)
    Route::post('/clients', [\App\Http\Controllers\Api\ApiBudgetController::class, 'createClient']);
});
