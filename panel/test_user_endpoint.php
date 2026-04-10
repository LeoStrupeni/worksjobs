<?php
/**
 * Script para probar el endpoint /api/user con token
 * Ejecutar: http://localhost/test_user_endpoint.php?email=leonardo.strupeni@gmail.com
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$email = $_GET['email'] ?? 'leonardo.strupeni@gmail.com';

$user = \App\Models\User::where('email', $email)->first();

if (!$user) {
    echo "<h1>❌ Usuario no encontrado: $email</h1>";
    exit;
}

// Crear token temporal
$token = $user->createToken('test-token')->plainTextToken;

echo "<h1>Testing /api/user endpoint</h1>";
echo "<h2>Token generado (temporal):</h2>";
echo "<pre style='background: #f0f0f0; padding: 10px; word-break: break-all;'>$token</pre>";

// Hacer request al endpoint /api/user usando cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/api/user');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h2>Respuesta del endpoint /api/user:</h2>";
echo "<p><strong>HTTP Status:</strong> $httpCode</p>";
echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT);
echo "</pre>";

$data = json_decode($response, true);

echo "<h2>🔍 Verificación de Permisos de Budgets:</h2>";
echo "<ul>";
if (isset($data['permissions'])) {
    $hasReadBudgets = in_array('read budgets', $data['permissions']);
    $hasCreateBudgets = in_array('create budgets', $data['permissions']);
    
    echo "<li>Tiene 'read budgets': " . ($hasReadBudgets ? '✅ SÍ' : '❌ NO') . "</li>";
    echo "<li>Tiene 'create budgets': " . ($hasCreateBudgets ? '✅ SÍ' : '❌ NO') . "</li>";
} else {
    echo "<li>❌ NO SE ENCONTRÓ ARRAY DE PERMISOS</li>";
}
echo "</ul>";

// Revocar el token temporal
$user->tokens()->where('name', 'test-token')->delete();
echo "<p style='color: green;'>✅ Token temporal revocado</p>";
?>
