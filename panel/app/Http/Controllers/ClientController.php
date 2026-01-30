<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Clients_Addres;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ClientController extends Controller
{
    public function index()
    {
        if(Auth::check()){
            $val = $this->getloginrol();
            if ($val == false){
                return redirect()->route('logout');     
            }
            $countries = DB::table('countries')->select('country')->get();
            return view("clients", compact("countries"));
        }

        return redirect()->route('login');
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $request->validate([
                'first_name' => ['required','string'],
                'last_name' => ['string'],
                'type_doc' => ['required'],
                'num_doc' => ['required'],
                'email' => ['email','string'],
            ],
            [
                'required' => 'El campo es requerido.',
                'string' => 'El campo debe ser de tipo alfanumérico.',
                'email' => 'El campo no es un email.',
            ]
        );
    
        $client = Client::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'type_doc' => $request->type_doc,
            'num_doc' => $request->num_doc,
            'email' => $request->email,
            'phone1' => $request->phone1,
            'phone2' => $request->phone2,
            'country' => $request->country,
            'state' => $request->state,
            'city' => $request->city,
            'cp' => $request->cp,
            'address_street' => $request->address_street,
            'address_nro' => $request->address_nro,
            'address_apartament' => $request->address_apartament,
            'address_detail' => $request->address_detail,
            'other_obs' => $request->other_obs,
        ]);

        $this->storeAddress($request, $client->id);

        return redirect()->route('client.index');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $client = Client::find($id);
        return $client;
    }

    public function update(Request $request, $id)
    {
        $client = Client::find($id);

        $datos = array();
        if(isset($request->first_name) && $request->first_name != $client->first_name){
            $request->validate(['first_name' => ['required','string']],
                [ 'required' => 'El campo es requerido.','string' => 'El campo debe ser de tipo alfanumérico.']
            );
            $datos['first_name'] = $request->first_name;
        }
        if(isset($request->last_name) && $request->last_name != $client->last_name){
            $request->validate(['last_name' => ['required','string']],
                [ 'required' => 'El campo es requerido.','string' => 'El campo debe ser de tipo alfanumérico.']
            );
            $datos['last_name'] = $request->last_name;
        }
        if(isset($request->type_doc) && $request->type_doc != $client->type_doc){
            $request->validate(['type_doc' => ['required']],
                [ 'required' => 'El campo es requerido.']
            );
            $datos['type_doc'] = $request->type_doc;
        }
        if(isset($request->num_doc) && $request->num_doc != $client->num_doc){
            $request->validate(['num_doc' => ['required']],
                [ 'required' => 'El campo es requerido.']
            );
            $datos['num_doc'] = $request->num_doc;
        }
        if(isset($request->email) && $request->email != $client->email){
            $request->validate(['email' => ['required','email','string','unique:users,email']],
                [ 'required' => 'El campo es requerido.', 'string' => 'El campo debe ser de tipo alfanumérico.', 'email' => 'El campo no es un email.', 'unique' => 'El mail ya se encuentra registrado.']
            );
            $datos['email'] = $request->email;
        }
        if(isset($request->phone1) && $request->phone1 != $client->phone1){
            $datos['phone1'] = $request->phone1;
        }
        if(isset($request->phone2) && $request->phone2 != $client->phone2){
            $datos['phone2'] = $request->phone2;
        }
        if(isset($request->country) && $request->country != $client->country){
            $datos['country'] = $request->country;
        }
        if(isset($request->state) && $request->state != $client->state){
            $datos['state'] = $request->state;
        }
        if(isset($request->city) && $request->city != $client->city){
            $datos['city'] = $request->city;
        }
        if(isset($request->address_street) && $request->address_street != $client->address_street){
            $datos['address_street'] = $request->address_street;
        }
        if(isset($request->address_nro) && $request->address_nro != $client->address_nro){
            $datos['address_nro'] = $request->address_nro;
        }
        if(isset($request->address_apartament) && $request->address_apartament != $client->address_apartament){
            $datos['address_apartament'] = $request->address_apartament;
        }
        if(isset($request->address_detail) && $request->address_detail != $client->address_detail){
            $datos['address_detail'] = $request->address_detail;
        }
        if(isset($request->cp) && $request->cp != $client->cp){
            $datos['cp'] = $request->cp;
        }
        if(isset($request->other_obs) && $request->other_obs != $client->other_obs){
            $datos['other_obs'] = $request->other_obs;
        }
        
        if(count($datos) > 0){
            Client::where('id',$id)->update($datos);
        }
        
        return redirect()->route('client.index');
    }

    public function destroy($id)
    {
        Client::find($id)->update([
            'deleted_at' => Carbon::now()
        ]);

        return redirect()->route('client.index');
    }

    public function getAddress($client_id)
    {
        $adress = Clients_Addres::where('client_id',$client_id)->get();
        return $adress;
    }

    public function postAddress(Request $request)
    {
        $request->validate([
            'client_id' => ['required'],
            'country' => ['required'],
            'state' => ['required'],
            'cp' => ['required'],
            'city' => ['required'],
            'address_street' => ['required'],
        ],
        [
            'required' => 'El campo es requerido.',
            'string' => 'El campo debe ser de tipo alfanumérico.',
        ]);

        $this->storeAddress($request, $request->client_id);

        return redirect()->route('client.index');
    }

    protected function storeAddress(Request $request, $client_id)
    {
        Clients_Addres::create([
            'client_id' => $client_id,
            'country' => $request->country,
            'state' => $request->state,
            'cp' => $request->cp,
            'city' => $request->city,
            'address_street' => $request->address_street,
            'address_nro' => $request->address_nro,
            'address_apartament' => $request->address_apartament,
            'address_detail' => $request->address_detail,
        ]);
    }

    public function detroyAddress($id)
    {
        Clients_Addres::find($id)->update([
            'deleted_at' => Carbon::now()
        ]);

        return redirect()->route('client.index');
    }
    
}
