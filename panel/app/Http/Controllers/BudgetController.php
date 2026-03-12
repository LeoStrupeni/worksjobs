<?php

namespace App\Http\Controllers;

use App\Services\ColppyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class BudgetController extends Controller
{
    protected $colppyService;

    public function __construct(ColppyService $colppyService)
    {
        $this->colppyService = $colppyService;
    }

    /**
     * Mostrar vista principal de presupuestos
     */
    public function index()
    {
        if (Auth::check()) {
            $val = $this->getloginrol();
            if ($val == false) {
                return redirect()->route('logout');
            }

            return view("budgets");
        }

        return redirect()->route('login');
    }

    /**
     * Obtener datos de presupuestos para DataTable vía AJAX
     * Filtra presupuestos borradores: nroFactura >= "0002-00000000"
     */
    public function getBudgetsDataTable(Request $request)
    {
        try {
            $roluser = Session::get('user')['roles'][0];
            $permissions = Session::get('user')['permissions']['jobs'] ?? ['read'];

            $order = $request->order;
            $page = $request->page ?? 1;
            $limit = $request->limit ?? 10;
            $search = $request->search;

            // Calcular start para la API de Colppy
            $start = ($page - 1) * $limit;

            // Construir filtros para Colppy
            // Filtrar presupuestos borradores: nroFactura >= "0002-0000000" (formato: XXXX-XXXXXXX)
            $filtros = [
                [
                    'field' => 'nroFactura',
                    'op' => '>=',
                    'value' => '0002-0000000'
                ],
                [
                    'field' => 'nroFactura',
                    'op' => '<=',
                    'value' => '0002-9999999'
                ]
            ];

            // Si hay búsqueda, agregar filtro (buscar en cliente o nro factura)
            if (!empty($search)) {
                // Nota: Colppy puede no soportar búsqueda por texto libre
                // Intentamos buscar por nroFactura
                $filtros[] = [
                    'field' => 'nroFactura',
                    'op' => 'like',
                    'value' => '%' . $search . '%'
                ];
            }

            // Construir orden según documentación Colppy
            // Debe ser un objeto con: field (array) y order (string "asc"/"desc")
            $orden = (object)[];
            if (!empty($order)) {
                // Parsear orden del formato "campo ASC" o "campo DESC"
                $orderParts = explode(' ', $order);
                if (count($orderParts) >= 2) {
                    $orden = (object)[
                        'field' => [$orderParts[0]],  // Colppy espera un array de campos
                        'order' => strtolower($orderParts[1])  // "asc" o "desc" en minúsculas
                    ];
                }
            }

            // Si no hay orden específico, dejar vacío para que el servicio use el default
            if (empty((array)$orden)) {
                $orden = null;
            }

            // Llamar al servicio de Colppy
            $resultado = $this->colppyService->listarFacturasVenta($start, $limit, $filtros, $orden);

            if (!$resultado['success']) {
                // Log::error('Error al obtener presupuestos desde Colppy', [
                //     'mensaje' => $resultado['mensaje'] ?? 'Error desconocido'
                // ]);

                return response()->json([
                    'success' => false,
                    'message' => $resultado['mensaje'] ?? 'Error al obtener presupuestos'
                ], 500);
            }

            // Procesar datos de respuesta
            $datos = $resultado['datos'] ?? [];
            $total = $resultado['total'] ?? count($datos);

            // Obtener IDs de facturas para verificar asociaciones con tareas
            $idsFacturas = array_column($datos, 'idFactura');
            
            // Consultar jobs que tienen asociación con estos presupuestos
            // Agregar información completa de las tareas asociadas
            $jobsAsociados = DB::table('jobs')
                ->whereIn('colppy_budget_id', $idsFacturas)
                ->whereNull('deleted_at')
                ->select('id', 'colppy_budget_id')
                ->get()
                ->groupBy('colppy_budget_id');

            // Formatear datos para la tabla
            $datosFormateados = [];
            foreach ($datos as $item) {
                $idFactura = $item['idFactura'] ?? '';
                
                // Verificar si tiene tareas asociadas (puede tener múltiples)
                $tareasAsociadas = $jobsAsociados->has($idFactura) ? $jobsAsociados->get($idFactura) : collect();
                $idsTareas = $tareasAsociadas->pluck('id')->toArray();
                
                $datosFormateados[] = [
                    'idFactura' => $idFactura,
                    'nroFactura' => $item['nroFactura'] ?? '',
                    'fechaFactura' => $item['fechaFactura'] ?? '',
                    'idCliente' => $item['idCliente'] ?? '',
                    'nombreCliente' => $item['RazonSocial'] ?? 'N/A',
                    'totalFactura' => number_format(floatval($item['totalFactura'] ?? 0), 2, '.', ''),
                    'descripcion' => $item['descripcion'] ?? '',
                    'idEstadoFactura' => $item['idEstadoFactura'] ?? '',
                    'estadoDescripcion' => $this->getEstadoDescripcion($item['idEstadoFactura'] ?? ''),
                    'idsTareas' => $idsTareas,  // Array de IDs de tareas
                    'cantidadTareas' => count($idsTareas)
                ];
            }

            // Construir respuesta compatible con tableAjaxLocal.js
            $totalPages = ceil($total / $limit);
            $totalFiltrados = $total; // En este caso son iguales ya que filtramos en Colppy

            $respuesta = [
                'totales' => $total,
                'filtrados' => $totalFiltrados,
                'paginastotal' => $totalPages,
                'datos' => $datosFormateados,
                'roluser' => $roluser,
                'permissions' => $permissions,
                'special_role_ids' => get_special_role_ids()
            ];

            // Información de paginación
            $inicio = $start + 1;
            $fin = min($start + $limit, $total);
            $respuesta['infototal'] = "Mostrando registros del $inicio al $fin de un total de $total";

            return response()->json($respuesta);

        } catch (\Exception $e) {
            // Log::error('Excepción en getBudgetsDataTable', [
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno al obtener presupuestos'
            ], 500);
        }
    }

    /**
     * Obtener detalle completo de una factura desde Colppy
     */
    public function getBudgetDetail($idFactura)
    {
        try {
            if (Auth::check()) {
                $val = $this->getloginrol();
                if ($val == false) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Sesión inválida'
                    ], 401);
                }

                // Llamar al servicio de Colppy para leer el detalle
                $resultado = $this->colppyService->leerFacturaVenta($idFactura);

                if (!$resultado['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => $resultado['mensaje'] ?? 'Error al obtener detalle de factura'
                    ], 500);
                }

                // Extraer datos de la respuesta
                $response = $resultado['response'] ?? [];
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'infofactura' => $response['infofactura'] ?? [],
                        'itemsFactura' => $response['itemsFactura'] ?? [],
                        'totalesiva' => $response['totalesiva'] ?? [],
                        'UrlFacturaPdf' => $response['UrlFacturaPdf'] ?? null
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No autenticado'
            ], 401);

        } catch (\Exception $e) {
            // Log::error('Excepción en getBudgetDetail', [
            //     'idFactura' => $idFactura,
            //     'error' => $e->getMessage()
            // ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno al obtener detalle'
            ], 500);
        }
    }

    /**
     * Obtener descripción legible del estado de factura
     */
    private function getEstadoDescripcion($idEstado)
    {
        $estados = [
            '1' => 'Borrador',
            '2' => 'Facturado',
            '3' => 'Anulado',
            '4' => 'Pendiente',
            '5' => 'Pagado',
        ];

        return $estados[$idEstado] ?? 'Desconocido';
    }
}
