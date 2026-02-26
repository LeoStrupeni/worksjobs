<?php

use App\Http\Controllers\Api\ApiDataTablesController;
use App\Http\Controllers\Api\ApiJobController;
use App\Http\Controllers\ApiConfigController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CmsController;
use App\Http\Controllers\ExcelController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

// Route::get('test', function () {
// 	dd( base_path(). '/../public/storage/',env('APP_URL'),storage_path('app'), storage_path('app/public'));
// });
// Route::get('/storagelink', function () { 
//     $target = storage_path('app').'/public';
//     $shortcut = 'storage';
//     symlink($target, $shortcut);
//     // Artisan::call('storage:link'); return 'Storage link created'; 
//     return 'Storage link created';
// });

Route::get('/clear-cache', function() {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    return "Cache cleared successfully!";
})->name('cache.clear');

Route::get('/test', function() {
    dd(Session::get('users'));
    echo md5("Alma2024");
    echo "<br>";
    echo md5("strU1184!!");
    echo "<br>";
    return 'listo';
});

Route::get('/', [HomeController::class,'index'])->name('home.index');

// Ruta para ver la web pública (sin afectar login/dashboard)
Route::get('/web-publica', [HomeController::class,'webPublica'])->name('web.publica');

// Route::view('/login','Auth.login', ['google_api_key' => DB::table('configs')->where('name','google_api_key')->first()->value])->name('login');
Route::get('/login', [LoginController::class,'loginget'])->name('login');
Route::post('/login', [LoginController::class,'login']);
Route::post('/logout', [LoginController::class,'logout']);
Route::get('/logout', [LoginController::class,'logoutGet'])->name('logout');

Route::view('/password/reset','Auth.passwords.email')->name('password.request');
Route::post('/password/email', [ForgotPasswordController::class,'sendResetLinkEmail'])->name('password.email');
Route::get('/password/reset/{token}',[ForgotPasswordController::class,'showResetForm'])->name('password.reset');
Route::post('/password/reset', [ForgotPasswordController::class,'reset'])->name('password.update');

// RUTAS DE DEBUG - SIN MIDDLEWARE AUTH
Route::get('/debug/test1', function() {
    return response()->json(['test' => 'Ruta básica funciona', 'time' => date('Y-m-d H:i:s')]);
});

Route::get('/debug/test2', function() {
    $count = \App\Models\Client::count();
    return response()->json(['test' => 'Base de datos funciona', 'clientes' => $count]);
});

Route::get('/debug/sync-stats-public', function() {
    try {
        $clientesLocales = \App\Models\Client::where(function($query) {
            $query->where('is_from_colppy', false)
                  ->orWhereNull('is_from_colppy');
        })->count();
        
        $clientesColppy = \App\Models\Client::where('is_from_colppy', true)->count();
        $totalLocal = \App\Models\Client::count();
        
        // Intentar obtener total desde Colppy API
        $totalColppyValue = 0;
        try {
            $colppyService = new \App\Services\ColppyService();
            $resultado = $colppyService->listarClientes(0, 1, [], []);
            $totalColppyValue = $resultado['total'] ?? 0;
        } catch (\Exception $colppyError) {
            // Continuar sin Colppy si falla
            \Log::warning('No se pudo consultar Colppy desde debug', ['error' => $colppyError->getMessage()]);
        }
        
        $diferencia = $totalColppyValue - $clientesColppy;
        
        return response()->json([
            'success' => true,
            'stats' => [
                'local_total' => $totalLocal,
                'local_propios' => $clientesLocales,
                'local_de_colppy' => $clientesColppy,
                'colppy_total' => $totalColppyValue,
                'diferencia' => $diferencia,
                'necesita_sincronizar' => $diferencia != 0
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
});

// Ruta de estadísticas Colppy (fuera de auth para evitar conflictos con AJAX)
Route::get('/client/sync-stats', [ApiDataTablesController::class,'getSyncStats']);

Route::view('/home','home')->middleware('auth');

Route::group(['middleware' => 'auth'], function () {
    Route::redirect('/home', '/');
    Route::resource('/users',UserController::class)->except(['edit']);
    Route::get('/users/{id}/edit', [ApiDataTablesController::class,'getUserEdit']);
    Route::post('/users/table', [ApiDataTablesController::class,'getUsersDataTable'])->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    Route::resource('/roles',RolController::class)->except(['edit']);
    Route::get('/roles/{id}/edit', [ApiDataTablesController::class,'getRolEdit']);
    Route::post('/roles/table', [ApiDataTablesController::class,'getRolesDataTable'])->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    Route::get('/roles/users/{id}', [ApiDataTablesController::class,'getUsersByRol']);
    
    Route::resource('/permission',PermissionController::class);
    Route::post('/permission/table', [ApiDataTablesController::class,'getPermissionsDataTable'])->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

    Route::post('/roles/permission/update', [PermissionController::class,'updaterolpermission'])->name('updaterolpermission');

    Route::resource('/client',ClientController::class)->except(['edit']);
    Route::get('/client/{id}/edit', [ApiDataTablesController::class,'getClientEdit']);
    Route::post('/client/table', [ApiDataTablesController::class,'getClientsDataTable'])->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    Route::post('/client/sync-colppy', [ApiDataTablesController::class,'syncColppyClients'])->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    Route::post('/client/sync-colppy-now', [ApiDataTablesController::class,'syncColppyClientsNow'])->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

    Route::post('/client/excel', [ExcelController::class,'importaClientsExcel'])->name('importaExcelClient');

    Route::get('/client/address/{id}', [ApiDataTablesController::class,'getClientAddresses']);
    Route::post('/client/address', [ClientController::class,'postAddress']);
    Route::delete('/client/address/{id}', [ClientController::class,'detroyAddress']);

    Route::resource('/jobs',JobController::class);
    Route::post('/jobs/table', [ApiJobController::class,'getDataTable'])->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    Route::post('/jobs/markarrival', [JobController::class,'markarrival']);
    Route::post('/jobs/backarrival', [JobController::class,'backarrival']);
    Route::post('/jobs/closed', [JobController::class,'closed'])->name('job.closed');
    Route::post('/jobs/archive/{id}', [JobController::class,'archive'])->name('job.archive');
    Route::post('/jobs/addnote', [JobController::class,'addnote']);
    Route::get('/jobs/notes/{id}', [JobController::class,'getnotes']);
    Route::get('/jobs/destroynote/{id}', [JobController::class,'destroynote']);
    Route::post('/jobs/files', [JobController::class,'onlyaddfiles'])->name('job.files');
    Route::get('/jobs/destroyfile/{id}', [JobController::class,'destroyfile']);
    
    // ============= RUTAS CMS =============
    // Panel principal CMS
    Route::get('/cms', [SectionController::class,'index'])->name('cms.index');
    
    // Secciones CMS
    Route::get('/cms/sections/{slug}/edit', [SectionController::class,'edit'])->name('cms.sections.edit');
    Route::post('/cms/sections/{slug}', [SectionController::class,'update'])->name('cms.sections.update');
    Route::get('/cms/sections/{slug}/versions', [SectionController::class,'versions'])->name('cms.sections.versions');
    Route::post('/cms/sections/{slug}/restore/{versionId}', [SectionController::class,'restoreVersion'])->name('cms.sections.restore');
    
    // Media Library
    Route::get('/cms/media', [MediaController::class,'index'])->name('cms.media');
    Route::post('/cms/media/upload', [MediaController::class,'upload'])->name('cms.media.upload');
    Route::post('/cms/media/upload-multiple', [MediaController::class,'uploadMultiple'])->name('cms.media.upload-multiple');
    Route::post('/cms/media/{id}/update-name', [MediaController::class,'updateName'])->name('cms.media.update-name');
    Route::post('/cms/media/{id}', [MediaController::class,'update'])->name('cms.media.update');
    Route::delete('/cms/media/{id}', [MediaController::class,'destroy'])->name('cms.media.destroy');
    
    // API Configuration
    Route::get('/cms/api-config', [ApiConfigController::class,'index'])->name('cms.api-config.index');
    Route::post('/cms/api-config', [ApiConfigController::class,'update'])->name('cms.api-config.update');
    
});