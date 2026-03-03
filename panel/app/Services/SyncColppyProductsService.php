<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SyncColppyProductsService
{
    private $colppyService;

    public function __construct()
    {
        $this->colppyService = new ColppyService();
    }

    /**
     * Sincronizar productos de Colppy a base de datos local
     * Solo sincroniza productos tipo "P" (no servicios ni kits)
     * 
     * @return array Resultado de la sincronización
     */
    public function syncProducts(): array
    {
        try {
            Log::info('=== INICIO SINCRONIZACIÓN PRODUCTOS COLPPY ===');
            
            $productosSincronizados = 0;
            $productosActualizados = 0;
            $errores = 0;
            $start = 0;
            $limit = 100; // Traer de a 100 para no saturar
            $totalProcesados = 0;

            do {
                // Obtener productos de Colppy (solo tipo "P")
                $resultado = $this->colppyService->listarInventario($start, $limit, [], []);
                
                if (!$resultado['success']) {
                    Log::error('Error al obtener productos de Colppy', ['resultado' => $resultado]);
                    break;
                }

                $productos = $resultado['datos'] ?? [];
                $total = $resultado['total'] ?? 0;

                if (empty($productos)) {
                    break;
                }

                // IMPORTANTE: Colppy a veces devuelve el primer elemento como array de headers (nombres de columnas)
                // Necesitamos detectar y saltar ese elemento
                if (isset($productos[0]) && is_array($productos[0])) {
                    $primerElemento = $productos[0];
                    // Si el primer elemento es un array numérico de strings, son los headers
                    if (isset($primerElemento[0]) && is_string($primerElemento[0]) && $primerElemento[0] === 'idItem') {
                        array_shift($productos); // Remover el primer elemento (headers)
                    }
                }

                // Procesar cada producto
                foreach ($productos as $index => $productoColppy) {
                    try {
                        // Solo productos tipo "P"
                        if (isset($productoColppy['tipoItem']) && $productoColppy['tipoItem'] === 'P') {
                            $resultado = $this->syncProduct($productoColppy);
                            
                            if ($resultado['creado']) {
                                $productosSincronizados++;
                            } elseif ($resultado['actualizado']) {
                                $productosActualizados++;
                            }
                            
                            $totalProcesados++;
                        }
                    } catch (\Exception $e) {
                        $errores++;
                        Log::error('Error al sincronizar producto', [
                            'idItem' => $productoColppy['idItem'] ?? 'desconocido',
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                $start += $limit;

            } while ($totalProcesados < $total);

            Log::info('=== FIN SINCRONIZACIÓN PRODUCTOS COLPPY ===', [
                'nuevos' => $productosSincronizados,
                'actualizados' => $productosActualizados,
                'errores' => $errores,
                'total' => $totalProcesados
            ]);

            return [
                'success' => true,
                'nuevos' => $productosSincronizados,
                'actualizados' => $productosActualizados,
                'errores' => $errores,
                'total' => $totalProcesados
            ];

        } catch (\Exception $e) {
            Log::error('Error general en sincronización de productos', ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'mensaje' => $e->getMessage()
            ];
        }
    }

    /**
     * Sincronizar un producto individual
     * 
     * @param array $productoColppy Datos del producto desde Colppy
     * @return array ['creado' => bool, 'actualizado' => bool, 'product_id' => int]
     */
    private function syncProduct(array $productoColppy): array
    {
        $idColppy = $productoColppy['idItem'] ?? null;
        
        if (!$idColppy) {
            throw new \Exception('Producto sin idItem');
        }

        // Buscar si ya existe
        $productoExistente = Product::where('colppy_id', $idColppy)->first();

        // Preparar datos del producto
        $datosProducto = $this->transformarDatosColppy($productoColppy);

        $creado = false;
        $actualizado = false;

        if ($productoExistente) {
            // Verificar si necesita actualización comparando fechas
            $fechaColppy = $datosProducto['colppy_updated_at'];
            $fechaLocal = $productoExistente->colppy_updated_at;
            
            // Solo actualizar si:
            // 1. No tenemos fecha local guardada (primera sincronización después de agregar el campo)
            // 2. La fecha de Colppy es más reciente que la local
            if (!$fechaLocal || ($fechaColppy && $fechaColppy > $fechaLocal)) {
                $productoExistente->update($datosProducto);
                $actualizado = true;
            }
            
            $productoId = $productoExistente->id;
        } else {
            // Crear nuevo producto
            $producto = Product::create($datosProducto);
            $productoId = $producto->id;
            $creado = true;
        }

        return [
            'creado' => $creado,
            'actualizado' => $actualizado,
            'product_id' => $productoId
        ];
    }

    /**
     * Transformar datos de Colppy a formato local
     * 
     * @param array $producto Datos del producto desde Colppy
     * @return array Datos transformados
     */
    private function transformarDatosColppy(array $producto): array
    {
        return [
            'colppy_id' => $producto['idItem'] ?? null,
            'colppy_empresa_id' => $producto['idEmpresa'] ?? null,
            'codigo' => $producto['codigo'] ?? null,
            'descripcion' => $producto['descripcion'] ?? null,
            'detalle' => $producto['detalle'] ?? null,
            'cta_costo_ventas' => $producto['ctaCostoVentas'] ?? null,
            'cta_ingreso_ventas' => $producto['ctaIngresoVentas'] ?? null,
            'cta_inventario' => $producto['ctaInventario'] ?? null,
            'desc_cta_inventario' => $producto['descCtaInventario'] ?? null,
            'desc_cta_costo_ventas' => $producto['descCtaCostoVentas'] ?? null,
            'desc_cta_ingreso_ventas' => $producto['descCtaIngresoVentas'] ?? null,
            'stock_minimo' => $producto['minimo'] ?? 0,
            'disponibilidad' => $producto['disponibilidad'] ?? 0,
            'costo_calculado' => $producto['costoCalculado'] ?? 0,
            'ultimo_precio_compra' => $producto['ultimoPrecioCompra'] ?? 0,
            'precio_venta' => $producto['precioVenta'] ?? null,
            'iva' => $producto['iva'] ?? null,
            'unidad_medida' => $producto['unidadMedida'] ?? null,
            'tipo_item' => $producto['tipoItem'] ?? 'P',
            'es_kit' => ($producto['esKit'] ?? '0') === '1',
            'fecha_alta' => !empty($producto['fechaAlta']) ? $producto['fechaAlta'] : null,
            'fecha_baja' => !empty($producto['fechaBaja']) ? $producto['fechaBaja'] : null,
            'comentario_factura' => $producto['comentarioFactura'] ?? null,
            'is_from_colppy' => true,
            'colppy_updated_at' => !empty($producto['record_update_ts']) ? $producto['record_update_ts'] : null,
        ];
    }

    /**
     * Sincronizar un producto específico por su ID de Colppy
     * 
     * @param string $idItem ID del item en Colppy
     * @return array Resultado de la sincronización
     */
    public function syncProductById(string $idItem): array
    {
        try {
            // Obtener el producto desde Colppy
            $resultado = $this->colppyService->obtenerItemInventario($idItem);
            
            if (!$resultado['success']) {
                return [
                    'success' => false,
                    'mensaje' => 'No se pudo obtener el producto de Colppy'
                ];
            }

            $productoColppy = $resultado['datos'] ?? null;
            
            if (!$productoColppy) {
                return [
                    'success' => false,
                    'mensaje' => 'Producto no encontrado en Colppy'
                ];
            }

            // Solo sincronizar si es tipo "P"
            if (($productoColppy['tipoItem'] ?? '') !== 'P') {
                return [
                    'success' => false,
                    'mensaje' => 'El item no es un producto (tipo P)'
                ];
            }

            $resultado = $this->syncProduct($productoColppy);
            
            return [
                'success' => true,
                'creado' => $resultado['creado'],
                'actualizado' => $resultado['actualizado'],
                'product_id' => $resultado['product_id']
            ];

        } catch (\Exception $e) {
            Log::error('Error al sincronizar producto por ID', [
                'idItem' => $idItem,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'mensaje' => $e->getMessage()
            ];
        }
    }
}
