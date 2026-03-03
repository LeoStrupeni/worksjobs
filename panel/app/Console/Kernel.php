<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {       
        // Sincronizar clientes de Colppy cada minuto (configuración original - NO MODIFICAR)
        $schedule->call(function () {
            \App\Jobs\SyncColppyClientsJob::dispatch();
        })->everyMinute()
          ->name('sync-colppy-clients')
          ->withoutOverlapping()
          ->onOneServer();

        // Sincronizar productos de Colppy cada 2 horas
        $schedule->call(function () {
            $syncService = new \App\Services\SyncColppyProductsService();
            $syncService->syncProducts();
        })->everyTwoHours()
          ->name('sync-colppy-products')
          ->withoutOverlapping()
          ->onOneServer();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
