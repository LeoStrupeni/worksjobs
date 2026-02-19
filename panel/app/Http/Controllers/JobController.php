<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Clients_Addres;

use App\Models\Job;
use App\Models\Jobs_file;
use App\Models\Jobs_Note;
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
            'visit_datetime' => $request->visit_datetime,
            'job_description' => $request->job_description,
            'visit_latitud' => $request->latitude,
            'visit_longitud' => $request->longitude,
            'visit_coords_status' => $request->latitude != null && $request->longitude != null ? '1' : '0',
            'visit_json_coords' => $request->jsongeolocation
        ]);

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
        $repuesta['job'] = $job;
        $repuesta['address'] = $address;
        $repuesta['files'] = $files;
        return $repuesta;
    }

    public function update(Request $request, $id)
    {   
        Log::info('Request Data: ', $request->all(),$id);
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
            $datos['visit_datetime'] = $request->visit_datetime;
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
        // Soportar parámetros de web (id, closed_job_observation, latitude, longitude) 
        // y de app móvil (id en ruta, observation, latitud, longitud)
        $job_id = $request->id ?? $request->route('id');
        $observation = $request->closed_job_observation ?? $request->observation;
        $latitud = $request->latitude ?? $request->latitud;
        $longitud = $request->longitude ?? $request->longitud;
        
        if (!$observation) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'La observación es requerida'], 400);
            }
            return back()->withErrors(['closed_job_observation' => 'El campo es requerido.']);
        }

        Job::where('id', $job_id)->update([
            'closed_datetime' => Carbon::now(),
            'closed_latitud' => $latitud,
            'closed_longitud' => $longitud,
            'closed_coords_status' => 1,
            'closed_json_coords' => $request->jsongeolocation,
            'closed_job_observation' => $observation
        ]);
        $this->addfiles($request, $job_id);

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
        
        Log::info('onlyaddfiles: job_id=' . $job_id);
        Log::info('onlyaddfiles: files in request', ['files' => $request->allFiles()]);
        
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

                $path = $attachment->storeAs('public', $job_id.'_'.time().'_'.$randomString.'.'.$attachment->getClientOriginalExtension());

                Jobs_file::create([
                    'job_id' => $job_id,
                    'name' => basename($path),
                    'original_name' => $attachment->getClientOriginalName(),
                    'original_extension' => $attachment->getClientOriginalExtension(),
                ]);
            }
        }
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

    
}
