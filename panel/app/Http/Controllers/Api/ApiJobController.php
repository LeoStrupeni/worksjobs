<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Clients_Addres;
use App\Models\Config;
use App\Models\Job;
use App\Models\Jobs_file;
use App\Models\Jobs_Note;
use App\Models\JobProduct;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ApiJobController extends Controller
{
    /**
     * Obtener datos para DataTable (paginación, búsqueda, filtros)
     * Usado por: Web (AJAX)
     */
    public function getDataTable(Request $request)
    {        
        $roluser = Session::get('user')['roles'][0];
        $permissions = Session::get('user')['permissions']['jobs'] ?? [];
        $authUser = $request->user() ?? Auth::user();
        if ($authUser && $authUser->can('times jobs') && !in_array('times', $permissions, true)) {
            $permissions[] = 'times';
        }

        $order = $request->order;
        $page = $request->page ?? 1;
        $limit = $request->limit ?? 10;
        $search = $request->search;

        $totales = Job::count();

        // Usar query centralizado del modelo
        $query = Job::getJobsQuery();
                
        if (isset($request->periodo)) {
            $fechas = explode(' - ', $request->periodo);
            $_fechamin = explode('/', $fechas[0]);
            $_fechamax = explode('/', $fechas[1]);
            $fechamin = date($_fechamin[2] . "-" . $_fechamin[0] . "-" . $_fechamin[1]);
            $fechamax = date($_fechamax[2] . "-" . $_fechamax[0] . "-" . $_fechamax[1]);

            $query->whereRaw("DATE(C.visit_datetime) >= ?", [$fechamin])
                  ->whereRaw("DATE(C.visit_datetime) <= ?", [$fechamax]);
        }


        if ($search != '' && isset($search)) {
            $query->where(function($q) use ($search) {
                $q->whereRaw("CL.first_name LIKE ?", ["%$search%"])
                  ->orWhereRaw("CL.last_name LIKE ?", ["%$search%"])
                  ->orWhereRaw("DATE_FORMAT(C.created_at,'%d/%m/%y %H:%i') LIKE ?", ["%$search%"])
                  ->orWhereRaw("DATE_FORMAT(C.visit_datetime,'%d/%m/%y %H:%i') LIKE ?", ["%$search%"])
                  ->orWhereRaw("DATE_FORMAT(C.arrival_datetime,'%d/%m/%y %H:%i') LIKE ?", ["%$search%"])
                  ->orWhereRaw("DATE_FORMAT(C.closed_datetime,'%d/%m/%y %H:%i') LIKE ?", ["%$search%"])
                  // Días de la semana en español
                  ->orWhereRaw("CASE DATE_FORMAT(C.created_at,'%W') WHEN 'Monday' THEN 'Lun' WHEN 'Tuesday' THEN 'Mar' WHEN 'Wednesday' THEN 'Mie' WHEN 'Thursday' THEN 'Jue' WHEN 'Friday' THEN 'Vie' WHEN 'Saturday' THEN 'Sab' WHEN 'Sunday' THEN 'Dom' END LIKE ?", ["%$search%"])
                  ->orWhereRaw("CASE DATE_FORMAT(C.visit_datetime,'%W') WHEN 'Monday' THEN 'Lun' WHEN 'Tuesday' THEN 'Mar' WHEN 'Wednesday' THEN 'Mie' WHEN 'Thursday' THEN 'Jue' WHEN 'Friday' THEN 'Vie' WHEN 'Saturday' THEN 'Sab' WHEN 'Sunday' THEN 'Dom' END LIKE ?", ["%$search%"])
                  ->orWhereRaw("CASE DATE_FORMAT(C.arrival_datetime,'%W') WHEN 'Monday' THEN 'Lun' WHEN 'Tuesday' THEN 'Mar' WHEN 'Wednesday' THEN 'Mie' WHEN 'Thursday' THEN 'Jue' WHEN 'Friday' THEN 'Vie' WHEN 'Saturday' THEN 'Sab' WHEN 'Sunday' THEN 'Dom' END LIKE ?", ["%$search%"])
                  ->orWhereRaw("CASE DATE_FORMAT(C.closed_datetime,'%W') WHEN 'Monday' THEN 'Lun' WHEN 'Tuesday' THEN 'Mar' WHEN 'Wednesday' THEN 'Mie' WHEN 'Thursday' THEN 'Jue' WHEN 'Friday' THEN 'Vie' WHEN 'Saturday' THEN 'Sab' WHEN 'Sunday' THEN 'Dom' END LIKE ?", ["%$search%"])
                  // Meses en español
                  ->orWhereRaw("CASE DATE_FORMAT(C.created_at,'%m') WHEN '01' THEN 'Ene' WHEN '02' THEN 'Feb' WHEN '03' THEN 'Mar' WHEN '04' THEN 'Abr' WHEN '05' THEN 'May' WHEN '06' THEN 'Jun' WHEN '07' THEN 'Jul' WHEN '08' THEN 'Ago' WHEN '09' THEN 'Sep' WHEN '10' THEN 'Oct' WHEN '11' THEN 'Nov' WHEN '12' THEN 'Dic' END LIKE ?", ["%$search%"])
                  ->orWhereRaw("CASE DATE_FORMAT(C.visit_datetime,'%m') WHEN '01' THEN 'Ene' WHEN '02' THEN 'Feb' WHEN '03' THEN 'Mar' WHEN '04' THEN 'Abr' WHEN '05' THEN 'May' WHEN '06' THEN 'Jun' WHEN '07' THEN 'Jul' WHEN '08' THEN 'Ago' WHEN '09' THEN 'Sep' WHEN '10' THEN 'Oct' WHEN '11' THEN 'Nov' WHEN '12' THEN 'Dic' END LIKE ?", ["%$search%"])
                  ->orWhereRaw("CASE DATE_FORMAT(C.arrival_datetime,'%m') WHEN '01' THEN 'Ene' WHEN '02' THEN 'Feb' WHEN '03' THEN 'Mar' WHEN '04' THEN 'Abr' WHEN '05' THEN 'May' WHEN '06' THEN 'Jun' WHEN '07' THEN 'Jul' WHEN '08' THEN 'Ago' WHEN '09' THEN 'Sep' WHEN '10' THEN 'Oct' WHEN '11' THEN 'Nov' WHEN '12' THEN 'Dic' END LIKE ?", ["%$search%"])
                  ->orWhereRaw("CASE DATE_FORMAT(C.closed_datetime,'%m') WHEN '01' THEN 'Ene' WHEN '02' THEN 'Feb' WHEN '03' THEN 'Mar' WHEN '04' THEN 'Abr' WHEN '05' THEN 'May' WHEN '06' THEN 'Jun' WHEN '07' THEN 'Jul' WHEN '08' THEN 'Ago' WHEN '09' THEN 'Sep' WHEN '10' THEN 'Oct' WHEN '11' THEN 'Nov' WHEN '12' THEN 'Dic' END LIKE ?", ["%$search%"])
                  // Status y descripciones
                  ->orWhereRaw("CASE WHEN C.closed_datetime IS NOT NULL THEN 'Cerrado' WHEN C.arrival_datetime IS NOT NULL THEN 'En Lugar' ELSE 'Pendiente' END LIKE ?", ["%$search%"])
                  ->orWhereRaw("IFNULL(C.job_description,'') LIKE ?", ["%$search%"])
                  ->orWhereRaw("IFNULL(C.closed_job_observation,'') LIKE ?", ["%$search%"])
                  ->orWhereRaw("C.id LIKE ?", ["%$search%"])
                  ->orWhereRaw("C.colppy_budget_number LIKE ?", ["%$search%"])
                  ;
            });
        }
        // Optimización: contar filtrados sin hacer get() completo
        $filtrados = ($search != '' && isset($search) || isset($request->periodo)) ? $query->count() : $totales;

        // Aplicar ordenamiento
        if ($order) {
            $query->orderByRaw($order);
        } else {
            $query->orderBy('estatusorder', 'ASC')
                  ->orderBy('ordervisit', 'DESC');
        }

        // Aplicar paginación
        if ($limit) {
            $query->limit($limit);
        }
        if ($page) {
            $query->offset($limit * $page - $limit);
        }

        $lista = $query->get();

        foreach ($lista as $j) {
            $note = Jobs_Note::where('jobs_id', $j->id)->first();   
            $j->getnotes = $note ? 'si' : 'no';
            
            // Agregar contador de imágenes
            $j->images_count = Jobs_file::where('job_id', $j->id)->count();
        }

        $respuesta['totales'] = $totales;
        $respuesta['filtrados'] = $filtrados;
        $respuesta['paginastotal'] = ceil($filtrados / $limit);
        $respuesta['datos'] = $lista;

        if ($limit * $page > $filtrados) {
            $respuesta['infototal'] = 'Mostrando registros del ' . ($limit * $page - $limit + 1) . ' al ' . $filtrados . ' de un total de ' . $filtrados;
        } else {
            $respuesta['infototal'] = 'Mostrando registros del ' . ($limit * $page - $limit + 1) . ' al ' . ($limit * $page) . ' de un total de ' . $filtrados;
        }

        $respuesta['roluser'] = $roluser;
        $respuesta['permissions'] = $permissions;
        $respuesta['special_role_ids'] = get_special_role_ids();
        $respuesta['user_role_id'] = get_role_id_by_name($roluser);

        return $respuesta;
    }
    
    /**
     * Obtener trabajos del día (incluye trabajos anteriores abiertos)
     * Usado por: Web (AJAX) y App Móvil
     */
    public function getTodayJobs(Request $request)
    {
        try {
            $user = $request->user();
            $today = Carbon::now()->format('Y-m-d');
            
            // Log del usuario que hace la petición
            // Log::info('📱 getTodayJobs - Usuario: ' . ($user ? $user->id . ' - ' . $user->email : 'NO AUTENTICADO'));
            
            if (!$user) {
                Log::error('❌ getTodayJobs - Usuario no autenticado');
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado'
                ], 401);
            }
            
            // Usar query centralizado del modelo Job
            $query = Job::getJobsQuery();
            
            // Trabajos de hoy O trabajos anteriores que estén abiertos (no cerrados)
            $query->where(function($q) use ($today) {
                $q->whereRaw("DATE(C.visit_datetime) = ?", [$today])
                  ->orWhere(function($subQ) use ($today) {
                      $subQ->whereRaw("DATE(C.visit_datetime) < ?", [$today])
                           ->whereNull('C.closed_datetime');
                  });
            });
            
            $query->orderBy('estatusorder', 'ASC')
                  ->orderBy('ordervisit', 'ASC');
            
            $jobs = $query->get();
            // Log::info('✅ getTodayJobs - ' . $jobs->count() . ' trabajos encontrados para ' . $user->email);
            
            foreach ($jobs as $j) {
                $note = Jobs_Note::where('jobs_id', $j->id)->first();   
                $j->getnotes = $note ? 'si' : 'no';
            }
            
            $permissions = $this->getUserPermissions($user);
            
            return response()->json([
                'success' => true,
                'data' => $jobs,
                'count' => $jobs->count(),
                'permissions' => $permissions
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ getTodayJobs - Exception: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener trabajos del día',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Obtener próximos trabajos
     * Usado por: Web (AJAX) y App Móvil
     */
    public function getUpcomingJobs(Request $request)
    {
        try {
            $user = $request->user();
            $today = Carbon::now()->format('Y-m-d');
            
            // Log::info('📱 getUpcomingJobs - Usuario: ' . ($user ? $user->id . ' - ' . $user->email : 'NO AUTENTICADO'));
            
            if (!$user) {
                Log::error('❌ getUpcomingJobs - Usuario no autenticado');
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado'
                ], 401);
            }
            
            // Usar query centralizado del modelo Job
            $query = Job::getJobsQuery();
            
            // Trabajos futuros que NO estén cerrados
            $query->whereRaw("DATE(C.visit_datetime) > ?", [$today])
                  ->whereNull('C.closed_datetime');
            
            $query->orderBy('ordervisit', 'ASC');
            
            $jobs = $query->get();
            // Log::info('✅ getUpcomingJobs - ' . $jobs->count() . ' trabajos encontrados para ' . $user->email);
            
            foreach ($jobs as $j) {
                $note = Jobs_Note::where('jobs_id', $j->id)->first();   
                $j->getnotes = $note ? 'si' : 'no';
            }
            
            $permissions = $this->getUserPermissions($user);
            
            return response()->json([
                'success' => true,
                'data' => $jobs,
                'count' => $jobs->count(),
                'permissions' => $permissions
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ getUpcomingJobs - Exception: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener próximos trabajos',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Obtener trabajos por rango de fechas
     * Usado por: Web (AJAX) y App Móvil
     */
    public function getJobsByDateRange(Request $request)
    {
        try {
            $user = $request->user();
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');
            
            // Log::info('📱 getJobsByDateRange - Usuario: ' . ($user ? $user->id . ' - ' . $user->email : 'NO AUTENTICADO'));
            
            if (!$user) {
                Log::error('❌ getJobsByDateRange - Usuario no autenticado');
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado'
                ], 401);
            }
            
            if (!$start_date || !$end_date) {
                Log::warning('⚠️ getJobsByDateRange - Faltan fechas');
                return response()->json([
                    'success' => false,
                    'message' => 'Se requieren start_date y end_date'
                ], 400);
            }
            
            // Usar query centralizado del modelo Job
            $query = Job::getJobsQuery();
            
            $query->whereRaw("DATE(C.visit_datetime) >= ?", [$start_date])
                  ->whereRaw("DATE(C.visit_datetime) <= ?", [$end_date]);
            
            $query->orderBy('ordervisit', 'ASC');
            
            $jobs = $query->get();
            // Log::info('✅ getJobsByDateRange - ' . $jobs->count() . ' trabajos encontrados para ' . $user->email);
            
            foreach ($jobs as $j) {
                $note = Jobs_Note::where('jobs_id', $j->id)->first();   
                $j->getnotes = $note ? 'si' : 'no';
            }
            
            $permissions = $this->getUserPermissions($user);
            
            return response()->json([
                'success' => true,
                'data' => $jobs,
                'count' => $jobs->count(),
                'permissions' => $permissions
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ getJobsByDateRange - Exception: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener trabajos por rango de fechas',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtener listado completo de tareas con filtros y paginación
     * Usado por: App Móvil
     */
    public function getAllJobs(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado'
                ], 401);
            }

            if (!$user->can('read jobs')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para ver tareas'
                ], 403);
            }

            $page = max((int) $request->input('page', 1), 1);
            $limit = max(min((int) $request->input('limit', 20), 100), 1);
            $search = trim((string) $request->input('search', ''));
            $clientId = $request->input('client_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $status = trim((string) $request->input('status', ''));

            // Para búsquedas de texto, usar páginas más livianas.
            if (!empty($search) && $limit > 50) {
                $limit = 50;
            }

            $cacheKey = 'api_jobs_all:' . md5(json_encode([
                'user' => $user->id,
                'page' => $page,
                'limit' => $limit,
                'search' => $search,
                'client_id' => $clientId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $status,
            ]));

            $cached = Cache::remember($cacheKey, now()->addSeconds(20), function () use (
                $page,
                $limit,
                $search,
                $clientId,
                $startDate,
                $endDate,
                $status
            ) {
                $query = Job::getJobsQuery();

                if (!empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->whereRaw("CL.first_name LIKE ?", ["%$search%"])
                          ->orWhereRaw("CL.last_name LIKE ?", ["%$search%"])
                          ->orWhereRaw("IFNULL(C.job_description,'') LIKE ?", ["%$search%"])
                          ->orWhereRaw("IFNULL(C.closed_job_observation,'') LIKE ?", ["%$search%"])
                          ->orWhereRaw("C.id LIKE ?", ["%$search%"])
                          ->orWhereRaw("IFNULL(C.colppy_budget_number,'') LIKE ?", ["%$search%"]);
                    });
                }

                if ($clientId !== null && $clientId !== '') {
                    $query->where('C.client_id', (int) $clientId);
                }

                if (!empty($startDate)) {
                    $query->whereRaw("DATE(C.visit_datetime) >= ?", [$startDate]);
                }

                if (!empty($endDate)) {
                    $query->whereRaw("DATE(C.visit_datetime) <= ?", [$endDate]);
                }

                if (!empty($status)) {
                    switch ($status) {
                        case 'pendiente':
                            $query->whereNull('C.arrival_datetime')
                                  ->whereNull('C.closed_datetime')
                                  ->where(function ($q) {
                                      $q->whereNull('C.archived')->orWhere('C.archived', 0);
                                  });
                            break;

                        case 'en_lugar':
                            $query->whereNotNull('C.arrival_datetime')
                                  ->whereNull('C.closed_datetime')
                                  ->where(function ($q) {
                                      $q->whereNull('C.archived')->orWhere('C.archived', 0);
                                  });
                            break;

                        case 'cerrada':
                            $query->whereNotNull('C.closed_datetime')
                                  ->where(function ($q) {
                                      $q->whereNull('C.archived')->orWhere('C.archived', 0);
                                  });
                            break;

                        case 'archivada':
                            $query->where('C.archived', 1);
                            break;
                    }
                }

                $total = (clone $query)->count();

                $jobs = $query->orderBy('estatusorder', 'ASC')
                    ->orderBy('ordervisit', 'DESC')
                    ->limit($limit)
                    ->offset(($page - 1) * $limit)
                    ->get();

                $jobIds = $jobs->pluck('id')->toArray();
                $notesIds = Jobs_Note::whereIn('jobs_id', $jobIds)
                    ->select('jobs_id')
                    ->distinct()
                    ->pluck('jobs_id')
                    ->toArray();
                $filesCount = Jobs_file::whereIn('job_id', $jobIds)
                    ->selectRaw('job_id, COUNT(*) as total')
                    ->groupBy('job_id')
                    ->pluck('total', 'job_id')
                    ->toArray();

                foreach ($jobs as $j) {
                    $j->getnotes = in_array($j->id, $notesIds, true) ? 'si' : 'no';
                    $j->images_count = (int) ($filesCount[$j->id] ?? 0);
                }

                return [
                    'total' => $total,
                    'jobs' => $jobs,
                ];
            });

            $permissions = $this->getUserPermissions($user);

            return response()->json([
                'success' => true,
                'data' => $cached['jobs'],
                'count' => $cached['jobs']->count(),
                'total' => $cached['total'],
                'page' => $page,
                'limit' => $limit,
                'permissions' => $permissions
            ]);
        } catch (\Exception $e) {
            Log::error('❌ getAllJobs - Exception: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener listado de tareas',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Buscar clientes
     * Usado por: Web (AJAX) y App Móvil
     */
    /**
     * Obtener clientes para la app móvil
     * Filtra según el modo configurado (local/api/hibrido)
     * Usado por: App Móvil
     */
    public function getClients(Request $request)
    {
        $search = $request->input('search', '');
        $limit = max(min((int) $request->input('limit', 20), 100), 5);
        
        // Obtener modo configurado
        $modo = Config::where('name', 'colppy_clientes_modo')->value('value') ?? 'local';

        $clientsCacheKey = 'api_jobs_clients:' . md5(json_encode([
            'modo' => $modo,
            'search' => $search,
            'limit' => $limit,
        ]));

        $clients = Cache::remember($clientsCacheKey, now()->addSeconds(30), function () use ($modo, $search, $limit) {
            $query = Client::query()
                ->whereNull('deleted_at')
                ->where('is_active', 1);  // Solo clientes activos

            // Aplicar filtro según modo
            switch ($modo) {
                case 'api':
                    // Solo clientes de Colppy
                    $query->where('is_from_colppy', 1);
                    break;
                case 'hibrido':
                    // Todos los clientes (no aplicar filtro adicional)
                    break;
                default: // 'local'
                    // Solo clientes locales (no de Colppy)
                    $query->where(function($q) {
                        $q->where('is_from_colppy', '!=', 1)
                          ->orWhereNull('is_from_colppy');
                    });
                    break;
            }

            // Aplicar búsqueda si existe
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%$search%")
                      ->orWhere('last_name', 'LIKE', "%$search%")
                      ->orWhere('phone1', 'LIKE', "%$search%")
                      ->orWhere('email', 'LIKE', "%$search%");
                });
            }

            return $query
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->limit($limit)
                ->get(['id', 'first_name', 'last_name', 'email', 'phone1']);
        });
        
        return response()->json([
            'success' => true,
            'clients' => $clients,
            'modo' => $modo // Incluir modo en la respuesta para debugging
        ]);
    }
    
    /**
     * Obtener detalle de un trabajo
     * Usado por: Web (AJAX) y App Móvil
     */
    public function show(Request $request, $id)
    {
        $job = Job::leftjoin('clients','jobs.client_id','clients.id')
            ->leftjoin('clients_address','jobs.client_addres_id','clients_address.id')
            ->where('jobs.id',$id)
            ->selectraw("jobs.id,
                jobs.client_id,
                jobs.client_addres_id,
                CONCAT(clients.first_name,' ',IFNULL(clients.last_name,'')) AS client_name,
                IFNULL(clients.phone1,'') AS client_phone,
                CONCAT(IFNULL(clients_address.address_detail,''),' ',
                       IFNULL(clients_address.address_street,''),' ',
                       IFNULL(clients_address.address_nro,''),' ',
                       IFNULL(clients_address.city,'')) AS client_addres_name, 
                jobs.created_at,
                jobs.visit_datetime,
                jobs.arrival_datetime,
                jobs.closed_datetime,
                jobs.job_description,
                CONCAT(jobs.arrival_latitud,',',jobs.arrival_longitud) as arrival_coords,
                CONCAT(jobs.closed_latitud,',',jobs.closed_longitud) as closed_coords,
                jobs.closed_job_observation,
                CASE WHEN jobs.closed_datetime IS NOT NULL THEN 'Cerrado' 
                    WHEN jobs.arrival_datetime IS NOT NULL THEN 'En Lugar'
                ELSE 'Pendiente' END as status")
        ->first();
        
        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Tarea no encontrada'
            ], 404);
        }
        
        $notes = Jobs_Note::with('user:id,name')->where('jobs_id', $id)
            ->selectraw("id, jobs_id, note, DATE_FORMAT(created_at,'%d/%m/%y %H:%i') as created, created_at")
            ->orderby('created_at', 'desc')
            ->get();
        
        $files = Jobs_file::with('user:id,name')->where('job_id', $id)->get();
        
        // Obtener técnicos asignados
        $jobModel = Job::find($id);
        $technicians = $jobModel->technicians()->select('users.id', 'users.name')->get();
        
        // Obtener productos relacionados con el campo is_from_colppy del producto
        $products = JobProduct::where('job_id', $id)
            ->whereNull('job_products.deleted_at')
            ->leftJoin('products', 'job_products.product_id', '=', 'products.id')
            ->select('job_products.*', 'products.is_from_colppy')
            ->get();
        
        // Convertir job a array y agregar técnicos
        $jobData = json_decode(json_encode($job), true);
        $jobData['technicians'] = $technicians->toArray();
        $jobData['products'] = $products->toArray();
        
        $permissions = $this->getUserPermissions($request->user());
        
        return response()->json([
            'success' => true,
            'job' => $jobData,
            'notes' => $notes,
            'files' => $files,
            'permissions' => $permissions
        ]);
    }
    
    /**
     * Obtener notas de un trabajo
     * Usado por: Web (AJAX) y App Móvil
     */
    public function getNotes(Request $request, $id)
    {
        $notes = Jobs_Note::where('jobs_id', $id)
            ->selectraw("id, jobs_id, note, DATE_FORMAT(created_at,'%d/%m/%y %H:%i') as created, created_at")
            ->orderby('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $notes,
            'count' => $notes->count()
        ]);
    }
    
    /**
     * Obtener archivos de un trabajo
     * Usado por: Web (AJAX) y App Móvil
     */
    public function getFiles(Request $request, $id)
    {
        $files = Jobs_file::where('job_id', $id)->get();
        
        return response()->json([
            'success' => true,
            'files' => $files
        ]);
    }
    
    /**
     * Obtener permisos del usuario autenticado
     */
    protected function getUserPermissions($user)
    {
        if (!$user) {
            return [
                'create' => false,
                'read' => false,
                'update' => false,
                'delete' => false,
                'times' => false,
                'all_permissions' => [],
                'roles' => []
            ];
        }
        
        $permissions = $user->getAllPermissions()->pluck('name')->toArray();
        $roles = $user->roles->pluck('name')->toArray();
        
        return [
            'create' => in_array('create jobs', $permissions),
            'read' => in_array('read jobs', $permissions),
            'update' => in_array('update jobs', $permissions),
            'delete' => in_array('delete jobs', $permissions),
            'times' => in_array('times jobs', $permissions),
            'all_permissions' => $permissions,
            'roles' => $roles
        ];
    }
    
    /**
     * Eliminar una nota
     * Usado por: App Móvil
     */
    public function deleteNote(Request $request, $noteId)
    {
        try {
            $note = Jobs_Note::find($noteId);
            
            if (!$note) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nota no encontrada'
                ], 404);
            }
            
            $note->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Nota eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar nota: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Eliminar una imagen/archivo
     * Usado por: App Móvil
     */
    public function deleteFile(Request $request, $fileId)
    {
        try {
            $file = Jobs_file::find($fileId);
            
            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado'
                ], 404);
            }
            
            // Eliminar archivo físico si existe
            $filePath = public_path('storage/' . $file->file);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            $file->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Archivo eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener direcciones de un cliente
     * Usado por: App Móvil
     * Soporta clientes locales (ID numérico) y externos (ID con prefijo como 'colppy_123')
     */
    public function getClientAddresses(Request $request, $clientId)
    {
        // Obtener direcciones de la tabla clients_address
        $addresses = Clients_Addres::where('client_id', $clientId)
            ->whereNull('deleted_at')
            ->get();
        
        return response()->json([
            'success' => true,
            'datos' => $addresses
        ]);
    }
    
    /**
     * Crear nueva dirección para un cliente
     * Usado por: App Móvil
     * Soporta clientes locales (ID numérico) y externos (ID con prefijo como 'colppy_123')
     */
    public function createClientAddress(Request $request)
    {
        // Validar campos base
        $validated = $request->validate([
            'client_id' => 'required', // Puede ser int o string con prefijo
            'address_street' => 'required|string',
            'address_nro' => 'nullable|string',
            'city' => 'required|string',
            'address_detail' => 'nullable|string',
        ]);
        
        $clientId = $validated['client_id'];
        
        // Validar que el cliente existe
        if (!Client::find($clientId)) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ], 404);
        }
        
        $addressData = [
            'address_street' => $validated['address_street'],
            'address_nro' => $validated['address_nro'] ?? null,
            'city' => $validated['city'],
            'address_detail' => $validated['address_detail'] ?? null,
            'country' => 'Argentina',
            'state' => 'Santa Fe',
            'cp' => '2000',
        ];
        
        // Guardar en tabla clients_address
        $address = Clients_Addres::create(array_merge($addressData, [
            'client_id' => $clientId
        ]));
        
        return response()->json([
            'success' => true,
            'address' => $address,
            'message' => 'Dirección creada exitosamente'
        ], 201);
    }

    /**
     * Actualizar solo los técnicos asignados a una tarea
     * PATCH /api/jobs/{id}/technicians
     * Usado por: App Móvil
     */
    public function updateTechnicians(Request $request, $id)
    {
        try {
            $job = Job::findOrFail($id);
            
            // Validar que se envíen técnicos
            $validated = $request->validate([
                'technician_ids' => 'nullable|array',
                'technician_ids.*' => 'integer|exists:users,id'
            ]);
            
            // Sincronizar técnicos
            if (isset($validated['technician_ids'])) {
                $job->technicians()->sync($validated['technician_ids']);
            } else {
                $job->technicians()->sync([]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Técnicos actualizados exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar técnicos: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Buscar productos/servicios para agregar a tareas
     * Usado por: App Móvil
     * CENTRALIZADO: Usa Product::searchProductsAndServices()
     */
    public function getProducts(Request $request)
    {
        $search = $request->input('search', '');
        $tipo = $request->input('tipo', null); // 'P' = Productos, 'S' = Servicios, null = Ambos
        $limit = $request->input('limit', 50);
        
        // USAR MÉTODO CENTRALIZADO DEL MODELO
        $products = Product::searchProductsAndServices($search, $tipo, $limit);
        
        return response()->json([
            'success' => true,
            'data' => $products,
            'count' => $products->count()
        ]);
    }

}
