<?php

use App\Http\Controllers\Api\ApiDataTablesController;
use App\Http\Controllers\Api\ApiJobController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ExcelController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

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

Route::get('/', [HomeController::class,'index'])->name('home.index');
// Route::view('/login','Auth.login', ['google_api_key' => DB::table('configs')->where('name','google_api_key')->first()->value])->name('login');
Route::get('/login', [LoginController::class,'loginget'])->name('login');
Route::post('/login', [LoginController::class,'login']);
Route::post('/logout', [LoginController::class,'logout']);
Route::get('/logout', [LoginController::class,'logoutGet'])->name('logout');

Route::view('/password/reset','Auth.passwords.email')->name('password.request');
Route::post('/password/email', [ForgotPasswordController::class,'sendResetLinkEmail'])->name('password.email');
Route::get('/password/reset/{token}',[ForgotPasswordController::class,'showResetForm'])->name('password.reset');
Route::post('/password/reset', [ForgotPasswordController::class,'reset'])->name('password.update');


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
    Route::post('/client/excel', [ExcelController::class,'importaClientsExcel'])->name('importaExcelClient');

    Route::get('/client/address/{id}', [ApiDataTablesController::class,'getClientAddresses']);
    Route::post('/client/address', [ClientController::class,'postAddress']);
    Route::delete('/client/address/{id}', [ClientController::class,'detroyAddress']);

    Route::resource('/jobs',JobController::class);
    Route::post('/jobs/table', [ApiJobController::class,'getDataTable'])->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    Route::post('/jobs/markarrival', [JobController::class,'markarrival']);
    Route::post('/jobs/backarrival', [JobController::class,'backarrival']);
    Route::post('/jobs/closed', [JobController::class,'closed'])->name('job.closed');
    Route::post('/jobs/addnote', [JobController::class,'addnote']);
    Route::get('/jobs/notes/{id}', [JobController::class,'getnotes']);
    Route::get('/jobs/destroynote/{id}', [JobController::class,'destroynote']);
    Route::post('/jobs/files', [JobController::class,'onlyaddfiles'])->name('job.files');
    Route::get('/jobs/destroyfile/{id}', [JobController::class,'destroyfile']);
    
    
});