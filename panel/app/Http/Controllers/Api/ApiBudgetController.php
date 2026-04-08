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
            $start = $request->input('start', 0);
            $limit = $request->input('limit', 50);
            $search = $request->input('search', '');
            
            $colppyService = new ColppyService();
            
            // Filtros para obtener solo presupuestos del talonario 0002
            $filtros = [
                [
                    'field' => 'nroFactura1',
                    'comparison' => 'eq',
                    'value' => '0002'
                ],
                [
                    'field' => 'idTipoFactura',
                    'comparison' => 'eq',
                    'value' => 'X'  // X = Presupuesto/Cotización
                ]
            ];
            
            // Si hay búsqueda, agregar filtro
            if (!empty($search)) {
                $filtros[] = [
                    'field' => 'descripcion',
                    'comparison' => 'like',
                    'value' => '%' . $search . '%'
                ];
            }
            
            // Orden descendente por fecha
            $orden = (object)[
                'field' => ['fechaFactura'],
                'order' => 'desc'
            ];
            
            $resultado = $colppyService->listarFacturasVenta($start, $limit, $filtros, $orden);
            
            if ($resultado['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $resultado['datos']['facturas'] ?? [],
                    'total' => $resultado['datos']['total_registros'] ?? 0,
                    'start' => $start,
                    'limit' => $limit
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => $resultado['mensaje'] ?? 'Error al listar presupuestos'
            ], 500);
            
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
            
            if ($resultado['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $resultado['datos'] ?? [],
                    'message' => 'Presupuesto obtenido correctamente'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => $resultado['mensaje'] ?? 'Presupuesto no encontrado'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Error en ApiBudgetController::show', [
                'idFactura' => $idFactura,
                'error' => $e->getMessage()
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
            
            if (!$client->idcolppy) {
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
                    'idCliente' => $client->idcolppy,
                    'idCondicionPago' => 'a 7 Dias',
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
                
                if (isset($response['success']) && $response['success'] === true) {
                    $idFactura = $response['response']['idfactura'] ?? null;
                    
                    if ($idFactura) {
                        $presupuestoCreado = true;
                        
                        Log::info('Presupuesto creado desde app móvil', [
                            'idFactura' => $idFactura,
                            'nroPresupuesto' => $talonario . '-' . $numeroPresupuesto,
                            'client_id' => $client->id,
                            'intento' => $intentoActual
                        ]);
                        
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
            
            if (!$client->idcolppy) {
                return response()->json([
                    'success' => false,
                    'message' => 'El cliente no está sincronizado con Colppy'
                ], 400);
            }
            
            $colppyService = new ColppyService();
            
            // Primero leer el presupuesto actual para obtener su número
            $presupuestoActual = $colppyService->leerFacturaVenta($idFactura);
            
            if (!$presupuestoActual['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo leer el presupuesto: ' . ($presupuestoActual['mensaje'] ?? 'Error desconocido')
                ], 404);
            }
            
            $datosActuales = $presupuestoActual['datos'] ?? [];
            
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
            
            // Preparar datos para actualización
            $datosActualizacion = [
                'idFactura' => $idFactura,
                'descripcion' => $request->description ?? 'Presupuesto actualizado desde app móvil',
                'fechaFactura' => $datosActuales['fechaFactura'] ?? Carbon::now()->format('d-m-Y'),
                'fechaPago' => $datosActuales['fechaPago'] ?? Carbon::now()->format('d-m-Y'),
                'idCliente' => $client->idcolppy,
                'idCondicionPago' => $datosActuales['idCondicionPago'] ?? 'a 7 Dias',
                'idEstadoFactura' => $datosActuales['idEstadoFactura'] ?? 'Borrador',
                'idEstadoAnterior' => $datosActuales['idEstadoAnterior'] ?? '',
                'idTipoFactura' => 'X',
                'idTipoComprobante' => '4',
                'idMoneda' => $datosActuales['idMoneda'] ?? '1',
                'valorCambio' => $datosActuales['valorCambio'] ?? '1',
                'nroFactura1' => $datosActuales['nroFactura1'] ?? '0002',
                'nroFactura2' => $datosActuales['nroFactura2'] ?? '',
                'percepcionIVA' => '0.00',
                'percepcionIIBB' => '0.00',
                'orderId' => '',
                'items' => $items
            ];
            
            // Actualizar el presupuesto
            $response = $colppyService->editarFacturaVenta($datosActualizacion);
            
            if (isset($response['success']) && $response['success'] === true) {
                Log::info('Presupuesto actualizado desde app móvil', [
                    'idFactura' => $idFactura,
                    'client_id' => $client->id
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Presupuesto actualizado correctamente',
                    'data' => [
                        'idFactura' => $idFactura
                    ]
                ]);
            }
            
            // Error al actualizar
            $mensajeError = $response['mensaje'] ?? $response['result']['mensaje'] ?? 'Error desconocido';
            
            Log::error('Error al actualizar presupuesto desde app', [
                'idFactura' => $idFactura,
                'mensaje' => $mensajeError
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar presupuesto: ' . $mensajeError
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('Excepción en ApiBudgetController::update', [
                'idFactura' => $idFactura,
                'error' => $e->getMessage(),
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
            
            $query = Product::whereNotNull('colppy_id')
                ->whereNull('deleted_at')
                ->whereIn('tipo_item', ['P', 'S']);
            
            // Filtrar por tipo si se especifica
            if ($tipo && in_array($tipo, ['P', 'S'])) {
                $query->where('tipo_item', $tipo);
            }
            
            // Búsqueda por código o descripción
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('codigo', 'like', '%' . $search . '%')
                      ->orWhere('descripcion', 'like', '%' . $search . '%');
                });
            }
            
            $items = $query->select(
                'id',
                'codigo',
                'descripcion',
                'tipo_item',
                'colppy_id',
                'precio_venta',
                'stock',
                'stock_minimo'
            )
            ->orderBy('descripcion', 'asc')
            ->limit($limit)
            ->get();
            
            return response()->json([
                'success' => true,
                'data' => $items,
                'count' => $items->count()
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
            $colppyService = new ColppyService();
            $datosAfip = null;
            $datosCliente = [];

            // Si se proporciona CUIT, intentar obtener datos de AFIP
            if ($request->filled('cuit')) {
                Log::info('ApiBudgetController::createClient - Consultando AFIP', [
                    'cuit' => $request->cuit
                ]);

                $resultadoAfip = $colppyService->obtenerDatosTerceroDeAfip($request->cuit);

                if ($resultadoAfip['success']) {
                    $datosAfip = $resultadoAfip['data'];
                    
                    Log::info('ApiBudgetController::createClient - Datos AFIP obtenidos', [
                        'nombre' => $datosAfip['nombre'] ?? 'N/A'
                    ]);

                    // Preparar datos del cliente desde AFIP
                    $datosCliente['razon_social'] = $datosAfip['nombre'] ?? '';
                    $datosCliente['nombre_fantasia'] = $datosAfip['nombre'] ?? '';
                    $datosCliente['cuit'] = $request->cuit;
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
                    // Si falla la consulta de AFIP, continuar con datos manuales
                    Log::warning('ApiBudgetController::createClient - No se pudieron obtener datos de AFIP', [
                        'cuit' => $request->cuit,
                        'mensaje' => $resultadoAfip['mensaje'] ?? 'Error desconocido'
                    ]);
                }
            }

            // Si no hay datos de AFIP, usar datos manuales del request
            if (empty($datosAfip)) {
                // Validación para datos manuales
                $validator = Validator::make($request->all(), [
                    'first_name' => 'required|string|max:100',
                    'last_name' => 'nullable|string|max:100',
                    'email' => 'nullable|email|max:100',
                    'phone' => 'nullable|string|max:50',
                    'address' => 'nullable|string|max:255',
                    'city' => 'nullable|string|max:100',
                    'state' => 'nullable|string|max:100',
                    'postal_code' => 'nullable|string|max:20',
                    'cuit' => 'nullable|string|max:20'
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
                    'cuit' => $request->cuit,
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
            Log::info('ApiBudgetController::createClient - Creando cliente en Colppy', [
                'razon_social' => $datosCliente['razon_social'] ?? 'N/A'
            ]);

            $resultadoColppy = $colppyService->crearCliente($datosCliente);

            if (!$resultadoColppy['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear cliente en Colppy: ' . $resultadoColppy['mensaje'],
                    'datos_afip' => $datosAfip  // Incluir datos de AFIP para debug
                ], 400);
            }

            $idColppy = $resultadoColppy['idCliente'];

            Log::info('ApiBudgetController::createClient - Cliente creado en Colppy', [
                'idColppy' => $idColppy
            ]);

            // CREAR CLIENTE LOCALMENTE
            $clienteLocal = Client::create([
                'first_name' => $request->first_name ?? $datosCliente['razon_social'] ?? '',
                'last_name' => $request->last_name ?? '',
                'email' => $request->email ?? $datosCliente['email'] ?? null,
                'phone' => $request->phone ?? $datosCliente['telefono'] ?? null,
                'address' => $request->address ?? $datosCliente['direccion'] ?? null,
                'city' => $request->city ?? $datosCliente['ciudad'] ?? null,
                'state' => $request->state ?? $datosCliente['provincia'] ?? null,
                'postal_code' => $request->postal_code ?? $datosCliente['codigo_postal'] ?? null,
                'cuit' => $request->cuit ?? $datosCliente['cuit'] ?? null,
                'idcolppy' => $idColppy,
                'is_from_colppy' => 1  // Cliente sincronizado con Colppy
            ]);

            Log::info('ApiBudgetController::createClient - Cliente guardado localmente', [
                'client_id' => $clienteLocal->id,
                'idColppy' => $idColppy
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cliente creado correctamente en Colppy y sincronizado localmente',
                'data' => [
                    'id' => $clienteLocal->id,
                    'idColppy' => $idColppy,
                    'first_name' => $clienteLocal->first_name,
                    'last_name' => $clienteLocal->last_name,
                    'full_name' => trim($clienteLocal->first_name . ' ' . $clienteLocal->last_name),
                    'email' => $clienteLocal->email,
                    'phone' => $clienteLocal->phone,
                    'cuit' => $clienteLocal->cuit,
                    'datos_afip' => $datosAfip  // Incluir datos originales de AFIP para referencia
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error en ApiBudgetController::createClient', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear cliente: ' . $e->getMessage()
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
            
            Log::info('Tareas asociadas a presupuesto desde app', [
                'idFactura' => $idFactura,
                'budget_number' => $budgetNumber,
                'job_ids' => $jobIds,
                'updated_count' => $updated
            ]);
            
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
     * Descargar PDF del presupuesto generado localmente
     * GET /api/budgets/{idFactura}/pdf
     * 
     * @param string $idFactura ID del presupuesto en Colppy
     * @return Response
     */
    public function downloadPdf($idFactura)
    {
        try {
            Log::info('ApiBudgetController::downloadPdf - Generando PDF desde Colppy', [
                'id_factura' => $idFactura
            ]);

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

            Log::info('PDF generado exitosamente', [
                'id_factura' => $idFactura,
                'filename' => $filename
            ]);

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
                
                Log::info('Logo cargado correctamente', [
                    'path' => $logoPath,
                    'size' => strlen($logoData),
                    'base64_length' => strlen($logoBase64)
                ]);
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
        if ($client && !empty($client->address)) {
            $clienteDomicilio = $client->address;
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
