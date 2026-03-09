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
                                ->orwhere('num_doc','LIKE',"%$search%");
                            })
                            ->limit(10)->get();
                    break;
                case 'hibrido':
                    $respuesta = Client::where('first_name','LIKE',"%$search%")
                            ->orwhere('last_name','LIKE',"%$search%")
                            ->orwhere('num_doc','LIKE',"%$search%")
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
                                ->orwhere('num_doc','LIKE',"%$search%");
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

}
