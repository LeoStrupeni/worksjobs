<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Clients_Addres;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SyncColppyClientsService
{
    private $colppyService;

    public function __construct()
    {
        $this->colppyService = new ColppyService();
    }

    /**
     * Sincronizar clientes de Colppy a base de datos local
     * 
     * IMPORTANTE: La API de Colppy NO devuelve el campo "Activo" correctamente
     * a menos que se filtre explícitamente. Por eso hacemos DOS consultas:
     * 1. Clientes ACTIVOS (Activo = 1)
     * 2. Clientes INACTIVOS (Activo = 0)
     * 
     * @return array Resultado de la sincronización
     */
    public function syncClients(): array
    {
        try {
            // Log::info('=== INICIO SINCRONIZACIÓN CLIENTES COLPPY (CON FILTRO DE ESTADO) ===');
            
            $clientesSincronizados = 0;
            $clientesActualizados = 0;
            $errores = 0;
            $totalProcesados = 0;

            // ==========================================
            // PASO 1: Sincronizar CLIENTES ACTIVOS
            // ==========================================
            $resultadoActivos = $this->syncClientesPorEstado(1); // Activo = 1
            
            $clientesSincronizados += $resultadoActivos['nuevos'];
            $clientesActualizados += $resultadoActivos['actualizados'];
            $errores += $resultadoActivos['errores'];
            $totalProcesados += $resultadoActivos['total'];

            // Log::info('Clientes ACTIVOS sincronizados', [
            //     'nuevos' => $resultadoActivos['nuevos'],
            //     'actualizados' => $resultadoActivos['actualizados'],
            //     'total' => $resultadoActivos['total']
            // ]);

            // ==========================================
            // PASO 2: Sincronizar CLIENTES INACTIVOS
            // ==========================================
            $resultadoInactivos = $this->syncClientesPorEstado(0); // Activo = 0
            
            $clientesSincronizados += $resultadoInactivos['nuevos'];
            $clientesActualizados += $resultadoInactivos['actualizados'];
            $errores += $resultadoInactivos['errores'];
            $totalProcesados += $resultadoInactivos['total'];

            // Log::info('Clientes INACTIVOS sincronizados', [
            //     'nuevos' => $resultadoInactivos['nuevos'],
            //     'actualizados' => $resultadoInactivos['actualizados'],
            //     'total' => $resultadoInactivos['total']
            // ]);

            // Log::info('=== FIN SINCRONIZACIÓN CLIENTES COLPPY ===', [
            //     'nuevos' => $clientesSincronizados,
            //     'actualizados' => $clientesActualizados,
            //     'errores' => $errores,
            //     'total' => $totalProcesados,
            //     'activos' => $resultadoActivos['total'],
            //     'inactivos' => $resultadoInactivos['total']
            // ]);

            return [
                'success' => true,
                'nuevos' => $clientesSincronizados,
                'actualizados' => $clientesActualizados,
                'errores' => $errores,
                'total' => $totalProcesados,
                'activos' => $resultadoActivos['total'],
                'inactivos' => $resultadoInactivos['total']
            ];

        } catch (\Exception $e) {
            Log::error('Error general en sincronización', ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'mensaje' => $e->getMessage()
            ];
        }
    }

    /**
     * Sincronizar clientes por estado (activo/inactivo)
     * 
     * @param int $estadoActivo 1 = Activo, 0 = Inactivo
     * @return array Resultado de la sincronización
     */
    private function syncClientesPorEstado(int $estadoActivo): array
    {
        $clientesSincronizados = 0;
        $clientesActualizados = 0;
        $errores = 0;
        $start = 0;
        $limit = 100;
        $totalProcesados = 0;

        // Filtro para obtener solo clientes con estado específico
        $filtros = [
            [
                'field' => 'Activo',
                'op' => '=',
                'value' => (string)$estadoActivo
            ]
        ];

        do {
            // Obtener clientes de Colppy con filtro de estado
            $resultado = $this->colppyService->listarClientes($start, $limit, $filtros, []);
            
            if (!$resultado['success']) {
                Log::error('Error al obtener clientes de Colppy', [
                    'estado' => $estadoActivo,
                    'resultado' => $resultado
                ]);
                break;
            }

            $clientes = $resultado['datos'] ?? [];
            $total = $resultado['total'] ?? 0;

            if (empty($clientes)) {
                break;
            }

            // Remover headers si están presentes
            if (isset($clientes[0]) && is_array($clientes[0])) {
                $primerElemento = $clientes[0];
                if (isset($primerElemento[0]) && is_string($primerElemento[0]) && $primerElemento[0] === 'idCliente') {
                    array_shift($clientes);
                }
            }

            // Procesar cada cliente
            foreach ($clientes as $index => $clienteColppy) {
                try {
                    // Pasar el estado explícitamente
                    $resultado = $this->syncCliente($clienteColppy, $estadoActivo);
                    
                    if ($resultado['creado']) {
                        $clientesSincronizados++;
                    } elseif ($resultado['actualizado']) {
                        $clientesActualizados++;
                    }
                    
                    $totalProcesados++;
                } catch (\Exception $e) {
                    $errores++;
                    Log::error('Error al sincronizar cliente', [
                        'idCliente' => $clienteColppy['idCliente'] ?? 'desconocido',
                        'estado' => $estadoActivo,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $start += $limit;

        } while ($totalProcesados < $total);

        return [
            'nuevos' => $clientesSincronizados,
            'actualizados' => $clientesActualizados,
            'errores' => $errores,
            'total' => $totalProcesados
        ];
    }

    /**
     * Sincronizar un cliente individual
     * 
     * @param array $clienteColppy Datos del cliente desde Colppy
     * @param int $estadoActivo Estado del cliente (1 = activo, 0 = inactivo)
     * @return array ['creado' => bool, 'actualizado' => bool, 'client_id' => int]
     */
    private function syncCliente(array $clienteColppy, int $estadoActivo): array
    {
        $idColppy = $clienteColppy['idCliente'] ?? null;
        
        if (!$idColppy) {
            throw new \Exception('Cliente sin IdCliente');
        }

        // Buscar si ya existe
        $clienteExistente = Client::where('colppy_id', $idColppy)->first();

        // Preparar datos del cliente (pasar estado explícitamente)
        $datosCliente = $this->transformarDatosColppy($clienteColppy, $estadoActivo);

        $creado = false;
        $actualizado = false;

        if ($clienteExistente) {
            // Verificar si necesita actualización comparando fechas
            $fechaColppy = $datosCliente['colppy_updated_at'];
            $fechaLocal = $clienteExistente->colppy_updated_at;
            
            // IMPORTANTE: Siempre actualizar el estado (is_active)
            // porque la API de Colppy no lo devuelve en consultas normales
            $clienteExistente->update($datosCliente);
            $actualizado = true;
            
            $clienteId = $clienteExistente->id;
        } else {
            // Crear nuevo cliente
            $cliente = Client::create($datosCliente);
            $clienteId = $cliente->id;
            $creado = true;
        }

        // Sincronizar domicilios
        $this->syncDomicilios($clienteId, $clienteColppy);

        return [
            'creado' => $creado,
            'actualizado' => $actualizado,
            'client_id' => $clienteId
        ];
    }

    /**
     * Transformar datos de Colppy a formato local
     * 
     * @param array $cliente Datos del cliente desde Colppy
     * @param int $estadoActivo Estado del cliente (1 = activo, 0 = inactivo)
     * @return array Datos transformados
     */
    private function transformarDatosColppy(array $cliente, int $estadoActivo): array
    {
        // Determinar tipo de documento
        $typeDoc = 3; // Por defecto CUIT
        if (!empty($cliente['CUIT'])) {
            $typeDoc = 3; // CUIT
            // Remover guiones y espacios del CUIT
            $numDoc = preg_replace('/[^0-9]/', '', $cliente['CUIT']);
        } elseif (!empty($cliente['DNI'])) {
            $typeDoc = 1; // DNI
            $numDoc = preg_replace('/[^0-9]/', '', $cliente['DNI']);
        } else {
            $numDoc = '0'; // Valor por defecto si no hay CUIT ni DNI
        }

        return [
            'colppy_id' => $cliente['idCliente'] ?? null,
            'first_name' => $cliente['RazonSocial'] ?? '',
            'last_name' => '', // Colppy no tiene apellido separado
            'nombre_fantasia' => $cliente['NombreFantasia'] ?? null,
            'type_doc' => $typeDoc,
            'num_doc' => $numDoc ?? '',
            'email' => $cliente['Email'] ?? null,
            'phone1' => $cliente['Telefono'] ?? null,
            'phone2' => null, // Colppy no tiene phone2 en la estructura básica
            'fax' => $cliente['Fax'] ?? null,
            'country' => 'Argentina', // Por defecto
            'state' => $cliente['DirPostalProvincia'] ?? null,
            'city' => $cliente['DirPostalCiudad'] ?? null,
            'cp' => null, // Agregar si está en los datos
            'address_street' => null,
            'address_nro' => null,
            'address_apartament' => null,
            'address_detail' => null,
            'other_obs' => null,
            'is_from_colppy' => true,
            'is_active' => $estadoActivo, // Usar el estado pasado explícitamente
            'colppy_updated_at' => !empty($cliente['record_update_ts']) ? $cliente['record_update_ts'] : null,
        ];
    }

    /**
     * Sincronizar domicilios del cliente
     * 
     * @param int $clientId ID del cliente local
     * @param array $clienteColppy Datos del cliente desde Colppy
     * @return void
     */
    private function syncDomicilios(int $clientId, array $clienteColppy): void
    {
        // ============================================
        // DOMICILIO POSTAL - Solo guardar si DirPostal tiene contenido
        // ============================================
        $dirPostal = [
            'country' => 'Argentina',
            'state' => $clienteColppy['DirPostalProvincia'] ?? null,
            'city' => $clienteColppy['DirPostalCiudad'] ?? null,
            'cp' => $clienteColppy['DirPostalCodigoPostal'] ?? null,
            'address_street' => $clienteColppy['DirPostal'] ?? null,
            'address_nro' => null,
            'address_apartament' => null,
            'address_detail' => 'Domicilio Postal (Colppy)',
        ];

        // Solo crear si DirPostal tiene contenido (NO vacío y NO null)
        if (!empty(trim($dirPostal['address_street'] ?? ''))) {
            // Verificar si ya existe este domicilio
            $existeDirPostal = Clients_Addres::where('client_id', $clientId)
                ->where('address_detail', 'Domicilio Postal (Colppy)')
                ->whereNull('deleted_at')
                ->first();

            if ($existeDirPostal) {
                $existeDirPostal->update($dirPostal);
            } else {
                Clients_Addres::create(array_merge($dirPostal, ['client_id' => $clientId]));
            }
        }

        // ============================================
        // DOMICILIO FISCAL - Solo guardar si DirFiscal tiene contenido
        // ============================================
        $dirFiscal = [
            'country' => 'Argentina',
            'state' => $clienteColppy['DirFiscalProvincia'] ?? null,
            'city' => $clienteColppy['DirFiscalCiudad'] ?? null,
            'cp' => $clienteColppy['DirFiscalCodigoPostal'] ?? null,
            'address_street' => $clienteColppy['DirFiscal'] ?? null,
            'address_nro' => null,
            'address_apartament' => null,
            'address_detail' => 'Domicilio Fiscal (Colppy)',
        ];

        // Solo crear si DirFiscal tiene contenido Y es diferente al postal
        $tieneDirFiscal = !empty(trim($dirFiscal['address_street'] ?? ''));
        $esDiferenteAlPostal = ($dirFiscal['address_street'] ?? '') != ($dirPostal['address_street'] ?? '') ||
                               ($dirFiscal['city'] ?? '') != ($dirPostal['city'] ?? '');

        if ($tieneDirFiscal && $esDiferenteAlPostal) {
            // Verificar si ya existe este domicilio
            $existeDirFiscal = Clients_Addres::where('client_id', $clientId)
                ->where('address_detail', 'Domicilio Fiscal (Colppy)')
                ->whereNull('deleted_at')
                ->first();

            if ($existeDirFiscal) {
                $existeDirFiscal->update($dirFiscal);
            } else {
                Clients_Addres::create(array_merge($dirFiscal, ['client_id' => $clientId]));
            }
        }
    }
}
