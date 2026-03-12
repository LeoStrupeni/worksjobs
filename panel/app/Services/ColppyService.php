<?php

namespace App\Services;

use App\Models\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ColppyService
{
    private string $urlApi;
    private string $authUsuario;
    private string $authPassword;
    private string $paramUsuario;
    private string $paramPassword;
    private string $idEmpresa;
    private const SESSION_KEY = 'colppy_clave_sesion';

    public function __construct()
    {
        // Obtener configuraciones de la base de datos (api_config)
        $configs = Config::whereIn('name', [
            'url_api_login',
            'user_dev_api',
            'pass_dev_api',
            'user_api',
            'pass_api',
            'id_empresa_api'
        ])->pluck('value', 'name');
        

        $this->urlApi = $configs['url_api_login'] ?? '';

        // Credenciales separadas segun requerimiento de Colppy
        $this->authUsuario = $configs['user_dev_api'] ?? '';
        $this->authPassword = $configs['pass_dev_api'] ?? '';
        $this->paramUsuario = $configs['user_api'] ?? '';
        $this->paramPassword = $configs['pass_api'] ?? '';

        $this->idEmpresa = $configs['id_empresa_api'] ?? '';

        // Validar que tenemos las configuraciones necesarias
        if (
            empty($this->urlApi) ||
            empty($this->authUsuario) ||
            empty($this->authPassword) ||
            empty($this->paramUsuario) ||
            empty($this->paramPassword)
        ) {
            // Log::warning('Configuración de Colppy incompleta');
        }
    }

    /**
     * Obtener clave de sesión de la API de Colppy
     * Si existe en SESSION, no corta el flujo.
     * Si no, obtiene una nueva y la guarda en SESSION.
     *
     * @param bool $forceNew Forzar obtener nueva claveSesion aunque exista en SESSION
     * @return array {success: bool, mensaje?: string}
     */
    public function obtenerClaveSesion(bool $forceNew = false): array
    {
        try {
            // Verificar si ya tenemos una clave en la sesión
            $claveSesionGuardada = Session::get(self::SESSION_KEY);
            
            if (!$forceNew && !empty($claveSesionGuardada)) {
                return [
                    'success' => true
                ];
            }

            if ($forceNew) {
                Session::forget(self::SESSION_KEY);
            }

            // Obtener nueva clave de sesión desde Colppy
            $payload = [
                'auth' => [
                    'usuario' => $this->authUsuario,
                    'password' => md5($this->authPassword)
                ],
                'service' => [
                    'provision' => 'Usuario',
                    'operacion' => 'iniciar_sesion'
                ],
                'parameters' => [
                    'usuario' => $this->paramUsuario,
                    'password' => md5($this->paramPassword)
                ]
            ];

            $response = Http::withOptions(['verify' => false])->timeout(30)->post($this->urlApi, $payload);

            if ($response->successful()) {
                $data = $response->json();

                $esExito = (isset($data['exito']) && $data['exito'] === true)
                    || (isset($data['result']['estado']) && (int) $data['result']['estado'] === 0)
                    || (isset($data['response']['success']) && $data['response']['success'] === true);

                if ($esExito) {
                    $claveSesion = $data['datos'][0]['claveSesion']
                        ?? $data['response']['data']['claveSesion']
                        ?? null;

                    if ($claveSesion) {
                        // Guardar en SESSION de Laravel
                        Session::put(self::SESSION_KEY, $claveSesion);

                        return [
                            'success' => true
                        ];
                    }
                }

                $mensajeError = $data['mensaje']
                    ?? $data['result']['mensaje']
                    ?? $data['response']['message']
                    ?? 'Error obteniendo clave de sesión';

                Log::error('Error en respuesta de Colppy', ['respuesta' => $data]);
                return [
                    'success' => false,
                    'mensaje' => $mensajeError
                ];
            }

            Log::error('Error HTTP obteniendo clave de sesión de Colppy', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'mensaje' => 'Error de conexión con la API de Colppy'
            ];

        } catch (\Exception $e) {
            Log::error('Excepción obteniendo clave de sesión', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Realizar una llamada genérica a la API de Colppy
     * Recibe el payload completo (auth/service/parameters) sin modificarlo.
     *
     * @param array $payload
     *
     * @return array {success: bool, datos?: array, mensaje?: string, total?: int}
     */
    public function hacerLlamada(array $payload): array
    {
        try {
            if (!isset($payload['auth'], $payload['service'], $payload['parameters'])) {
                return [
                    'success' => false,
                    'mensaje' => 'Payload inválido: falta auth, service o parameters'
                ];
            }

            $response = Http::withOptions(['verify' => false])->timeout(30)->post($this->urlApi, $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                // Log de respuesta completa para debugging
                // Log::info('Respuesta completa de Colppy', [
                //     'provision' => $payload['service']['provision'] ?? 'unknown',
                //     'operacion' => $payload['service']['operacion'] ?? 'unknown',
                //     'response' => $data
                // ]);

                $esExito = ($data['exito'] ?? false)
                    || (isset($data['result']['estado']) && (int) $data['result']['estado'] === 0)
                    || (isset($data['response']['success']) && $data['response']['success'] === true);

                if ($esExito) {
                    // Leer datos desde response (formato Colppy para listar_facturasventa)
                    $datos = $data['response']['datos']  // Array de facturas
                        ?? $data['response']['data'] 
                        ?? $data['datos'] 
                        ?? [];
                    
                    // Leer total_registros desde response
                    $total = isset($data['response']['total_registros']) 
                        ? (int) $data['response']['total_registros'] 
                        : (isset($data['response']['total']) ? (int) $data['response']['total'] : null);

                    return [
                        'success' => true,
                        'datos' => $datos,
                        'total' => $total,
                        'response' => $data['response'] ?? []  // Incluir response completo para acceder a campos adicionales
                    ];
                }

                $mensajeError = $data['mensaje']
                    ?? $data['result']['mensaje']
                    ?? $data['response']['message']
                    ?? 'Error en respuesta de Colppy';

                return [
                    'success' => false,
                    'mensaje' => $mensajeError
                ];
            }

            Log::error('Error HTTP en llamada a Colppy', [
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'mensaje' => 'Error de conexión con Colppy'
            ];

        } catch (\Exception $e) {
            Log::error('Excepción en llamada a Colppy', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Listar clientes de Colppy
     */
    public function listarClientes(
        int $start = 0,
        int $limit = 50,
        array $filtros = [],
        array $orden = []
    ): array {
        $resultadoSesion = $this->obtenerClaveSesion();
        if (!$resultadoSesion['success']) {
            return $resultadoSesion;
        }

        $claveSesion = Session::get(self::SESSION_KEY);
        if (empty($claveSesion)) {
            return [
                'success' => false,
                'mensaje' => 'No se pudo obtener claveSesion'
            ];
        }

        // Validar limit y start según documentación Colppy
        if (empty($limit)) {
            $limit = 50;
        }
        
        if (empty($start) || $start > $limit) {
            $start = 0;
        }

        $parameters = [
            'sesion' => [
                'usuario' => $this->paramUsuario,
                'claveSesion' => $claveSesion
            ],
            'idEmpresa' => $this->idEmpresa,
            'start' => $start,
            'limit' => $limit
        ];

        // Filter es obligatorio, si está vacío enviar array vacío
        $parameters['filter'] = !empty($filtros) ? $filtros : [];

        // Order es OBLIGATORIO según documentación Colppy
        // Si está vacío, usar ordenamiento por defecto
        if (empty($orden)) {
            $parameters['order'] = [
                [
                    'field' => 'RazonSocial',
                    'dir' => 'ASC'
                ]
            ];
        } else {
            $parameters['order'] = $orden;
        }

        $payload = [
            'auth' => [
                'usuario' => $this->authUsuario,
                'password' => md5($this->authPassword)
            ],
            'service' => [
                'provision' => 'Cliente',
                'operacion' => 'listar_cliente'
            ],
            'parameters' => $parameters
        ];

        return $this->hacerLlamada($payload);
    }

    /**
     * Obtener cliente específico
     */
    public function obtenerCliente(string $idCliente): array
    {
        $resultadoSesion = $this->obtenerClaveSesion();
        if (!$resultadoSesion['success']) {
            return $resultadoSesion;
        }

        $claveSesion = Session::get(self::SESSION_KEY);
        if (empty($claveSesion)) {
            return [
                'success' => false,
                'mensaje' => 'No se pudo obtener claveSesion'
            ];
        }

        $payload = [
            'auth' => [
                'usuario' => $this->authUsuario,
                'password' => md5($this->authPassword)
            ],
            'service' => [
                'provision' => 'Cliente',
                'operacion' => 'obtener_cliente'
            ],
            'parameters' => [
                'sesion' => [
                    'usuario' => $this->paramUsuario,
                    'claveSesion' => $claveSesion
                ],
                'idEmpresa' => $this->idEmpresa,
                'idCliente' => $idCliente
            ]
        ];

        return $this->hacerLlamada($payload);
    }

    /**
     * Listar items de inventario de Colppy (solo productos tipo "P")
     * 
     * @param int $start Inicio de paginación
     * @param int $limit Límite de registros
     * @param array $filtros Filtros adicionales
     * @param array $orden Ordenamiento
     * @return array
     */
    public function listarInventario(
        int $start = 0,
        int $limit = 50,
        array $filtros = [],
        array $orden = []
    ): array {
        $resultadoSesion = $this->obtenerClaveSesion();
        if (!$resultadoSesion['success']) {
            return $resultadoSesion;
        }

        $claveSesion = Session::get(self::SESSION_KEY);
        if (empty($claveSesion)) {
            return [
                'success' => false,
                'mensaje' => 'No se pudo obtener claveSesion'
            ];
        }

        // Validar limit y start según documentación Colppy
        if (empty($limit)) {
            $limit = 50;
        }
        
        if (empty($start) || $start > $limit) {
            $start = 0;
        }

        $parameters = [
            'sesion' => [
                'usuario' => $this->paramUsuario,
                'claveSesion' => $claveSesion
            ],
            'idEmpresa' => $this->idEmpresa,
            'start' => $start,
            'limit' => $limit
        ];

        // Agregar filtro para solo productos tipo "P" (no servicios ni kits)
        // Según documentación: tipoItem = "P" (Producto), "S" (Servicio), "K" (Kit)
        $filtrosBase = [
            // [
            //     'field' => 'tipoItem',
            //     'comparison' => 'eq',
            //     'value' => 'P'
            // ]
        ];

        // Combinar filtros base con filtros adicionales
        $parameters['filter'] = array_merge($filtrosBase, $filtros);

        // Order es OBLIGATORIO según documentación Colppy
        // Si está vacío, usar ordenamiento por defecto
        if (empty($orden)) {
            $parameters['order'] = [
                [
                    'field' => 'descripcion',
                    'dir' => 'ASC'
                ]
            ];
        } else {
            $parameters['order'] = $orden;
        }

        $payload = [
            'auth' => [
                'usuario' => $this->authUsuario,
                'password' => md5($this->authPassword)
            ],
            'service' => [
                'provision' => 'Inventario',
                'operacion' => 'listar_itemsinventario'
            ],
            'parameters' => $parameters
        ];

        return $this->hacerLlamada($payload);
    }

    /**
     * Obtener item de inventario específico
     * 
     * @param string $idItem ID del item en Colppy
     * @return array
     */
    public function obtenerItemInventario(string $idItem): array
    {
        $resultadoSesion = $this->obtenerClaveSesion();
        if (!$resultadoSesion['success']) {
            return $resultadoSesion;
        }

        $claveSesion = Session::get(self::SESSION_KEY);
        if (empty($claveSesion)) {
            return [
                'success' => false,
                'mensaje' => 'No se pudo obtener claveSesion'
            ];
        }

        $payload = [
            'auth' => [
                'usuario' => $this->authUsuario,
                'password' => md5($this->authPassword)
            ],
            'service' => [
                'provision' => 'Inventario',
                'operacion' => 'obtener_itemsinventario'
            ],
            'parameters' => [
                'sesion' => [
                    'usuario' => $this->paramUsuario,
                    'claveSesion' => $claveSesion
                ],
                'idEmpresa' => $this->idEmpresa,
                'idItem' => $idItem
            ]
        ];

        return $this->hacerLlamada($payload);
    }

    /**
     * Crear Factura de Venta en Colppy
     * Estructura según documentación oficial de Colppy
     * 
     * @param array $data Datos de la factura
     * @return array Respuesta de Colppy con idFactura
     */
    public function crearFacturaVenta(array $data): array
    {
        $resultadoSesion = $this->obtenerClaveSesion();
        if (!$resultadoSesion['success']) {
            return $resultadoSesion;
        }

        $claveSesion = Session::get(self::SESSION_KEY);
        if (empty($claveSesion)) {
            return [
                'success' => false,
                'mensaje' => 'No se pudo obtener claveSesion'
            ];
        }

        // Valores por defecto según apéndice de Colppy
        $defaults = [
            'idTipoFactura' => '7',  // 7 = Presupuesto/Cotización (tipo X)
            'idEstadoFactura' => '1',  // 1 = Borrador
            'idCondiciónPago' => 'a 15 dias',  // Texto según aparece en Colppy
            'idTipoComprobante' => '4',
            'idMoneda' => '1',  // Pesos argentinos
            'valorCambio' => '1',
            'netoNoGravado' => '0.00',
            'percepcionIVA' => '0.00',
            'percepcionIIBB' => '0.00',
            'nroFactura1' => '',
            'nroFactura2' => '',  // Vacío, Colppy podría calcularlo automáticamente
            'idFactura' => '',
            'idEstadoAnterior' => '',
            'idUsuario' => '',
            'orderId' => ''
        ];

        $data = array_merge($defaults, $data);

        // Validar campos obligatorios
        if (empty($data['idCliente'])) {
            return [
                'success' => false,
                'mensaje' => 'idCliente es obligatorio'
            ];
        }

        if (empty($data['items']) || !is_array($data['items'])) {
            return [
                'success' => false,
                'mensaje' => 'items es obligatorio y debe ser un array'
            ];
        }

        // Construir itemsFactura según estructura de Colppy
        $itemsFactura = [];
        $netoGravado = 0;
        $netoNoGravado = 0;
        $totalesPorIVA = [
            '0' => ['baseImp' => 0, 'importe' => 0],
            '10.5' => ['baseImp' => 0, 'importe' => 0],
            '21' => ['baseImp' => 0, 'importe' => 0],
            '27' => ['baseImp' => 0, 'importe' => 0]
        ];

        foreach ($data['items'] as $item) {
            $cantidad = $item['Cantidad'] ?? $item['cantidad'] ?? 1;
            $importeUnitario = $item['ImporteUnitario'] ?? $item['precioUnitario'] ?? 0;
            $iva = $item['IVA'] ?? $item['iva'] ?? 21;
            $porcDesc = $item['porcDesc'] ?? '0.00';

            // Calcular subtotal del item
            $subtotalItem = $cantidad * $importeUnitario;
            
            // Aplicar descuento si existe
            if ($porcDesc > 0) {
                $subtotalItem = $subtotalItem * (1 - ($porcDesc / 100));
            }

            $itemFactura = [
                'Descripcion' => $item['Descripcion'] ?? $item['descripcion'] ?? '',
                'unidadMedida' => $item['unidadMedida'] ?? 'U',  // U = Unidades en Colppy
                'ccosto1' => $item['ccosto1'] ?? '',
                'ccosto2' => $item['ccosto2'] ?? '',
                'Cantidad' => $cantidad,
                'ImporteUnitario' => $importeUnitario,
                'porcDesc' => $porcDesc,
                'IVA' => (string)$iva,
                'idPlanCuenta' => $item['idPlanCuenta'] ?? 'Ventas de mercaderías',
                'Comentario' => $item['Comentario'] ?? ''
            ];

            // Si el item tiene idItem (producto de inventario), agregar campos adicionales
            if (!empty($item['idItem'])) {
                $itemFactura['idItem'] = $item['idItem'];
                
                if (isset($item['minimo'])) {
                    $itemFactura['minimo'] = $item['minimo'];
                }
                
                if (isset($item['tipoItem'])) {
                    $itemFactura['tipoItem'] = $item['tipoItem'];
                }
                
                if (isset($item['codigo'])) {
                    $itemFactura['codigo'] = $item['codigo'];
                }
                
                if (isset($item['almacen'])) {
                    $itemFactura['almacen'] = $item['almacen'];
                }
            }

            $itemsFactura[] = $itemFactura;

            // Acumular en neto gravado o no gravado
            if ($iva > 0) {
                $netoGravado += $subtotalItem;
                
                // Calcular IVA y acumular por alícuota
                $importeIVA = round($subtotalItem * ($iva / 100), 2);
                $totalesPorIVA[(string)$iva]['baseImp'] += $subtotalItem;
                $totalesPorIVA[(string)$iva]['importe'] += $importeIVA;
            } else {
                $netoNoGravado += $subtotalItem;
            }
        }

        // Redondear totales
        $netoGravado = round($netoGravado, 2);
        $netoNoGravado = round($netoNoGravado, 2);
        $totalIVA = 0;

        // Construir array totalesiva
        $totalesiva = [];
        foreach (['0', '10.5', '21', '27'] as $alicuota) {
            $baseImp = round($totalesPorIVA[$alicuota]['baseImp'], 2);
            $importe = round($totalesPorIVA[$alicuota]['importe'], 2);
            $totalIVA += $importe;
            
            $totalesiva[] = [
                'alicuotaIva' => $alicuota,
                'baseImpIva' => number_format($baseImp, 2, '.', ''),
                'importeIva' => number_format($importe, 2, '.', '')
            ];
        }

        $totalIVA = round($totalIVA, 2);
        $totalFactura = $netoGravado + $netoNoGravado + $totalIVA + 
                        floatval($data['percepcionIVA']) + 
                        floatval($data['percepcionIIBB']);

        $payload = [
            'auth' => [
                'usuario' => $this->authUsuario,
                'password' => md5($this->authPassword)
            ],
            'service' => [
                'provision' => 'FacturaVenta',
                'operacion' => 'alta_facturaventa'
            ],
            'parameters' => [
                'sesion' => [
                    'usuario' => $this->paramUsuario,
                    'claveSesion' => $claveSesion
                ],
                'descripcion' => $data['descripcion'] ?? '',
                'fechaFactura' => $data['fechaFactura'],
                'fechaPago' => $data['fechaPago'] ?? $data['fechaFactura'],
                'idCliente' => $data['idCliente'],
                'idCondicionPago' => $data['idCondiciónPago'],  // Sin tilde según API Colppy
                'idEmpresa' => $this->idEmpresa,
                'idEstadoAnterior' => $data['idEstadoAnterior'],
                'idEstadoFactura' => $data['idEstadoFactura'],
                'idFactura' => $data['idFactura'],
                'idMoneda' => $data['idMoneda'],
                'idTipoComprobante' => $data['idTipoComprobante'],
                'idTipoFactura' => $data['idTipoFactura'],
                'idUsuario' => $data['idUsuario'],
                'netoGravado' => number_format($netoGravado, 2, '.', ''),
                'netoNoGravado' => number_format($netoNoGravado, 2, '.', ''),
                'nroFactura1' => $data['nroFactura1'],
                'nroFactura2' => $data['nroFactura2'],
                'percepcionIVA' => number_format(floatval($data['percepcionIVA']), 2, '.', ''),
                'percepcionIIBB' => number_format(floatval($data['percepcionIIBB']), 2, '.', ''),
                'orderId' => $data['orderId'],
                'itemsFactura' => $itemsFactura,
                'totalFactura' => number_format($totalFactura, 2, '.', ''),
                'totalIVA' => number_format($totalIVA, 2, '.', ''),
                'valorCambio' => $data['valorCambio'],
                'totalesiva' => $totalesiva
            ]
        ];

        // Log::info('Creando factura/presupuesto en Colppy', [
        //     'idCliente' => $data['idCliente'],
        //     'idTipoFactura' => $data['idTipoFactura'],
        //     'items' => count($itemsFactura),
        //     'totalFactura' => $totalFactura
        // ]);

        // Log del payload completo para debugging
        // Log::info('Payload completo enviado a Colppy alta_facturaventa', [
        //     'payload_parameters' => $payload['parameters']
        // ]);

        return $this->hacerLlamada($payload);
    }

    /**
     * Listar facturas de venta (incluye presupuestos borradores)
     * 
     * @param int $start Inicio de paginación
     * @param int $limit Límite de registros
     * @param array $filtros Filtros adicionales
     * @param array $orden Ordenamiento
     * @return array
     */
    public function listarFacturasVenta(
        int $start = 0,
        int $limit = 50,
        array $filtros = [],
        $orden = null  // Puede ser object o null
    ): array {
        $resultadoSesion = $this->obtenerClaveSesion();
        if (!$resultadoSesion['success']) {
            return $resultadoSesion;
        }

        $claveSesion = Session::get(self::SESSION_KEY);
        if (empty($claveSesion)) {
            return [
                'success' => false,
                'mensaje' => 'No se pudo obtener claveSesion'
            ];
        }

        // Validar limit y start según documentación Colppy
        if (empty($limit)) {
            $limit = 50;
        }
        
        if (empty($start) || $start > $limit) {
            $start = 0;
        }

        $parameters = [
            'sesion' => [
                'usuario' => $this->paramUsuario,
                'claveSesion' => $claveSesion
            ],
            'idEmpresa' => $this->idEmpresa,
            'start' => $start,
            'limit' => $limit
        ];

        // Filter: agregar filtros personalizados
        $parameters['filter'] = !empty($filtros) ? $filtros : [];

        // Order: Según documentación Colppy debe ser un OBJETO con field (array) y order (string)
        if (empty($orden)) {
            $parameters['order'] = (object)[
                'field' => ['fechaFactura'],
                'order' => 'desc'
            ];
        } else {
            $parameters['order'] = $orden;
        }

        $payload = [
            'auth' => [
                'usuario' => $this->authUsuario,
                'password' => md5($this->authPassword)
            ],
            'service' => [
                'provision' => 'FacturaVenta',
                'operacion' => 'listar_facturasventa'
            ],
            'parameters' => $parameters
        ];

        // Log detallado para debug
        // Log::info('=== PETICIÓN A COLPPY (listar_facturasventa) ===');
        // Log::info('Filtros aplicados:', $filtros);
        // Log::info('Start: ' . $start . ' | Limit: ' . $limit);
        // Log::info('Payload completo:', [
        //     'service' => $payload['service'],
        //     'parameters' => [
        //         'idEmpresa' => $parameters['idEmpresa'],
        //         'start' => $parameters['start'],
        //         'limit' => $parameters['limit'],
        //         'filter' => $parameters['filter'],
        //         'order' => $parameters['order']
        //     ]
        // ]);

        $resultado = $this->hacerLlamada($payload);
        
        // Log de respuesta
        if (isset($resultado['success']) && $resultado['success']) {
            $total = $resultado['datos']['total_registros'] ?? 0;
            $registros = count($resultado['datos']['facturas'] ?? []);
            // Log::info("=== RESPUESTA EXITOSA: {$registros} registros de {$total} totales ===");
        } else {
            Log::error('=== ERROR EN RESPUESTA DE COLPPY ===', [
                'mensaje' => $resultado['mensaje'] ?? 'Sin mensaje',
                'respuesta' => $resultado
            ]);
        }

        return $resultado;
    }

    /**
     * Leer detalle completo de una factura de venta
     * Operación: leer_facturaventa
     * 
     * @param string $idFactura ID de la factura en Colppy
     * @return array Respuesta con infofactura, itemsFactura, totalesiva, etc.
     */
    public function leerFacturaVenta(string $idFactura): array
    {
        $resultadoSesion = $this->obtenerClaveSesion();
        if (!$resultadoSesion['success']) {
            return $resultadoSesion;
        }

        $claveSesion = Session::get(self::SESSION_KEY);
        if (empty($claveSesion)) {
            return [
                'success' => false,
                'mensaje' => 'No se pudo obtener claveSesion'
            ];
        }

        $payload = [
            'auth' => [
                'usuario' => $this->authUsuario,
                'password' => md5($this->authPassword)
            ],
            'service' => [
                'provision' => 'FacturaVenta',
                'operacion' => 'leer_facturaventa'
            ],
            'parameters' => [
                'sesion' => [
                    'usuario' => $this->paramUsuario,
                    'claveSesion' => $claveSesion
                ],
                'idEmpresa' => $this->idEmpresa,
                'idFactura' => $idFactura
            ]
        ];

        // Log::info('=== PETICIÓN A COLPPY (leer_facturaventa) ===', [
        //     'idFactura' => $idFactura
        // ]);

        $resultado = $this->hacerLlamada($payload);

        if (isset($resultado['success']) && $resultado['success']) {
            // Log::info('=== DETALLE DE FACTURA OBTENIDO EXITOSAMENTE ===');
        } else {
            Log::error('=== ERROR AL LEER FACTURA ===', [
                'mensaje' => $resultado['mensaje'] ?? 'Sin mensaje'
            ]);
        }

        return $resultado;
    }

    /**
     * Invalidar sesión actual (limpiar de SESSION)
     */
    public function invalidarSesion(): void
    {
        Session::forget(self::SESSION_KEY);
        // Log::info('Sesión de Colppy invalidada');
    }
}
