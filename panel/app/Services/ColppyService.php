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
                
                // Log TEMPORAL para debugging (eliminar después)
                Log::info('🔍 ColppyService::hacerLlamada - $data completo', [
                    'provision' => $payload['service']['provision'] ?? 'unknown',
                    'operacion' => $payload['service']['operacion'] ?? 'unknown',
                    'data_completo' => $data,
                    'exito' => $data['exito'] ?? 'NO EXISTE',
                    'response_success' => $data['response']['success'] ?? 'NO EXISTE'
                ]);

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
            'idCondicionPago' => 'a 15 Dias',  // Sin tilde, Dias con mayúscula
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
                'idCondicionPago' => $data['idCondicionPago'],  // SIN tilde (consistente)
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
     * Editar Factura de Venta en Colppy (operación editar_facturaventa)
     * Usado para actualizar presupuestos existentes
     * 
     * @param array $data Datos de la factura (DEBE incluir idFactura)
     * @return array Respuesta de Colppy
     */
    public function editarFacturaVenta(array $data): array
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

        // Validar campo obligatorio: idFactura
        if (empty($data['idFactura'])) {
            return [
                'success' => false,
                'mensaje' => 'idFactura es obligatorio para editar'
            ];
        }

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
            
            // Normalizar IVA: '21.00' => '21', '10.50' => '10.5', '0.00' => '0'
            $ivaFloat = floatval($iva);
            $iva = rtrim(rtrim(number_format($ivaFloat, 2, '.', ''), '0'), '.');

            // Calcular subtotal del item
            $subtotalItem = $cantidad * $importeUnitario;
            
            // Aplicar descuento si existe
            if ($porcDesc > 0) {
                $subtotalItem = $subtotalItem * (1 - ($porcDesc / 100));
            }

            $itemFactura = [
                'Descripcion' => $item['Descripcion'] ?? $item['descripcion'] ?? '',
                'unidadMedida' => $item['unidadMedida'] ?? 'U',
                'ccosto1' => $item['ccosto1'] ?? '',
                'ccosto2' => $item['ccosto2'] ?? '',
                'Cantidad' => $cantidad,
                'ImporteUnitario' => $importeUnitario,
                'porcDesc' => $porcDesc,
                'IVA' => (string)$iva,
                'idPlanCuenta' => $item['idPlanCuenta'] ?? 'Ventas de mercaderías',
                'Comentario' => $item['Comentario'] ?? '',
                'subtotal' => round($subtotalItem, 2)
            ];

            // Si el item tiene idItem (producto de inventario), agregar campos adicionales
            if (!empty($item['idItem'])) {
                $itemFactura['idItem'] = $item['idItem'];
                
                if (isset($item['codigo'])) {
                    $itemFactura['codigo'] = $item['codigo'];
                }
                
                if (isset($item['tipoItem'])) {
                    $itemFactura['tipoItem'] = $item['tipoItem'];
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

        foreach (['0', '10.5', '21', '27'] as $alicuota) {
            $totalIVA += $totalesPorIVA[$alicuota]['importe'];
        }

        $totalIVA = round($totalIVA, 2);
        $totalFactura = $netoGravado + $netoNoGravado + $totalIVA + 
                        floatval($data['percepcionIVA'] ?? 0) + 
                        floatval($data['percepcionIIBB'] ?? 0);

        // Construir payload según documentación de Colppy editar_facturaventa
        $payload = [
            'auth' => [
                'usuario' => $this->authUsuario,
                'password' => md5($this->authPassword)
            ],
            'service' => [
                'provision' => 'FacturaVenta',
                'operacion' => 'editar_facturaventa'
            ],
            'parameters' => [
                'sesion' => [
                    'usuario' => $this->paramUsuario,
                    'claveSesion' => $claveSesion
                ],
                'idFactura' => $data['idFactura'],  // Campo OBLIGATORIO
                'descripcion' => $data['descripcion'] ?? '',
                'esresumen' => '0',
                'fechaFactura' => $data['fechaFactura'],
                'fechaPago' => $data['fechaPago'] ?? $data['fechaFactura'],
                'idCliente' => $data['idCliente'],
                'idCondicionIva' => $data['idCondicionIva'] ?? '1',
                'idCondicionPago' => $data['idCondicionPago'] ?? 'Contado',
                'idEmpresa' => $this->idEmpresa,
                'idEstadoAnterior' => $data['idEstadoAnterior'] ?? '',
                'idEstadoFactura' => $data['idEstadoFactura'] ?? 'Aprobada',
                'idMoneda' => $data['idMoneda'] ?? '1',
                'idPlanCuenta' => $data['idPlanCuenta'] ?? 'Ventas',
                'idTipoComprobante' => $data['idTipoComprobante'] ?? '4',
                'idTipoFactura' => $data['idTipoFactura'] ?? 'A',
                'labelfe' => $data['labelfe'] ?? '',
                'netoGravado' => number_format($netoGravado, 2, '.', ''),
                'netoNoGravado' => number_format($netoNoGravado, 2, '.', ''),
                'nroFactura1' => $data['nroFactura1'] ?? '',
                'nroFactura2' => $data['nroFactura2'] ?? '',
                'orderId' => $data['orderId'] ?? '',
                'percepcionIVA' => number_format(floatval($data['percepcionIVA'] ?? 0), 2, '.', ''),
                'percepcionIIBB' => number_format(floatval($data['percepcionIIBB'] ?? 0), 2, '.', ''),
                'IVA105' => number_format($totalesPorIVA['10.5']['importe'], 2, '.', ''),
                'IVA21' => number_format($totalesPorIVA['21']['importe'], 2, '.', ''),
                'IVA27' => number_format($totalesPorIVA['27']['importe'], 2, '.', ''),
                'itemsFactura' => $itemsFactura,
                'totalFactura' => number_format($totalFactura, 2, '.', ''),
                'totalIVA' => number_format($totalIVA, 2, '.', ''),
                'valorCambio' => $data['valorCambio'] ?? '1'
            ]
        ];

        // Agregar campos opcionales si están presentes
        if (isset($data['codigoActividad'])) {
            $payload['parameters']['codigoActividad'] = (int)$data['codigoActividad'];
        }
        
        if (isset($data['codigoOperacion'])) {
            $payload['parameters']['codigoOperacion'] = (int)$data['codigoOperacion'];
        }

        Log::info('🔧 PAYLOAD EDITARFACTURAVENTA', [
            'idFactura' => $data['idFactura'],
            'descripcion' => $payload['parameters']['descripcion'],
            'items_count' => count($itemsFactura),
            'totalFactura' => $payload['parameters']['totalFactura']
        ]);

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
     * Listar talonarios/resoluciones de un tipo de comprobante
     * Operación: listar_resoluciones (provision: Empresa)
     * 
     * @param string $idTipoComprobante Tipo de comprobante (FAV, NDV, NCV, REC, FE, FAV-FE, REM)
     * @return array ['success' => bool, 'datos' => array, 'total' => int, 'mensaje' => string]
     */
    public function listarTalonarios(string $idTipoComprobante = 'FAV-FE'): array
    {
        // Asegurar que existe una sesión activa
        $resultadoSesion = $this->obtenerClaveSesion();
        if (!$resultadoSesion['success']) {
            return $resultadoSesion;
        }

        $claveSesion = Session::get(self::SESSION_KEY);
        if (empty($claveSesion)) {
            return [
                'success' => false,
                'mensaje' => 'No se pudo obtener claveSesion para listar talonarios'
            ];
        }

        $payload = [
            'auth' => [
                'usuario' => $this->authUsuario,
                'password' => md5($this->authPassword)
            ],
            'service' => [
                'provision' => 'Empresa',
                'operacion' => 'listar_resoluciones'
            ],
            'parameters' => [
                'sesion' => [
                    'usuario' => $this->paramUsuario,
                    'claveSesion' => $claveSesion
                ],
                'idEmpresa' => $this->idEmpresa,
                'idTipoComprobante' => $idTipoComprobante
            ]
        ];

        Log::info('Listando talonarios desde Colppy', [
            'idTipoComprobante' => $idTipoComprobante
        ]);

        $resultado = $this->hacerLlamada($payload);

        if (isset($resultado['success']) && $resultado['success']) {
            $talonarios = $resultado['datos'] ?? [];
            Log::info('Talonarios obtenidos exitosamente', [
                'total' => count($talonarios),
                'tipo' => $idTipoComprobante,
                'response_completa' => $resultado  // Log de respuesta completa para debug
            ]);

            return [
                'success' => true,
                'datos' => $talonarios,
                'total' => count($talonarios),
                'response_raw' => $resultado  // Incluir respuesta completa para debugging
            ];
        }

        Log::error('Error al listar talonarios desde Colppy', [
            'mensaje' => $resultado['mensaje'] ?? 'Error desconocido',
            'idTipoComprobante' => $idTipoComprobante
        ]);

        return [
            'success' => false,
            'mensaje' => $resultado['mensaje'] ?? 'Error al listar talonarios',
            'datos' => []
        ];
    }

    /**
     * Obtener el próximo número disponible de un talonario específico
     * Busca el talonario por su prefijo (ej: '0002') y devuelve su proximoNum
     * 
     * @param string $prefijo Prefijo del talonario (ej: '0002', '0001', '0004')
     * @param string $idTipoComprobante Tipo de comprobante (FAV, NDV, NCV, FAV-FE, etc.)
     * @return array ['success' => bool, 'proximoNum' => string, 'talonario' => array, 'mensaje' => string]
     */
    public function obtenerProximoNumeroTalonario(
        string $prefijo = '0002',
        string $idTipoComprobante = 'FAV-FE'
    ): array {
        // Listar todos los talonarios del tipo especificado
        $resultadoListado = $this->listarTalonarios($idTipoComprobante);

        if (!$resultadoListado['success']) {
            return [
                'success' => false,
                'mensaje' => 'No se pudo listar talonarios: ' . ($resultadoListado['mensaje'] ?? 'Error desconocido')
            ];
        }

        $talonarios = $resultadoListado['datos'] ?? [];

        if (empty($talonarios)) {
            Log::warning('No se encontraron talonarios en Colppy', [
                'idTipoComprobante' => $idTipoComprobante
            ]);
            return [
                'success' => false,
                'mensaje' => 'No hay talonarios configurados en Colppy para el tipo ' . $idTipoComprobante
            ];
        }

        // Buscar el talonario con el prefijo especificado
        $talonarioEncontrado = null;
        foreach ($talonarios as $talonario) {
            if (isset($talonario['prefijo']) && $talonario['prefijo'] === $prefijo) {
                $talonarioEncontrado = $talonario;
                break;
            }
        }

        if ($talonarioEncontrado === null) {
            Log::warning('Talonario no encontrado', [
                'prefijo_buscado' => $prefijo,
                'idTipoComprobante' => $idTipoComprobante,
                'talonarios_disponibles' => array_column($talonarios, 'prefijo')
            ]);
            return [
                'success' => false,
                'mensaje' => "No se encontró un talonario con prefijo '{$prefijo}' en Colppy"
            ];
        }

        // Verificar que tenga próximo número configurado
        if (!isset($talonarioEncontrado['proximoNum']) || empty($talonarioEncontrado['proximoNum'])) {
            Log::error('Talonario sin próximo número configurado', [
                'prefijo' => $prefijo,
                'talonario' => $talonarioEncontrado
            ]);
            return [
                'success' => false,
                'mensaje' => "El talonario '{$prefijo}' no tiene un próximo número configurado en Colppy"
            ];
        }

        $proximoNum = $talonarioEncontrado['proximoNum'];

        Log::info('Próximo número de talonario obtenido exitosamente', [
            'prefijo' => $prefijo,
            'proximoNum' => $proximoNum,
            'descripcion' => $talonarioEncontrado['descripcion'] ?? 'Sin descripción'
        ]);

        return [
            'success' => true,
            'proximoNum' => $proximoNum,
            'talonario' => $talonarioEncontrado,
            'mensaje' => 'Próximo número obtenido correctamente'
        ];
    }

    /**
     * Obtener datos de tercero desde AFIP por CUIT
     * 
     * @param string $cuit CUIT del tercero (solo números, con o sin guiones)
     * @return array
     */
    public function obtenerDatosTerceroDeAfip(string $cuit): array
    {
        try {
            $resultadoSesion = $this->obtenerClaveSesion();
            if (!$resultadoSesion['success']) {
                return $resultadoSesion;
            }

            $claveSesion = Session::get(self::SESSION_KEY);
            if (empty($claveSesion)) {
                return [
                    'success' => false,
                    'mensaje' => 'No se pudo obtener la clave de sesión'
                ];
            }

            // Limpiar el CUIT (quitar guiones y espacios)
            $cuitLimpio = preg_replace('/[^0-9]/', '', $cuit);

            $payload = [
                'auth' => [
                    'usuario' => $this->authUsuario,
                    'password' => md5($this->authPassword)
                ],
                'service' => [
                    'provision' => 'Tercero',
                    'operacion' => 'obtener_datos_tercero_de_afip'
                ],
                'parameters' => [
                    'sesion' => [
                        'usuario' => $this->paramUsuario,
                        'claveSesion' => $claveSesion
                    ],
                    'cuit' => $cuitLimpio,
                    'idEmpresa' => $this->idEmpresa
                ]
            ];

            Log::info('ColppyService::obtenerDatosTerceroDeAfip - Request', [
                'cuit' => $cuitLimpio,
                'idEmpresa' => $this->idEmpresa
            ]);

            $response = Http::withOptions(['verify' => false])->timeout(30)->post($this->urlApi, $payload);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('ColppyService::obtenerDatosTerceroDeAfip - Response', [
                    'data' => $data
                ]);

                // Verificar si la operación fue exitosa
                $esExito = (isset($data['response']['success']) && $data['response']['success'] === true)
                    || (isset($data['result']['estado']) && (int) $data['result']['estado'] === 0);

                if ($esExito && isset($data['response']['data'])) {
                    return [
                        'success' => true,
                        'data' => $data['response']['data'],
                        'mensaje' => $data['response']['message'] ?? 'Datos obtenidos correctamente'
                    ];
                }

                // Si no fue exitoso, devolver el mensaje de error
                $mensajeError = $data['response']['message'] ?? $data['result']['mensaje'] ?? 'Error al obtener datos de AFIP';
                
                return [
                    'success' => false,
                    'mensaje' => $mensajeError,
                    'data' => $data
                ];
            }

            Log::error('ColppyService::obtenerDatosTerceroDeAfip - Error HTTP', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'mensaje' => 'Error de comunicación con Colppy: ' . $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('ColppyService::obtenerDatosTerceroDeAfip - Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'mensaje' => 'Error al consultar AFIP: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Crear cliente en Colppy
     * 
     * @param array $datosCliente Datos del cliente a crear
     * @return array
     */
    public function crearCliente(array $datosCliente): array
    {
        try {
            $resultadoSesion = $this->obtenerClaveSesion();
            if (!$resultadoSesion['success']) {
                return $resultadoSesion;
            }

            $claveSesion = Session::get(self::SESSION_KEY);
            if (empty($claveSesion)) {
                return [
                    'success' => false,
                    'mensaje' => 'No se pudo obtener la clave de sesión'
                ];
            }

            // Preparar datos obligatorios
            $infoGeneral = [
                'idEmpresa' => $this->idEmpresa,
                'RazonSocial' => $datosCliente['razon_social'] ?? '',
                'NombreFantasia' => $datosCliente['nombre_fantasia'] ?? $datosCliente['razon_social'] ?? '',
            ];

            // Agregar CUIT si existe
            if (!empty($datosCliente['cuit'])) {
                $infoGeneral['CUIT'] = preg_replace('/[^0-9]/', '', $datosCliente['cuit']);
            }

            // Agregar DNI si existe
            if (!empty($datosCliente['dni'])) {
                $infoGeneral['dni'] = preg_replace('/[^0-9]/', '', $datosCliente['dni']);
            }

            // Agregar datos opcionales de contacto
            if (!empty($datosCliente['email'])) {
                $infoGeneral['Email'] = $datosCliente['email'];
            }

            if (!empty($datosCliente['telefono'])) {
                $infoGeneral['Telefono'] = $datosCliente['telefono'];
            }

            // Agregar datos de dirección postal
            if (!empty($datosCliente['direccion'])) {
                $infoGeneral['DirPostal'] = $datosCliente['direccion'];
            }

            if (!empty($datosCliente['ciudad'])) {
                $infoGeneral['DirPostalCiudad'] = $datosCliente['ciudad'];
            }

            if (!empty($datosCliente['codigo_postal'])) {
                $infoGeneral['DirPostalCodigoPostal'] = $datosCliente['codigo_postal'];
            }

            if (!empty($datosCliente['provincia'])) {
                $infoGeneral['DirPostalProvincia'] = $datosCliente['provincia'];
            }

            if (!empty($datosCliente['pais'])) {
                $infoGeneral['DirPostalPais'] = $datosCliente['pais'];
            }

            // Información adicional (obligatoria)
            $infoOtra = [
                'Activo' => '1',
                'FechaAlta' => '',  // Colppy asigna automáticamente
                'DirFiscal' => '',
                'DirFiscalCiudad' => '',
                'DirFiscalCodigoPostal' => '',
                'DirFiscalProvincia' => '',
                'DirFiscalPais' => '',
                'idCondicionPago' => '',  // Condición de pago por defecto
                'porcentajeIVA' => '',
                'idPlanCuenta' => '',  // Cuenta de ingresos
                'CuentaCredito' => '',
                'DirEnvio' => '',
                'DirEnvioCiudad' => '',
                'DirEnvioCodigoPostal' => '',
                'DirEnvioProvincia' => '',
                'DirEnvioPais' => ''
            ];

            // Agregar condición de IVA si existe
            if (!empty($datosCliente['id_condicion_iva'])) {
                $infoOtra['idCondicionIva'] = $datosCliente['id_condicion_iva'];
            }

            $payload = [
                'auth' => [
                    'usuario' => $this->authUsuario,
                    'password' => md5($this->authPassword)
                ],
                'service' => [
                    'provision' => 'Cliente',
                    'operacion' => 'alta_cliente'
                ],
                'parameters' => [
                    'sesion' => [
                        'usuario' => $this->paramUsuario,
                        'claveSesion' => $claveSesion
                    ],
                    'info_general' => $infoGeneral,
                    'info_otra' => $infoOtra
                ]
            ];

            Log::info('ColppyService::crearCliente - Request', [
                'info_general' => $infoGeneral
            ]);

            $response = Http::withOptions(['verify' => false])->timeout(30)->post($this->urlApi, $payload);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('ColppyService::crearCliente - Response', [
                    'data' => $data
                ]);

                // Verificar si la operación fue exitosa
                $esExito = (isset($data['response']['success']) && $data['response']['success'] === true)
                    || (isset($data['result']['estado']) && (int) $data['result']['estado'] === 0);

                if ($esExito) {
                    // Colppy puede devolver 'idcliente' o 'idCliente'
                    $idCliente = $data['response']['data']['idCliente'] 
                              ?? $data['response']['data']['idcliente'] 
                              ?? null;
                    
                    if ($idCliente) {
                        return [
                            'success' => true,
                            'idCliente' => $idCliente,
                            'mensaje' => $data['response']['message'] ?? 'Cliente creado correctamente'
                        ];
                    }
                }

                // Si no fue exitoso, devolver el mensaje de error
                $mensajeError = $data['response']['message'] ?? $data['result']['mensaje'] ?? 'Error al crear cliente';
                
                return [
                    'success' => false,
                    'mensaje' => $mensajeError,
                    'data' => $data
                ];
            }

            Log::error('ColppyService::crearCliente - Error HTTP', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'mensaje' => 'Error de comunicación con Colppy: ' . $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('ColppyService::crearCliente - Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'mensaje' => 'Error al crear cliente: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Invalidar sesión actual (limpiar de SESSION)
     */
    public function invalidarSesion(): void
    {
        Session::forget(self::SESSION_KEY);
        // Log::info('Sesión de Colppy invalidada');
    }

    /**
     * Generar PDF de un presupuesto/factura NO electrónica desde Colppy
     * 
     * @param string $idFactura ID de la factura en Colppy
     * @param string $idCliente ID del cliente en Colppy
     * @return array {success: bool, pdf?: string (binary), mensaje?: string}
     */
    public function generateBudgetPdf(string $idFactura, string $idCliente): array
    {
        try {
            // Validar parámetros obligatorios
            if (empty($idFactura) || empty($idCliente)) {
                return [
                    'success' => false,
                    'mensaje' => 'Faltan parámetros obligatorios (idFactura, idCliente)'
                ];
            }

            // Validar configuración
            if (empty($this->idEmpresa) || empty($this->paramUsuario)) {
                return [
                    'success' => false,
                    'mensaje' => 'Configuración de Colppy incompleta'
                ];
            }

            // URL del endpoint para facturas NO electrónicas
            // NOTA: Puede ser staging.colppy.com o login.colppy.com según la empresa
            // Intentaremos con login primero ya que es la URL configurada para esta empresa
            $baseUrl = 'https://login.colppy.com';
            $url = $baseUrl . '/resources/php/clientes/AR_ImprimirFactura.php';

            // Parámetros GET
            $params = [
                'idEmpresa' => $this->idEmpresa,
                'idCliente' => $idCliente,
                'idFactura' => $idFactura,
                'idUsuario' => $this->paramUsuario,
                'correo' => 'no' // No enviar por email
            ];

            Log::info('ColppyService::generateBudgetPdf - Solicitando PDF', [
                'params' => $params
            ]);

            // Hacer petición GET
            $response = Http::withOptions([
                'verify' => false,
                'timeout' => 60 // Mayor timeout para descarga de PDF
            ])->get($url, $params);

            // Verificar respuesta
            if ($response->successful()) {
                $contentType = $response->header('Content-Type');

                // Verificar que la respuesta sea un PDF
                if (strpos($contentType, 'application/pdf') !== false) {
                    Log::info('ColppyService::generateBudgetPdf - PDF generado correctamente');

                    return [
                        'success' => true,
                        'pdf' => $response->body(), // Contenido binario del PDF
                        'content_type' => $contentType
                    ];
                } else {
                    // Si no es PDF, probablemente sea un error en HTML o JSON
                    Log::error('ColppyService::generateBudgetPdf - Respuesta no es PDF', [
                        'content_type' => $contentType,
                        'body' => substr($response->body(), 0, 500)
                    ]);

                    return [
                        'success' => false,
                        'mensaje' => 'La respuesta de Colppy no es un PDF. Verifique los parámetros.'
                    ];
                }
            }

            // Error HTTP (403, 404, etc.)
            Log::error('ColppyService::generateBudgetPdf - Error HTTP', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500)
            ]);

            $errorMessage = 'Error al generar PDF';

            if ($response->status() === 403) {
                $errorMessage = 'Acceso denegado. Verifique las credenciales y permisos en Colppy.';
            } elseif ($response->status() === 404) {
                $errorMessage = 'Presupuesto no encontrado en Colppy.';
            }

            return [
                'success' => false,
                'mensaje' => $errorMessage,
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('ColppyService::generateBudgetPdf - Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'mensaje' => 'Error al generar PDF: ' . $e->getMessage()
            ];
        }
    }
}
