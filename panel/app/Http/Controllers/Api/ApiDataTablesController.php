<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Clients_Addres;
use App\Models\Permission;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ApiDataTablesController extends Controller
{
    /**
     * Obtener datos para DataTable de Clientes
     * Usado por: Web (AJAX)
     */
    public function getClientsDataTable(Request $request)
    {        
        $roluser = Session::get('user')['roles'][0];
        $permissions = Session::get('user')['permissions']['clients'];

        $order = $request->order;
        $page = $request->page ?? 1;
        $limit = $request->limit ?? 10;
        $search = $request->search;

        $totales = Client::count();

        $query = "SELECT C.*, (CASE WHEN C.type_doc = 2 THEN 'CUIL' WHEN C.type_doc = 3 THEN 'CUIT' ELSE 'DNI' END ) as tipodoc
            FROM clients C
            WHERE ISNULL(C.deleted_at) ";

        if ($search != '' && isset($search)) {
            $query .= " AND (C.first_name LIKE '%$search%' 
                OR C.last_name LIKE '%$search%'
                OR C.num_doc LIKE '%$search%'
                OR C.email LIKE '%$search%'
                OR C.phone1 LIKE '%$search%'
                OR C.phone2 LIKE '%$search%'
                OR C.country LIKE '%$search%'
                OR C.state LIKE '%$search%'
                OR C.city LIKE '%$search%'
                OR C.address_street LIKE '%$search%'
                OR C.address_nro LIKE '%$search%'
                OR C.address_apartament LIKE '%$search%'
                OR C.address_detail LIKE '%$search%'
                OR (CASE WHEN C.type_doc = 2 THEN 'CUIL' WHEN C.type_doc = 3 THEN 'CUIT' ELSE 'DNI' END ) LIKE '%$search%' ) ";
        }

        $filtrados = DB::select($query);

        $querylist = '';
        if ($order) {
            $querylist .= " ORDER BY $order ";
        } else {
            $querylist .= " ORDER BY C.id DESC ";
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

    /**
     * Obtener datos para DataTable de Usuarios
     * Usado por: Web (AJAX)
     */
    public function getUsersDataTable(Request $request)
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

        return $respuesta;
    }

    /**
     * Obtener datos para DataTable de Roles
     * Usado por: Web (AJAX)
     */
    public function getRolesDataTable(Request $request)
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

    /**
     * Obtener datos para DataTable de Permisos
     * Usado por: Web (AJAX)
     */
    public function getPermissionsDataTable(Request $request)
    {        
        $roluser = Session::get('user')['roles'][0];
        $permissions = isset(Session::get('user')['permissions']['permissions']) 
                        ? Session::get('user')['permissions']['permissions'] 
                        : Session::get('user')['permissions']['users'];

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

    /**
     * Obtener datos de un usuario para edición
     * Usado por: Web (AJAX)
     */
    public function getUserEdit($id)
    {
        $user = User::join('model_has_roles AS MR','users.id','MR.model_id')
            ->join('roles AS R','MR.role_id','R.id')
            ->selectRaw("users.id, users.name, users.email, R.id as rolid,R.name as rolname, users.imagen")
            ->where('users.id',$id)
            ->first();
        
        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }
        
        return response()->json($user);
    }

    /**
     * Obtener datos de un cliente para edición
     * Usado por: Web (AJAX)
     */
    public function getClientEdit($id)
    {
        $client = Client::find($id);
        return $client;
    }

    /**
     * Obtener datos de un rol para edición
     * Usado por: Web (AJAX)
     */
    public function getRolEdit($id)
    {
        $rol = Rol::find($id);
        return $rol;
    }

    /**
     * Obtener usuarios de un rol específico
     * Usado por: Web (AJAX)
     */
    public function getUsersByRol($id)
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

    /**
     * Obtener direcciones de un cliente
     * Usado por: Web (AJAX)
     */
    public function getClientAddresses($id)
    {
        $permission = Session::get('user')['permissions']['clients'];
        $respuesta['permission'] = $permission;
        $respuesta['datos'] = Clients_Addres::where('client_id', $id)->get();
        return $respuesta;
    }
}

