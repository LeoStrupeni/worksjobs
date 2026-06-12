<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role_Has_Permission;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PermissionController extends Controller{
    /**
     * Traduce el nombre de un permiso a español usando el archivo de idioma.
     */
    protected function translatePermission($key)
    {
        return __("permissions." . $key);
    }

    public function index()
    {
        if(Auth::check()){
            $val = $this->getloginrol();
            if ($val == false){
                return redirect()->route('logout');     
            }
            if(user_has_special_role()){
                return view("permission");
            } else {
                return redirect()->route('home');
            }
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
                IF(GROUP_CONCAT(SUBSTRING_INDEX(name,' ',1)) like '%update%', 1, 0) as p_update,
                IF(GROUP_CONCAT(SUBSTRING_INDEX(name,' ',1)) like '%delete%', 1, 0) as p_delete,
                IF(GROUP_CONCAT(SUBSTRING_INDEX(name,' ',1)) like '%times%', 1, 0) as p_times
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
                    "p_delete" => 0,
                    "p_times" => 0
                ];
                array_push($permisos, $newperm);
            }
        }
        // Traducir los nombres
        foreach ($permisos as &$perm) {
            $perm['general_es'] = $this->translatePermission($perm['general']);
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
        $permiso = Permission::where('general', $request->general)
            ->whereRaw("SUBSTRING_INDEX(name, ' ', 1) = ?", [$request->tipo])
            ->first();

        if (!$permiso) {
            return response()->json(['message' => 'Permiso no encontrado'], 404);
        }

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
