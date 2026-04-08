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

            // ========================================================
            // ESTRATEGIA: Obtener TODOS los registros y ordenar localmente
            // porque Colppy ordena como texto (fechas y números incorrectos)
            // ========================================================
            
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

            // Obtener TODOS los registros (sin paginación) para ordenar localmente
            // Usamos un límite alto (1000) asumiendo que no hay tantos presupuestos borradores
            $resultado = $this->colppyService->listarFacturasVenta(0, 1000, $filtros, null);

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
            $totalRegistros = count($datos);

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

            // ========================================================
            // ORDENAMIENTO PERSONALIZADO (fechas y números correctos)
            // ========================================================
            if (!empty($order)) {
                $orderParts = explode(' ', $order);
                $campo = $orderParts[0] ?? '';
                $direccion = strtoupper($orderParts[1] ?? 'ASC');
                
                usort($datosFormateados, function($a, $b) use ($campo, $direccion) {
                    $valorA = $a[$campo] ?? '';
                    $valorB = $b[$campo] ?? '';
                    
                    // Ordenamiento para nroFactura (formato: 0002-00000046)
                    if ($campo === 'nroFactura') {
                        // Extraer los números eliminando el guión
                        $numA = (int)str_replace('-', '', $valorA);
                        $numB = (int)str_replace('-', '', $valorB);
                        
                        $resultado = $numA <=> $numB;
                    }
                    // Ordenamiento para fechaFactura (formato: YYYY-MM-DD o DD/MM/YYYY)
                    elseif ($campo === 'fechaFactura') {
                        // Convertir a timestamp para comparar correctamente
                        $timeA = strtotime($valorA);
                        $timeB = strtotime($valorB);
                        
                        // Si no se pueden convertir, ordenar alfabéticamente
                        if ($timeA === false || $timeB === false) {
                            $resultado = strcmp($valorA, $valorB);
                        } else {
                            $resultado = $timeA <=> $timeB;
                        }
                    }
                    // Ordenamiento alfabético para otros campos
                    else {
                        $resultado = strcasecmp($valorA, $valorB);
                    }
                    
                    // Invertir si es DESC
                    return $direccion === 'DESC' ? -$resultado : $resultado;
                });
            }

            // ========================================================
            // PAGINACIÓN LOCAL
            // ========================================================
            $totalFiltrados = count($datosFormateados);
            $totalPages = ceil($totalFiltrados / $limit);
            $start = ($page - 1) * $limit;
            
            // Obtener solo los registros de la página actual
            $datosPaginados = array_slice($datosFormateados, $start, $limit);

            // Construir respuesta compatible con tableAjaxLocal.js
            $respuesta = [
                'totales' => $totalFiltrados,
                'filtrados' => $totalFiltrados,
                'paginastotal' => $totalPages,
                'datos' => $datosPaginados,
                'roluser' => $roluser,
                'permissions' => $permissions,
                'special_role_ids' => get_special_role_ids()
            ];

            // Información de paginación
            $inicio = $start + 1;
            $fin = min($start + $limit, $totalFiltrados);
            $respuesta['infototal'] = "Mostrando registros del $inicio al $fin de un total de $totalFiltrados";

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

    /**
     * Obtener tareas disponibles para asociar a un presupuesto
     * SOLO tareas con status 'Pendiente' o 'En Lugar' (NOT 'Cerrado')
     * Y que NO tengan presupuesto asociado (colppy_budget_id IS NULL)
     */
    public function getAvailableJobs(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado'
                ], 401);
            }

            // Obtener tareas disponibles
            $jobs = DB::table('jobs as j')
                ->leftJoin('clients as cl', 'j.client_id', '=', 'cl.id')
                ->whereNull('j.deleted_at')
                ->whereNull('j.colppy_budget_id')  // Sin presupuesto asociado
                ->whereNull('j.closed_datetime')   // NO cerradas
                ->select(
                    'j.id',
                    'j.created_at',
                    'j.visit_datetime',
                    'j.arrival_datetime',
                    'j.job_description',
                    DB::raw("CONCAT(cl.first_name, ' ', IFNULL(cl.last_name, '')) AS client_name"),
                    DB::raw("CASE WHEN j.arrival_datetime IS NOT NULL THEN 'En Lugar' ELSE 'Pendiente' END as status")
                )
                ->orderBy('j.created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'jobs' => $jobs
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getAvailableJobs', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tareas disponibles'
            ], 500);
        }
    }

    /**
     * Asociar una o varias tareas a un presupuesto/factura de Colppy
     * Guarda tanto el ID (colppy_budget_id) como el número de factura (colppy_budget_number)
     */
    public function associateJobs(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado'
                ], 401);
            }

            $request->validate([
                'budget_id' => 'required|string',
                'budget_number' => 'required|string',
                'job_ids' => 'required|array',
                'job_ids.*' => 'required|integer|exists:jobs,id'
            ]);

            $budgetId = $request->budget_id;
            $budgetNumber = $request->budget_number;
            $jobIds = $request->job_ids;

            // Verificar que las tareas estén disponibles (sin presupuesto y no cerradas)
            $unavailableJobs = DB::table('jobs')
                ->whereIn('id', $jobIds)
                ->where(function($query) {
                    $query->whereNotNull('colppy_budget_id')
                          ->orWhereNotNull('closed_datetime');
                })
                ->whereNull('deleted_at')
                ->pluck('id')
                ->toArray();

            if (count($unavailableJobs) > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Algunas tareas ya tienen presupuesto asociado o están cerradas',
                    'unavailable_jobs' => $unavailableJobs
                ], 400);
            }

            // Actualizar las tareas
            DB::table('jobs')
                ->whereIn('id', $jobIds)
                ->update([
                    'colppy_budget_id' => $budgetId,
                    'colppy_budget_number' => $budgetNumber,
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Tareas asociadas correctamente al presupuesto',
                'jobs_updated' => count($jobIds)
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error en associateJobs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al asociar tareas al presupuesto'
            ], 500);
        }
    }
}
