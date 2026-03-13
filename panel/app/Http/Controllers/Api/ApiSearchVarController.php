<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Clients_Addres;
use App\Models\Config;
use App\Models\Product;
use Illuminate\Http\Request;


class ApiSearchVarController extends Controller
{
    public function searchvar(Request $request)
    {   
        $search = $request->search;
        $tipo = $request->tipo;
        $respuesta = [];
        if($tipo == 'clients'){
            $modo = Config::where('name', 'colppy_clientes_modo')->value('value') ?? 'local';
    
            switch ($modo) {
                case 'api':
                    $respuesta = Client::wherenull('deleted_at')
                            ->where('is_from_colppy', 1)
                            ->where(function($query) use ($search) {
                                $query->where('first_name','LIKE',"%$search%")
                                ->orwhere('last_name','LIKE',"%$search%")
                                ->orwhere('num_doc','LIKE',"%$search%")
                                ->orwhere('id','LIKE',"%$search%");
                            })
                            ->limit(10)->get();
                    break;
                case 'hibrido':
                    $respuesta = Client::where('first_name','LIKE',"%$search%")
                            ->orwhere('last_name','LIKE',"%$search%")
                            ->orwhere('num_doc','LIKE',"%$search%")
                            ->orwhere('id','LIKE',"%$search%")
                            ->limit(10)
                            ->get();
                    break;
                default:
                    $respuesta = Client::wherenull('deleted_at')
                        ->where(function($query) {
                            $query->where('is_from_colppy', '!=', 1)
                                  ->orWhereNull('is_from_colppy');
                        })
                        ->where(function($query) use ($search) {
                                $query->where('first_name','LIKE',"%$search%")
                                ->orwhere('last_name','LIKE',"%$search%")
                                ->orwhere('num_doc','LIKE',"%$search%")
                                ->orwhere('id','LIKE',"%$search%");
                            })
                        ->limit(10)->get();
            }

        } elseif($tipo == 'address') {
            // Buscar en tabla clients_address
            $respuesta = Clients_Addres::where('client_id', $search)
                ->whereNull('deleted_at')
                ->get();
        } else if($tipo == 'products') {
            $respuesta = Product::whereNull('deleted_at')
                ->where('tipo_item', 'P')  // Solo mostrar productos, no servicios ni kits
                ->where(function($query) use ($search) {
                    $query->where('codigo', 'LIKE', "%$search%")
                          ->orWhere('descripcion', 'LIKE', "%$search%");
                })
                ->limit(10)
                ->get();
        }

        return $respuesta;
    }

    /**
     * Buscar cliente por colppy_id
     */
    public function getClientByColppyId(Request $request)
    {
        try {
            $colppyId = $request->input('colppy_id');
            
            if (!$colppyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'colppy_id es requerido'
                ], 400);
            }
            
            $client = Client::where('colppy_id', $colppyId)
                ->whereNull('deleted_at')
                ->first();
            
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'client' => [
                    'id' => $client->id,
                    'first_name' => $client->first_name,
                    'last_name' => $client->last_name,
                    'colppy_id' => $client->colppy_id
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar cliente: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Buscar productos por array de colppy_ids
     */
    public function getProductsByColppyIds(Request $request)
    {
        try {
            $colppyIds = $request->input('colppy_ids', []);
            
            if (!is_array($colppyIds) || empty($colppyIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'colppy_ids debe ser un array no vacío'
                ], 400);
            }
            
            // Usar colppy_id que es el nombre real de la columna en BD
            $products = Product::whereIn('colppy_id', $colppyIds)
                ->whereNull('deleted_at')
                ->get();
            
            return response()->json([
                'success' => true,
                'products' => $products->map(function($product) {
                    return [
                        'id' => $product->id,
                        'codigo' => $product->codigo,
                        'descripcion' => $product->descripcion,
                        'idcolppy' => $product->idcolppy  // Accessor que mapea a colppy_id
                    ];
                })
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar productos: ' . $e->getMessage()
            ], 500);
        }
    }

}
