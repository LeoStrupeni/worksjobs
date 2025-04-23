<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
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
            return view("user");
        }
        return redirect()->route('login');
    }

    public function getDataTable(Request $request)
    {        
        $roluser = Session::get('user')['roles'][0];
        $permissions = Session::get('user')['permissions']['users'];

        $order = $request->order;
        $page = $request->page ?? 1;
        $limit = $request->limit ?? 10;
        $search = $request->search;

        $totales = User::count();

        $query = "SELECT U.id, U.name, U.email, R.name as rolname, U.imagen, 
            IF(U.estatus=1,'Activo','Inactivo') as estatus
            FROM users U
            JOIN model_has_roles MR ON U.id = MR.model_id
            JOIN roles R ON MR.role_id = R.id 
            WHERE ISNULL(U.deleted_at) AND R.name != 'sistema' ";

        if ($search != '' && isset($search)) {
            $query .= " AND (U.name LIKE '%$search%' 
                OR U.email LIKE '%$search%'
                OR R.name LIKE '%$search%' 
                OR IF(U.estatus=1,'Activo','Inactivo') LIKE '%$search%' ) ";
        }

        $filtrados = DB::select($query);

        $querylist = '';
        if ($order) {
            $querylist .= " ORDER BY $order ";
        } else {
            $querylist .= " ORDER BY U.id DESC ";
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

        // dd(in_array('aaaa',$respuesta['permissions']));

        return $respuesta;
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {   
        $request->validate([
                'name' => ['required','string'],
                'email' => ['required','email','string','unique:users,email'],
                'password' => ['required','confirmed','string'],
                'rol' => ['required'],
            ],
            [
                'required' => 'El campo es requerido.',
                'string' => 'El campo debe ser de tipo alfanumérico.',
                'email' => 'El campo no es un email.',
                'confirmed' => 'Las contraseñas no coinciden.',
                'unique' => 'El mail ya se encuentra registrado.',
            ]
        );
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'imagen' => $request->base64 ?? null,
            'estatus' => 1
        ]);

        $rol = Rol::find($request->rol);

        if(isset($rol)){
            $user->assignRole($rol->name);
        } else {
            $rolT = Rol::first();
            $user->assignRole($rolT->name);
        }
        
        return back();
    }

    public function show($id)
    {
        $user = User::find($id);

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
        $user = User::join('model_has_roles AS MR','users.id','MR.model_id')
            ->join('roles AS R','MR.role_id','R.id')
            ->selectRaw("users.id, users.name, users.email, R.id as rolid,R.name as rolname, users.imagen")
            ->where('users.id',$id)
            ->first();
        return $user;
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        $datos = array();
        if(isset($request->name) && $request->name != $user->name){
            $request->validate(['name' => ['required','string']],
                [ 'required' => 'El campo es requerido.','string' => 'El campo debe ser de tipo alfanumérico.']
            );
            $datos['name'] = $request->name;
        }
        if(isset($request->email) && $request->email != $user->email){
            $request->validate(['email' => ['required','email','string','unique:users,email']],
                [ 'required' => 'El campo es requerido.', 'string' => 'El campo debe ser de tipo alfanumérico.', 'email' => 'El campo no es un email.', 'unique' => 'El mail ya se encuentra registrado.']
            );
            $datos['email'] = $request->email;
        }

        $request->validate(['rol' => ['required']],
            [ 'required' => 'El campo es requerido.' ]
        );

        if(isset($request->password)){
            $request->validate([ 'password' => ['required','confirmed','string']],
                [ 'required' => 'El campo es requerido.', 'string' => 'El campo debe ser de tipo alfanumérico.', 'confirmed' => 'Las contraseñas no coinciden.']
            );

            $datos['password'] = Hash::make($request->password);
        }
        
        if(isset($request->base64)){
            $datos['imagen'] = $request->base64;
        }

        if(count($datos) > 0){
            User::where('id',$id)->update($datos);
        }

        $rol = Rol::find($request->rol);

        if(isset($rol)){
            $user->assignRole($rol->name);
        } else {
            $rolT = Rol::first();
            $user->assignRole($rolT->name);
        }
        
        return back();
    }

    public function destroy($id, Request $request)
    {
        User::find($id)->update([
            'deleted_at' => Carbon::now(), 
            'motivo_delete' => $request->motivo,
            'estatus' => 0
        ]);

        return back();
    }
}
