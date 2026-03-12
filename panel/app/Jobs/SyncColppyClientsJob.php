<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\SyncColppyClientsService;
use Illuminate\Support\Facades\Log;

class SyncColppyClientsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * El número de veces que el job puede intentarse.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * El número de segundos que el job puede ejecutarse antes de timeout.
     *
     * @var int
     */
    public $timeout = 300; // 5 minutos

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Log::info('Job SyncColppyClientsJob iniciado');

        try {
            $syncService = new SyncColppyClientsService();
            $resultado = $syncService->syncClients();

            if ($resultado['success']) {
                // Log::info('Job SyncColppyClientsJob completado exitosamente', [
                //     'nuevos' => $resultado['nuevos'],
                //     'actualizados' => $resultado['actualizados'],
                //     'errores' => $resultado['errores'],
                //     'total' => $resultado['total']
                // ]);
            } else {
                Log::error('Job SyncColppyClientsJob falló', ['mensaje' => $resultado['mensaje']]);
                throw new \Exception($resultado['mensaje']);
            }

        } catch (\Exception $e) {
            Log::error('Error en Job SyncColppyClientsJob', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-lanzar la excepción para que el job se reintente si falló
            throw $e;
        }
    }

    /**
     * Manejar un job fallido.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Job SyncColppyClientsJob falló definitivamente después de todos los intentos', [
            'error' => $exception->getMessage()
        ]);
    }
}
