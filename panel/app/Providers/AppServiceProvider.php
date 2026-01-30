<?php

namespace App\Providers;

use App\Models\Rol;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Compartir roles con todas las vistas de usuario
        View::composer(['user.create', 'user.edit', 'user.show'], function ($view) {
            $view->with('roles', Rol::where('estatus', 1)->get());
        });
    }
}
