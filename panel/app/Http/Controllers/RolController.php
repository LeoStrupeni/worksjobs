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

        $respuesta['datos'] = DB::select("SELECT U.id, U.name, U.email, R.id as role_id, R.name as rolname, U.imagen, 
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
        $rol = Rol::find($id);
        if (!$rol) {
            return response()->json(['error' => 'Rol no encontrado'], 404);
        }
        // No permitir desactivar roles de sistema
        if ($rol->is_system_role == 1) {
            return response()->json(['error' => 'No se puede desactivar un rol de sistema.'], 403);
        }
        $rol->estatus = $rol->estatus == 1 ? 0 : 1;
        $rol->save();
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
        $rol = Rol::find($id);
        if (!$rol) {
            return redirect()->route('roles.index')->with('error', 'Rol no encontrado.');
        }
        // No permitir eliminar roles de sistema
        if ($rol->is_system_role == 1) {
            return redirect()->route('roles.index')->with('error', 'No se puede eliminar un rol de sistema.');
        }
        // No permitir eliminar roles con usuarios asociados
        $usuariosAsociados = DB::table('model_has_roles')
            ->where('role_id', $rol->id)
            ->count();
        if ($usuariosAsociados > 0) {
            return redirect()->route('roles.index')->with('error', 'No se puede eliminar un rol con usuarios asociados.');
        }
        $rol->update([
            'deleted_at' => Carbon::now(),
            'estatus' => 0
        ]);
        return redirect()->route('roles.index')->with('success', 'Rol eliminado correctamente.');
    }
}
