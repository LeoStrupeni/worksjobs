<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ApiAuthController extends Controller
{
    /**
     * Login de usuario y generación de token Sanctum
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required',
        ]);

        // Buscar por email o name
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Las credenciales son incorrectas.'
            ], 401);
        }

        // Verificar si el usuario está activo
        if ($user->estatus != 1 || $user->deleted_at !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario inactivo o eliminado.'
            ], 403);
        }

        // Revocar tokens anteriores
        $user->tokens()->delete();

        // Crear nuevo token
        $token = $user->createToken('mobile-app')->plainTextToken;

        // Obtener roles y permisos
        $roles = $user->getRoleNames();
        $permissions = $user->getAllPermissions()->pluck('name');

        // Obtener lista de técnicos (misma lógica que en web)
        $_users = User::join('model_has_roles','users.id','=','model_has_roles.model_id')
            ->where('estatus', 1)
            ->whereNotIn('model_has_roles.role_id', [3,4])
            ->select('users.id', 'users.name')
            ->get();
        $technicians = $_users->map(function($u) {
            return ['id' => $u->id, 'name' => $u->name];
        })->values()->toArray();

        // Obtener lista de productos (misma lógica que en web)
        $_products = Product::query()->whereNull('deleted_at')->limit(10)->get(['id', 'codigo', 'descripcion', 'is_from_colppy']);
        $products = $_products->map(function($u) {
            return [
                'id' => $u->id, 
                'codigo' => $u->codigo, 
                'descripcion' => $u->descripcion,
                'is_from_colppy' => $u->is_from_colppy ?? false
            ];
        })->values()->toArray();

        return response()->json([
            'success' => true,
            'message' => 'Login exitoso',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'imagen' => $user->imagen,
                    'roles' => $roles,
                    'permissions' => $permissions
                ],
                'token' => $token,
                'technicians' => $technicians,
                'products' => $products
            ]
        ], 200);
    }

    /**
     * Logout del usuario y revocación de tokens
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Revocar el token actual
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout exitoso'
        ], 200);
    }

    /**
     * Revocar todos los tokens del usuario
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logoutAll(Request $request)
    {
        // Revocar todos los tokens del usuario
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Se cerraron todas las sesiones'
        ], 200);
    }
    
    /**
     * Health check - Verificar autenticación y estado del usuario
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function healthCheck(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                \Log::warning('⚠️ Health Check - Usuario no autenticado');
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado',
                    'authenticated' => false
                ], 401);
            }
            
            // Obtener información del usuario
            $roles = $user->getRoleNames();
            $permissions = $user->getAllPermissions()->pluck('name');
            
            \Log::info('✅ Health Check - Usuario: ' . $user->id . ' - ' . $user->email . ' - Roles: ' . $roles->implode(','));
            
            return response()->json([
                'success' => true,
                'message' => 'Autenticación válida',
                'authenticated' => true,
                'data' => [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $roles,
                    'permissions_count' => $permissions->count(),
                    'estatus' => $user->estatus,
                    'token_name' => $user->currentAccessToken()->name ?? null,
                    'token_created_at' => $user->currentAccessToken()->created_at ?? null
                ]
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('❌ Health Check - Exception: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error en health check',
                'authenticated' => false,
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
