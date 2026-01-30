<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Jobs_file;
use App\Models\Jobs_Note;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApiJobController extends Controller
{
    /**
     * Obtener citas del día actual O citas abiertas/pendientes
     * Incluye: citas de hoy + citas pasadas que aún no están cerradas
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTodayJobs(Request $request)
    {
        $today = Carbon::today();
        
        // Usar query centralizado del modelo Job
        // Obtener citas de HOY + citas ABIERTAS (no cerradas) de días anteriores
        $jobs = Job::getJobsQuery()
            ->where(function($query) use ($today) {
                $query->whereDate('C.visit_datetime', $today)
                      ->orWhere(function($q) use ($today) {
                          $q->whereDate('C.visit_datetime', '<', $today)
                            ->whereNull('C.closed_datetime'); // Citas pasadas sin cerrar
                      });
            })
            ->whereNull('C.closed_datetime') // Excluir cerradas
            ->orderBy('C.visit_datetime', 'asc')
            ->get();

        // Obtener permisos del usuario autenticado
        $user = $request->user();
        $permissions = $this->getUserPermissions($user);

        return response()->json([
            'success' => true,
            'data' => $jobs,
            'count' => $jobs->count(),
            'date' => $today->format('Y-m-d'),
            'permissions' => $permissions
        ], 200);
    }

    /**
     * Obtener citas por rango de fechas (para calendario)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJobsByDateRange(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Fechas inválidas',
                'errors' => $validator->errors()
            ], 422);
        }

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $jobs = Job::getJobsQuery()
            ->whereBetween('C.visit_datetime', [$startDate, $endDate])
            ->orderBy('C.visit_datetime', 'asc')
            ->get();

        $user = $request->user();
        $permissions = $this->getUserPermissions($user);

        return response()->json([
            'success' => true,
            'data' => $jobs,
            'count' => $jobs->count(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'permissions' => $permissions
        ], 200);
    }

    /**
     * Obtener próximas citas (desde mañana en adelante)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUpcomingJobs(Request $request)
    {
        $limit = $request->input('limit', 50);
        $tomorrow = Carbon::tomorrow();
        
        // Usar query centralizado del modelo Job
        $jobs = Job::getJobsQuery()
            ->whereDate('C.visit_datetime', '>=', $tomorrow)
            ->whereNull('C.closed_datetime')
            ->orderBy('C.visit_datetime', 'asc')
            ->limit($limit)
            ->get();

        // Obtener permisos del usuario autenticado
        $user = $request->user();
        $permissions = $this->getUserPermissions($user);

        return response()->json([
            'success' => true,
            'data' => $jobs,
            'count' => $jobs->count(),
            'permissions' => $permissions
        ], 200);
    }

    /**
     * Obtener detalle de una cita específica
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // Usar query centralizado del modelo Job
        $job = Job::getJobsQuery()
            ->where('C.id', $id)
            ->first();

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Cita no encontrada'
            ], 404);
        }

        // Obtener notas (jobs_notes usa jobs_id)
        $notes = Jobs_Note::where('jobs_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Obtener archivos (jobs_files usa job_id)
        $files = Jobs_file::where('job_id', $id)
            ->get();

        $job->notes = $notes;
        $job->files = $files;

        return response()->json([
            'success' => true,
            'data' => $job
        ], 200);
    }

    /**
     * Marcar llegada a una cita
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markArrival(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'latitud' => 'nullable|numeric',
            'longitud' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $validator->errors()
            ], 422);
        }

        $job = Job::find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Cita no encontrada'
            ], 404);
        }

        if ($job->arrival_datetime) {
            return response()->json([
                'success' => false,
                'message' => 'Ya se marcó llegada para esta cita'
            ], 400);
        }

        $job->arrival_datetime = Carbon::now();
        $job->arrival_latitud = $request->latitud;
        $job->arrival_longitud = $request->longitud;
        $job->save();

        return response()->json([
            'success' => true,
            'message' => 'Llegada marcada exitosamente',
            'data' => $job
        ], 200);
    }

    /**
     * Cerrar una cita
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function closeJob(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'observation' => 'required|string',
            'latitud' => 'nullable|numeric',
            'longitud' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $validator->errors()
            ], 422);
        }

        $job = Job::find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Cita no encontrada'
            ], 404);
        }

        if ($job->closed_datetime) {
            return response()->json([
                'success' => false,
                'message' => 'Esta cita ya está cerrada'
            ], 400);
        }

        $job->closed_datetime = Carbon::now();
        $job->closed_job_observation = $request->observation;
        $job->closed_latitud = $request->latitud;
        $job->closed_longitud = $request->longitud;
        $job->save();

        return response()->json([
            'success' => true,
            'message' => 'Cita cerrada exitosamente',
            'data' => $job
        ], 200);
    }

    /**
     * Añadir nota a una cita
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addNote(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'note' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $validator->errors()
            ], 422);
        }

        $job = Job::find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Cita no encontrada'
            ], 404);
        }

        $note = new Jobs_Note();
        $note->jobs_id = $id;
        $note->note = $request->note;
        $note->user_id = $request->user()->id;
        $note->save();

        return response()->json([
            'success' => true,
            'message' => 'Nota añadida exitosamente',
            'data' => $note
        ], 201);
    }

    /**
     * Obtener notas de una cita
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNotes($id)
    {
        $job = Job::find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Cita no encontrada'
            ], 404);
        }

        $notes = Jobs_Note::where('jobs_id', $id)
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notes,
            'count' => $notes->count()
        ], 200);
    }

    /**
     * Obtener archivos de una cita
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFiles($id)
    {
        $job = Job::find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Cita no encontrada'
            ], 404);
        }

        $files = Jobs_file::where('job_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $files,
            'count' => $files->count()
        ], 200);
    }

    /**
     * Crear nueva tarea/cita
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $permissions = $this->getUserPermissions($user);

        if (!$permissions['create']) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para crear tareas'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'visit_datetime' => 'required|date',
            'job_description' => 'nullable|string',
            'visit_latitud' => 'nullable|numeric',
            'visit_longitud' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $job = Job::create([
                'client_id' => $request->client_id,
                'visit_datetime' => $request->visit_datetime,
                'job_description' => $request->job_description,
                'visit_latitud' => $request->visit_latitud,
                'visit_longitud' => $request->visit_longitud,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tarea creada exitosamente',
                'data' => $job
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar tarea/cita
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $permissions = $this->getUserPermissions($user);

        if (!$permissions['update']) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para actualizar tareas'
            ], 403);
        }

        $job = Job::find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Tarea no encontrada'
            ], 404);
        }

        // No permitir editar si ya marcó llegada
        if ($job->arrival_datetime) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede editar una tarea donde ya se marcó llegada'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'client_id' => 'sometimes|exists:clients,id',
            'visit_datetime' => 'sometimes|date',
            'job_description' => 'nullable|string',
            'visit_latitud' => 'nullable|numeric',
            'visit_longitud' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $job->update($request->only([
                'client_id',
                'visit_datetime',
                'job_description',
                'visit_latitud',
                'visit_longitud'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Tarea actualizada exitosamente',
                'data' => $job
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar tarea/cita
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $permissions = $this->getUserPermissions($user);

        if (!$permissions['delete']) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para eliminar tareas'
            ], 403);
        }

        $job = Job::find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Tarea no encontrada'
            ], 404);
        }

        // No permitir eliminar si ya marcó llegada
        if ($job->arrival_datetime) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una tarea donde ya se marcó llegada'
            ], 403);
        }

        try {
            $job->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tarea eliminada exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Subir archivos/imágenes a una tarea
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadFiles(Request $request, $id)
    {
        $user = $request->user();
        $permissions = $this->getUserPermissions($user);

        if (!$permissions['update']) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para subir archivos'
            ], 403);
        }

        $job = Job::find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Tarea no encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'files.*' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240' // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Archivos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $uploadedFiles = [];

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('jobs_files', $filename, 'public');

                    $jobFile = Jobs_file::create([
                        'job_id' => $id,
                        'file_path' => $path,
                        'file_name' => $filename,
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                    ]);

                    $uploadedFiles[] = $jobFile;
                }
            }

            return response()->json([
                'success' => true,
                'message' => count($uploadedFiles) . ' archivo(s) subido(s) exitosamente',
                'data' => $uploadedFiles
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir archivos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Volver tarea a estado pendiente (solo sistema/admin)
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function backToPending(Request $request, $id)
    {
        $user = $request->user();
        $permissions = $this->getUserPermissions($user);

        // Solo sistema/admin pueden volver a pendiente
        $roles = $permissions['roles'];
        if (!in_array('sistema', $roles) && !in_array('admin', $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para realizar esta acción'
            ], 403);
        }

        $job = Job::find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Tarea no encontrada'
            ], 404);
        }

        if (!$job->arrival_datetime || $job->closed_datetime) {
            return response()->json([
                'success' => false,
                'message' => 'La tarea debe tener llegada marcada y no estar cerrada'
            ], 400);
        }

        try {
            $job->arrival_datetime = null;
            $job->arrival_latitud = null;
            $job->arrival_longitud = null;
            $job->save();

            return response()->json([
                'success' => true,
                'message' => 'Tarea vuelta a estado pendiente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener lista de clientes para crear tareas
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClients(Request $request)
    {
        $search = $request->get('search', '');

        $query = Client::select('id', 'first_name', 'last_name', 'email', 'phone1')
            ->whereNull('deleted_at');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'LIKE', "%$search%")
                  ->orWhere('last_name', 'LIKE', "%$search%")
                  ->orWhere('email', 'LIKE', "%$search%");
            });
        }

        $clients = $query->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $clients,
            'count' => $clients->count()
        ], 200);
    }

    /**
     * Obtener permisos del usuario para el módulo de jobs
     * 
     * @param User $user
     * @return array
     */
    private function getUserPermissions($user)
    {
        if (!$user) {
            return [
                'create' => false,
                'read' => false,
                'update' => false,
                'delete' => false
            ];
        }

        $allPermissions = $user->getAllPermissions()->pluck('name')->toArray();
        
        return [
            'create' => in_array('create jobs', $allPermissions),
            'read' => in_array('read jobs', $allPermissions),
            'update' => in_array('update jobs', $allPermissions),
            'delete' => in_array('delete jobs', $allPermissions),
            'all_permissions' => $allPermissions,
            'roles' => $user->getRoleNames()->toArray()
        ];
    }
}
