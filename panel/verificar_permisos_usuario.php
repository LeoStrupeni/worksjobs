<?php
/**
 * Script para verificar permisos de un usuario
 * Ejecutar desde navegador: http://tu-dominio/verificar_permisos_usuario.php?email=leonardo.strupeni@gmail.com
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

echo "<h1>👤 Usuario: {$user->name} ({$user->email})</h1>";
echo "<h2>🔑 Roles:</h2>";
echo "<ul>";
foreach ($user->getRoleNames() as $role) {
    echo "<li><strong>$role</strong></li>";
}
echo "</ul>";

echo "<h2>🔐 Permisos:</h2>";
echo "<ul>";
$permissions = $user->getAllPermissions()->pluck('name');
foreach ($permissions as $permission) {
    $badge = '';
    if (str_contains($permission, 'budget')) {
        $badge = ' <span style="color: green; font-weight: bold;">✅ BUDGET</span>';
    }
    echo "<li>$permission$badge</li>";
}
echo "</ul>";

echo "<h2>🔍 Verificación Específica:</h2>";
echo "<ul>";
echo "<li>Tiene 'read budgets': " . ($permissions->contains('read budgets') ? '✅ SÍ' : '❌ NO') . "</li>";
echo "<li>Tiene 'create budgets': " . ($permissions->contains('create budgets') ? '✅ SÍ' : '❌ NO') . "</li>";
echo "</ul>";

echo "<h2>📋 JSON que recibiría la app:</h2>";
echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
echo json_encode([
    'user' => [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'roles' => $user->getRoleNames(),
        'permissions' => $permissions
    ]
], JSON_PRETTY_PRINT);
echo "</pre>";
