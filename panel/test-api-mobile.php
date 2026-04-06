#!/usr/bin/env php
<?php
/**
 * Script de Testing para API de App Móvil
 * 
 * CÓMO USAR:
 * 1. php panel/test-api-mobile.php {email_del_usuario}
 * 2. El script mostrará el estado de autenticación y probará los endpoints
 * 
 * EJEMPLO:
 *   php panel/test-api-mobile.php juan@strupeni.com.ar
 */

// Configuración
define('BASE_URL', 'https://tecnicos.strupeni.com.ar/api');
// define('BASE_URL', 'http://localhost/panel/api'); // Para testing local

// Colores para terminal
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m");

function printSuccess($message) {
    echo COLOR_GREEN . "✅ " . $message . COLOR_RESET . "\n";
}

function printError($message) {
    echo COLOR_RED . "❌ " . $message . COLOR_RESET . "\n";
}

function printWarning($message) {
    echo COLOR_YELLOW . "⚠️  " . $message . COLOR_RESET . "\n";
}

function printInfo($message) {
    echo COLOR_BLUE . "ℹ️  " . $message . COLOR_RESET . "\n";
}

function printSeparator() {
    echo "\n" . str_repeat("─", 80) . "\n\n";
}

// Verificar argumento
if ($argc < 2) {
    printError("Falta el email del usuario");
    echo "\nUSO: php test-api-mobile.php {email_del_usuario}\n";
    echo "EJEMPLO: php test-api-mobile.php juan@strupeni.com.ar\n\n";
    exit(1);
}

$userEmail = $argv[1];

printInfo("Iniciando test para usuario: $userEmail");
printSeparator();

// Cargar configuración de Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Buscar usuario en la base de datos
printInfo("PASO 1: Verificando usuario en base de datos...");

$user = \App\Models\User::where('email', $userEmail)->first();

if (!$user) {
    printError("Usuario no encontrado: $userEmail");
    exit(1);
}

printSuccess("Usuario encontrado: {$user->name} (ID: {$user->id})");

if ($user->estatus != 1) {
    printWarning("Usuario NO está activo (estatus: {$user->estatus})");
} else {
    printSuccess("Usuario activo");
}

if ($user->deleted_at !== null) {
    printError("Usuario está ELIMINADO (deleted_at: {$user->deleted_at})");
    exit(1);
}

// Roles y permisos
$roles = $user->getRoleNames()->toArray();
$permissions = $user->getAllPermissions()->pluck('name')->toArray();

printInfo("Roles: " . implode(', ', $roles));
printInfo("Permisos: " . count($permissions) . " permisos");

printSeparator();

// 2. Buscar token del usuario O crear uno temporal
printInfo("PASO 2: Verificando token de autenticación...");

$existingToken = \Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', $user->id)
    ->orderBy('created_at', 'desc')
    ->first();

if ($existingToken) {
    printInfo("Token existente encontrado (ID: {$existingToken->id})");
    printInfo("Creado: {$existingToken->created_at}");
    printInfo("Último uso: " . ($existingToken->last_used_at ?? 'Nunca'));
    
    $diasDesdeCreacion = \Carbon\Carbon::now()->diffInDays($existingToken->created_at);
    printInfo("Días desde creación: $diasDesdeCreacion");
    
    if ($diasDesdeCreacion > 30) {
        printWarning("Token tiene más de 30 días, podría estar expirado");
    }
}

// Generar un token temporal para testing
printInfo("Generando token temporal de prueba...");
$testTokenObject = $user->createToken('test-script-' . date('Y-m-d-H-i-s'));
$testToken = $testTokenObject->plainTextToken;

printSuccess("Token de prueba generado");
printInfo("Token (primeros/últimos 10 chars): " . substr($testToken, 0, 10) . "..." . substr($testToken, -10));
printWarning("Este token será eliminado al final del test");

printSeparator();

// 3. Probar Health Check
printInfo("PASO 3: Probando endpoint /health-check...");

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, BASE_URL . '/health-check');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $testToken,
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Solo para desarrollo

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    printError("Error de conexión: $error");
} else if ($httpCode == 200) {
    printSuccess("Health check OK (HTTP $httpCode)");
    $data = json_decode($response, true);
    if ($data['authenticated'] === true) {
        printSuccess("Usuario autenticado correctamente");
    }
} else {
    printError("Health check falló (HTTP $httpCode)");
    printInfo("Respuesta: $response");
}

printSeparator();

// 4. Probar endpoint de citas del día
printInfo("PASO 4: Probando endpoint /jobs/today...");

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, BASE_URL . '/jobs/today');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $testToken,
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$start = microtime(true);
$response = curl_exec($ch);
$end = microtime(true);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$responseTime = round(($end - $start) * 1000); // en milisegundos

if ($error) {
    printError("Error de conexión: $error");
} else if ($httpCode == 200) {
    $data = json_decode($response, true);
    
    if ($data['success'] === true) {
        printSuccess("Endpoint /jobs/today OK (HTTP $httpCode)");
        printInfo("Trabajos encontrados: " . $data['count']);
        printInfo("Tiempo de respuesta: {$responseTime}ms");
        
        if ($responseTime > 5000) {
            printWarning("Respuesta lenta (más de 5 segundos)");
        }
    } else {
        printError("Respuesta indica error");
        printInfo("Mensaje: " . ($data['message'] ?? 'Sin mensaje'));
    }
} else {
    printError("Endpoint /jobs/today falló (HTTP $httpCode)");
    printInfo("Respuesta: $response");
}

printSeparator();

// 5. Probar endpoint de próximas citas
printInfo("PASO 5: Probando endpoint /jobs/upcoming...");

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, BASE_URL . '/jobs/upcoming');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $testToken,
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$start = microtime(true);
$response = curl_exec($ch);
$end = microtime(true);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$responseTime = round(($end - $start) * 1000);

if ($error) {
    printError("Error de conexión: $error");
} else if ($httpCode == 200) {
    $data = json_decode($response, true);
    
    if ($data['success'] === true) {
        printSuccess("Endpoint /jobs/upcoming OK (HTTP $httpCode)");
        printInfo("Próximos trabajos: " . $data['count']);
        printInfo("Tiempo de respuesta: {$responseTime}ms");
    } else {
        printError("Respuesta indica error");
        printInfo("Mensaje: " . ($data['message'] ?? 'Sin mensaje'));
    }
} else {
    printError("Endpoint /jobs/upcoming falló (HTTP $httpCode)");
    printInfo("Respuesta: $response");
}

printSeparator();

// 6. Estadísticas de trabajos
printInfo("PASO 6: Estadísticas de trabajos...");

$todayJobs = \App\Models\Job::whereRaw("DATE(visit_datetime) = CURDATE()")
    ->whereNull('deleted_at')
    ->count();

$oldOpenJobs = \App\Models\Job::whereRaw("DATE(visit_datetime) < CURDATE()")
    ->whereNull('deleted_at')
    ->whereNull('closed_datetime')
    ->count();

$upcomingJobs = \App\Models\Job::whereRaw("DATE(visit_datetime) > CURDATE()")
    ->whereNull('deleted_at')
    ->whereNull('closed_datetime')
    ->count();

printInfo("Trabajos de hoy: $todayJobs");
printInfo("Trabajos antiguos abiertos: $oldOpenJobs");
printInfo("Próximos trabajos: $upcomingJobs");

if ($oldOpenJobs > 50) {
    printWarning("Hay muchos trabajos antiguos abiertos ($oldOpenJobs)");
    printWarning("Esto puede hacer lenta la carga de 'Citas de Hoy'");
}

printSeparator();

// Limpiar: Eliminar el token temporal generado
printInfo("Limpiando token temporal de prueba...");
$testTokenObject->accessToken->delete();
printSuccess("Token temporal eliminado");

printSeparator();

// Resumen final
echo "\n";
printInfo("═══════════════════════════════════════════════════════════════════════════════");
printInfo("                              RESUMEN DEL TEST");
printInfo("═══════════════════════════════════════════════════════════════════════════════");
echo "\n";

if ($user->estatus == 1 && $user->deleted_at === null && $existingToken && $httpCode == 200) {
    printSuccess("TODO OK - El usuario debería poder usar la app sin problemas");
    echo "\n";
    printInfo("Si el usuario sigue reportando errores:");
    printInfo("  1. Pedirle que cierre y abra la app completamente");
    printInfo("  2. Verificar que tenga buena conexión a internet");
    printInfo("  3. Pedirle que haga logout/login nuevamente");
    printInfo("  4. Revisar logs del servidor: panel/storage/logs/laravel.log");
} else {
    printError("HAY PROBLEMAS - Revisar los errores marcados arriba");
    echo "\n";
    printInfo("Acciones recomendadas:");
    
    if ($user->estatus != 1) {
        printInfo("  - Activar el usuario en la base de datos");
    }
    
    if (!$existingToken) {
        printInfo("  - El usuario debe hacer login desde la app");
    }
    
    if ($httpCode != 200) {
        printInfo("  - Verificar logs del servidor");
        printInfo("  - Revisar conectividad al servidor");
    }
}

echo "\n";
printInfo("═══════════════════════════════════════════════════════════════════════════════");
echo "\n";
