<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role_Has_Permission;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PermissionController extends Controller
{
    public function index()
    {
        if(Auth::check()){
            $val = $this->getloginrol();
            if ($val == false){
                return redirect()->route('logout');     
            }
            if((Session::get('user')['roles'][0] == 'sistema' || Session::get('user')['roles'][0] == 'admin')){
                return view("permission");
            } else {
                return redirect()->route('home');
            }
        }
        return redirect()->route('login');
    }

    public function getDataTable(Request $request)
    {        
        $roluser = Session::get('user')['roles'][0];
        $permissions = isset(Session::get('user')['permissions']['permissions']) 
                        ? Session::get('user')['permissions']['permissions'] 
                        : Session::get('user')['permissions']['users'] ;

        $order = $request->order;
        $page = $request->page ?? 1;
        $limit = $request->limit ?? 10;
        $search = $request->search;

        $totales = Permission::count();

        $query = "SELECT P.general, GROUP_CONCAT(SUBSTRING_INDEX(P.name,' ',1)) as listpermisos
            FROM permissions P
            WHERE ISNULL(P.deleted_at) ";

        if ($search != '' && isset($search)) {
            $query .= " AND  (P.general LIKE '%$search%' 
                OR P.name LIKE '%$search%' ) ";
        }

        $querylist = ' GROUP BY P.general ';

        $filtrados = DB::select($query . $querylist);

        
        if ($order) {
            $querylist .= " ORDER BY $order ";
        } else {
            $querylist .= " ORDER BY P.id DESC ";
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

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $request->validate([
                'name' => ['required','string','unique:permissions,name'],
            ],
            [
                'required' => 'El campo es requerido.',
                'string' => 'El campo debe ser de tipo alfanumérico.',
                'unique' => 'El Permiso ya se encuentra registrado.',
            ]
        );

        foreach ($request->opciones as $val) {
            Permission::create([
                'name' => $val.' '.strtolower(str_replace(" ", "_", $request->name)),
                'general'=> strtolower(str_replace(" ", "_", $request->name)),
                'guard_name' => 'web',
            ]);
        }

        return redirect()->route('permission.index');
    }

    public function show($rolid)
    {
        $permisos = Role_Has_Permission::join('permissions','permissions.id','role_has_permissions.permission_id')
            ->where('role_has_permissions.role_id',$rolid)
            ->wherenull('permissions.deleted_at')
            ->selectRaw("general, 
                IF(GROUP_CONCAT(SUBSTRING_INDEX(name,' ',1)) like '%create%', 1, 0) as p_create,
                IF(GROUP_CONCAT(SUBSTRING_INDEX(name,' ',1)) like '%read%', 1, 0) as p_read,
                IF(GROUP_CONCAT(SUBSTRING_INDEX(name,' ',1)) like '%ùpdate%', 1, 0) as p_update,
                IF(GROUP_CONCAT(SUBSTRING_INDEX(name,' ',1)) like '%delete%', 1, 0) as p_delete
            ")
            ->groupby('general')
        ->get()->toArray();

        $list = Permission::select('general')->groupby('general')->pluck('general');
        
        foreach ($list as $l) {
            $agregar=0;
            foreach ($permisos as $perm) {
                if($perm['general'] == $l){$agregar++; break;}
            }
            if($agregar==0){
                $newperm=[
                    "general" => $l,
                    "p_create" => 0,
                    "p_read" => 0,
                    "p_update" => 0,
                    "p_delete" => 0
                ];
                array_push($permisos, $newperm);
            }
        }

        $respuesta['datos']=$permisos;

        $permissions = Session::get('user')['permissions']['roles'];
        $respuesta['permissions'] = $permissions;

        return $respuesta;
    }

    public function edit($id)
    {
        $namegeneral=$id;
        $permission = Permission::where('general',$namegeneral)->first();
        return $permission;
    }

    public function update(Request $request, $id)
    {       
        $request->validate([
                'name' => ['required','string','unique:permissions,name'],
            ],
            [
                'required' => 'El campo es requerido.',
                'string' => 'El campo debe ser de tipo alfanumérico.',
                'unique' => 'El Permiso ya se encuentra registrado.',
            ]
        );

        $namegeneral=$id;
        $permissions = Permission::where('general',$namegeneral)->get();
        $newname=strtolower(str_replace(" ", "_", $request->name));

        foreach ($permissions as $perm) {    
            Permission::find($perm->id)->update([
                'name' => strtolower(str_replace($perm->general, $newname, $perm->name)),
                'general'=> $newname,
            ]);
        }
        return redirect()->route('permission.index');
    }

    public function destroy($id)
    {
        $namegeneral=$id;
        $permissions = Permission::where('general',$namegeneral)->get();
        foreach ($permissions as $perm) {    
            Permission::find($perm->id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }
        return redirect()->route('permission.index');
    }

    public function updaterolpermission(Request $request)
    {
        $permiso = Permission::where('general',$request->general)->whereraw("name like '%".$request->tipo."%'")->first();

        $rolpermission = Role_Has_Permission::where('permission_id',$permiso->id)->where('role_id',$request->rolid)->first();
        if (isset($rolpermission)) {
            Role_Has_Permission::where('permission_id',$permiso->id)->where('role_id',$request->rolid)->delete();
        } else {
            Role_Has_Permission::create([
                'permission_id' => $permiso->id,
                'role_id'=> $request->rolid,
            ]);
        }

        return 'ok';
    }
}
