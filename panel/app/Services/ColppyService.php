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
            Log::warning('Configuración de Colppy incompleta');
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

                $esExito = ($data['exito'] ?? false)
                    || (isset($data['result']['estado']) && (int) $data['result']['estado'] === 0)
                    || (isset($data['response']['success']) && $data['response']['success'] === true);

                if ($esExito) {
                    // Leer datos desde response.data (formato Colppy)
                    $datos = $data['response']['data'] 
                        ?? $data['datos'] 
                        ?? [];
                    
                    // Leer total desde response.total
                    $total = isset($data['response']['total']) 
                        ? (int) $data['response']['total'] 
                        : null;

                    return [
                        'success' => true,
                        'datos' => $datos,
                        'total' => $total
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
            [
                'field' => 'tipoItem',
                'comparison' => 'eq',
                'value' => 'P'
            ]
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
     * Invalidar sesión actual (limpiar de SESSION)
     */
    public function invalidarSesion(): void
    {
        Session::forget(self::SESSION_KEY);
        Log::info('Sesión de Colppy invalidada');
    }
}
