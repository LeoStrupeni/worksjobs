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

    public function getDataTable(Request $request)
    {        
        $roluser = Session::get('user')['roles'][0];
        $permissions = Session::get('user')['permissions']['roles'];

        $order = $request->order;
        $page = $request->page ?? 1;
        $limit = $request->limit ?? 10;
        $search = $request->search;

        $totales = Rol::count();

        $query = "SELECT R.id, R.name, R.description, 
            IF(R.estatus=1,'Activo','Inactivo') as estatus
            FROM roles R
            WHERE ISNULL(R.deleted_at) ";

        if ($search != '' && isset($search)) {
            $query .= " AND (R.name LIKE '%$search%' 
                OR R.description LIKE '%$search%'
                OR IF(R.estatus=1,'Activo','Inactivo') LIKE '%$search%' ) ";
        }

        $filtrados = DB::select($query);

        $querylist = '';
        if ($order) {
            $querylist .= " ORDER BY $order ";
        } else {
            $querylist .= " ORDER BY R.id DESC ";
        }
        if ($limit) {
            $querylist .= " LIMIT " . $limit;
        }
        if ($page) {
            $querylist .= " OFFSET " . ($limit * $page - $limit);
        }

        $lista = DB::select(DB::raw($query . $querylist));

        $respuesta['totales'] = $totales;
        $respuesta['filtrados'] = count($filtrados);
        $respuesta['paginastotal'] = ceil(count($filtrados) / $limit);
        $respuesta['datos'] = $lista;

        if ($limit * $page > count($filtrados)) {
            $respuesta['infototal'] = 'Mostrando registros del ' . ($limit * $page - $limit + 1) . ' al ' . count($filtrados) . ' de un total de ' . count($filtrados);
        } else {
            $respuesta['infototal'] = 'Mostrando registros del ' . ($limit * $page - $limit + 1) . ' al ' . ($limit * $page) . ' de un total de ' . count($filtrados);
        }

        $respuesta['query'] = $query.$querylist;
        $respuesta['roluser'] = $roluser;
        $respuesta['permissions'] = $permissions;

        return $respuesta;
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
