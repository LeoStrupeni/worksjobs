<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Clients_Addres;
use Illuminate\Http\Request;

class ApiSearchVarController extends Controller
{
    public function searchvar(Request $request)
    {   
        $search = $request->search;
        $tipo = $request->tipo;
        $respuesta = [];
        if($tipo == 'clients'){
            $respuesta = Client::where('first_name','LIKE',"%$search%")
                        ->orwhere('last_name','LIKE',"%$search%")
                        ->orwhere('num_doc','LIKE',"%$search%")
                        ->limit(10)
                        ->get();
        } elseif($tipo == 'address') {
            $respuesta = Clients_Addres::where('client_id',$search)->get();
        }

        return $respuesta;
    }
}
