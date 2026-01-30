<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class RolController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {   
        if(Auth::check()){
            $val = $this->getloginrol();
            if ($val == false){
                return redirect()->route('logout');     
            }
            return view("roles");
        }
        return redirect()->route('login');
    }

    public function getUsersRol($id)
    {

        $respuesta['datos'] = DB::select("SELECT U.id, U.name, U.email, R.name as rolname, U.imagen, 
            IF(U.estatus=1,'Activo','Inactivo') as estatus
            FROM users U
            JOIN model_has_roles MR ON U.id = MR.model_id
            JOIN roles R ON MR.role_id = R.id 
            WHERE ISNULL(U.deleted_at)
            AND R.id = $id
        ");
        return $respuesta;
    }

    public function store(Request $request)
    {
        $request->validate([
                'name' => ['required','string','unique:roles,name'],
            ],
            [
                'required' => 'El campo es requerido.',
                'string' => 'El campo debe ser de tipo alfanumérico.',
                'unique' => 'El Rol ya existe ya se encuentra registrado.',
            ]
        );

        $rol = Rol::create([
            'name' => $request->name,
            'guard_name' => 'web',
            'description' => $request->description ?? null,
            'estatus' => 1
        ]);

        return redirect()->route('roles.index');
    }

    public function show($id)
    {
        $user = Rol::find($id);

        if($user->estatus == 1){
            $user->estatus = 0;
            $user->save();
        }else {
            $user->estatus = 1;
            $user->save();
        }

        return 1;
    }

    public function edit($id)
    {
        $rol = Rol::find($id);
        return $rol;
    }

    public function update(Request $request, $id)
    {
        $rol = Rol::find($id);
     
        $datos = array();
        if(isset($request->name) && $request->name != $rol->name){
            $request->validate([
                    'name' => ['required','string','unique:roles,name'],
                ],
                [
                    'required' => 'El campo es requerido.',
                    'string' => 'El campo debe ser de tipo alfanumérico.',
                    'unique' => 'El Rol ya existe ya se encuentra registrado.',
                ]
            );
            $datos['name'] = $request->name;
        }

        if(isset($request->description)){
            $datos['description'] = $request->description;
        }

        if(count($datos) > 0){
            Rol::find($id)->update($datos);
        }
        
        return redirect()->route('roles.index');
    }

    public function destroy($id)
    {
        Rol::find($id)->update([
            'deleted_at' => Carbon::now(),
            'estatus' => 0
        ]);

        return redirect()->route('roles.index');
    }
}
