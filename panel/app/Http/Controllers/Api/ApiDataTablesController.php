<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncColppyClientsJob;
use App\Jobs\SyncColppyProductsJob;
use App\Models\Client;
use App\Models\Clients_Addres;
use App\Models\Config;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Rol;
use App\Models\User;
use App\Services\ColppyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ApiDataTablesController extends Controller
{
    /**
     * Obtener datos para DataTable de Clientes
     * Usado por: Web (AJAX)
     * CONFIGURABLE: Obtiene datos según configuración (local, api, hibrido)
     * 
     * NOTA: Modo 'local' es el recomendado - sincroniza automáticamente desde Colppy en background
     */
    public function getClientsDataTable(Request $request)
    {        
        $roluser = Session::get('user')['roles'][0];
        $permissions = Session::get('user')['permissions']['clients'];

        $order = $request->order;
        $page = $request->page ?? 1;
        $limit = $request->limit ?? 10;
        $search = $request->search;

        // Obtener modo de operación desde configuración
        $modo = Config::where('name', 'colppy_clientes_modo')->value('value') ?? 'local';
        
        // Log::info('=== MODO CLIENTES ===', ['modo' => $modo]);

        // Ejecutar según modo configurado
        switch ($modo) {
            case 'api':
                return $this->getClientsFromColppyOnly($request, $roluser, $permissions, $order, $page, $limit, $search);
            
            case 'hibrido':
                return $this->getClientsHibrido($request, $roluser, $permissions, $order, $page, $limit, $search);
            
            case 'local':
            default:
                return $this->getClientsFromLocalOnly($request, $roluser, $permissions, $order, $page, $limit, $search);
        }
    }

    /**
     * Obtener clientes SOLO de base de datos local
     * MUESTRA: TODOS los clientes (locales + sincronizados desde Colppy)
     */
    private function getClientsFromLocalOnly(Request $request, $roluser, $permissions, $order, $page, $limit, $search)
    {
        $totales = Client::count();

        $query = "SELECT C.id, C.colppy_id, C.first_name, C.last_name, C.nombre_fantasia,
            CASE 
                WHEN C.type_doc = '1' THEN 'DNI'
                WHEN C.type_doc = '2' THEN 'CUIL'
                WHEN C.type_doc = '3' THEN 'CUIT'
                ELSE ''
            END as tipodoc,
            C.type_doc, C.num_doc, C.email, C.phone1, C.phone2, C.fax,
            C.country, C.state, C.city, C.cp, C.address_street, 
            C.address_nro, C.address_apartament, C.address_detail,
            C.other_obs, C.is_from_colppy, C.created_at, C.updated_at
            FROM clients C
            WHERE ISNULL(C.deleted_at) 
            AND (C.is_from_colppy != 1 OR ISNULL(C.is_from_colppy)) ";

        if ($search != '' && isset($search)) {
            $query .= " AND (C.first_name LIKE '%$search%' 
                OR C.last_name LIKE '%$search%'
                OR C.nombre_fantasia LIKE '%$search%'
                OR C.num_doc LIKE '%$search%'
                OR C.email LIKE '%$search%'
                OR C.phone1 LIKE '%$search%' ) ";
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

        $respuesta['query'] = $query . $querylist;
        $respuesta['roluser'] = $roluser;
        $respuesta['permissions'] = $permissions;
        $respuesta['special_role_ids'] = get_special_role_ids();

        return $respuesta;
    }

    /**
     * Obtener clientes SOLO sincronizados desde Colppy (BD local)
     * MUESTRA: Solo clientes con is_from_colppy = true
     */
    private function getClientsFromColppyOnly(Request $request, $roluser, $permissions, $order, $page, $limit, $search)
    {
        $totales = Client::where('is_from_colppy', true)->count();

        $query = "SELECT C.id, C.colppy_id, C.first_name, C.last_name, C.nombre_fantasia,
            CASE 
                WHEN C.type_doc = '1' THEN 'DNI'
                WHEN C.type_doc = '2' THEN 'CUIL'
                WHEN C.type_doc = '3' THEN 'CUIT'
                ELSE ''
            END as tipodoc,
            C.type_doc, C.num_doc, C.email, C.phone1, C.phone2, C.fax,
            C.country, C.state, C.city, C.cp, C.address_street, 
            C.address_nro, C.address_apartament, C.address_detail,
            C.other_obs, C.is_from_colppy, C.created_at, C.updated_at
            FROM clients C
            WHERE ISNULL(C.deleted_at) 
            AND C.is_from_colppy = 1 ";

        if ($search != '' && isset($search)) {
            $query .= " AND (C.first_name LIKE '%$search%' 
                OR C.last_name LIKE '%$search%'
                OR C.nombre_fantasia LIKE '%$search%'
                OR C.num_doc LIKE '%$search%'
                OR C.email LIKE '%$search%'
                OR C.phone1 LIKE '%$search%' ) ";
        }

        $filtrados = DB::select($query);

        $querylist = '';
        if ($order) {
            $querylist .= " ORDER BY $order ";
        } else {
            $querylist .= " ORDER BY C.colppy_id ASC "; // Ordenar por colppy_id para mantener secuencia original de Colppy
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
            $respuesta['infototal'] = 'Mostrando registros del ' . ($limit * $page - $limit + 1) . ' al ' . count($filtrados) . ' de un total de ' . count($filtrados) . ' (Solo Colppy)';
        } else {
            $respuesta['infototal'] = 'Mostrando registros del ' . ($limit * $page - $limit + 1) . ' al ' . ($limit * $page) . ' de un total de ' . count($filtrados) . ' (Solo Colppy)';
        }

        $respuesta['query'] = $query . $querylist;
        $respuesta['roluser'] = $roluser;
        $respuesta['permissions'] = $permissions;
        $respuesta['special_role_ids'] = get_special_role_ids();

        return $respuesta;
    }

    /**
     * Obtener clientes de BD local (modo híbrido)
     * MUESTRA: TODOS los clientes con distinción de origen
     */
    private function getClientsHibrido(Request $request, $roluser, $permissions, $order, $page, $limit, $search)
    {
        $totales = Client::count();

        $query = "SELECT C.id, C.colppy_id, C.first_name, C.last_name, C.nombre_fantasia,
            CASE 
                WHEN C.type_doc = '1' THEN 'DNI'
                WHEN C.type_doc = '2' THEN 'CUIL'
                WHEN C.type_doc = '3' THEN 'CUIT'
                ELSE ''
            END as tipodoc,
            C.type_doc, C.num_doc, C.email, C.phone1, C.phone2, C.fax,
            C.country, C.state, C.city, C.cp, C.address_street, 
            C.address_nro, C.address_apartament, C.address_detail,
            C.other_obs, C.is_from_colppy, C.created_at, C.updated_at
            FROM clients C
            WHERE ISNULL(C.deleted_at) ";

        if ($search != '' && isset($search)) {
            $query .= " AND (C.first_name LIKE '%$search%' 
                OR C.last_name LIKE '%$search%'
                OR C.nombre_fantasia LIKE '%$search%'
                OR C.num_doc LIKE '%$search%'
                OR C.email LIKE '%$search%'
                OR C.phone1 LIKE '%$search%' ) ";
        }

        $filtrados = DB::select($query);

        $querylist = '';
        if ($order) {
            $querylist .= " ORDER BY $order ";
        } else {
            $querylist .= " ORDER BY C.id ASC ";
        }
        if ($limit) {
            $querylist .= " LIMIT " . $limit;
        }
        if ($page) {
            $querylist .= " OFFSET " . ($limit * $page - $limit);
        }

        $lista = DB::select(DB::raw($query . $querylist));

        // Contar clientes por origen
        $totalesLocales = Client::where('is_from_colppy', false)->orWhereNull('is_from_colppy')->count();
        $totalesColppy = Client::where('is_from_colppy', true)->count();

        $respuesta['totales'] = $totales;
        $respuesta['filtrados'] = count($filtrados);
        $respuesta['paginastotal'] = ceil(count($filtrados) / $limit);
        $respuesta['datos'] = $lista;

        // Mensaje informativo con distinción de origen
        if ($limit * $page > count($filtrados)) {
            $respuesta['infototal'] = 'Mostrando registros del ' . ($limit * $page - $limit + 1) . ' al ' . count($filtrados) . ' de un total de ' . count($filtrados) . " (Locales: $totalesLocales | Colppy: $totalesColppy)";
        } else {
            $respuesta['infototal'] = 'Mostrando registros del ' . ($limit * $page - $limit + 1) . ' al ' . ($limit * $page) . ' de un total de ' . count($filtrados) . " (Locales: $totalesLocales | Colppy: $totalesColppy)";
        }
        $respuesta['query'] = $query . $querylist;
        $respuesta['roluser'] = $roluser;
        $respuesta['permissions'] = $permissions;
        $respuesta['special_role_ids'] = get_special_role_ids();
        
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

        $specialRoleIds = implode(',', get_special_role_ids());
        $query = "SELECT U.id, U.name, U.email, R.id as role_id, R.name as rolname, U.imagen, 
            IF(U.estatus=1,'Activo','Inactivo') as estatus
            FROM users U
            JOIN model_has_roles MR ON U.id = MR.model_id
            JOIN roles R ON MR.role_id = R.id 
            WHERE ISNULL(U.deleted_at) ";

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
            WHERE ISNULL(R.deleted_at) "; // Excluir rol 'sistema' con ID 3

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
        $respuesta['special_role_ids'] = get_special_role_ids();

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
        // Traducción de nombres
        foreach ($lista as &$item) {
            $item->general_es = __("permissions." . $item->general);
        }
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
        $respuesta['special_role_ids'] = get_special_role_ids();
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

    /**
     * Obtener direcciones de un cliente
     * Usado por: Web (AJAX)
     */
    public function getClientAddresses($id)
    {
        $permission = Session::get('user')['permissions']['clients'];
        $respuesta['permission'] = $permission;
        
        // Leer de tabla clients_address (todos los clientes, locales y Colppy)
        $respuesta['datos'] = Clients_Addres::where('client_id', $id)
            ->whereNull('deleted_at')
            ->get();
        
        return $respuesta;
    }

    /**
     * Disparar sincronización de clientes Colppy en background
     * Usado por: Vista de clientes al cargar la página
     * NO BLOQUEANTE - Se ejecuta en un job de cola
     */
    public function syncColppyClients()
    {
        try {
            // Obtener modo de operación
            $modo = Config::where('name', 'colppy_clientes_modo')->value('value') ?? 'local';
            
            // Solo sincronizar si el modo es 'api' o 'hibrido'
            if ($modo === 'api' || $modo === 'hibrido') {
                // Verificar si no hay un job de sincronización reciente (últimos 5 minutos)
                $ultimaSinc = Session::get('ultima_sinc_colppy');
                $tiempoActual = time();
                
                // Solo sincronizar si pasaron más de 5 minutos desde la última sincronización
                if (!$ultimaSinc || ($tiempoActual - $ultimaSinc) > 300) {
                    SyncColppyClientsJob::dispatch();
                    Session::put('ultima_sinc_colppy', $tiempoActual);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Sincronización de clientes Colppy iniciada en background',
                        'nota' => 'La sincronización se ejecuta automáticamente via Scheduler cada minuto'
                    ]);
                } else {
                    return response()->json([
                        'success' => true,
                        'message' => 'Sincronización reciente, no es necesario ejecutar nuevamente'
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Modo configurado no requiere sincronización'
                ]);
            }
        } catch (\Exception $e) {
            // Log::error('Error al disparar sincronización Colppy', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar sincronización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ejecutar sincronización de forma SINCRÓNICA (para debugging)
     * NO usar en producción con muchos clientes - puede tardar varios minutos
     * Usado por: Debug/Testing
     */
    public function syncColppyClientsNow()
    {
        try {
            // Log::info('=== SINCRONIZACIÓN SINCRÓNICA INICIADA MANUALMENTE ===');
            
            $syncService = new \App\Services\SyncColppyClientsService();
            $resultado = $syncService->syncClients();
            
            if ($resultado['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sincronización completada',
                    'datos' => [
                        'nuevos' => $resultado['nuevos'],
                        'actualizados' => $resultado['actualizados'],
                        'errores' => $resultado['errores'],
                        'total' => $resultado['total']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $resultado['mensaje'] ?? 'Error desconocido'
                ], 500);
            }
        } catch (\Exception $e) {
            // Log::error('Error en sincronización sincrónica', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de sincronización
     * Compara clientes locales vs Colppy
     */
    public function getSyncStats()
    {
        try {
            // Contar clientes locales (propios, no de Colppy)
            $clientesLocales = Client::where(function($query) {
                $query->where('is_from_colppy', false)
                      ->orWhereNull('is_from_colppy');
            })->count();
            
            // Contar clientes de Colppy
            $clientesColppyLocal = Client::where('is_from_colppy', true)->count();
            
            // Total local
            $totalLocal = Client::count();
            
            // Intentar obtener total desde Colppy API
            $totalColppy = 0;
            try {
                $colppyService = new ColppyService();
                $resultado = $colppyService->listarClientes(0, 1, [], []);
                $totalColppy = $resultado['total'] ?? 0;
            } catch (\Exception $colppyError) {
                // Continuar sin Colppy
            }
            
            $diferencia = $totalColppy - $clientesColppyLocal;
            
            return response()->json([
                'success' => true,
                'stats' => [
                    'local_total' => $totalLocal,
                    'local_propios' => $clientesLocales,
                    'local_de_colppy' => $clientesColppyLocal,
                    'colppy_total' => $totalColppy,
                    'diferencia' => $diferencia,
                    'necesita_sincronizar' => $diferencia != 0
                ]
            ]);
            
        } catch (\Exception $e) {
            // Log::error('ERROR FATAL en getSyncStats', [
            //     'error' => $e->getMessage(),
            //     'file' => $e->getFile(),
            //     'line' => $e->getLine()
            // ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Disparar sincronización de productos Colppy
     * Usado por: Vista de productos al cargar la página
     * Se ejecuta directamente usando el Scheduler (no usa Jobs ni queue:work)
     */
    public function syncColppyProducts()
    {
        try {
            // Verificar si no hay una sincronización reciente (últimos 5 minutos)
            $ultimaSinc = Session::get('ultima_sinc_productos_colppy');
            $tiempoActual = time();
            
            // Solo sincronizar si pasaron más de 5 minutos desde la última sincronización
            if (!$ultimaSinc || ($tiempoActual - $ultimaSinc) > 300) {
                Session::put('ultima_sinc_productos_colppy', $tiempoActual);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Sincronización de productos programada',
                    'nota' => 'La sincronización se ejecuta automáticamente cada 2 horas vía Scheduler'
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'Sincronización reciente, no es necesario ejecutar nuevamente'
                ]);
            }
        } catch (\Exception $e) {
            // Log::error('Error al verificar sincronización de productos Colppy', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ejecutar sincronización de productos de forma SINCRÓNICA (para debugging)
     * NO usar en producción con muchos productos - puede tardar varios minutos
     * Usado por: Debug/Testing
     */
    public function syncColppyProductsNow()
    {
        try {
            // Log::info('=== SINCRONIZACIÓN SINCRÓNICA DE PRODUCTOS INICIADA MANUALMENTE ===');
            
            $syncService = new \App\Services\SyncColppyProductsService();
            $resultado = $syncService->syncProducts();
            
            if ($resultado['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sincronización de productos completada',
                    'datos' => [
                        'nuevos' => $resultado['nuevos'],
                        'actualizados' => $resultado['actualizados'],
                        'errores' => $resultado['errores'],
                        'total' => $resultado['total']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $resultado['mensaje'] ?? 'Error desconocido'
                ], 500);
            }
        } catch (\Exception $e) {
            // Log::error('Error en sincronización sincrónica de productos', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de sincronización de productos
     * Compara productos locales vs Colppy
     */
    public function getProductSyncStats()
    {
        try {
            // Contar productos locales (propios, no de Colppy)
            $productosLocales = Product::where(function($query) {
                $query->where('is_from_colppy', false)
                      ->orWhereNull('is_from_colppy');
            })->count();
            
            // Contar productos de Colppy
            $productosColppyLocal = Product::where('is_from_colppy', true)->count();
            
            // Total local
            $totalLocal = Product::count();
            
            // Intentar obtener total desde Colppy API
            $totalColppy = 0;
            try {
                $colppyService = new ColppyService();
                $resultado = $colppyService->listarInventario(0, 1, [], []);
                $totalColppy = $resultado['total'] ?? 0;
            } catch (\Exception $colppyError) {
                // Continuar sin Colppy
            }
            
            $diferencia = $totalColppy - $productosColppyLocal;
            
            return response()->json([
                'success' => true,
                'stats' => [
                    'local_total' => $totalLocal,
                    'local_propios' => $productosLocales,
                    'local_de_colppy' => $productosColppyLocal,
                    'colppy_total' => $totalColppy,
                    'diferencia' => $diferencia,
                    'necesita_sincronizar' => $diferencia != 0
                ]
            ]);
            
        } catch (\Exception $e) {
            // Log::error('ERROR FATAL en getProductSyncStats', [
            //     'error' => $e->getMessage(),
            //     'file' => $e->getFile(),
            //     'line' => $e->getLine()
            // ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas de productos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener listado de productos para DataTable o select
     * Usado por: Web panel
     */
    public function getProducts(Request $request)
    {
        try {
            $query = Product::query()
                ->where('tipo_item', 'P')
                ->activos() // Solo productos activos (sin fecha de baja)
                ->orderBy('descripcion', 'ASC');
            
            // Filtro por búsqueda
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('codigo', 'LIKE', "%{$search}%")
                      ->orWhere('descripcion', 'LIKE', "%{$search}%")
                      ->orWhere('detalle', 'LIKE', "%{$search}%");
                });
            }
            
            // Paginación
            if ($request->has('limit')) {
                $limit = (int) $request->limit;
                $productos = $query->paginate($limit);
                
                return response()->json([
                    'success' => true,
                    'data' => $productos->items(),
                    'total' => $productos->total(),
                    'per_page' => $productos->perPage(),
                    'current_page' => $productos->currentPage()
                ]);
            } else {
                $productos = $query->get();
                
                return response()->json([
                    'success' => true,
                    'data' => $productos,
                    'total' => $productos->count()
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error al obtener productos', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos: ' . $e->getMessage()
            ], 500);
        }
    }
}

