<?php
// Script de prueba para verificar que el cron funciona
$logFile = __DIR__ . '/storage/logs/cron-test.log';
$timestamp = date('Y-m-d H:i:s');
$message = "[{$timestamp}] ✅ Cron ejecutado correctamente\n";

file_put_contents($logFile, $message, FILE_APPEND);

echo "OK - {$timestamp}\n";
