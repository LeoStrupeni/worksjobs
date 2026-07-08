<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Clients_Addres;
use App\Models\Config;
use App\Models\Job;
use App\Models\Jobs_file;
use App\Models\Jobs_Note;
use App\Models\JobProduct;
use App\Models\Product;
use App\Services\ColppyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Font;

class JobController extends Controller
{
    private function canEditJobTimes(): bool
    {
        $sessionPermissions = Session::get('user.permissions.jobs', []);
        if (is_array($sessionPermissions) && in_array('times', $sessionPermissions, true)) {
            return true;
        }

        $user = Auth::user();
        return $user ? Gate::forUser($user)->allows('times jobs') : false;
    }

    private function denyEditTimes(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para editar tiempos de arribo/cierre.'
            ], 403);
        }

        return back()->with('error', 'No tiene permisos para editar tiempos de arribo/cierre.');
    }

    private function denyEditClosedTime(Request $request, string $message = 'No se puede editar fecha/hora de cierre en tareas sin cierre registrado.')
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message
            ], 422);
        }

        return back()->with('error', $message);
    }

    public function index()
    {
        if(Auth::check()){
            $val = $this->getloginrol();
            if ($val == false){
                return redirect()->route('logout');     
            }

            $fecha1 = date('m/01/Y', strtotime('-2 month'));
            $fecha2 = date('m/d/Y');
            $fechaRango = $fecha1 . ' - ' . $fecha2;
            return view("jobs", compact('fechaRango'));
        }
       
        return redirect()->route('login');
    }


    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        // dd($request->all(),$request->hasFile('images'));
        $request->validate([
                'client_id' => ['required'],
                'address_id' => ['required'],
                'visit_datetime' => ['required'],
                'job_description' => ['required']
            ],
            [
                'required' => 'El campo es requerido.',
            ]
        );

        $job = Job::create([
            'client_id' => $request->client_id,
            'client_addres_id' => $request->address_id,
            'visit_datetime' => $this->convertDateTimeFormat($request->visit_datetime),
            'job_description' => $request->job_description,
            'visit_latitud' => $request->latitude,
            'visit_longitud' => $request->longitude,
            'visit_coords_status' => $request->latitude != null && $request->longitude != null ? '1' : '0',
            'visit_json_coords' => $request->jsongeolocation,
            'colppy_budget_id' => $request->colppy_budget_id ?? null,
            'colppy_budget_number' => $request->colppy_budget_number ?? null
        ]);

        // Sincronizar técnicos asignados
        if ($request->has('technician_ids')) {
            $job->technicians()->sync($request->technician_ids ?? []);
        }

        // Guardar productos relacionados
        if ($request->has('products') && is_array($request->products)) {
            foreach ($request->products as $productData) {
                if (isset($productData['product_id'])) {
                    // Buscar información del producto
                    $product = Product::find($productData['product_id']);
                    if ($product) {
                        JobProduct::create([
                            'job_id' => $job->id,
                            'product_id' => $product->id,
                            'idcolppy' => $product->idcolppy,
                            'codigo' => $product->codigo,
                            'descripcion' => $product->descripcion,
                            'unit_type' => $productData['unit_type'] ?? 'Unidad',
                            'quantity' => $productData['quantity'] ?? 1.00
                        ]);
                    }
                }
            }
        }

        $this->addfiles($request, $job->id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true, 
                'message' => 'Tarea creada correctamente',
                'job' => $job
            ]);
        }
        return back();
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $job = Job::leftjoin('clients','jobs.client_id','clients.id')
            ->leftjoin('clients_address','jobs.client_addres_id','clients_address.id')
            ->where('jobs.id',$id)
            ->selectraw("jobs.id,
                jobs.client_id,
                jobs.client_addres_id,
                CONCAT(clients.first_name,' ',IFNULL(clients.last_name,'')) AS client_name, 
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
                jobs.closed_job_observation")
        ->first();

        // Obtener direcciones de la tabla clients_address
        $address = Clients_Addres::where('client_id', $job->client_id)
            ->whereNull('deleted_at')
            ->get();
        
        $files = Jobs_file::with('user:id,name')->where('job_id',$id)->get();
        // Adjuntamos la URL de la ruta puente a cada archivo antes de enviarlo
        foreach ($files as $file) {
            // Pasamos el ID de drive (guardado en name) a nuestra ruta para generar la URL final
            $file->url_web = route('drive.file', ['id' => $file->name]);
        }
        
        // Obtener técnicos asignados al job
        $jobModel = Job::find($id);
        $repuesta['technicians'] = $jobModel->technicians()->select('users.id', 'users.name')->get();
        
        // Obtener productos relacionados
        $repuesta['products'] = JobProduct::where('job_id', $id)
            ->whereNull('deleted_at')
            ->get();

        $repuesta['notes'] = Jobs_Note::with('user:id,name')->where('jobs_id', $id)
            ->selectraw("jobs_notes.id,jobs_notes.jobs_id, jobs_notes.note, DATE_FORMAT(jobs_notes.created_at,'%d/%m/%y %H:%i') as created, jobs_notes.created_at, jobs_notes.created_by" )
            ->orderby('created_at','desc')
            ->get();

        // Convertir fechas de BD (YYYY-MM-DD HH:mm:ss) a formato frontend (DD/MM/YYYY HH:mm)
        $dateFields = ['visit_datetime', 'arrival_datetime', 'closed_datetime'];
        foreach ($dateFields as $field) {
            if ($job->$field) {
                try {
                    $date = \DateTime::createFromFormat('Y-m-d H:i:s', $job->$field);
                    if ($date !== false) {
                        $job->$field = $date->format('d/m/Y H:i');
                    }
                } catch (\Exception $e) {
                    // Si falla, intentar con formato sin segundos
                    try {
                        $date = \DateTime::createFromFormat('Y-m-d H:i', $job->$field);
                        if ($date !== false) {
                            $job->$field = $date->format('d/m/Y H:i');
                        }
                    } catch (\Exception $e2) {
                        // Log::warning('No se pudo convertir fecha para edición', [
                        //     'field' => $field,
                        //     'value' => $job->$field,
                        //     'error' => $e2->getMessage()
                        // ]);
                    }
                }
            }
        }

        $repuesta['job'] = $job;
        $repuesta['address'] = $address;
        $repuesta['files'] = $files;
        
        // Agregar permisos al response
        $permissions = Session::get('user')['permissions'];
        $repuesta['permissions'] = [
            'jobs' => $permissions['jobs'] ?? [],
            'share' => $permissions['share'] ?? [],
            'pdf' => $permissions['pdf'] ?? []
        ];
        
        return $repuesta;
    }

    public function update(Request $request, $id)
    {
        $job = Job::find($id);
        $canEditTimes = $this->canEditJobTimes();

        if (($request->filled('arrival_datetime') || $request->filled('closed_datetime')) && !$canEditTimes) {
            return $this->denyEditTimes($request);
        }

        $datos = [];

        if(isset($request->client_id)){
            $request->validate(['client_id' => ['required']],
                [ 'required' => 'El campo es requerido.']
            );
        }

        if(isset($request->visit_datetime) && $request->visit_datetime != $job->visit_datetime){
            $request->validate(['visit_datetime' => ['required']],
                [ 'required' => 'El campo es requerido.']
            );
            $datos['visit_datetime'] = $this->convertDateTimeFormat($request->visit_datetime);
        }

        if(isset($request->job_description) && $request->job_description != $job->job_description){
            $request->validate(['job_description' => ['required']],
                [ 'required' => 'El campo es requerido.']
            );
            $datos['job_description'] = $request->job_description;
        }

        if(isset($request->address_id) && $request->address_id != $job->client_addres_id){
            $request->validate(['address_id' => ['required']],
                [ 'required' => 'El campo es requerido.']
            );
            $datos['client_addres_id'] = $request->address_id;
        }

        if ($request->has('arrival_datetime') && $canEditTimes) {
            $arrivalDatetime = trim((string) $request->input('arrival_datetime', ''));
            $newArrivalDatetime = $arrivalDatetime === ''
                ? null
                : $this->convertDateTimeFormat($arrivalDatetime);

            if ($newArrivalDatetime != $job->arrival_datetime) {
                $datos['arrival_datetime'] = $newArrivalDatetime;
            }
        }

        if ($request->has('closed_datetime') && $canEditTimes) {
            if (empty($job->closed_datetime)) {
                return $this->denyEditClosedTime($request);
            }

            $closedDatetime = trim((string) $request->input('closed_datetime', ''));
            if ($closedDatetime === '') {
                return $this->denyEditClosedTime($request, 'Debe indicar fecha/hora de cierre para editar el presupuesto asociado.');
            }

            $newClosedDatetime = $this->convertDateTimeFormat($closedDatetime);
            if ($newClosedDatetime != $job->closed_datetime) {
                $datos['closed_datetime'] = $newClosedDatetime;
            }
        }

        if( (isset($request->visit_datetime) && $request->visit_datetime != $job->visit_datetime)
            || (isset($request->job_description) && $request->job_description != $job->job_description)) {
            $datos['visit_latitud'] = $request->latitude;
            $datos['visit_longitud'] = $request->longitude;
            $datos['visit_coords_status'] = $request->latitude != null && $request->longitude != null ? '1' : '0';
            $datos['visit_json_coords'] = $request->jsongeolocation;
        }

        if(count($datos) > 0){
            Job::where('id',$id)->update($datos);
        }

        // Sincronizar técnicos asignados (siempre, incluso si viene vacío para permitir deseleccionar todos)
        $jobModel = Job::find($id);
        $jobModel->technicians()->sync($request->technician_ids ?? []);

        // Actualizar productos relacionados
        if ($request->has('products')) {
            JobProduct::where('job_id', $id)->delete();

            if (is_array($request->products)) {
                foreach ($request->products as $productData) {
                    if (isset($productData['product_id'])) {
                        $product = Product::find($productData['product_id']);
                        if ($product) {
                            JobProduct::create([
                                'job_id' => $id,
                                'product_id' => $product->id,
                                'idcolppy' => $product->idcolppy,
                                'codigo' => $product->codigo,
                                'descripcion' => $product->descripcion,
                                'unit_type' => $productData['unit_type'] ?? 'Unidad',
                                'quantity' => $productData['quantity'] ?? 1.00
                            ]);
                        }
                    }
                }
            }
        }

        $this->addfiles($request, $id);

        $timeFieldsTouched = array_key_exists('arrival_datetime', $datos) || array_key_exists('closed_datetime', $datos);
        $jobModel = Job::find($id);
        $shouldSyncColppyBudget = $timeFieldsTouched
            && !empty($jobModel->colppy_budget_id)
            && !empty($jobModel->arrival_datetime)
            && !empty($jobModel->closed_datetime);

        if ($shouldSyncColppyBudget) {
            $this->syncColppyBudgetFromEditedTimes($id);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Tarea actualizada correctamente']);
        }

        return back();
    }

    public function destroy(Request $request, $id)
    {
        Job::find($id)->update([
            'deleted_at' => Carbon::now()
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Tarea eliminada correctamente']);
        }
        return back();
    }

    public function markarrival(Request $request)
    {
        // Soportar parámetros de web (job_id, arrival_latitud, arrival_longitud) 
        // y de app móvil (id en ruta, latitud, longitud)
        $job_id = $request->job_id ?? $request->route('id');
        $latitud = $request->arrival_latitud ?? $request->latitud;
        $longitud = $request->arrival_longitud ?? $request->longitud;
        
        Job::where('id', $job_id)->update([
            'arrival_datetime' => Carbon::now(),
            'arrival_latitud' => $latitud,
            'arrival_longitud' => $longitud,
            'arrival_coords_status' => '1',
            'arrival_json_coords' => $request->jsongeolocation
        ]);

        // Auto-asignar al técnico actual si no fue asignado previamente
        $currentUserId = Auth::id();
        if ($currentUserId) {
            $isTechnician = DB::table('model_has_roles')
                ->where('model_id', $currentUserId)
                ->whereNotIn('role_id', [3, 4])
                ->exists();
            if ($isTechnician) {
                $jobModel = Job::find($job_id);
                $jobModel->technicians()->syncWithoutDetaching([$currentUserId]);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Arribo marcado correctamente']);
        }
        return 1;
    }

    public function backarrival(Request $request)
    {
        // Soportar parámetros de web (job_id) y de app móvil (id en ruta)
        $job_id = $request->job_id ?? $request->route('id');
        
        Job::where('id', $job_id)->update([
            'arrival_datetime' => null,
            'arrival_latitud' => null,
            'arrival_longitud' => null,
            'arrival_coords_status' => '0',
            'arrival_json_coords' => null
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Arribo revertido correctamente']);
        }
        return 1;
    }

    public function closed(Request $request)
    {
        // Soportar parámetros de web (id, latitude, longitude) 
        // y de app móvil (id en ruta, latitud, longitud)
        $job_id = $request->id ?? $request->route('id');
        $latitud = $request->latitude ?? $request->latitud;
        $longitud = $request->longitude ?? $request->longitud;

        $closedDateTime = Carbon::now();
        if ($this->canEditJobTimes() && $request->filled('closed_datetime')) {
            $closedDateTime = $this->convertDateTimeFormat($request->closed_datetime);
        }

        Job::where('id', $job_id)->update([
            'closed_datetime' => $closedDateTime,
            'closed_latitud' => $latitud,
            'closed_longitud' => $longitud,
            'closed_coords_status' => 1,
            'closed_json_coords' => $request->jsongeolocation,
        ]);

        // Intentar generar presupuesto en Colppy
        try {
            $this->generarPresupuestoColppy($job_id);
        } catch (\Exception $e) {
            // Log del error pero no interrumpir el cierre de la tarea
            // Log::warning('Error al generar presupuesto en Colppy al cerrar tarea', [
            //     'job_id' => $job_id,
            //     'error' => $e->getMessage()
            // ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Tarea cerrada correctamente']);
        }
        return back();
    }

    public function addnote(Request $request)
    {
        // Soportar parámetros de web y de app móvil
        $job_id = $request->job_id ?? $request->route('id');
        
        if (!$request->note) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'La nota es requerida'], 400);
            }
            return back()->withErrors(['note' => 'El campo es requerido.']);
        }
        
        $note = Jobs_Note::create([
            'jobs_id' => $job_id,
            'note' => $request->note,
            'latitud' => $request->latitud,
            'longitud' => $request->longitud,
            'json_coords' => $request->jsongeolocation
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true, 
                'message' => 'Nota agregada correctamente',
                'note' => [
                    'id' => $note->id,
                    'jobs_id' => $note->jobs_id,
                    'note' => $note->note,
                    'created' => $note->created_at->format('d/m/y H:i'),
                    'created_at' => $note->created_at
                ]
            ]);
        }
        return 1;
    }

    public function getnotes(Request $request, $job_id = null)
    {
        // Soportar job_id como parámetro de ruta o del query string
        $job_id = $job_id ?? $request->route('id');
        
        $notes = Jobs_Note::with('user:id,name')->where('jobs_id', $job_id)
            ->selectraw("jobs_notes.id,jobs_notes.jobs_id, jobs_notes.note, DATE_FORMAT(jobs_notes.created_at,'%d/%m/%y %H:%i') as created, jobs_notes.created_at, jobs_notes.created_by" )
            ->orderby('created_at','desc')
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $notes,  // La app espera 'data', no 'notes'
                'count' => $notes->count()
            ]);
        }

        $permissions = Session::get('user')['permissions']['jobs'];
        $respuesta['permissions'] = $permissions;
        $respuesta['datos'] = $notes;
        return $respuesta;
    }
    
    public function destroynote(Request $request, $id)
    {
        $note = Jobs_Note::find($id);

        if ($note) {
            $note->delete();
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Nota eliminada correctamente']);
        }
        return 1;
    }

    public function onlyaddfiles(Request $request)
    {
        // dd($request->all() );
        // Soportar id en body o en ruta (Tu lógica intacta)
        $job_id = $request->id ?? $request->route('id');
        
        // Ejecuta la subida a Google Drive, la optimización y el guardado en BD 
        // que modificamos en el método addfiles
        $this->addfiles($request, $job_id);
        
        // Respuesta al usuario
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true, 
                'message' => 'Imágenes organizadas y subidas a Google Drive correctamente.'
            ]);
        }
        
        return back()->with('success', 'Archivos guardados en Google Drive.');
    }

    protected function addfiles(Request $request, $job_id)
    {
        // Buscamos la tarea y su cliente para pasárselos a la función centralizada
        $job = Job::with('client')->find($job_id); 
        $nombreCliente = $job && $job->client ? $job->client->first_name.($job->client->last_name!=null? ' '.$job->client->last_name : '') : 'cliente-desconocido';
        $nombreTarea = 'tarea_' . $job_id;
        
        // Soportar tanto 'images' como 'files'
        $fileField = $request->hasFile('images') ? 'images' : 'files';
        $capturedAt = $request->input('captured_at');
        $capturedLatitude = $request->input('captured_latitude');
        $capturedLongitude = $request->input('captured_longitude');
        $uploadSource = $request->input('upload_source', 'web_panel');
        $appQueueId = $request->input('app_queue_id');
        $clientCompressed = $request->boolean('client_compressed', false);
        
        if ($request->hasFile($fileField)) {
            $file = $request->file($fileField);

            foreach ($file as $attachment) {
                $checksum = hash_file('sha256', $attachment->getRealPath());

                // Evitar duplicados por reintentos: misma tarea + mismo archivo
                $alreadyExists = Jobs_file::where('job_id', $job_id)
                    ->where('checksum', $checksum)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($alreadyExists) {
                    continue;
                }

                $n = 10;
                $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $randomString = '';
                for ($i = 0; $i < $n; $i++) { $index = random_int(0, strlen($characters) - 1); $randomString .= $characters[$index];}

                $extension = strtolower($attachment->getClientOriginalExtension());
                $filename = $job_id.'_'.time().'_'.$randomString.'.'.$extension;
                
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif']) && !$clientCompressed) {
                    // 1. Si es imagen, la optimizas local en el servidor temporalmente
                    $optimizedPath = $this->optimizeAndSaveImage($attachment, $filename);
                    
                    // Obtenemos el archivo optimizado real para mandarlo a Drive
                    $fileForDrive = new \Illuminate\Http\File(storage_path('app/public/' . $filename));
                    
                    // LLAMADA CENTRALIZADA AL PARENT CONTROLLER
                    $resultadoDrive = $this->subirArchivoADrive($fileForDrive, $nombreCliente, $nombreTarea, $filename);
                    
                    // Limpiamos el servidor local eliminando el temporal optimizado
                    unlink(storage_path('app/public/' . $filename));
                } else {
                    // 2. Si no es imagen, se sube directo desde el request a Drive sin tocar el disco local
                    // LLAMADA CENTRALIZADA AL PARENT CONTROLLER
                    $resultadoDrive = $this->subirArchivoADrive($attachment, $nombreCliente, $nombreTarea, $filename);
                }
                
                Jobs_file::create([
                    'job_id' => $job_id,
                    'name' => $resultadoDrive['ruta_completa'],
                    'original_name' => $attachment->getClientOriginalName(),
                    'original_extension' => $attachment->getClientOriginalExtension(),
                    'checksum' => $checksum,
                    'captured_at' => $capturedAt,
                    'captured_latitude' => $capturedLatitude,
                    'captured_longitude' => $capturedLongitude,
                    'uploaded_at' => Carbon::now(),
                    'upload_source' => $uploadSource,
                    'app_queue_id' => $appQueueId,
                ]);
            }
        }
    }

    /**
     * Optimiza y guarda una imagen redimensionándola y comprimiéndola
     */
    private function optimizeAndSaveImage($file, $filename)
    {
        // Configuración optimizada para fotos de cámaras de alta resolución
        $maxWidth = 600;   // Ancho máximo (suficiente para PDFs y visualización)
        $maxHeight = 600;  // Alto máximo
        $jpegQuality = 60; // Calidad JPEG reducida para fotos de cámara
        $pngCompression = 9; // Compresión PNG máxima

        $extension = strtolower($file->getClientOriginalExtension());
        $tempPath = $file->getRealPath();
        
        // Log::info("Optimizando imagen: $filename - Extension: $extension");
        
        // Crear imagen desde archivo temporal
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $sourceImage = imagecreatefromjpeg($tempPath);
                break;
            case 'png':
                $sourceImage = imagecreatefrompng($tempPath);
                break;
            case 'gif':
                $sourceImage = imagecreatefromgif($tempPath);
                break;
            default:
                // Si no se puede procesar, guardar como está
                return $file->storeAs('public', $filename);
        }

        if (!$sourceImage) {
            // Si falla la creación de imagen, guardar sin optimizar
            // Log::warning("No se pudo crear imagen desde: $filename");
            return $file->storeAs('public', $filename);
        }

        // Obtener dimensiones originales
        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);
        
        // Log::info("Dimensiones originales: {$originalWidth}x{$originalHeight}");

        // Calcular nuevas dimensiones manteniendo proporción
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        
        // SIEMPRE redimensionar para optimizar espacio
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);
        
        // Log::info("Nuevas dimensiones: {$newWidth}x{$newHeight} (ratio: $ratio)");

        // Crear imagen redimensionada
        $optimizedImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preservar transparencia para PNG y GIF
        if ($extension === 'png' || $extension === 'gif') {
            imagealphablending($optimizedImage, false);
            imagesavealpha($optimizedImage, true);
            $transparent = imagecolorallocatealpha($optimizedImage, 255, 255, 255, 127);
            imagefilledrectangle($optimizedImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Redimensionar con mejor calidad de resampling
        imagecopyresampled($optimizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        // Guardar imagen optimizada
        $storagePath = storage_path('app/public/' . $filename);
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($optimizedImage, $storagePath, $jpegQuality);
                // Log::info("Imagen JPEG guardada con calidad $jpegQuality");
                break;
            case 'png':
                imagepng($optimizedImage, $storagePath, $pngCompression);
                // Log::info("Imagen PNG guardada con compresión $pngCompression");
                break;
            case 'gif':
                imagegif($optimizedImage, $storagePath);
                break;
        }
        
        // Verificar tamaño del archivo guardado
        $fileSize = filesize($storagePath);
        // Log::info("Tamaño final del archivo: " . round($fileSize / 1024, 2) . " KB");

        // Liberar memoria
        imagedestroy($sourceImage);
        imagedestroy($optimizedImage);

        return 'public/' . $filename;
    }

    public function destroyfile($id)
    {
        // Buscamos el registro del archivo en la base de datos
        $file = Jobs_file::find($id);

        if ($file) {
            // LLAMADA CENTRALIZADA: Eliminamos el archivo físico de Google Drive usando su ID
            // Pasamos $file->name que es donde se guardó el ID de Drive al subirlo
            $this->eliminarArchivoDeDrive($file->name);

            // Tu lógica de borrado lógico (Soft Delete) intacta
            $file->update([
                'deleted_at' => \Carbon\Carbon::now() // Aseguramos el namespace de Carbon por si acaso
            ]);
        }

        // Retornamos el listado actualizado de archivos activos de la tarea (Tu retorno intacta)
        return Jobs_file::where('job_id', $file->job_id)->whereNull('deleted_at')->get();
    }
    
    public function destroyallfiles($id)
    {
        $files = Jobs_file::where('job_id',$id)->get();
        foreach ($files as $file) {
            $path = 'storage/'.$file->name;
            if (file_exists($path)) {
                unlink($path);
            }
            $file->update([
                'deleted_at' => Carbon::now()
            ]);
        }
        return 1;
    }

    public function archive(Request $request, $id)
    {
        $job = Job::findOrFail($id);
        $job->archived = $request->input('archived', 1);
        $job->save();
        
        return response()->json([
            'success' => true,
            'message' => $job->archived == 1 ? 'Tarea archivada correctamente' : 'Tarea desarchivada correctamente'
        ]);
    }

    /**
     * Agregar productos directamente a una tarea existente
     */
    public function updateProducts(Request $request, $id)
    {
        try {
            $job = Job::findOrFail($id);
            
            // Verificar que no esté cerrado Y archivado
            if ($job->closed_datetime != null && $job->archived == 1) {
                return redirect()->back()->with('error', 'No se pueden agregar productos a una tarea cerrada y archivada');
            }
            
            // Eliminar productos existentes (soft delete)
            JobProduct::where('job_id', $id)->delete();
            
            // Agregar nuevos productos
            if ($request->has('products') && is_array($request->products)) {
                foreach ($request->products as $p) {
                    $product = Product::find($p['product_id']);
                    if ($product) {
                        JobProduct::create([
                            'job_id' => $id,
                            'product_id' => $product->id,
                            'idcolppy' => $product->idcolppy,
                            'codigo' => $product->codigo,
                            'descripcion' => $product->descripcion,
                            'unit_type' => $p['unit_type'],
                            'quantity' => $p['quantity']
                        ]);
                    }
                }
            }
            
            return redirect()->back()->with('success', 'Productos actualizados correctamente');
        } catch (\Exception $e) {
            // Log::error('Error al actualizar productos: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar productos');
        }
    }

    /**
     * Generar presupuesto en Colppy al cerrar una tarea
     * 
     * @param int $job_id ID de la tarea
     * @return void
     */
    public function generarPresupuestoColppy($job_id)
    {
        $job = Job::with(['client', 'products.product', 'notes'])->find($job_id);
        
        if (!$job) {
            // Log::warning('Tarea no encontrada para generar presupuesto', ['job_id' => $job_id]);
            return;
        }

        // Si la tarea ya tiene un presupuesto asociado, no generar uno nuevo
        if ($job->colppy_budget_id) {
            // Log::info('Tarea ya tiene presupuesto asociado, no se genera nuevo presupuesto', [
            //     'job_id' => $job_id,
            //     'colppy_budget_id' => $job->colppy_budget_id
            // ]);
            return;
        }

        // Validar que el cliente tenga idcolppy
        if (!$job->client || !$job->client->idcolppy) {
            // Log::info('Cliente sin idcolppy, no se genera presupuesto', [
            //     'job_id' => $job_id,
            //     'client_id' => $job->client->id ?? null
            // ]);
            return;
        }

        // Construir items del presupuesto según estructura de Colppy
        $items = [];

        // 1. Agregar servicio de mano de obra (código 14)
        $servicioManoObra = Product::where('codigo', '14')
            ->where('tipo_item', 'S')
            ->whereNotNull('colppy_id')
            ->whereNull('deleted_at')
            ->first();

        if ($servicioManoObra) {
            // Calcular cantidad de horas trabajadas
            $totalMinutos = 0;
            $cantidadHorasDecimal = 1.0;
            $descripcion = $servicioManoObra->descripcion;
            $comentario = '';
            if ($job->arrival_datetime && $job->closed_datetime) {
                try {
                    $fechaIngreso = Carbon::parse($job->arrival_datetime);
                    $fechaCierre = Carbon::parse($job->closed_datetime);
                    
                    // Calcular diferencia en minutos
                    $totalMinutos = $fechaCierre->diffInMinutes($fechaIngreso);
                    $cantidadHorasDecimal = round($totalMinutos / 60, 2);
                    
                    // Asegurar que sea al menos 0.01 si hay diferencia
                    if ($cantidadHorasDecimal <= 0 && $totalMinutos > 0) {
                        $cantidadHorasDecimal = 0.01;
                    }
                    
                    // Construir descripción con fechas y minutos
                    $arriboStr = $fechaIngreso->format('d/m H:i');
                    $salidaStr = $fechaCierre->format('d/m H:i');
                    // $descripcion .= " (Arribo: {$arriboStr} - Salida: {$salidaStr} - {$totalMinutos} min)";
                    $comentario = "Arribo: {$arriboStr} - Salida: {$salidaStr} - {$totalMinutos} min";

                } catch (\Exception $e) {
                    // Log::warning('Error al calcular horas de trabajo', [
                    //     'job_id' => $job_id,
                    //     'error' => $e->getMessage()
                    // ]);
                }
            }
            
            $items[] = [
                'Descripcion' => $descripcion,
                'unidadMedida' => 'U',
                'Cantidad' => $cantidadHorasDecimal,
                'ImporteUnitario' => $servicioManoObra->precio_venta,
                'porcDesc' => '0',
                'IVA' => '21',
                'idPlanCuenta' => 'Ingresos Por Servicios',
                'Comentario' => '',
                // Campos para servicios de inventario
                'idItem' => $servicioManoObra->colppy_id,
                'codigo' => $servicioManoObra->codigo,
                'tipoItem' => 'S',  // S = Servicio
                'Comentario' => $comentario
            ];
        } else {
            // Log::warning('No se encontró servicio de mano de obra con código 14', [
            //     'job_id' => $job_id
            // ]);
        }

        // 2. Agregar productos de la tarea
        foreach ($job->products as $jobProduct) {
            if ($jobProduct->product && $jobProduct->product->idcolppy) {
                $item = [
                    'Descripcion' => $jobProduct->product->descripcion,
                    'unidadMedida' => 'U',  // U = Unidades en Colppy
                    'Cantidad' => $jobProduct->quantity,
                    'ImporteUnitario' => 1, // Precio fijo según especificación
                    'porcDesc' => '100.00',
                    'IVA' => '21',
                    'idPlanCuenta' => 'Ventas de mercaderías',
                    'Comentario' => '',
                    // Campos obligatorios para productos de inventario
                    'idItem' => $jobProduct->product->idcolppy,
                    'codigo' => $jobProduct->product->codigo,
                    'tipoItem' => 'P'  // P = Producto
                ];
                
                // Campos opcionales si existen en el producto
                if (isset($jobProduct->product->stock_minimo)) {
                    $item['minimo'] = $jobProduct->product->stock_minimo;
                }
                
                $items[] = $item;
            }
        }

        // Si no hay items, no generar presupuesto
        if (empty($items)) {
            // Log::info('No hay items para generar presupuesto', ['job_id' => $job_id]);
            return;
        }

        // Preparar datos para el presupuesto según formato de Colppy
        $fechaActual = Carbon::now()->format('d-m-Y');
        $colppyService = new ColppyService();
        $talonario = '0002';
        
        // Sistema de reintentos para manejar conflictos de numeración
        $maxIntentos = 3;
        $intentoActual = 0;
        $presupuestoCreado = false;
        
        while ($intentoActual < $maxIntentos && !$presupuestoCreado) {
            $intentoActual++;
            
            // Obtener próximo número de talonario desde Colppy API
            $resultadoTalonario = $colppyService->obtenerProximoNumeroTalonario('0002', 'FAV-FE');
            
            if (!$resultadoTalonario['success']) {
                Log::error('No se pudo obtener próximo número de talonario para presupuesto', [
                    'job_id' => $job_id,
                    'intento' => $intentoActual,
                    'error' => $resultadoTalonario['mensaje'] ?? 'Error desconocido'
                ]);
                
                // Si es el último intento, salir
                if ($intentoActual >= $maxIntentos) {
                    return;
                }
                
                // Esperar 1 segundo antes de reintentar
                sleep(1);
                continue;
            }
            
            $numeroPresupuesto = $resultadoTalonario['proximoNum'];
            
            // Preparar datos del presupuesto
            $datosPresupuesto = [
                'descripcion' => 'Generado desde la tarea #' . $job_id,
                'fechaFactura' => $fechaActual,
                'fechaPago' => $fechaActual,
                'idCliente' => $job->client->idcolppy,
                'idCondiciónPago' => 'a 7 Dias',
                'idEstadoFactura' => 'Borrador',
                'idEstadoAnterior' => '',
                'idFactura' => '',
                'idTipoFactura' => 'X',
                'idTipoComprobante' => '4',
                'idMoneda' => '1',
                'idUsuario' => '',
                'valorCambio' => '1',
                'nroFactura1' => $talonario,
                'nroFactura2' => $numeroPresupuesto,
                'percepcionIVA' => '0.00',
                'percepcionIIBB' => '0.00',
                'orderId' => '',
                'items' => $items
            ];

            // Intentar crear el presupuesto en Colppy
            $response = $colppyService->crearFacturaVenta($datosPresupuesto);

            // Verificar respuesta
            if (isset($response['success']) && $response['success'] === true) {
                // Colppy devuelve idfactura en response.idfactura para alta_facturaventa
                $idFactura = $response['response']['idfactura'] ?? null;
                
                if ($idFactura) {
                    // Construir número de presupuesto completo
                    $nroPresupuestoCompleto = $talonario . '-' . $numeroPresupuesto;
                    
                    // Guardar el ID y número del presupuesto en la tarea
                    Job::where('id', $job_id)->update([
                        'colppy_budget_id' => $idFactura,
                        'colppy_budget_number' => $nroPresupuestoCompleto
                    ]);

                    $presupuestoCreado = true;
                    
                    // Log::info('Presupuesto generado exitosamente en Colppy', [
                    //     'job_id' => $job_id,
                    //     'idFactura' => $idFactura,
                    //     'nroFactura' => $nroPresupuestoCompleto,
                    //     'intento' => $intentoActual
                    // ]);
                } else {
                    Log::warning('Respuesta OK pero sin idfactura', [
                        'job_id' => $job_id,
                        'intento' => $intentoActual,
                        'response' => $response
                    ]);
                    break; // Salir del loop, no es un error de número duplicado
                }
            } else {
                // Error al crear presupuesto
                $mensajeError = $response['mensaje'] ?? $response['result']['mensaje'] ?? 'Error desconocido';
                
                // Detectar si es error de número duplicado o similar
                $esErrorNumeracion = stripos($mensajeError, 'duplicad') !== false 
                                  || stripos($mensajeError, 'existe') !== false
                                  || stripos($mensajeError, 'número') !== false
                                  || stripos($mensajeError, 'ya se encuentra') !== false;
                
                if ($esErrorNumeracion && $intentoActual < $maxIntentos) {
                    // Es un error de numeración, reintentar con nuevo número
                    Log::warning('Conflicto de numeración detectado, reintentando con nuevo número', [
                        'job_id' => $job_id,
                        'intento' => $intentoActual,
                        'numeroIntentado' => $talonario . '-' . $numeroPresupuesto,
                        'mensaje_error' => $mensajeError
                    ]);
                    
                    // Esperar 1 segundo antes de consultar nuevo número
                    sleep(1);
                    continue;
                } else {
                    // Error diferente o se agotaron los reintentos
                    Log::error('Error al crear presupuesto en Colppy', [
                        'job_id' => $job_id,
                        'intento' => $intentoActual,
                        'mensaje' => $mensajeError,
                        'response' => $response
                    ]);
                    break;
                }
            }
        }
        
        if (!$presupuestoCreado && $intentoActual >= $maxIntentos) {
            Log::error('No se pudo crear presupuesto después de múltiples intentos', [
                'job_id' => $job_id,
                'intentos_realizados' => $intentoActual
            ]);
        }
    }

    /**
     * Generar PDF de trabajo realizado
     * 
     * @param int $id ID del trabajo
     * @param Request $request Contiene los elementos a incluir en el PDF
     * @return \Illuminate\Http\Response
     */
    public function generatePDF(Request $request, $id)
    {
        try {
            // Obtener el trabajo con sus relaciones
            $job = Job::with([
                'client', 
                'files.user:id,name', 
                'notes.user:id,name', 
                'technicians',
                'products'
            ])->find($id);

            if (!$job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trabajo no encontrado'
                ], 404);
            }

            // Cargar la dirección del cliente manualmente
            if ($job->client_addres_id) {
                $job->clientAddress = Clients_Addres::find($job->client_addres_id);
            }

            // Obtener configuración de qué incluir en el PDF desde el request
            $includeDescription = $request->input('include_description', true);
            $includeNotes = $request->input('include_notes', true);
            $selectedNoteIds = $request->input('note_ids', []); // IDs específicas de notas
            $includeArrivalTime = $request->input('include_arrival_time', true);
            $includeDepartureTime = $request->input('include_departure_time', true);
            $includeImages = $request->input('include_images', true);
            $selectedImageIds = $request->input('image_ids', []); // IDs específicas de imágenes
            $includeProducts = $request->input('include_products', true);
            $includeTechnicians = $request->input('include_technicians', true);

            // Filtrar notas si se especificaron IDs
            $notes = $includeNotes ? $job->notes : collect();
            if (!empty($selectedNoteIds) && $includeNotes) {
                $notes = $notes->whereIn('id', $selectedNoteIds);
            }

            // Filtrar imágenes si se especificaron IDs
            $images = $includeImages ? $job->files : collect();
            if (!empty($selectedImageIds) && $includeImages) {
                $images = $images->whereIn('id', $selectedImageIds);
            }

            // Convertir imágenes a base64 para embeber en el PDF
            // (dompdf no puede acceder a URLs externas ni paths de Drive)
            $imagesBase64 = [];
            if ($includeImages) {
                $adapter = \Illuminate\Support\Facades\Storage::disk('google')->getAdapter();
                $service = $adapter->getService();
                foreach ($images as $image) {
                    try {
                        $name = $image->name;
                        if (pathinfo($name, PATHINFO_EXTENSION) !== '') {
                            // Archivo local
                            $path = storage_path('app/public/' . $name);
                            if (file_exists($path)) {
                                $mime = mime_content_type($path);
                                $imagesBase64[$image->id] = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
                            }
                        } else {
                            // ID de Google Drive
                            $meta = $service->files->get($name, ['fields' => 'mimeType']);
                            $response = $service->files->get($name, ['alt' => 'media']);
                            $content = $response->getBody()->getContents();
                            $imagesBase64[$image->id] = 'data:' . $meta->getMimeType() . ';base64,' . base64_encode($content);
                        }
                    } catch (\Exception $e) {
                        \Log::warning('PDF: no se pudo cargar imagen id=' . $image->id . ': ' . $e->getMessage());
                    }
                }
            }

            // Generamos el Base64 puro del SVG de FontAwesome (Busto de usuario)
            $usuarioIconoBase64 = 'data:image/svg+xml;base64,' . base64_encode(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="#555555"><path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512H418.3c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304H178.3z"/></svg>'
            );

            // Preparar datos para la vista
            $data = [
                'job' => $job,
                'includeDescription' => $includeDescription,
                'includeNotes' => $includeNotes,
                'notes' => $notes,
                'usuarioIconoBase64' => $usuarioIconoBase64,
                'includeArrivalTime' => $includeArrivalTime,
                'includeDepartureTime' => $includeDepartureTime,
                'includeImages' => $includeImages,
                'images' => $images,
                'imagesBase64' => $imagesBase64,
                'includeProducts' => $includeProducts,
                'includeTechnicians' => $includeTechnicians,
            ];

            // Generar PDF
            $pdf = Pdf::loadView('job.pdf', $data);
            
            // Configurar orientación y tamaño
            $pdf->setPaper('A4', 'portrait');
            
            $fileName = 'Trabajo_' . $job->id . '_' . date('Y-m-d') . '.pdf';

            // Si es una petición de la API (JSON), devolver el PDF como base64
            if ($request->expectsJson() || $request->is('api/*')) {
                $pdfContent = $pdf->output();
                return response()->json([
                    'success' => true,
                    'filename' => $fileName,
                    'pdf' => base64_encode($pdfContent),
                    'mime_type' => 'application/pdf'
                ]);
            }

            // Si es petición web, descargar directamente
            return $pdf->download($fileName);

        } catch (\Exception $e) {
            // Log::error('Error al generar PDF', [
            //     'job_id' => $id,
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al generar PDF: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error al generar PDF: ' . $e->getMessage());
        }
    }

    private function syncColppyBudgetFromEditedTimes(int $jobId): void
    {
        try {
            $job = Job::with('client')->find($jobId);
            if (!$job || empty($job->colppy_budget_id)) {
                return;
            }

            // Si no hay ambos tiempos, no hay base para recalcular mano de obra.
            if (empty($job->arrival_datetime) || empty($job->closed_datetime)) {
                return;
            }

            $colppyService = new ColppyService();
            $budgetResult = $colppyService->leerFacturaVenta((string) $job->colppy_budget_id);
            if (!($budgetResult['success'] ?? false)) {
                Log::warning('No se pudo leer presupuesto Colppy al editar tiempos', [
                    'job_id' => $jobId,
                    'colppy_budget_id' => $job->colppy_budget_id,
                    'mensaje' => $budgetResult['mensaje'] ?? 'Error desconocido'
                ]);
                return;
            }

            $infofactura = $budgetResult['response']['infofactura'] ?? [];
            $itemsFactura = $budgetResult['response']['itemsFactura'] ?? [];
            if (empty($itemsFactura) || !is_array($itemsFactura)) {
                return;
            }

            [$cantidadHoras, $comentarioHoras] = $this->calculateWorkedHoursForBudget($job->arrival_datetime, $job->closed_datetime);

            $itemsActualizados = $this->applyLaborHoursToBudgetItems($itemsFactura, $cantidadHoras, $comentarioHoras);
            if (empty($itemsActualizados)) {
                return;
            }

            [$nroFactura1, $nroFactura2] = $this->extractBudgetNumberParts($infofactura);

            $clientColppyId = $infofactura['idCliente']
                ?? $job->client->colppy_id
                ?? $job->client->idcolppy
                ?? null;

            if (empty($clientColppyId)) {
                Log::warning('No se pudo determinar idCliente de Colppy para edición de presupuesto', [
                    'job_id' => $jobId,
                    'colppy_budget_id' => $job->colppy_budget_id,
                ]);
                return;
            }

            $payload = [
                'idFactura' => (string) $job->colppy_budget_id,
                'descripcion' => $infofactura['descripcion'] ?? ('Actualizado por edición de tiempos - Tarea #' . $jobId),
                'fechaFactura' => $infofactura['fechaFactura'] ?? Carbon::now()->format('d-m-Y'),
                'fechaPago' => $infofactura['fechaPago'] ?? ($infofactura['fechaFactura'] ?? Carbon::now()->format('d-m-Y')),
                'idCliente' => $clientColppyId,
                'idCondicionPago' => $infofactura['idCondicionPago'] ?? 'a 7 Dias',
                'idEstadoFactura' => $infofactura['idEstadoFactura'] ?? 'Borrador',
                'idEstadoAnterior' => $infofactura['idEstadoAnterior'] ?? '',
                'idTipoFactura' => 'X',
                'idTipoComprobante' => '4',
                'idMoneda' => $infofactura['idMoneda'] ?? '1',
                'valorCambio' => $infofactura['valorCambio'] ?? '1',
                'nroFactura1' => $nroFactura1,
                'nroFactura2' => $nroFactura2,
                'percepcionIVA' => $infofactura['percepcionIVA'] ?? '0.00',
                'percepcionIIBB' => $infofactura['percepcionIIBB'] ?? '0.00',
                'orderId' => $infofactura['orderId'] ?? '',
                'items' => $itemsActualizados,
            ];

            $updateResult = $colppyService->editarFacturaVenta($payload);
            if (!($updateResult['success'] ?? false)) {
                Log::warning('Falló actualización de presupuesto Colppy al editar tiempos', [
                    'job_id' => $jobId,
                    'colppy_budget_id' => $job->colppy_budget_id,
                    'mensaje' => $updateResult['mensaje'] ?? ($updateResult['result']['mensaje'] ?? 'Error desconocido')
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Error sincronizando presupuesto Colppy tras edición de tiempos', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function calculateWorkedHoursForBudget($arrivalDatetime, $closedDatetime): array
    {
        $start = Carbon::parse($arrivalDatetime);
        $end = Carbon::parse($closedDatetime);

        $minutes = $end->diffInMinutes($start);
        $hours = round($minutes / 60, 2);

        if ($hours <= 0 && $minutes > 0) {
            $hours = 0.01;
        }

        $comment = sprintf(
            'Arribo: %s - Salida: %s - %s min',
            $start->format('d/m H:i'),
            $end->format('d/m H:i'),
            $minutes
        );

        return [$hours, $comment];
    }

    private function applyLaborHoursToBudgetItems(array $itemsFactura, float $hours, string $comment): array
    {
        $items = [];
        $targetIndex = null;

        foreach ($itemsFactura as $index => $item) {
            $codigo = (string) ($item['codigo'] ?? '');
            $tipoItem = strtoupper((string) ($item['tipoItem'] ?? ''));

            if ($targetIndex === null && $codigo === '14') {
                $targetIndex = $index;
            }

            if ($targetIndex === null && $tipoItem === 'S') {
                $targetIndex = $index;
            }

            $items[] = $item;
        }

        if ($targetIndex === null) {
            return $items;
        }

        $items[$targetIndex]['Cantidad'] = $hours;
        $items[$targetIndex]['Comentario'] = $comment;

        return $items;
    }

    private function extractBudgetNumberParts(array $infofactura): array
    {
        $nroFactura1 = $infofactura['nroFactura1'] ?? '';
        $nroFactura2 = $infofactura['nroFactura2'] ?? '';

        if (!empty($nroFactura1) && !empty($nroFactura2)) {
            return [$nroFactura1, $nroFactura2];
        }

        $nroFactura = $infofactura['nroFactura'] ?? '';
        if (!empty($nroFactura)) {
            $parts = explode('-', $nroFactura);
            if (count($parts) === 2) {
                return [trim($parts[0]), trim($parts[1])];
            }
        }

        return ['', ''];
    }

    /**
     * Convertir formato de fecha del frontend (DD/MM/YYYY HH:mm) al formato de BD (YYYY-MM-DD HH:mm:ss)
     * 
     * @param string $dateTime Fecha en formato DD/MM/YYYY HH:mm
     * @return string|null Fecha en formato YYYY-MM-DD HH:mm:ss
     */
    private function convertDateTimeFormat($dateTime)
    {
        if (empty($dateTime)) {
            return null;
        }

        try {
            // Si ya viene en formato ISO (YYYY-MM-DD), retornar as-is
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $dateTime)) {
                return $dateTime;
            }

            // Convertir de DD/MM/YYYY HH:mm a YYYY-MM-DD HH:mm:ss
            // Formato esperado: 25/12/2024 14:30
            $date = \DateTime::createFromFormat('d/m/Y H:i', $dateTime);
            
            if ($date === false) {
                // Intentar con otros formatos comunes
                $date = \DateTime::createFromFormat('d/m/Y H:i:s', $dateTime);
            }
            
            if ($date === false) {
                // Log::warning('Formato de fecha no reconocido', ['dateTime' => $dateTime]);
                return $dateTime; // Retornar original si no se puede convertir
            }

            return $date->format('Y-m-d H:i:s');
            
        } catch (\Exception $e) {
            // Log::error('Error al convertir formato de fecha', [
            //     'dateTime' => $dateTime,
            //     'error' => $e->getMessage()
            // ]);
            return $dateTime; // Retornar original en caso de error
        }
    }

    public function generateExcel(Request $request)
    {
        if (ob_get_contents()) ob_end_clean();

        $query = Job::getJobsQueryExcel();
                
        if (isset($request->periodo)) {
            $fechas = explode(' - ', $request->periodo);
            $_fechamin = explode('/', $fechas[0]);
            $_fechamax = explode('/', $fechas[1]);
            $fechamin = date($_fechamin[2] . "-" . $_fechamin[0] . "-" . $_fechamin[1]);
            $fechamax = date($_fechamax[2] . "-" . $_fechamax[0] . "-" . $_fechamax[1]);

            $query->whereRaw("DATE(C.visit_datetime) >= ?", [$fechamin])
                  ->whereRaw("DATE(C.visit_datetime) <= ?", [$fechamax]);
        }

        if ($request->search != '' && isset($request->search)) {
            $search = $request->search;
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
                  ->orWhereRaw("C.colppy_budget_number LIKE ?", ["%$search%"]);
            });
        }

        $jobs = $query->orderBy('client_name', 'ASC')->orderBy('visit_datetime', 'ASC')->orderBy('id', 'ASC')->get();
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Trabajos');

        $sheet->setCellValue('A1', 'DATOS GENERALES DE LA TAREA');
        $sheet->mergeCells('A1:C1');
        
        $sheet->setCellValue('D1', 'INFORMACIÓN DEL CLIENTE');
        $sheet->mergeCells('D1:G1');
        
        $sheet->setCellValue('H1', 'TIEMPOS Y ESTADOS');
        $sheet->mergeCells('H1:K1');
        
        $sheet->setCellValue('L1', 'MÉTRICAS, PRODUCTOS Y SEGUIMIENTO');
        $sheet->mergeCells('L1:P1');

        // Definición de las columnas solicitadas
        $headers = [
            'A' => 'Nº TRABAJO', 
            'B' => 'DESCRIPCIÓN', 
            'C' => 'FECHA CREACIÓN',

            'D' => 'CLIENTE',
            'E' => 'TELEFONO / MOVIL',
            'F' => 'NRO. DOCUMENTO',
            'G' => 'DIRECCION',

            'H' => 'ESTADO',
            'I' => 'VISITA', // Desde job_attachments
            'J' => 'ARRIBO', // Desde job_attachments
            'K' => 'CIERRE', // Desde job_attachments
            
            'L' => 'CANT. ARCHIVOS', // Desde job_attachments
            'M' => 'TÉCNICOS ASOCIADOS', // Desde job_technicians (Nombres concatenados)
            'N' => 'PRODUCTOS VINCULADOS', // Desde job_products (Concatenados)
            'O' => 'NOTAS E HISTORIAL', // Desde job_notes (Concatenación de textos)
            'P' => 'ÚLTIMA ACTUALIZACIÓN'
        ];

        foreach ($headers as $col => $text) {
            $sheet->setCellValue($col . '2', $text);
        }

        $row = 3;

        foreach ($jobs as $key => $job) {
            $sheet->setCellValueExplicit('A' . $row, $job->id, DataType::TYPE_STRING);
            $sheet->setCellValue('B' . $row, $job->job_description);
            $sheet->setCellValue('C' . $row, $job->created ?? '');
            // Datos Cliente
            $sheet->setCellValue('D' . $row, $job->client_name ?? '');
            
            // Unificamos Teléfono / Móvil en una sola celda separado por barra si existen ambos
            $phoneAndMobile = array_filter([$job->client_phone1, $job->client_phone2]);
            $sheet->setCellValueExplicit('E' . $row, implode(' / ', $phoneAndMobile), DataType::TYPE_STRING);
            
            $sheet->setCellValueExplicit('F' . $row, $job->client_document, DataType::TYPE_STRING);
            $sheet->setCellValue('G' . $row, $job->client_addres_name);

            // Tiempos
            $sheet->setCellValue('H' . $row, $job->status ?? '');
            $sheet->setCellValue('I' . $row, $job->visit ?? '');
            $sheet->setCellValue('J' . $row, $job->arrival ?? '');
            $sheet->setCellValue('K' . $row, $job->closed ?? '');

            // Métricas y relaciones con saltos de línea ya procesados de la DB
            $sheet->setCellValue('L' . $row, $job->cant_archivos ?? 0);
            $sheet->setCellValue('M' . $row, $job->tecnicos ?? '');
            $sheet->setCellValue('N' . $row, $job->productos ?? '');

            $notas_repetidas = json_decode($job->notas_json);
            $notas = [];

            if (!empty($notas_repetidas) && is_array($notas_repetidas)) {
                $textos_vistos = [];
                foreach ($notas_repetidas as $n) {
                    if (!empty($n->texto)) {
                        // Usamos el texto de la nota como llave única para descartar duplicados de raíz
                        $textoLimpio = trim((string)$n->texto);
                        if (!in_array($textoLimpio, $textos_vistos)) {
                            $textos_vistos[] = $textoLimpio;
                            $notas[] = $n; // Guardamos el objeto original limpio
                        }
                    }
                }
            }

            if (!empty($notas)) {
                $richText = new RichText();

                foreach ($notas as $index => $nota) {
                    if ($index > 0) { 
                        // Usamos el salto de línea nativo de PHP para asegurar compatibilidad en XML
                        $richText->createText(PHP_EOL . PHP_EOL); 
                    }
                    // 1. Limpieza estricta y escape de caracteres para XML (Evita la corrupción)
                    $usuarioTexto = isset($nota->usuario) ? htmlspecialchars((string)$nota->usuario, ENT_QUOTES, 'UTF-8') : 'Sistema';
                    $fechaTexto   = isset($nota->fecha) ? htmlspecialchars((string)$nota->fecha, ENT_QUOTES, 'UTF-8') : '';
                    $notaTexto    = isset($nota->texto) ? htmlspecialchars((string)$nota->texto, ENT_QUOTES, 'UTF-8') : '';

                    // Negrita al usuario
                    $userRun = $richText->createTextRun($usuarioTexto);
                    $userRun->getFont()->setBold(true);

                    $richText->createTextRun(" ");

                    // Subrayado a la fecha
                    $dateRun = $richText->createTextRun($fechaTexto);
                    $dateRun->getFont()->setUnderline(Font::UNDERLINE_SINGLE);

                    $richText->createTextRun(": " . $notaTexto);
                }
                $sheet->setCellValue('O' . $row, $richText);
            } else {
                $sheet->setCellValue('O' . $row, '');
            }
            
            $sheet->setCellValue('P' . $row, $job->updated ?? '');

            $sheet->getStyle('B' . $row )->getAlignment()->setWrapText(true);
            $sheet->getStyle('D' . $row )->getAlignment()->setWrapText(true);
            $sheet->getStyle('G' . $row )->getAlignment()->setWrapText(true);
            $sheet->getStyle('M' . $row . ':O' . $row)->getAlignment()->setWrapText(true);

            $sheet->getStyle('A' . $row . ':P' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('A' . $row )->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $row )->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $row . ':F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H' . $row . ':L' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('P' . $row )->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        // 4. Autoajustar el ancho de las columnas (De la A a la Q)
        foreach (range('A', 'Q') as $col) {
            if ($col === 'B' || $col === 'D' || $col === 'G' ) {
                // Le asignamos un ancho fijo inicial cómodo para leer párrafos largos
                $sheet->getColumnDimension($col)->setWidth(30);
            } else if ($col === 'O') {
                $sheet->getColumnDimension($col)->setWidth(50);
            } else {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        // 5. Aplicación del Diseño Institucional de la Fila 1 y Fila 2
        $styleGroup = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];

        $sheet->getStyle('A1:C1')->applyFromArray(array_merge($styleGroup, ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E548A']]]));
        $sheet->getStyle('D1:G1')->applyFromArray(array_merge($styleGroup, ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0077CC']]]));
        $sheet->getStyle('H1:K1')->applyFromArray(array_merge($styleGroup, ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00B5C9']]]));
        $sheet->getStyle('L1:P1')->applyFromArray(array_merge($styleGroup, ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4A5568']]]));

        // Estilo de los encabezados individuales (Fila 2)
        $sheet->getStyle('A2:P2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2D3748']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Bordes de la grilla completa
        $highestRow = $row - 1;
        if ($highestRow >= 2) {
            $sheet->getStyle('A1:P' . $highestRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0'],
                    ],
                ],
            ]);
        }

        $sheet->setSelectedCell('A3');
        $sheet->setAutoFilter('A2:P2');
        $sheet->freezePane('A3');

        $writer = new Xlsx($spreadsheet);

        if (ob_get_length()) ob_end_clean();

        $response =  new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', 'attachment;filename="jobs.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');
        return $response;

    }            

    
}
