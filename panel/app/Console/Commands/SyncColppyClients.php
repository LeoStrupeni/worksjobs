<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SyncColppyClientsService;

class SyncColppyClients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'colppy:sync-clients';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizar clientes de Colppy a la base de datos local';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('=== INICIANDO SINCRONIZACIÓN DE CLIENTES COLPPY ===');
        $this->info('');

        $syncService = new SyncColppyClientsService();
        
        // Mostrar barra de progreso
        $this->info('Sincronizando clientes...');
        
        $resultado = $syncService->syncClients();

        $this->info('');
        
        if ($resultado['success']) {
            $this->info('=== SINCRONIZACIÓN COMPLETADA ===');
            $this->info('');
            $this->line("✓ Clientes nuevos: {$resultado['nuevos']}");
            $this->line("✓ Clientes actualizados: {$resultado['actualizados']}");
            $this->line("✓ Total procesados: {$resultado['total']}");
            
            if ($resultado['errores'] > 0) {
                $this->warn("⚠ Errores: {$resultado['errores']} (ver logs para detalles)");
            }
            
            $this->info('');
            return 0;
        } else {
            $this->error('=== ERROR EN SINCRONIZACIÓN ===');
            $this->error($resultado['mensaje']);
            $this->info('');
            return 1;
        }
    }
}
