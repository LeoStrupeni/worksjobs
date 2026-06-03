<?php

namespace App\Providers;

use App\Models\Rol;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Google\Client;
use Hypweb\Flysystem\GoogleDrive\GoogleDriveAdapter;
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

        // Registramos el driver personalizado 'google'
        Storage::extend('google', function ($app, $config) {
            $client = new Client();
            $client->setClientId($config['clientId']);
            $client->setClientSecret($config['clientSecret']);
            $client->refreshToken($config['refreshToken']);

            // El adaptador de Flysystem v1 que interactúa con la API de Google
            $service = new \Google\Service\Drive($client);
            $adapter = new GoogleDriveAdapter($service, $config['folderId']);

            return new Filesystem($adapter);
        });
    }
}
