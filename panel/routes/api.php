<?php

use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiColppyController;
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

// API pública para tema Flutter
Route::get('/flutter/theme', [CmsController::class, 'getActiveTheme']);

// Rutas usadas por Web y App (sin autenticación Sanctum pero con sesión)
Route::get('/searchvar', [ApiSearchVarController::class, 'searchvar']);
Route::post('/searchvar', [ApiSearchVarController::class, 'searchvar']);

// Rutas protegidas (requieren autenticación Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Usuario autenticado
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [ApiAuthController::class, 'logout']);
    
    // Jobs/Citas endpoints
    Route::prefix('jobs')->group(function () {
        // Lectura (GET) - ApiJobController
        Route::get('/today', [ApiJobController::class, 'getTodayJobs']);
        Route::get('/upcoming', [ApiJobController::class, 'getUpcomingJobs']);
        Route::get('/calendar', [ApiJobController::class, 'getJobsByDateRange']);
        Route::get('/clients', [ApiJobController::class, 'getClients']);
        Route::get('/{id}', [ApiJobController::class, 'show']);
        Route::get('/{id}/notes', [ApiJobController::class, 'getNotes']);
        Route::get('/{id}/files', [ApiJobController::class, 'getFiles']);
        
        // Escritura (POST/PUT/DELETE) - JobController (lógica original de la web)
        Route::post('/', [JobController::class, 'store']);
        Route::put('/{id}', [JobController::class, 'update']);
        Route::delete('/{id}', [JobController::class, 'destroy']);
        Route::post('/{id}/arrival', [JobController::class, 'markarrival']);
        Route::post('/{id}/back-to-pending', [JobController::class, 'backarrival']);
        Route::post('/{id}/close', [JobController::class, 'closed']);
        Route::post('/{id}/notes', [JobController::class, 'addnote']);
        Route::delete('/notes/{id}', [JobController::class, 'destroynote']);
        Route::post('/{id}/files', [JobController::class, 'onlyaddfiles']);
        Route::delete('/files/{id}', [JobController::class, 'destroyfile']);
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
        Route::post('/call', [ApiColppyController::class, 'hacerLlamada']);
        Route::post('/invalidate-session', [ApiColppyController::class, 'invalidarSesion']);
    });
});
