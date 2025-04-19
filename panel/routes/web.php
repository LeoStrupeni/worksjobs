<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ExcelController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('test', function () {
	dd( base_path(). '/../public/storage/',env('APP_URL'),storage_path('app'));
});

Route::get('/', [HomeController::class,'index'])->name('home.index');

Route::view('/login','Auth.login')->name('login');
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
    Route::resource('/users',UserController::class);
    Route::post('/users/table', [UserController::class,'getDataTable']);
    Route::resource('/roles',RolController::class);
    Route::post('/roles/table', [RolController::class,'getDataTable']);
    Route::get('/roles/users/{id}', [RolController::class,'getUsersRol']);
    
    Route::resource('/permission',PermissionController::class);
    Route::post('/permission/table', [PermissionController::class,'getDataTable']);

    Route::post('/roles/permission/update', [PermissionController::class,'updaterolpermission'])->name('updaterolpermission');

    Route::resource('/client',ClientController::class);
    Route::post('/client/table', [ClientController::class,'getDataTable']);
    Route::post('/client/excel', [ExcelController::class,'importaClientsExcel'])->name('importaExcelClient');

    Route::get('/client/address/{id}', [ClientController::class,'getAddress']);
    Route::post('/client/address', [ClientController::class,'postAddress']);
    Route::delete('/client/address/{id}', [ClientController::class,'detroyAddress']);

    Route::resource('/jobs',JobController::class);
    Route::post('/jobs/table', [JobController::class,'getDataTable']);
    Route::post('/jobs/markarrival', [JobController::class,'markarrival']);
    Route::post('/jobs/addnote', [JobController::class,'addnote']);
    Route::get('/jobs/notes/{id}', [JobController::class,'getnotes']);
    Route::get('/jobs/destroynote/{id}', [JobController::class,'destroynote']);
    
});