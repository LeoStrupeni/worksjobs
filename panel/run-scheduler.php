<?php
/**
 * Script para ejecutar Laravel Scheduler sin usar exec()
 * Compatible con hosting compartido (Hostinger)
 */

// Redirigir errores y salida
$timestamp = date('Y-m-d H:i:s');
$logFile = __DIR__ . '/storage/logs/cron-scheduler.log';

// Crear log inicial
$log = "\n[{$timestamp}] ========== INICIO CRON ==========\n";
file_put_contents($logFile, $log, FILE_APPEND);

try {
    // Cargar Laravel
    require __DIR__.'/vendor/autoload.php';
    $app = require_once __DIR__.'/bootstrap/app.php';
    
    // Ejecutar el kernel
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    
    // Capturar salida
    ob_start();
    
    // Ejecutar schedule:run
    $status = $kernel->call('schedule:run');
    
    // Obtener salida
    $output = ob_get_clean();
    
    // Registrar resultado
    $log = "[{$timestamp}] Código de retorno: {$status}\n";
    $log .= "[{$timestamp}] Salida:\n{$output}\n";
    $log .= "[{$timestamp}] ========== FIN CRON ==========\n\n";
    file_put_contents($logFile, $log, FILE_APPEND);
    
    echo "✅ Scheduler ejecutado correctamente\n";
    echo $output;
    
} catch (\Exception $e) {
    $error = "[{$timestamp}] ERROR: " . $e->getMessage() . "\n";
    $error .= "[{$timestamp}] File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    $error .= "[{$timestamp}] ========== FIN CON ERROR ==========\n\n";
    file_put_contents($logFile, $error, FILE_APPEND);
    
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
