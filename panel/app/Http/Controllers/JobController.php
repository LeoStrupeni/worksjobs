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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class JobController extends Controller
{
    public function index()
    {
        if(Auth::check()){
            $val = $this->getloginrol();
            if ($val == false){
                return redirect()->route('logout');     
            }
            return view("jobs");
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
        
        $files = Jobs_file::where('job_id',$id)->get();
        
        // Obtener técnicos asignados al job
        $jobModel = Job::find($id);
        $repuesta['technicians'] = $jobModel->technicians()->select('users.id', 'users.name')->get();
        
        // Obtener productos relacionados
        $repuesta['products'] = JobProduct::where('job_id', $id)
            ->whereNull('deleted_at')
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
        // Log::info('Request Data: ', $request->all(),$id);
        $job = Job::find($id);
    
        $datos = array();
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
            // Eliminar productos anteriores (soft delete)
            JobProduct::where('job_id', $id)->delete();
            
            // Agregar nuevos productos
            if (is_array($request->products)) {
                foreach ($request->products as $productData) {
                    if (isset($productData['product_id'])) {
                        // Buscar información del producto
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

        Job::where('id', $job_id)->update([
            'closed_datetime' => Carbon::now(),
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
        
        $notes = Jobs_Note::where('jobs_id', $job_id)
            ->selectraw("id,jobs_id, note, DATE_FORMAT(created_at,'%d/%m/%y %H:%i') as created, created_at" )
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
        Jobs_Note::find($id)->update([
            'deleted_at' => Carbon::now()
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Nota eliminada correctamente']);
        }
        return 1;
    }

    public function onlyaddfiles(Request $request)
    {
        // Soportar id en body o en ruta
        $job_id = $request->id ?? $request->route('id');
        
        // Log::info('onlyaddfiles: job_id=' . $job_id);
        // Log::info('onlyaddfiles: files in request', ['files' => $request->allFiles()]);
        
        $this->addfiles($request, $job_id);
        
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Archivos subidos correctamente']);
        }
        return back();
    }

    protected function addfiles(Request $request, $job_id)
    {
        // Soportar tanto 'images' como 'files'
        $fileField = $request->hasFile('images') ? 'images' : 'files';
        
        if ($request->hasFile($fileField)) {
            $file = $request->file($fileField);

            foreach ($file as $attachment) {
                $n = 10;
                $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $randomString = '';
                for ($i = 0; $i < $n; $i++) { $index = random_int(0, strlen($characters) - 1); $randomString .= $characters[$index];}

                $extension = strtolower($attachment->getClientOriginalExtension());
                $filename = $job_id.'_'.time().'_'.$randomString.'.'.$extension;
                
                // Verificar si es una imagen y optimizarla
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $optimizedPath = $this->optimizeAndSaveImage($attachment, $filename);
                } else {
                    // Para archivos no imagen, guardar normalmente
                    $optimizedPath = $attachment->storeAs('public', $filename);
                }

                Jobs_file::create([
                    'job_id' => $job_id,
                    'name' => basename($optimizedPath),
                    'original_name' => $attachment->getClientOriginalName(),
                    'original_extension' => $attachment->getClientOriginalExtension(),
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
        $file = Jobs_file::find($id);
    
        $path = 'storage/'.$file->name;
        if (file_exists($path)) {
            unlink($path);
        }
        $file->update([
            'deleted_at' => Carbon::now()
        ]);

        return Jobs_file::where('job_id',$file->job_id)->wherenull('deleted_at')->get();
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
        
        // Obtener número de talonario y contador de presupuestos desde configs
        $talonario = Config::where('name', 'colppy_talonario_presupuesto')->value('value') ?? '0004';
        $numeroPresupuesto = Config::where('name', 'colppy_numero_presupuesto')->value('value') ?? '00000001';
        
        $datosPresupuesto = [
            'descripcion' => 'Generado desde la tarea #' . $job_id,
            'fechaFactura' => $fechaActual,
            'fechaPago' => $fechaActual,
            'idCliente' => $job->client->idcolppy,
            'idCondiciónPago' => 'a 7 Dias',
            'idEstadoFactura' => 'Borrador',
            'idEstadoAnterior' => '',  // Vacío en operación de alta
            'idFactura' => '',  // Vacío en operación de alta (Colppy lo genera)
            'idTipoFactura' => 'X',  // X = Presupuesto/Cotización
            'idTipoComprobante' => '4',
            'idMoneda' => '1',
            'idUsuario' => '',  // Vacío en operación de alta
            'valorCambio' => '1',
            'nroFactura1' => $talonario,
            'nroFactura2' => $numeroPresupuesto,
            'percepcionIVA' => '0.00',
            'percepcionIIBB' => '0.00',
            'orderId' => '',
            'items' => $items
        ];

        // Llamar al servicio de Colppy
        $colppyService = new ColppyService();
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

                // Incrementar el contador de presupuestos para el próximo
                $numeroActual = (int) $numeroPresupuesto;
                $siguienteNumero = str_pad($numeroActual + 1, 8, '0', STR_PAD_LEFT);
                Config::where('name', 'colppy_numero_presupuesto')->update(['value' => $siguienteNumero]);

                // Log::info('Presupuesto generado exitosamente en Colppy', [
                //     'job_id' => $job_id,
                //     'idFactura' => $idFactura,
                //     'nroFactura' => $talonario . '-' . $numeroPresupuesto,
                //     'siguiente_numero' => $siguienteNumero,
                //     'items_count' => count($items)
                // ]);
            } else {
                // Log::warning('Respuesta OK pero sin idfactura', [
                //     'job_id' => $job_id,
                //     'response' => $response
                // ]);
            }
        } else {
            Log::error('Error al crear presupuesto en Colppy', [
                'job_id' => $job_id,
                'response' => $response
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
                'files', 
                'notes', 
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

            // Preparar datos para la vista
            $data = [
                'job' => $job,
                'includeDescription' => $includeDescription,
                'includeNotes' => $includeNotes,
                'notes' => $notes,
                'includeArrivalTime' => $includeArrivalTime,
                'includeDepartureTime' => $includeDepartureTime,
                'includeImages' => $includeImages,
                'images' => $images,
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

    
}
