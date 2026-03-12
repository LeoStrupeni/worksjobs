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
     * @return array Resultado de la sincronización
     */
    public function syncClients(): array
    {
        try {
            // Log::info('=== INICIO SINCRONIZACIÓN CLIENTES COLPPY ===');
            
            $clientesSincronizados = 0;
            $clientesActualizados = 0;
            $errores = 0;
            $start = 0;
            $limit = 100; // Traer de a 100 para no saturar
            $totalProcesados = 0;

            do {
                // Obtener clientes de Colppy
                $resultado = $this->colppyService->listarClientes($start, $limit, [], []);
                
                if (!$resultado['success']) {
                    Log::error('Error al obtener clientes de Colppy', ['resultado' => $resultado]);
                    break;
                }

                $clientes = $resultado['datos'] ?? [];
                $total = $resultado['total'] ?? 0;

                if (empty($clientes)) {
                    break;
                }

                // IMPORTANTE: Colppy a veces devuelve el primer elemento como array de headers (nombres de columnas)
                // Necesitamos detectar y saltar ese elemento
                if (isset($clientes[0]) && is_array($clientes[0])) {
                    $primerElemento = $clientes[0];
                    // Si el primer elemento es un array numérico de strings, son los headers
                    if (isset($primerElemento[0]) && is_string($primerElemento[0]) && $primerElemento[0] === 'idCliente') {
                        array_shift($clientes); // Remover el primer elemento (headers)
                    }
                }

                // Procesar cada cliente
                foreach ($clientes as $index => $clienteColppy) {
                    try {
                        $resultado = $this->syncCliente($clienteColppy);
                        
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
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                $start += $limit;

            } while ($totalProcesados < $total);

            // Log::info('=== FIN SINCRONIZACIÓN CLIENTES COLPPY ===', [
            //     'nuevos' => $clientesSincronizados,
            //     'actualizados' => $clientesActualizados,
            //     'errores' => $errores,
            //     'total' => $totalProcesados
            // ]);

            return [
                'success' => true,
                'nuevos' => $clientesSincronizados,
                'actualizados' => $clientesActualizados,
                'errores' => $errores,
                'total' => $totalProcesados
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
     * Sincronizar un cliente individual
     * 
     * @param array $clienteColppy Datos del cliente desde Colppy
     * @return array ['creado' => bool, 'actualizado' => bool, 'client_id' => int]
     */
    private function syncCliente(array $clienteColppy): array
    {
        $idColppy = $clienteColppy['idCliente'] ?? null;
        
        if (!$idColppy) {
            throw new \Exception('Cliente sin IdCliente');
        }

        // Buscar si ya existe
        $clienteExistente = Client::where('colppy_id', $idColppy)->first();

        // Preparar datos del cliente
        $datosCliente = $this->transformarDatosColppy($clienteColppy);

        $creado = false;
        $actualizado = false;

        if ($clienteExistente) {
            // Verificar si necesita actualización comparando fechas
            $fechaColppy = $datosCliente['colppy_updated_at'];
            $fechaLocal = $clienteExistente->colppy_updated_at;
            
            // Solo actualizar si:
            // 1. No tenemos fecha local guardada (primera sincronización después de agregar el campo)
            // 2. La fecha de Colppy es más reciente que la local
            if (!$fechaLocal || ($fechaColppy && $fechaColppy > $fechaLocal)) {
                $clienteExistente->update($datosCliente);
                $actualizado = true;
            }
            
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
     * @return array Datos transformados
     */
    private function transformarDatosColppy(array $cliente): array
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
