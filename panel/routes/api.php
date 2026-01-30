<?php

use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiJobController;
use App\Http\Controllers\Api\ApiSearchVarController;
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

// Rutas protegidas (requieren autenticación Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Usuario autenticado
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [ApiAuthController::class, 'logout']);
    
    // Jobs/Citas endpoints para app móvil
    Route::prefix('jobs')->group(function () {
        Route::get('/today', [ApiJobController::class, 'getTodayJobs']);          // Citas del día
        Route::get('/upcoming', [ApiJobController::class, 'getUpcomingJobs']);    // Próximas citas
        Route::get('/calendar', [ApiJobController::class, 'getJobsByDateRange']); // Calendario (rango de fechas)
        Route::get('/clients', [ApiJobController::class, 'getClients']);          // Lista de clientes
        Route::post('/', [ApiJobController::class, 'store']);                     // Crear tarea
        Route::get('/{id}', [ApiJobController::class, 'show']);                   // Detalle de cita
        Route::put('/{id}', [ApiJobController::class, 'update']);                 // Actualizar tarea
        Route::delete('/{id}', [ApiJobController::class, 'destroy']);             // Eliminar tarea
        Route::post('/{id}/arrival', [ApiJobController::class, 'markArrival']);   // Marcar llegada
        Route::post('/{id}/back-to-pending', [ApiJobController::class, 'backToPending']); // Volver a pendiente
        Route::post('/{id}/close', [ApiJobController::class, 'closeJob']);        // Cerrar cita
        Route::post('/{id}/notes', [ApiJobController::class, 'addNote']);         // Añadir nota
        Route::get('/{id}/notes', [ApiJobController::class, 'getNotes']);         // Obtener notas
        Route::post('/{id}/files', [ApiJobController::class, 'uploadFiles']);     // Subir archivos
        Route::get('/{id}/files', [ApiJobController::class, 'getFiles']);         // Obtener archivos
    });
});

// Rutas legacy
Route::get('/searchvar', [ApiSearchVarController::class,'searchvar']);
Route::post('/searchvar', [ApiSearchVarController::class,'searchvar']);
