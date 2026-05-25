<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Job;
use App\Models\Product;
use App\Services\ColppyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class ApiBudgetController extends Controller
{
    /**
     * Constructor - Aplicar middleware de permisos
     */
    public function __construct()
    {
        // Permisos para CRUD de presupuestos
        $this->middleware('permission:read budgets')->only(['index', 'show']);
        $this->middleware('permission:create budgets')->only(['store']);
        $this->middleware('permission:update budgets')->only(['update']);
        
        // Permiso para generar PDF
        $this->middleware('permission:create pdf')->only(['downloadPdf']);
        
        // Permiso para crear clientes (alta con AFIP)
        $this->middleware('permission:create clients')->only(['createClient']);
        
        // getProductsAndServices no requiere permiso específico (búsqueda)
    }

    /**
     * Listar presupuestos desde Colppy (filtrados por talonario 0002)
     * 
     * GET /api/budgets
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 20);
            $search = $request->input('search', '');
            $clientId = $request->input('client_id');
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');

            // El frontend envía client_id LOCAL (tabla clients.id), pero Colppy filtra por idCliente.
            // Convertimos local -> colppy_id para evitar falsos "sin resultados".
            $clientColppyId = null;
            if ($clientId !== null && $clientId !== '') {
                $localClient = Client::select('id', 'colppy_id')
                    ->where('id', $clientId)
                    ->whereNull('deleted_at')
                    ->first();

                if ($localClient && !empty($localClient->colppy_id)) {
                    $clientColppyId = (string) $localClient->colppy_id;
                } else {
                    // Fallback por compatibilidad: si ya viniera id de Colppy, usarlo tal cual.
                    $clientColppyId = (string) $clientId;
                }
            }
            
            $colppyService = new ColppyService();
            
            // Filtros para obtener solo presupuestos del talonario 0002-
            // Formato: XXXX-XXXXXXX (ej: 0002-0000001)
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
            
            // Si hay búsqueda, agregar filtro
            if (!empty($search)) {
                $filtros[] = [
                    'field' => 'nroFactura',
                    'op' => 'like',
                    'value' => '%' . $search . '%'
                ];
            }
            
            // Obtener todos los registros (usamos límite alto)
            $resultado = $colppyService->listarFacturasVenta(0, 1000, $filtros, null);
            
            if (!$resultado['success']) {
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
            $jobsAsociados = DB::table('jobs')
                ->whereIn('colppy_budget_id', $idsFacturas)
                ->whereNull('deleted_at')
                ->select('id', 'colppy_budget_id')
                ->get()
                ->groupBy('colppy_budget_id');
            
            // Formatear datos para la app
            $datosFormateados = [];
            foreach ($datos as $item) {
                $idFactura = $item['idFactura'] ?? '';
                
                // Convertir client_id a int o null (evitar strings vacíos)
                $itemClientId = null;
                if (isset($item['idCliente']) && $item['idCliente'] !== '' && $item['idCliente'] !== null) {
                    $itemClientId = (int) $item['idCliente'];
                }
                
                $datosFormateados[] = [
                    'id' => null, // Budget local (no aplicable desde Colppy)
                    'id_factura' => (string) $idFactura,
                    'nro_factura' => (string) ($item['nroFactura'] ?? ''),
                    'client_id' => $itemClientId,
                    'client_name' => !empty($item['RazonSocial']) ? (string) $item['RazonSocial'] : null,
                    'client_cuit' => null, // TODO: obtener de cliente si es necesario
                    'fecha' => (string) ($item['fechaFactura'] ?? ''),
                    'total' => (float) ($item['totalFactura'] ?? 0.0),
                    'observaciones' => !empty($item['descripcion']) ? (string) $item['descripcion'] : null,
                    'created_by' => null,
                    'created_by_name' => null,
                    'created_at' => null,
                    'updated_at' => null,
                    'items' => [] // Vacío en el listado, se obtienen en el detalle
                ];
            }

            // Filtro por cliente (idCliente de Colppy)
            if ($clientColppyId !== null && $clientColppyId !== '') {
                $datosFormateados = array_values(array_filter($datosFormateados, function ($budget) use ($clientColppyId) {
                    if (!isset($budget['client_id']) || $budget['client_id'] === null || $budget['client_id'] === '') {
                        return false;
                    }

                    return (string) $budget['client_id'] === (string) $clientColppyId;
                }));
            }

            // Filtro por rango de fechas (inclusive)
            if (!empty($dateFrom) || !empty($dateTo)) {
                $dateFromCarbon = null;
                $dateToCarbon = null;

                try {
                    if (!empty($dateFrom)) {
                        $dateFromCarbon = Carbon::parse($dateFrom)->startOfDay();
                    }
                } catch (\Exception $e) {
                    $dateFromCarbon = null;
                }

                try {
                    if (!empty($dateTo)) {
                        $dateToCarbon = Carbon::parse($dateTo)->endOfDay();
                    }
                } catch (\Exception $e) {
                    $dateToCarbon = null;
                }

                $datosFormateados = array_values(array_filter($datosFormateados, function ($budget) use ($dateFromCarbon, $dateToCarbon) {
                    if (empty($budget['fecha'])) {
                        return false;
                    }

                    try {
                        $budgetDate = Carbon::parse($budget['fecha']);
                    } catch (\Exception $e) {
                        return false;
                    }

                    if ($dateFromCarbon && $budgetDate->lt($dateFromCarbon)) {
                        return false;
                    }

                    if ($dateToCarbon && $budgetDate->gt($dateToCarbon)) {
                        return false;
                    }

                    return true;
                }));
            }

            $totalRegistros = count($datosFormateados);
            
            // Ordenar por fecha descendente
            usort($datosFormateados, function($a, $b) {
                $fechaA = strtotime($a['fecha'] ?? '');
                $fechaB = strtotime($b['fecha'] ?? '');
                return $fechaB - $fechaA; // Descendente (más reciente primero)
            });
            
            // Aplicar paginación manual
            $start = ($page - 1) * $limit;
            $datosPaginados = array_slice($datosFormateados, $start, $limit);
            
            // DEBUG: Loguear EXACTAMENTE qué se está enviando
            // Log::info('DEBUG ApiBudgetController::index - Datos enviados', [
            //     'primer_presupuesto' => $datosPaginados[0] ?? null,
            //     'tipos' => array_map(function($item) {
            //         return array_map('gettype', $item);
            //     }, array_slice($datosPaginados, 0, 1))
            // ]);
            
            return response()->json([
                'success' => true,
                'data' => $datosPaginados,
                'total' => (int) $totalRegistros,
                'page' => (int) $page,
                'limit' => (int) $limit
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en ApiBudgetController::index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener presupuestos: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Formatear datos de Colppy al formato que espera la app
     */
    private function formatBudgetForApp($datosColppy)
    {
        // CRÍTICO: Buscar cliente LOCAL por colppy_id
        $clientId = null;
        $clientName = null;
        $clientCuit = null;
        $clientAddressId = null;
        $clientAddress = null;
        
        $idClienteColppy = $datosColppy['idCliente'] ?? null;
        if ($idClienteColppy) {
            $client = Client::where('colppy_id', $idClienteColppy)
                ->where('is_active', 1)
                ->first();
            if ($client) {
                // Usar ID LOCAL del cliente, NO el colppy_id
                $clientId = $client->id;
                $clientName = trim($client->first_name . ' ' . ($client->last_name ?? ''));
                $clientCuit = $client->num_doc;
                
                // Buscar domicilio principal
                $address = DB::table('clients_address')
                    ->where('client_id', $client->id)
                    ->whereNull('deleted_at')
                    ->orderBy('id', 'asc')
                    ->first();
                
                if ($address) {
                    $clientAddressId = $address->id;
                    $clientAddress = trim(
                        ($address->address_detail ?? '') . ' ' . 
                        ($address->address_street ?? '') . ' ' . 
                        ($address->address_nro ?? '') . ' ' . 
                        ($address->city ?? '')
                    );
                }
            }
        }
        
        // Formatear items
        $items = [];
        if (isset($datosColppy['items']) && is_array($datosColppy['items'])) {
            foreach ($datosColppy['items'] as $item) {
                // Parsear cantidad y precios de forma segura
                $quantity = floatval($item['Cantidad'] ?? $item['cantidad'] ?? 0);
                $unitPrice = floatval($item['ImporteUnitario'] ?? $item['precio'] ?? 0);
                $subtotal = $quantity * $unitPrice;
                
                // Aplicar descuento si existe
                if (isset($item['porcDesc']) && floatval($item['porcDesc']) > 0) {
                    $descuento = ($subtotal * floatval($item['porcDesc'])) / 100;
                    $subtotal -= $descuento;
                }
                
                $items[] = [
                    'id' => null,
                    'budget_id' => null,
                    'product_id' => null,
                    'colppy_id' => (string) ($item['idItem'] ?? ''),  // ← ID de Colppy del producto
                    'codigo' => (string) ($item['codigo'] ?? ''),
                    'descripcion' => (string) ($item['Descripcion'] ?? $item['descripcion'] ?? ''),
                    'tipo_item' => (string) ($item['tipoItem'] ?? 'P'),
                    'unit_type' => 'Unidad',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal
                ];
            }
        }
        
        // Número de factura completo
        $nroFactura = '';
        if (isset($datosColppy['nroFactura1']) && isset($datosColppy['nroFactura2'])) {
            $nroFactura = $datosColppy['nroFactura1'] . '-' . $datosColppy['nroFactura2'];
        } elseif (isset($datosColppy['nroFactura'])) {
            $nroFactura = $datosColppy['nroFactura'];
        }
        
        return [
            'id' => null,
            'id_factura' => (string) ($datosColppy['idFactura'] ?? ''),
            'nro_factura' => (string) $nroFactura,
            'client_id' => $clientId,
            'client_name' => $clientName,
            'client_cuit' => $clientCuit,
            'client_address_id' => $clientAddressId,
            'client_address' => $clientAddress,
            'fecha' => (string) ($datosColppy['fechaFactura'] ?? ''),
            'total' => (float) ($datosColppy['totalFactura'] ?? 0.0),
            'observaciones' => !empty($datosColppy['descripcion']) ? (string) $datosColppy['descripcion'] : null,
            'created_by' => null,
            'created_by_name' => null,
            'created_at' => null,
            'updated_at' => null,
            'items' => $items
        ];
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
     * Ver detalle de un presupuesto específico
     * 
     * GET /api/budgets/{idFactura}
     * 
     * @param string $idFactura
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($idFactura)
    {
        try {
            $colppyService = new ColppyService();
            $resultado = $colppyService->leerFacturaVenta($idFactura);
            
            if (!$resultado['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $resultado['mensaje'] ?? 'Presupuesto no encontrado'
                ], 404);
            }
            
            // leerFacturaVenta devuelve: response->infofactura, response->itemsFactura
            $infofactura = $resultado['response']['infofactura'] ?? [];
            $itemsFactura = $resultado['response']['itemsFactura'] ?? [];
            
            // DEBUG: VER QUÉ LLEGA DE COLPPY
            // Log::info('ApiBudgetController::show - Datos de Colppy', [
            //     'idFactura' => $idFactura,
            //     'cantidad_items_colppy' => count($itemsFactura),
            //     'keys_infofactura' => array_keys($infofactura),
            //     'primer_item' => $itemsFactura[0] ?? 'NO HAY ITEMS'
            // ]);
            
            // Combinar datos para formatear
            $datosCombinados = array_merge($infofactura, ['items' => $itemsFactura]);
            
            // Formatear datos para la app (incluye búsqueda del cliente local)
            $datosFormateados = $this->formatBudgetForApp($datosCombinados);
            
            // Log::info('ApiBudgetController::show - Datos formateados', [
            //     'cantidad_items_formateados' => count($datosFormateados['items'] ?? [])
            // ]);
            
            return response()->json([
                'success' => true,
                'data' => $datosFormateados,
                'message' => 'Presupuesto obtenido correctamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en ApiBudgetController::show', [
                'idFactura' => $idFactura,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener presupuesto: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Crear nuevo presupuesto en Colppy
     * 
     * POST /api/budgets
     * 
     * Body:
     * {
     *   "client_id": 123,  // ID local del cliente
     *   "description": "Descripción del presupuesto",
     *   "items": [
     *     {
     *       "product_id": 456,  // ID local del producto/servicio
     *       "quantity": 2,
     *       "unit_price": 1500.00,
     *       "discount_percent": 0
     *     }
     *   ]
     * }
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Validación
            $validator = Validator::make($request->all(), [
                'client_id' => 'required|integer|exists:clients,id',
                'description' => 'nullable|string|max:500',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer|exists:products,id',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit_price' => 'nullable|numeric|min:0',
                'items.*.discount_percent' => 'nullable|numeric|min:0|max:100'
            ], [
                'client_id.required' => 'El cliente es requerido',
                'client_id.exists' => 'El cliente no existe',
                'items.required' => 'Debe agregar al menos un producto o servicio',
                'items.*.product_id.required' => 'El producto es requerido',
                'items.*.product_id.exists' => 'El producto no existe',
                'items.*.quantity.required' => 'La cantidad es requerida',
                'items.*.quantity.min' => 'La cantidad debe ser mayor a 0'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Obtener cliente
            $client = Client::find($request->client_id);
            
            if (!$client->colppy_id) {  // ✅ CORREGIDO: colppy_id no idcolppy
                return response()->json([
                    'success' => false,
                    'message' => 'El cliente no está sincronizado con Colppy'
                ], 400);
            }
            
            $colppyService = new ColppyService();
            
            // Sistema de reintentos para manejar conflictos de numeración
            $maxIntentos = 3;
            $intentoActual = 0;
            $presupuestoCreado = false;
            $fechaActual = Carbon::now()->format('d-m-Y');
            
            while ($intentoActual < $maxIntentos && !$presupuestoCreado) {
                $intentoActual++;
                
                Log::info('🔄 ApiBudgetController::store - Intento de crear presupuesto', [
                    'intento' => $intentoActual,
                    'maxIntentos' => $maxIntentos,
                    'client_id' => $client->id,
                    'items_count' => count($request->items)
                ]);
                
                // Obtener próximo número de talonario
                $resultadoTalonario = $colppyService->obtenerProximoNumeroTalonario('0002', 'FAV-FE');
                
                if (!$resultadoTalonario['success']) {
                    if ($intentoActual >= $maxIntentos) {
                        return response()->json([
                            'success' => false,
                            'message' => 'No se pudo obtener número de presupuesto: ' . ($resultadoTalonario['mensaje'] ?? 'Error desconocido')
                        ], 500);
                    }
                    sleep(1);
                    continue;
                }
                
                $talonario = '0002';
                $numeroPresupuesto = $resultadoTalonario['proximoNum'];
                
                // Construir items del presupuesto
                $items = [];
                foreach ($request->items as $itemData) {
                    $product = Product::find($itemData['product_id']);
                    
                    if (!$product || !$product->colppy_id) {
                        return response()->json([
                            'success' => false,
                            'message' => "El producto '{$product->descripcion}' no está sincronizado con Colppy"
                        ], 400);
                    }
                    
                    $unitPrice = $itemData['unit_price'] ?? $product->precio_venta ?? 0;
                    $discountPercent = $itemData['discount_percent'] ?? 0;
                    
                    $items[] = [
                        'Descripcion' => $product->descripcion,
                        'unidadMedida' => 'U',
                        'Cantidad' => $itemData['quantity'],
                        'ImporteUnitario' => $unitPrice,
                        'porcDesc' => number_format($discountPercent, 2, '.', ''),
                        'IVA' => '21',
                        'idPlanCuenta' => $product->tipo_item === 'S' ? 'Ingresos Por Servicios' : 'Ventas de mercaderías',
                        'Comentario' => '',
                        'idItem' => $product->colppy_id,
                        'codigo' => $product->codigo,
                        'tipoItem' => $product->tipo_item ?? 'P'
                    ];
                }
                
                // Preparar datos del presupuesto
                $datosPresupuesto = [
                    'descripcion' => $request->description ?? 'Presupuesto generado desde app móvil',
                    'fechaFactura' => $fechaActual,
                    'fechaPago' => $fechaActual,
                    'idCliente' => $client->colppy_id,  // ✅ CORREGIDO: colppy_id no idcolppy
                    'idCondicionPago' => 'Contado',  // Valor válido según Colppy
                    'idEstadoFactura' => 'Borrador',
                    'idEstadoAnterior' => '',
                    'idFactura' => '',
                    'idTipoFactura' => 'X',
                    'idTipoComprobante' => '4',
                    'idMoneda' => '1',
                    'idUsuario' => '',
                    'valorCambio' => '1',
                    'nroFactura1' => $talonario,
                    'nroFactura2' => $numeroPresupuesto,
                    'percepcionIVA' => '0.00',
                    'percepcionIIBB' => '0.00',
                    'orderId' => '',
                    'items' => $items
                ];
                
                // Intentar crear el presupuesto
                $response = $colppyService->crearFacturaVenta($datosPresupuesto);
                
                Log::info('📥 ApiBudgetController::store - Respuesta de Colppy crearFacturaVenta', [
                    'intento' => $intentoActual,
                    'success' => $response['success'] ?? 'NO DEFINIDO',
                    'response_keys' => array_keys($response),
                    'response_completo' => $response['response'] ?? 'NO HAY RESPONSE',
                    'idfactura_minuscula' => $response['response']['idfactura'] ?? 'NO VIENE',
                    'idFactura_mayuscula' => $response['response']['idFactura'] ?? 'NO VIENE'
                ]);
                
                if (isset($response['success']) && $response['success'] === true) {
                    $idFactura = $response['response']['idfactura'] ?? null;
                    
                    if ($idFactura) {
                        $presupuestoCreado = true;
                        
                        Log::info('✅ ApiBudgetController::store - Presupuesto creado exitosamente', [
                            'idFactura' => $idFactura,
                            'nroPresupuesto' => $talonario . '-' . $numeroPresupuesto
                        ]);
                        
                        // Log::info('Presupuesto creado desde app móvil', [
                        //     'idFactura' => $idFactura,
                        //     'nroPresupuesto' => $talonario . '-' . $numeroPresupuesto,
                        //     'client_id' => $client->id,
                        //     'intento' => $intentoActual
                        // ]);
                        
                        // Leer el presupuesto recién creado para devolverlo formateado
                        $presupuestoCompleto = $colppyService->leerFacturaVenta($idFactura);
                        
                        if ($presupuestoCompleto['success']) {
                            $datosFormateados = $this->formatBudgetForApp($presupuestoCompleto['datos'] ?? []);
                            
                            return response()->json([
                                'success' => true,
                                'message' => 'Presupuesto creado correctamente',
                                'data' => $datosFormateados
                            ], 201);
                        }
                        
                        // Si no se pudo leer, devolver datos básicos
                        return response()->json([
                            'success' => true,
                            'message' => 'Presupuesto creado correctamente',
                            'data' => [
                                'idFactura' => $idFactura,
                                'nroPresupuesto' => $talonario . '-' . $numeroPresupuesto,
                                'talonario' => $talonario,
                                'numero' => $numeroPresupuesto
                            ]
                        ], 201);
                    Log::error('❌ ApiBudgetController::store - crearFacturaVenta retornó error', [
                        'intento' => $intentoActual,
                        'mensaje' => $mensajeError,
                        'response_completa' => $response
                    ]);
                    
                    }
                } else {
                    // Error al crear presupuesto
                    $mensajeError = $response['mensaje'] ?? $response['result']['mensaje'] ?? 'Error desconocido';
                    
                    // Detectar error de numeración
                    $esErrorNumeracion = stripos($mensajeError, 'duplicad') !== false 
                                      || stripos($mensajeError, 'existe') !== false
                                      || stripos($mensajeError, 'número') !== false
                                      || stripos($mensajeError, 'ya se encuentra') !== false;
                    
                    if ($esErrorNumeracion && $intentoActual < $maxIntentos) {
                        Log::warning('Conflicto de numeración en creación de presupuesto desde app', [
                            'intento' => $intentoActual,
                            'numeroIntentado' => $talonario . '-' . $numeroPresupuesto
                        ]);
                        sleep(1);
                        continue;
                    } else {
                        Log::error('Error al crear presupuesto desde app', [
                            'intento' => $intentoActual,
                            'mensaje' => $mensajeError
                        ]);
                        
                        return response()->json([
                            'success' => false,
                            'message' => 'Error al crear presupuesto: ' . $mensajeError
                        ], 500);
                    }
                }
            }
            
            // Si llegamos aquí, se agotaron los intentos
            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear el presupuesto después de múltiples intentos'
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('Excepción en ApiBudgetController::store', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear presupuesto: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Actualizar presupuesto existente en Colppy
     * 
     * PUT /api/budgets/{idFactura}
     * 
     * Body: Mismo formato que store()
     * {
     *   "client_id": 123,
     *   "description": "Descripción actualizada",
     *   "items": [...]
     * }
     * 
     * @param Request $request
     * @param string $idFactura ID del presupuesto en Colppy
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $idFactura)
    {
        try {
            // Validación
            $validator = Validator::make($request->all(), [
                'client_id' => 'required|integer|exists:clients,id',
                'description' => 'nullable|string|max:500',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'nullable|integer|exists:products,id',  // Nullable: items existentes no lo tienen
                'items.*.colppy_id' => 'nullable|string',  // ID del producto en Colppy (items existentes)
                'items.*.codigo' => 'required|string',  // Código del producto
                'items.*.descripcion' => 'required|string',  // Descripción del producto
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit_price' => 'nullable|numeric|min:0',
                'items.*.discount_percent' => 'nullable|numeric|min:0|max:100'
            ], [
                'client_id.required' => 'El cliente es requerido',
                'client_id.exists' => 'El cliente no existe',
                'items.required' => 'Debe agregar al menos un producto o servicio',
                'items.*.product_id.exists' => 'El producto no existe',
                'items.*.codigo.required' => 'El código del producto es requerido',
                'items.*.descripcion.required' => 'La descripción del producto es requerida',
                'items.*.quantity.required' => 'La cantidad es requerida',
                'items.*.quantity.min' => 'La cantidad debe ser mayor a 0'
            ]);
            
            if ($validator->fails()) {
                Log::error('❌ ApiBudgetController::update - VALIDACIÓN FALLÓ', [
                    'errors' => $validator->errors()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Obtener cliente
            $client = Client::find($request->client_id);
            
            if (!$client->colppy_id) {  // ✅ CORREGIDO: colppy_id no idcolppy
                Log::error('❌ ApiBudgetController::update - CLIENTE SIN COLPPY_ID');
                return response()->json([
                    'success' => false,
                    'message' => 'El cliente no está sincronizado con Colppy'
                ], 400);
            }
            
            $colppyService = new ColppyService();
            
            // Leer el presupuesto actual para obtener datos
            $presupuestoActual = $colppyService->leerFacturaVenta($idFactura);
            
            if (!$presupuestoActual['success']) {
                Log::error('❌ ApiBudgetController::update - NO SE PUDO LEER PRESUPUESTO', [
                    'mensaje' => $presupuestoActual['mensaje'] ?? 'Error desconocido'
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo leer el presupuesto: ' . ($presupuestoActual['mensaje'] ?? 'Error desconocido')
                ], 404);
            }
            
            // Los datos están en response->infofactura
            $datosActuales = $presupuestoActual['response']['infofactura'] ?? [];
            
            // Separar número de factura (puede venir como nroFactura1/nroFactura2 o como nroFactura combinado)
            $nroFactura1 = '';
            $nroFactura2 = '';
            
            if (!empty($datosActuales['nroFactura1']) && !empty($datosActuales['nroFactura2'])) {
                // Ya vienen separados
                $nroFactura1 = $datosActuales['nroFactura1'];
                $nroFactura2 = $datosActuales['nroFactura2'];
            } elseif (!empty($datosActuales['nroFactura'])) {
                // Viene combinado, separar por "-"
                $partes = explode('-', $datosActuales['nroFactura']);
                if (count($partes) === 2) {
                    $nroFactura1 = trim($partes[0]);
                    $nroFactura2 = trim($partes[1]);
                }
            }
            
            // Construir items del presupuesto
            $items = [];
            foreach ($request->items as $itemData) {
                $product = null;
                $unitPrice = $itemData['unit_price'] ?? 0;
                $discountPercent = $itemData['discount_percent'] ?? 0;
                $tipoItem = $itemData['tipo_item'] ?? 'P';
                $codigo = $itemData['codigo'];
                $descripcion = $itemData['descripcion'];
                $idItem = $itemData['colppy_id'] ?? '';  // ← Usa colppy_id si viene del item existente
                
                // Caso 1: Item NUEVO agregado (tiene product_id)
                if (isset($itemData['product_id']) && $itemData['product_id'] !== null) {
                    $product = Product::find($itemData['product_id']);
                    
                    if (!$product) {
                        return response()->json([
                            'success' => false,
                            'message' => "Producto con ID {$itemData['product_id']} no encontrado"
                        ], 400);
                    }
                    
                    if (!$product->colppy_id) {
                        return response()->json([
                            'success' => false,
                            'message' => "El producto '{$product->descripcion}' no está sincronizado con Colppy"
                        ], 400);
                    }
                    
                    // Usar datos del producto de BD
                    $codigo = $product->codigo;
                    $descripcion = $product->descripcion;
                    $idItem = $product->colppy_id;
                    $tipoItem = $product->tipo_item ?? 'P';
                    $unitPrice = $itemData['unit_price'] ?? $product->precio_venta ?? 0;
                }
                // Caso 2: Item EXISTENTE del presupuesto (sin product_id pero con colppy_id)
                // Ya tenemos $idItem del itemData['colppy_id']
                
                // Caso 3: Item EXISTENTE sin colppy_id (versión vieja) - Buscar por código
                if (empty($idItem) && !empty($codigo)) {
                    $product = Product::where('codigo', $codigo)->first();
                    if ($product && $product->colppy_id) {
                        $idItem = $product->colppy_id;
                    }
                }
                
                $items[] = [
                    'Descripcion' => $descripcion,
                    'unidadMedida' => 'U',
                    'Cantidad' => $itemData['quantity'],
                    'ImporteUnitario' => $unitPrice,
                    'porcDesc' => number_format($discountPercent, 2, '.', ''),
                    'IVA' => '21',
                    'idPlanCuenta' => $tipoItem === 'S' ? 'Ingresos Por Servicios' : 'Ventas de mercaderías',
                    'Comentario' => '',
                    'idItem' => $idItem,
                    'codigo' => $codigo,
                    'tipoItem' => $tipoItem
                ];
            }
            
            // Preparar datos para actualización
            $datosActualizacion = [
                'idFactura' => $idFactura,
                'descripcion' => $request->description ?? 'Presupuesto actualizado desde app móvil',
                'fechaFactura' => $datosActuales['fechaFactura'] ?? Carbon::now()->format('d-m-Y'),
                'fechaPago' => $datosActuales['fechaPago'] ?? Carbon::now()->format('d-m-Y'),
                'idCliente' => $client->colppy_id,  // ✅ CORREGIDO: colppy_id no idcolppy
                'idCondicionPago' => $datosActuales['idCondicionPago'] ?? 'a 7 Dias',
                'idEstadoFactura' => $datosActuales['idEstadoFactura'] ?? 'Borrador',
                'idEstadoAnterior' => $datosActuales['idEstadoAnterior'] ?? '',
                'idTipoFactura' => 'X',
                'idTipoComprobante' => '4',
                'idMoneda' => $datosActuales['idMoneda'] ?? '1',
                'valorCambio' => $datosActuales['valorCambio'] ?? '1',
                'nroFactura1' => $nroFactura1,
                'nroFactura2' => $nroFactura2,
                'percepcionIVA' => '0.00',
                'percepcionIIBB' => '0.00',
                'orderId' => '',
                'items' => $items
            ];
            
            // Actualizar el presupuesto
            $response = $colppyService->editarFacturaVenta($datosActualizacion);
            
            if (isset($response['success']) && $response['success'] === true) {
                // Leer el presupuesto actualizado para retornar datos completos
                $resultado = $colppyService->leerFacturaVenta($idFactura);
                
                if ($resultado['success']) {
                    $infofactura = $resultado['response']['infofactura'] ?? [];
                    $itemsFactura = $resultado['response']['itemsFactura'] ?? [];
                    
                    $datosCombinados = array_merge($infofactura, ['items' => $itemsFactura]);
                    $datosFormateados = $this->formatBudgetForApp($datosCombinados);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Presupuesto actualizado correctamente',
                        'data' => $datosFormateados
                    ]);
                }
                
                // Fallback si no se puede leer: retornar solo idFactura
                return response()->json([
                    'success' => true,
                    'message' => 'Presupuesto actualizado correctamente',
                    'data' => [
                        'id_factura' => $idFactura,
                        'nro_factura' => $datosActuales['nroFactura'] ?? ''
                    ]
                ]);
            }
            
            // Error al actualizar
            $mensajeError = $response['mensaje'] ?? $response['result']['mensaje'] ?? 'Error desconocido';
            
            Log::error('❌ ApiBudgetController::update - COLPPY RETORNÓ ERROR', [
                'idFactura' => $idFactura,
                'mensaje' => $mensajeError,
                'response_completa' => $response
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar presupuesto: ' . $mensajeError
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('💥 ApiBudgetController::update - EXCEPCIÓN CAPTURADA', [
                'idFactura' => $idFactura,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar presupuesto: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Listar productos Y servicios disponibles
     * CENTRALIZADO: Usa Product::searchProductsAndServices()
     * 
     * GET /api/products-services
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductsAndServices(Request $request)
    {
        try {
            $search = $request->input('search', '');
            $limit = $request->input('limit', 50);
            $tipo = $request->input('tipo', null); // 'P' = Productos, 'S' = Servicios, null = Ambos
            
            // USAR MÉTODO CENTRALIZADO DEL MODELO
            $items = Product::searchProductsAndServices($search, $tipo, $limit);
            
            // Mapear precio_venta a precio para Flutter
            $itemsFormateados = $items->map(function($item) {
                return [
                    'id' => $item->id,
                    'codigo' => $item->codigo,
                    'descripcion' => $item->descripcion,
                    'tipo_item' => $item->tipo_item,
                    'is_from_colppy' => $item->is_from_colppy,
                    'precio' => $item->precio_venta ?? 0.0  // Mapear precio_venta a precio
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $itemsFormateados,
                'count' => $itemsFormateados->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en ApiBudgetController::getProductsAndServices', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos/servicios: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Crear cliente en Colppy y sincronizar localmente
     * 
     * POST /api/clients
     * 
     * Body (Opción 1 - Con CUIT para consultar AFIP):
     * {
     *   "cuit": "20327342585"
     * }
     * 
     * Body (Opción 2 - Datos manuales):
     * {
     *   "first_name": "Juan",
     *   "last_name": "Pérez",
     *   "email": "juan@example.com",
     *   "phone": "123456789",
     *   "address": "Calle 123",
     *   "city": "Buenos Aires",
     *   "state": "CABA",
     *   "postal_code": "1234",
     *   "cuit": "20327342585" (opcional)
     * }
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createClient(Request $request)
    {
        try {
            // ========================================
            // VALIDACIÓN CRÍTICA: Verificar si el cliente YA EXISTE
            // ========================================
            if ($request->filled('cuit')) {
                $clienteExistente = Client::where('num_doc', $request->cuit)
                    ->where('is_active', 1)
                    ->whereNull('deleted_at')
                    ->first();
                
                if ($clienteExistente) {
                    $nombreCompleto = trim($clienteExistente->first_name . ' ' . ($clienteExistente->last_name ?? ''));
                    
                    Log::warning('ApiBudgetController::createClient - Cliente ya existe', [
                        'cuit' => $request->cuit,
                        'client_id' => $clienteExistente->id,
                        'nombre' => $nombreCompleto
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => "El CUIT {$request->cuit} ya está registrado: {$nombreCompleto}",
                        'error_type' => 'CLIENT_ALREADY_EXISTS',
                        'existing_client' => [
                            'id' => $clienteExistente->id,
                            'name' => $nombreCompleto,
                            'cuit' => $clienteExistente->num_doc, // ← Frontend espera 'cuit'
                            'num_doc' => $clienteExistente->num_doc,
                            'email' => $clienteExistente->email,
                            'phone' => $clienteExistente->phone1
                        ]
                    ], 409); // 409 Conflict
                }
            }
            
            $colppyService = new ColppyService();
            $datosAfip = null;
            $datosCliente = [];

            // Si se proporciona CUIT, intentar obtener datos de AFIP
            if ($request->filled('cuit')) {
                // Log::info('ApiBudgetController::createClient - Consultando AFIP', [
                //     'cuit' => $request->cuit
                // ]);

                $resultadoAfip = $colppyService->obtenerDatosTerceroDeAfip($request->cuit);

                if (!$resultadoAfip['success']) {
                    // Si AFIP falla completamente, retornar error específico
                    Log::error('ApiBudgetController::createClient - Error en consulta AFIP', [
                        'cuit' => $request->cuit,
                        'mensaje' => $resultadoAfip['mensaje'] ?? 'Error desconocido'
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'No se pudieron obtener datos de AFIP: ' . ($resultadoAfip['mensaje'] ?? 'Servicio no disponible'),
                        'error_type' => 'AFIP_ERROR'
                    ], 400);
                }

                $datosAfip = $resultadoAfip['data'];
                
                // Log::info('ApiBudgetController::createClient - Datos AFIP obtenidos', [
                //     'nombre' => $datosAfip['nombre'] ?? 'N/A'
                // ]);

                // Preparar datos del cliente desde AFIP
                $datosCliente['razon_social'] = $datosAfip['nombre'] ?? '';
                $datosCliente['nombre_fantasia'] = $datosAfip['nombre'] ?? '';
                $datosCliente['num_doc'] = preg_replace('/[^0-9]/', '', $request->cuit); // Remover guiones
                $datosCliente['type_doc'] = 3; // CUIT
                $datosCliente['pais'] = $datosAfip['pais'] ?? 'Argentina';

                // Condición de IVA
                if (isset($datosAfip['idCondicionIva'])) {
                    $datosCliente['id_condicion_iva'] = $datosAfip['idCondicionIva'];
                }

                // Domicilio fiscal
                if (isset($datosAfip['domicilioFiscal'])) {
                    $domicilio = $datosAfip['domicilioFiscal'];
                    $datosCliente['direccion'] = $domicilio['direccion'] ?? '';
                    $datosCliente['ciudad'] = $domicilio['localidad'] ?? '';
                    $datosCliente['provincia'] = $domicilio['provincia'] ?? '';
                    $datosCliente['codigo_postal'] = $domicilio['codPostal'] ?? '';
                }
            } else {
                // No hay CUIT, validar datos manuales
                $validator = Validator::make($request->all(), [
                    'first_name' => 'required|string|max:100',
                    'last_name' => 'nullable|string|max:100',
                    'email' => 'nullable|email|max:100',
                    'phone' => 'nullable|string|max:50',
                    'address' => 'nullable|string|max:255',
                    'city' => 'nullable|string|max:100',
                    'state' => 'nullable|string|max:100',
                    'postal_code' => 'nullable|string|max:20'
                ], [
                    'first_name.required' => 'El nombre o razón social es requerido',
                    'email.email' => 'El email no es válido'
                ]);
                
                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Errores de validación',
                        'errors' => $validator->errors()
                    ], 422);
                }

                // Construir nombre completo para razón social
                $nombreCompleto = trim(($request->first_name ?? '') . ' ' . ($request->last_name ?? ''));
                
                $datosCliente = [
                    'razon_social' => $nombreCompleto,
                    'nombre_fantasia' => $nombreCompleto,
                    'email' => $request->email,
                    'telefono' => $request->phone,
                    'direccion' => $request->address,
                    'ciudad' => $request->city,
                    'provincia' => $request->state,
                    'codigo_postal' => $request->postal_code,
                    'pais' => 'Argentina'
                ];
            }

            // CREAR CLIENTE EN COLPPY
            // Log::info('ApiBudgetController::createClient - Creando cliente en Colppy', [
            //     'razon_social' => $datosCliente['razon_social'] ?? 'N/A'
            // ]);

            $resultadoColppy = $colppyService->crearCliente($datosCliente);

            if (!$resultadoColppy['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear cliente en Colppy: ' . ($resultadoColppy['mensaje'] ?? 'Error desconocido'),
                    'datos_afip' => $datosAfip
                ], 400);
            }

            $idColppy = $resultadoColppy['idCliente'];

            // Log::info('ApiBudgetController::createClient - Cliente creado en Colppy', [
            //     'idColppy' => $idColppy
            // ]);

            // CREAR CLIENTE LOCALMENTE
            // Si vino de AFIP, usar razón social de AFIP como first_name
            $firstName = $request->first_name ?? $datosCliente['razon_social'] ?? '';
            $lastName = $request->last_name ?? '';
            
            // Preparar num_doc y type_doc
            $numDoc = 0;
            $typeDoc = 3; // Default CUIT
            if ($request->cuit) {
                $numDoc = preg_replace('/[^0-9]/', '', $request->cuit); // Remover guiones
                $typeDoc = 3; // CUIT
            } elseif (isset($datosCliente['num_doc'])) {
                $numDoc = $datosCliente['num_doc'];
                $typeDoc = $datosCliente['type_doc'] ?? 3;
            }
            
            $clienteLocal = Client::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $request->email ?? $datosCliente['email'] ?? null,
                'phone1' => $request->phone ?? $datosCliente['telefono'] ?? null,
                'address_street' => $request->address ?? $datosCliente['direccion'] ?? null,
                'city' => $request->city ?? $datosCliente['ciudad'] ?? null,
                'state' => $request->state ?? $datosCliente['provincia'] ?? null,
                'cp' => $request->postal_code ?? $datosCliente['codigo_postal'] ?? null,
                'num_doc' => $numDoc,
                'type_doc' => $typeDoc,
                'colppy_id' => $idColppy,
                'is_from_colppy' => 1
            ]);

            // Log::info('ApiBudgetController::createClient - Cliente guardado localmente', [
            //     'client_id' => $clienteLocal->id,
            //     'idColppy' => $idColppy
            // ]);

            // Construir nombre completo para respuesta
            $fullName = trim($firstName . ' ' . $lastName);

            return response()->json([
                'success' => true,
                'message' => 'Cliente creado correctamente',
                'data' => [
                    'id' => $clienteLocal->id,
                    'name' => $fullName,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $clienteLocal->email,
                    'phone' => $clienteLocal->phone1,
                    'cuit' => $clienteLocal->num_doc, // ← Frontend espera 'cuit'
                    'num_doc' => $clienteLocal->num_doc, // ← Mantener compatibilidad
                    'idcolppy' => $idColppy
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en ApiBudgetController::createClient', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor al crear cliente. Por favor, intenta nuevamente.',
                'error_detail' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Obtener tareas disponibles para asociar a un presupuesto
     * Tareas sin presupuesto asociado y no cerradas
     * 
     * GET /api/budgets/available-jobs
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableJobs(Request $request)
    {
        try {
            $search = $request->input('search', '');
            $limit = $request->input('limit', 50);
            
            $query = Job::with(['client:id,first_name,last_name,email', 'technicians:id,name'])
                ->whereHas('client', function($q) {
                    $q->where('is_active', 1);
                })
                ->whereNull('colppy_budget_id')  // Sin presupuesto asociado
                ->whereNull('closed_datetime')   // NO cerradas
                ->whereNull('deleted_at');
            
            // Búsqueda por descripción o ID
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('id', 'like', '%' . $search . '%')
                      ->orWhere('job_description', 'like', '%' . $search . '%');
                });
            }
            
            $jobs = $query->select(
                    'id',
                    'client_id',
                    'job_description',
                    'visit_datetime',
                    'arrival_datetime',
                    'created_at'
                )
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
            
            // Formatear datos para el frontend
            $formattedJobs = $jobs->map(function($job) {
                return [
                    'id' => $job->id,
                    'job_description' => $job->job_description ?? 'Sin descripción',
                    'client_name' => $job->client ? trim($job->client->first_name . ' ' . $job->client->last_name) : 'Cliente desconocido',
                    'client_email' => $job->client ? $job->client->email : null,
                    'technician_names' => $job->technicians->pluck('name')->join(', '),
                    'visit_datetime' => $job->visit_datetime,
                    'arrival_datetime' => $job->arrival_datetime,
                    'status' => $job->arrival_datetime ? 'En Lugar' : 'Pendiente',
                    'created_at' => $job->created_at->format('Y-m-d H:i:s')
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $formattedJobs,
                'count' => $formattedJobs->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en ApiBudgetController::getAvailableJobs', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tareas disponibles: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Asociar tareas a un presupuesto
     * Actualiza colppy_budget_id y colppy_budget_number en las tareas seleccionadas
     * 
     * POST /api/budgets/{idFactura}/associate-jobs
     * 
     * Body: {
     *   "job_ids": [1, 2, 3],
     *   "budget_number": "0002-00000046"
     * }
     * 
     * @param Request $request
     * @param string $idFactura
     * @return \Illuminate\Http\JsonResponse
     */
    public function associateJobsToBudget(Request $request, $idFactura)
    {
        try {
            $validator = Validator::make($request->all(), [
                'job_ids' => 'required|array|min:1',
                'job_ids.*' => 'required|integer|exists:jobs,id',
                'budget_number' => 'required|string'
            ], [
                'job_ids.required' => 'Debe seleccionar al menos una tarea',
                'job_ids.*.exists' => 'Una o más tareas no existen',
                'budget_number.required' => 'El número de presupuesto es requerido'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $jobIds = $request->job_ids;
            $budgetNumber = $request->budget_number;
            
            // Verificar que las tareas estén disponibles (sin presupuesto y no cerradas)
            $unavailableJobs = Job::whereIn('id', $jobIds)
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
            $updated = Job::whereIn('id', $jobIds)
                ->whereNull('deleted_at')
                ->update([
                    'colppy_budget_id' => $idFactura,
                    'colppy_budget_number' => $budgetNumber,
                    'updated_at' => now()
                ]);
            
            // Log::info('Tareas asociadas a presupuesto desde app', [
            //     'idFactura' => $idFactura,
            //     'budget_number' => $budgetNumber,
            //     'job_ids' => $jobIds,
            //     'updated_count' => $updated
            // ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Tareas asociadas correctamente al presupuesto',
                'data' => [
                    'jobs_updated' => $updated,
                    'budget_id' => $idFactura,
                    'budget_number' => $budgetNumber
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en ApiBudgetController::associateJobsToBudget', [
                'idFactura' => $idFactura,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al asociar tareas: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Listar tareas asociadas a un presupuesto
     * 
     * GET /api/budgets/{idFactura}/jobs
     * 
     * @param string $idFactura
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAssociatedJobs($idFactura)
    {
        try {
            $jobs = Job::with(['client:id,first_name,last_name,email', 'technicians:id,name'])
                ->whereHas('client', function($query) {
                    $query->where('is_active', 1);
                })
                ->where('colppy_budget_id', $idFactura)
                ->whereNull('deleted_at')
                ->select(
                    'id',
                    'client_id',
                    'job_description',
                    'visit_datetime',
                    'arrival_datetime',
                    'closed_datetime',
                    'archived',
                    'colppy_budget_number',
                    'created_at'
                )
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Formatear datos
            $formattedJobs = $jobs->map(function($job) {
                $status = 'Pendiente';
                if ($job->archived == 1) {
                    $status = 'Archivada';
                } elseif ($job->closed_datetime) {
                    $status = 'Cerrada';
                } elseif ($job->arrival_datetime) {
                    $status = 'En Lugar';
                }
                
                return [
                    'id' => $job->id,
                    'job_description' => $job->job_description ?? 'Sin descripción',
                    'client_name' => $job->client ? trim($job->client->first_name . ' ' . $job->client->last_name) : 'Cliente desconocido',
                    'client_email' => $job->client ? $job->client->email : null,
                    'technician_names' => $job->technicians->pluck('name')->join(', '),
                    'visit_datetime' => $job->visit_datetime,
                    'arrival_datetime' => $job->arrival_datetime,
                    'closed_datetime' => $job->closed_datetime,
                    'archived' => $job->archived == 1,
                    'status' => $status,
                    'budget_number' => $job->colppy_budget_number,
                    'created_at' => $job->created_at->format('Y-m-d H:i:s')
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $formattedJobs,
                'count' => $formattedJobs->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en ApiBudgetController::getAssociatedJobs', [
                'idFactura' => $idFactura,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tareas asociadas: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Crear nueva tarea a partir de un presupuesto
     * 
     * POST /api/budgets/{idFactura}/create-job
     * 
     * Body: {
     *   "job_description": "Instalación según presupuesto",
     *   "visit_datetime": "2026-04-10 14:00:00",
     *   "technician_ids": [1, 2]
     * }
     * 
     * @param Request $request
     * @param string $idFactura
     * @return \Illuminate\Http\JsonResponse
     */
    public function createJobFromBudget(Request $request, $idFactura)
    {
        try {
            // Validación
            $validator = Validator::make($request->all(), [
                'job_description' => 'required|string|max:500',
                'visit_datetime' => 'required|date|after_or_equal:now',
                'technician_ids' => 'nullable|array',  // ✅ Ahora es opcional
                'technician_ids.*' => 'nullable|integer|exists:users,id'
            ], [
                'job_description.required' => 'La descripción es requerida',
                'visit_datetime.required' => 'La fecha de visita es requerida',
                'visit_datetime.after_or_equal' => 'La fecha debe ser igual o posterior a hoy',
                'technician_ids.*.exists' => 'Uno o más técnicos no existen'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Obtener datos del presupuesto
            $colppyService = new ColppyService();
            $resultado = $colppyService->leerFacturaVenta($idFactura);
            
            if (!$resultado['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo leer el presupuesto: ' . ($resultado['mensaje'] ?? 'Error desconocido')
                ], 404);
            }
            
            // IMPORTANTE: leerFacturaVenta devuelve response->infofactura, NO datos
            $datosPresupuesto = $resultado['response']['infofactura'] ?? [];
            
            if (empty($datosPresupuesto)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudieron obtener los datos del presupuesto'
                ], 400);
            }
            
            // Obtener o crear cliente basado en idCliente de Colppy
            $idClienteColppy = $datosPresupuesto['idCliente'] ?? null;
            $client = null;
            
            if (!$idClienteColppy) {
                return response()->json([
                    'success' => false,
                    'message' => 'El presupuesto no tiene un cliente asociado'
                ], 400);
            }
            
            $client = Client::where('colppy_id', $idClienteColppy)
                ->where('is_active', 1)
                ->first();
            
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el cliente del presupuesto en el sistema local. El cliente debe estar sincronizado y activo.'
                ], 400);
            }
            
            // Obtener número de presupuesto
            $nroPresupuesto = '';
            if (isset($datosPresupuesto['nroFactura1']) && isset($datosPresupuesto['nroFactura2'])) {
                $nroPresupuesto = $datosPresupuesto['nroFactura1'] . '-' . $datosPresupuesto['nroFactura2'];
            } elseif (isset($datosPresupuesto['nroFactura'])) {
                $nroPresupuesto = $datosPresupuesto['nroFactura'];
            }
            
            // Obtener domicilio principal del cliente
            $clientAddress = DB::table('clients_address')
                ->where('client_id', $client->id)
                ->whereNull('deleted_at')
                ->orderBy('id', 'asc')
                ->first();
            
            if (!$clientAddress) {
                return response()->json([
                    'success' => false,
                    'message' => 'El cliente no tiene un domicilio registrado'
                ], 400);
            }
            
            // Crear la tarea
            $job = new Job();
            $job->client_id = $client->id;
            $job->client_addres_id = $clientAddress->id;
            $job->job_description = $request->job_description;
            $job->visit_datetime = $request->visit_datetime;
            $job->colppy_budget_id = $idFactura;
            $job->colppy_budget_number = $nroPresupuesto;
            $job->save();
            
            // Asignar técnicos (opcional)
            $technicianIds = $request->technician_ids ?? [];
            $job->technicians()->sync($technicianIds);
            
            // Log::info('Tarea creada desde presupuesto', [
            //     'job_id' => $job->id,
            //     'budget_id' => $idFactura,
            //     'budget_number' => $nroPresupuesto
            // ]);
            
            // Cargar relaciones para respuesta
            $job->load(['client:id,first_name,last_name,email', 'technicians:id,name']);
            
            return response()->json([
                'success' => true,
                'message' => 'Tarea creada exitosamente',
                'data' => [
                    'id' => $job->id,
                    'job_description' => $job->job_description,
                    'client_name' => trim($job->client->first_name . ' ' . $job->client->last_name),
                    'client_email' => $job->client->email,
                    'technician_names' => $job->technicians->pluck('name')->join(', '),
                    'visit_datetime' => $job->visit_datetime,
                    'budget_number' => $job->colppy_budget_number,
                    'created_at' => $job->created_at->format('Y-m-d H:i:s')
                ]
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Error en ApiBudgetController::createJobFromBudget', [
                'idFactura' => $idFactura,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar PDF del presupuesto generado localmente
     * GET /api/budgets/{idFactura}/pdf
     * 
     * @param string $idFactura ID del presupuesto en Colppy
     * @return Response
     */
    public function downloadPdf($idFactura)
    {
        try {
            // Log::info('ApiBudgetController::downloadPdf - Generando PDF desde Colppy', [
            //     'id_factura' => $idFactura
            // ]);

            // Obtener datos completos del presupuesto desde Colppy
            $colppyService = new ColppyService();
            $result = $colppyService->leerFacturaVenta($idFactura);

            if (!$result['success']) {
                Log::error('Error al obtener presupuesto de Colppy', [
                    'id_factura' => $idFactura,
                    'error' => $result['mensaje'] ?? 'Error desconocido'
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener el presupuesto desde Colppy: ' . ($result['mensaje'] ?? 'Error desconocido')
                ], 500);
            }

            // Extraer datos del presupuesto
            $infofactura = $result['response']['infofactura'] ?? [];
            $itemsFactura = $result['response']['itemsFactura'] ?? [];

            if (empty($infofactura)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudieron obtener los datos del presupuesto'
                ], 500);
            }

            // Intentar obtener datos del cliente local por colppy_id si existe
            $client = null;
            $idClienteColppy = $infofactura['idCliente'] ?? null;
            if ($idClienteColppy) {
                $client = DB::table('clients')->where('colppy_id', $idClienteColppy)->first();
            }

            // Preparar datos usando método auxiliar
            $data = $this->prepararDatosPdf(null, $infofactura, $itemsFactura, $client);

            // Generar PDF con DomPDF
            $pdf = Pdf::loadView('budget.pdf', $data);
            $pdf->setPaper('A4', 'portrait');
            
            // Generar nombre del archivo
            $filename = 'presupuesto_' . $data['nroFactura'] . '.pdf';

            // Log::info('PDF generado exitosamente', [
            //     'id_factura' => $idFactura,
            //     'filename' => $filename
            // ]);

            // Devolver el PDF
            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('Error en ApiBudgetController::downloadPdf', [
                'id_factura' => $idFactura,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ver PDF del presupuesto en el navegador (inline)
     * GET /api/budgets/{idFactura}/pdf/view
     * 
     * @param string $idFactura ID del presupuesto en Colppy
     * @return Response
     */
    public function viewPdf($idFactura)
    {
        try {
            // Obtener datos completos del presupuesto desde Colppy
            $colppyService = new ColppyService();
            $result = $colppyService->leerFacturaVenta($idFactura);

            if (!$result['success']) {
                return response('<h1>Error: No se pudo obtener el presupuesto desde Colppy</h1>', 500);
            }

            // Extraer datos del presupuesto
            $infofactura = $result['response']['infofactura'] ?? [];
            $itemsFactura = $result['response']['itemsFactura'] ?? [];

            if (empty($infofactura)) {
                return response('<h1>Error: No se pudieron obtener los datos del presupuesto</h1>', 500);
            }

            // Intentar obtener datos del cliente local por colppy_id si existe
            $client = null;
            $idClienteColppy = $infofactura['idCliente'] ?? null;
            if ($idClienteColppy) {
                $client = DB::table('clients')->where('colppy_id', $idClienteColppy)->first();
            }

            // Preparar datos (mismo código que downloadPdf)
            $data = $this->prepararDatosPdf(null, $infofactura, $itemsFactura, $client);

            // Generar PDF
            $pdf = Pdf::loadView('budget.pdf', $data);
            $pdf->setPaper('A4', 'portrait');

            // Devolver PDF para visualizar en navegador (inline)
            return $pdf->stream();

        } catch (\Exception $e) {
            return response('<h1>Error al generar PDF: ' . $e->getMessage() . '</h1>', 500);
        }
    }

    /**
     * Ver HTML del presupuesto directamente (sin convertir a PDF)
     * Útil para desarrollo y ajustes de diseño
     * GET /api/budgets/{idFactura}/pdf/preview
     * 
     * @param string $idFactura ID del presupuesto en Colppy
     * @return Response
     */
    public function previewHtml($idFactura)
    {
        try {
            // Obtener datos completos del presupuesto desde Colppy
            $colppyService = new ColppyService();
            $result = $colppyService->leerFacturaVenta($idFactura);

            if (!$result['success']) {
                return response('<h1>Error: No se pudo obtener el presupuesto desde Colppy</h1>', 500);
            }

            // Extraer datos del presupuesto
            $infofactura = $result['response']['infofactura'] ?? [];
            $itemsFactura = $result['response']['itemsFactura'] ?? [];

            if (empty($infofactura)) {
                return response('<h1>Error: No se pudieron obtener los datos del presupuesto</h1>', 500);
            }

            // Intentar obtener datos del cliente local por colppy_id si existe
            $client = null;
            $idClienteColppy = $infofactura['idCliente'] ?? null;
            if ($idClienteColppy) {
                $client = DB::table('clients')->where('colppy_id', $idClienteColppy)->first();
            }

            // Preparar datos
            $data = $this->prepararDatosPdf(null, $infofactura, $itemsFactura, $client);

            // Devolver la vista HTML directamente (sin convertir a PDF)
            return view('budget.pdf', $data);

        } catch (\Exception $e) {
            return response('<h1>Error: ' . $e->getMessage() . '</h1>', 500);
        }
    }

    /**
     * Método auxiliar para preparar los datos del PDF
     * Reutilizable por downloadPdf, viewPdf y previewHtml
     * 
     * @param object|null $budget (Opcional - puede ser null si se obtiene directamente desde Colppy)
     * @param array $infofactura
     * @param array $itemsFactura
     * @param object|null $client
     * @return array
     */
    private function prepararDatosPdf($budget, $infofactura, $itemsFactura, $client)
    {
        $nroFactura1 = $infofactura['nroFactura1'] ?? '0001';
        $nroFactura2 = $infofactura['nroFactura2'] ?? '00000000';
        
        // Obtener configuración de empresa
        $configs = DB::table('configs')
            ->whereIn('name', ['nombre_empresa_api', 'razon_social_empresa', 'domicilio_empresa', 'cuit_empresa', 'iibb_empresa', 'fecha_inicio_actividades'])
            ->pluck('value', 'name');
        
        // Convertir logo a base64
        $logoBase64 = '';
        
        // Intentar primero en public de Laravel
        $logoPath = public_path('assets/media/Logo.png');
        
        // Si no existe, intentar en public raíz del proyecto
        if (!file_exists($logoPath)) {
            $logoPath = base_path('../public/assets/media/Logo.png');
        }
        
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            if ($logoData !== false) {
                $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
                
                // Log::info('Logo cargado correctamente', [
                //     'path' => $logoPath,
                //     'size' => strlen($logoData),
                //     'base64_length' => strlen($logoBase64)
                // ]);
            } else {
                Log::warning('Error al leer archivo de logo', ['path' => $logoPath]);
            }
        } else {
            Log::warning('Logo no encontrado', [
                'attempted_paths' => [
                    public_path('assets/media/Logo.png'),
                    base_path('../public/assets/media/Logo.png')
                ]
            ]);
        }
        
        // Preparar items y calcular totales
        $items = [];
        $netoGravado = 0;
        $netoNoGravado = 0;
        $iva21 = 0;
        $iva105 = 0;
        $iva27 = 0;
        
        foreach ($itemsFactura as $item) {
            $cantidad = floatval($item['Cantidad'] ?? 0);
            $precioUnit = floatval($item['ImporteUnitario'] ?? 0);
            $descuento = floatval($item['porcDesc'] ?? 0);
            $ivaAlicuota = floatval($item['IVA'] ?? 0);
            
            $subtotal = $cantidad * $precioUnit;
            if ($descuento > 0) {
                $subtotal = $subtotal * (1 - ($descuento / 100));
            }
            
            $items[] = [
                'descripcion' => $item['Descripcion'] ?? '',
                'cantidad' => $cantidad,
                'unidadMedida' => $item['unidadMedida'] ?? 'U',
                'precioUnitario' => $precioUnit,
                'descuento' => $descuento,
                'iva' => $ivaAlicuota,
                'subtotal' => $subtotal
            ];
            
            if ($ivaAlicuota > 0) {
                $netoGravado += $subtotal;
                $ivaImporte = $subtotal * ($ivaAlicuota / 100);
                
                if ($ivaAlicuota == 21) {
                    $iva21 += $ivaImporte;
                } elseif ($ivaAlicuota == 10.5) {
                    $iva105 += $ivaImporte;
                } elseif ($ivaAlicuota == 27) {
                    $iva27 += $ivaImporte;
                }
            } else {
                $netoNoGravado += $subtotal;
            }
        }
        
        $totalIVA = $iva21 + $iva105 + $iva27;
        $totalFactura = $netoGravado + $netoNoGravado + $totalIVA;
        
        // Preparar nombre del cliente
        $clienteNombre = 'Consumidor Final';
        if ($client) {
            if (!empty($client->first_name) || !empty($client->last_name)) {
                $clienteNombre = trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? ''));
            } elseif (!empty($client->name)) {
                $clienteNombre = $client->name;
            }
        }
        
        // Obtener dirección del cliente
        $clienteDomicilio = ' - ';
        if ($client && !empty($client->address_street)) {
            $clienteDomicilio = $client->address_street;
            if (!empty($client->address_nro)) {
                $clienteDomicilio .= ' ' . $client->address_nro;
            }
            if (!empty($client->city)) {
                $clienteDomicilio .= ', ' . $client->city;
            }
        }
        
        // Mapear condición IVA
        $condicionesIVA = [
            1 => 'IVA Responsable Inscripto',
            2 => 'IVA Responsable no Inscripto',
            3 => 'Consumidor Final',
            4 => 'IVA Exento',
            5 => 'Monotributista',
            6 => 'IVA No Alcanzado',
        ];
        
        $clienteCondicionIVA = 'Consumidor Final';
        if ($client && isset($client->condicion_iva)) {
            $clienteCondicionIVA = $condicionesIVA[$client->condicion_iva] ?? 'Consumidor Final';
        }
        
        // Calcular fecha de vencimiento
        $condicionPago = $infofactura['idCondicionPago'] ?? '7 Días';
        $fechaFacturaStr = $infofactura['fechaFactura'] ?? date('d-m-Y');
        
        // Extraer número de días de la condición (ej: "a 7 Dias" -> 7, "30 días" -> 30)
        preg_match('/(\d+)/', $condicionPago, $matches);
        $diasVencimiento = isset($matches[1]) ? (int)$matches[1] : 7;
        
        // Parsear fecha y sumar días
        try {
            $fechaFacturaObj = \DateTime::createFromFormat('d-m-Y', $fechaFacturaStr);
            if ($fechaFacturaObj) {
                $fechaFacturaObj->modify("+{$diasVencimiento} days");
                $fechaVencimiento = $fechaFacturaObj->format('d-m-Y');
            } else {
                $fechaVencimiento = $fechaFacturaStr;
            }
        } catch (\Exception $e) {
            $fechaVencimiento = $fechaFacturaStr;
        }
        
        return [
            'nroFactura' => $nroFactura1 . '-' . $nroFactura2,
            'nroFactura1' => $nroFactura1,
            'nroFactura2' => $nroFactura2,
            'fechaFactura' => $fechaFacturaStr,
            'fechaPago' => $infofactura['fechaPago'] ?? date('d-m-Y'),
            'condicionPago' => $condicionPago,
            'fechaVencimiento' => $fechaVencimiento,
            'logoBase64' => $logoBase64,
            'empresaRazonSocial' => $configs['razon_social_empresa'] ?? 'FEDERICO LISANDRO STRUPENI',
            'empresaDomicilio' => $configs['domicilio_empresa'] ?? 'NECOCHEA 2420 LOCAL 2 ROSARIO',
            'empresaCUIT' => $configs['cuit_empresa'] ?? '20290017379',
            'empresaIIBB' => $configs['iibb_empresa'] ?? '0213498698',
            'empresaFechaInicio' => $configs['fecha_inicio_actividades'] ?? '00-00-0000',
            'empresaCondicionIVA' => 'Resp. Insc.',
            'clienteNombre' => $clienteNombre,
            'clienteCUIT' => $client->cuit ?? '--',
            'clienteDomicilio' => $clienteDomicilio,
            'clienteCondicionIVA' => $clienteCondicionIVA,
            'items' => $items,
            'netoGravado' => $netoGravado,
            'netoNoGravado' => $netoNoGravado,
            'iva21' => $iva21,
            'iva105' => $iva105,
            'iva27' => $iva27,
            'totalFactura' => $totalFactura,
        ];
    }
}
