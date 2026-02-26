<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
                'technicians' => $technicians
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
}
