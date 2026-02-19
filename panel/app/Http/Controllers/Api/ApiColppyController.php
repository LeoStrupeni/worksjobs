<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ColppyService;
use Illuminate\Http\Request;

class ApiColppyController extends Controller
{
    private ColppyService $colppyService;

    public function __construct(ColppyService $colppyService)
    {
        $this->colppyService = $colppyService;
    }

    /**
     * Obtener clave de sesión de Colppy
     */
    public function obtenerSesion()
    {
        $resultado = $this->colppyService->obtenerClaveSesion();

        if (!$resultado['success']) {
            return response()->json([
                'success' => false,
                'message' => $resultado['mensaje'] ?? 'Error'
            ], 400);
        }

        $claveSesion = \Illuminate\Support\Facades\Session::get('colppy_clave_sesion');

        return response()->json([
            'success' => true,
            'data' => [
                'claveSesion' => $claveSesion
            ]
        ]);
    }

    /**
     * Listar clientes de Colppy
     */
    public function listarClientes(Request $request)
    {
        $start = (int) $request->query('start', 0);
        $limit = (int) $request->query('limit', 100);
        
        $filtros = [];
        if ($request->has('filters')) {
            $filtros = json_decode($request->query('filters'), true) ?? [];
        }

        $orden = [];
        if ($request->has('order')) {
            $orden = json_decode($request->query('order'), true) ?? [];
        }

        $resultado = $this->colppyService->listarClientes($start, $limit, $filtros, $orden);

        if ($resultado['success']) {
            return response()->json([
                'success' => true,
                'data' => $resultado['datos']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $resultado['mensaje'] ?? 'Error'
        ], 400);
    }

    /**
     * Obtener cliente específico
     */
    public function obtenerCliente(string $idCliente)
    {
        $resultado = $this->colppyService->obtenerCliente($idCliente);

        if ($resultado['success']) {
            return response()->json([
                'success' => true,
                'data' => $resultado['datos'][0] ?? $resultado['datos']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $resultado['mensaje'] ?? 'Error'
        ], 400);
    }

    /**
     * Hacer una llamada genérica a Colppy
     */
    public function hacerLlamada(Request $request)
    {
        $validated = $request->validate([
            'auth' => 'required|array',
            'service' => 'required|array',
            'parameters' => 'required|array'
        ]);

        $resultado = $this->colppyService->hacerLlamada($validated);

        if ($resultado['success']) {
            return response()->json([
                'success' => true,
                'data' => $resultado['datos']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $resultado['mensaje'] ?? 'Error'
        ], 400);
    }

    /**
     * Invalidar sesión
     */
    public function invalidarSesion()
    {
        $this->colppyService->invalidarSesion();

        return response()->json([
            'success' => true,
            'message' => 'Sesión invalidada'
        ]);
    }
}
