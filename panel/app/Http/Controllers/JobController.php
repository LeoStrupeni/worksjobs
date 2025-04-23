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

    public function getDataTable(Request $request)
    {        
        $roluser = Session::get('user')['roles'][0];
        $permissions = Session::get('user')['permissions']['jobs'];

        $order = $request->order;
        $page = $request->page ?? 1;
        $limit = $request->limit ?? 10;
        $search = $request->search;

        $totales = Job::count();

        $query = $this->querydata();

        if ($search != '' && isset($search)) {
            $query .= " AND (CL.first_name LIKE '%$search%' 
                OR CL.last_name LIKE '%$search%'
                OR DATE_FORMAT(C.created_at,'%d/%m/%y %H:%i') LIKE '%$search%'
                OR DATE_FORMAT(C.visit_datetime,'%d/%m/%y %H:%i') LIKE '%$search%'
                OR DATE_FORMAT(C.arrival_datetime,'%d/%m/%y %H:%i') LIKE '%$search%'
                OR DATE_FORMAT(C.closed_datetime,'%d/%m/%y %H:%i') LIKE '%$search%'
                OR CASE DATE_FORMAT(C.created_at,'%W') WHEN 'Monday' THEN 'Lun' WHEN 'Tuesday' THEN 'Mar' WHEN 'Wednesday' THEN 'Mie' WHEN 'Thursday' THEN 'Jue' WHEN 'Friday' THEN 'Vie' WHEN 'Saturday' THEN 'Sab' WHEN 'Sunday' THEN 'Dom' END LIKE '%$search%'
                OR CASE DATE_FORMAT(C.visit_datetime,'%W') WHEN 'Monday' THEN 'Lun' WHEN 'Tuesday' THEN 'Mar' WHEN 'Wednesday' THEN 'Mie' WHEN 'Thursday' THEN 'Jue' WHEN 'Friday' THEN 'Vie' WHEN 'Saturday' THEN 'Sab' WHEN 'Sunday' THEN 'Dom' END LIKE '%$search%'
                OR CASE DATE_FORMAT(C.arrival_datetime,'%W') WHEN 'Monday' THEN 'Lun' WHEN 'Tuesday' THEN 'Mar' WHEN 'Wednesday' THEN 'Mie' WHEN 'Thursday' THEN 'Jue' WHEN 'Friday' THEN 'Vie' WHEN 'Saturday' THEN 'Sab' WHEN 'Sunday' THEN 'Dom' END LIKE '%$search%'
                OR CASE DATE_FORMAT(C.closed_datetime,'%W') WHEN 'Monday' THEN 'Lun' WHEN 'Tuesday' THEN 'Mar' WHEN 'Wednesday' THEN 'Mie' WHEN 'Thursday' THEN 'Jue' WHEN 'Friday' THEN 'Vie' WHEN 'Saturday' THEN 'Sab' WHEN 'Sunday' THEN 'Dom' END LIKE '%$search%'
                OR CASE DATE_FORMAT(C.created_at,'%m') WHEN '01' THEN 'Ene' WHEN '02' THEN 'Feb' WHEN '03' THEN 'Mar' WHEN '04' THEN 'Abr' WHEN '05' THEN 'May' WHEN '06' THEN 'Jun' WHEN '07' THEN 'Jul' WHEN '08' THEN 'Ago' WHEN '09' THEN 'Sep' WHEN '10' THEN 'Oct' WHEN '11' THEN 'Nov' WHEN '12' THEN 'Dic' END LIKE '%$search%'
                OR CASE DATE_FORMAT(C.visit_datetime,'%m') WHEN '01' THEN 'Ene' WHEN '02' THEN 'Feb' WHEN '03' THEN 'Mar' WHEN '04' THEN 'Abr' WHEN '05' THEN 'May' WHEN '06' THEN 'Jun' WHEN '07' THEN 'Jul' WHEN '08' THEN 'Ago' WHEN '09' THEN 'Sep' WHEN '10' THEN 'Oct' WHEN '11' THEN 'Nov' WHEN '12' THEN 'Dic' END LIKE '%$search%'
                OR CASE DATE_FORMAT(C.arrival_datetime,'%m') WHEN '01' THEN 'Ene' WHEN '02' THEN 'Feb' WHEN '03' THEN 'Mar' WHEN '04' THEN 'Abr' WHEN '05' THEN 'May' WHEN '06' THEN 'Jun' WHEN '07' THEN 'Jul' WHEN '08' THEN 'Ago' WHEN '09' THEN 'Sep' WHEN '10' THEN 'Oct' WHEN '11' THEN 'Nov' WHEN '12' THEN 'Dic' END LIKE '%$search%'
                OR CASE DATE_FORMAT(C.closed_datetime,'%m') WHEN '01' THEN 'Ene' WHEN '02' THEN 'Feb' WHEN '03' THEN 'Mar' WHEN '04' THEN 'Abr' WHEN '05' THEN 'May' WHEN '06' THEN 'Jun' WHEN '07' THEN 'Jul' WHEN '08' THEN 'Ago' WHEN '09' THEN 'Sep' WHEN '10' THEN 'Oct' WHEN '11' THEN 'Nov' WHEN '12' THEN 'Dic' END LIKE '%$search%'
                OR CASE WHEN C.closed_datetime IS NOT NULL THEN 'Cerrado' WHEN C.arrival_datetime IS NOT NULL THEN 'En Lugar' ELSE 'Pendiente' END LIKE '%$search%'
                OR IFNULL(C.job_description,'') LIKE '%$search%'
                OR IFNULL(C.closed_job_observation,'') LIKE '%$search%'    
            ) ";
        }

        $filtrados = DB::select($query);

        $querylist = '';
        if ($order) {
            $querylist .= " ORDER BY $order ";
        } else {
            $querylist .= " ORDER BY estatusorder ASC, ordervisit ASC ";
        }
        if ($limit) {
            $querylist .= " LIMIT " . $limit;
        }
        if ($page) {
            $querylist .= " OFFSET " . ($limit * $page - $limit);
        }

        $lista = DB::select(DB::raw($query . $querylist));

        foreach ($lista as $j) {
            $note =Jobs_Note::where('jobs_id',$j->id)->first();   
            $j->getnotes = $note ? 'si' : 'no';
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

        return $respuesta;
    }

    public function querydata()
    {
        $query = "SELECT C.id,
            CL.first_name AS client_first_name, 
            CL.last_name AS client_last_name,
            DATE_FORMAT(C.created_at,'%d/%m/%y %H:%i') as created,
            DATE_FORMAT(C.visit_datetime,'%d/%m/%y %H:%i') as visit,
            DATE_FORMAT(C.arrival_datetime,'%d/%m/%y %H:%i') as arrival,
            DATE_FORMAT(C.closed_datetime,'%d/%m/%y %H:%i') as closed,
            CASE DATE_FORMAT(C.created_at,'%W') WHEN 'Monday' THEN 'Lun' WHEN 'Tuesday' THEN 'Mar' WHEN 'Wednesday' THEN 'Mie' WHEN 'Thursday' THEN 'Jue' WHEN 'Friday' THEN 'Vie' WHEN 'Saturday' THEN 'Sab' WHEN 'Sunday' THEN 'Dom' END as created_day,
            CASE DATE_FORMAT(C.visit_datetime,'%W') WHEN 'Monday' THEN 'Lun' WHEN 'Tuesday' THEN 'Mar' WHEN 'Wednesday' THEN 'Mie' WHEN 'Thursday' THEN 'Jue' WHEN 'Friday' THEN 'Vie' WHEN 'Saturday' THEN 'Sab' WHEN 'Sunday' THEN 'Dom' END as visit_day,
            CASE DATE_FORMAT(C.arrival_datetime,'%W') WHEN 'Monday' THEN 'Lun' WHEN 'Tuesday' THEN 'Mar' WHEN 'Wednesday' THEN 'Mie' WHEN 'Thursday' THEN 'Jue' WHEN 'Friday' THEN 'Vie' WHEN 'Saturday' THEN 'Sab' WHEN 'Sunday' THEN 'Dom' END as arrival_day,
            CASE DATE_FORMAT(C.closed_datetime,'%W') WHEN 'Monday' THEN 'Lun' WHEN 'Tuesday' THEN 'Mar' WHEN 'Wednesday' THEN 'Mie' WHEN 'Thursday' THEN 'Jue' WHEN 'Friday' THEN 'Vie' WHEN 'Saturday' THEN 'Sab' WHEN 'Sunday' THEN 'Dom' END as closed_day,
            CASE DATE_FORMAT(C.created_at,'%m') WHEN '01' THEN 'Ene' WHEN '02' THEN 'Feb' WHEN '03' THEN 'Mar' WHEN '04' THEN 'Abr' WHEN '05' THEN 'May' WHEN '06' THEN 'Jun' WHEN '07' THEN 'Jul' WHEN '08' THEN 'Ago' WHEN '09' THEN 'Sep' WHEN '10' THEN 'Oct' WHEN '11' THEN 'Nov' WHEN '12' THEN 'Dic' END as created_month,
            CASE DATE_FORMAT(C.visit_datetime,'%m') WHEN '01' THEN 'Ene' WHEN '02' THEN 'Feb' WHEN '03' THEN 'Mar' WHEN '04' THEN 'Abr' WHEN '05' THEN 'May' WHEN '06' THEN 'Jun' WHEN '07' THEN 'Jul' WHEN '08' THEN 'Ago' WHEN '09' THEN 'Sep' WHEN '10' THEN 'Oct' WHEN '11' THEN 'Nov' WHEN '12' THEN 'Dic' END as visit_month,
            CASE DATE_FORMAT(C.arrival_datetime,'%m') WHEN '01' THEN 'Ene' WHEN '02' THEN 'Feb' WHEN '03' THEN 'Mar' WHEN '04' THEN 'Abr' WHEN '05' THEN 'May' WHEN '06' THEN 'Jun' WHEN '07' THEN 'Jul' WHEN '08' THEN 'Ago' WHEN '09' THEN 'Sep' WHEN '10' THEN 'Oct' WHEN '11' THEN 'Nov' WHEN '12' THEN 'Dic' END as arrival_month,
            CASE DATE_FORMAT(C.closed_datetime,'%m') WHEN '01' THEN 'Ene' WHEN '02' THEN 'Feb' WHEN '03' THEN 'Mar' WHEN '04' THEN 'Abr' WHEN '05' THEN 'May' WHEN '06' THEN 'Jun' WHEN '07' THEN 'Jul' WHEN '08' THEN 'Ago' WHEN '09' THEN 'Sep' WHEN '10' THEN 'Oct' WHEN '11' THEN 'Nov' WHEN '12' THEN 'Dic' END as closed_month,
            CASE WHEN C.closed_datetime IS NOT NULL THEN 'Cerrado' 
                WHEN C.arrival_datetime IS NOT NULL THEN 'En Lugar'
            ELSE 'Pendiente' END as estatus,
            CASE WHEN C.closed_datetime IS NOT NULL THEN 3 
                WHEN C.arrival_datetime IS NOT NULL THEN 1
            ELSE 2 END as estatusorder,
            C.visit_datetime as ordervisit,
            CASE WHEN C.closed_datetime IS NOT NULL THEN 'black' 
                WHEN C.arrival_datetime IS NOT NULL THEN 'green'  
                WHEN DATEDIFF(C.visit_datetime,now()) <= 0 THEN 'red' 
                WHEN DATEDIFF(C.visit_datetime,now()) <= 5 THEN 'orange' 
            ELSE 'blue' END as vencimiento, 
            IFNULL(C.job_description,'') as job_description,
            SUBSTRING(C.job_description,1,20) as job_description_short, 
            C.visit_latitud,
            C.visit_longitud,
            C.arrival_latitud,
            C.arrival_longitud,
            C.closed_latitud,
            C.closed_longitud,
            IFNULL(C.closed_job_observation,'') as closed_job_observation,
            SUBSTRING(IFNULL(C.closed_job_observation,''),1,20) as closed_job_observation_short
        FROM jobs C
        LEFT JOIN clients CL ON C.client_id = CL.id
        WHERE ISNULL(C.deleted_at) ";
        return $query;
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
            'visit_coords_status' => $request->latitude != null && $request->longitude != null ? 1 : 0,
            'visit_json_coords' => $request->jsongeolocation
        ]);

        $this->addfiles($request, $job->id);

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

        $address = Clients_Addres::where('client_id',$job->client_id)->get();
        $files = Jobs_file::where('job_id',$id)->get();
        $repuesta['job'] = $job;
        $repuesta['address'] = $address;
        $repuesta['files'] = $files;
        return $repuesta;
    }

    public function update(Request $request, $id)
    {   
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
                $datos['visit_coords_status'] = $request->latitude != null && $request->longitude != null ? 1 : 0;
                $datos['visit_json_coords'] = $request->jsongeolocation;
        }

        if(count($datos) > 0){
            Job::where('id',$id)->update($datos);
        }
        $this->addfiles($request, $id);

        return back();
    }

    public function destroy($id)
    {
        Job::find($id)->update([
            'deleted_at' => Carbon::now()
        ]);

        return back();
    }

    public function markarrival(Request $request)
    {
        Job::where('id',$request->job_id)->update([
            'arrival_datetime' => Carbon::now(),
            'arrival_latitud' => $request->arrival_latitud,
            'arrival_longitud' => $request->arrival_longitud,
            'arrival_coords_status' => '1',
            'arrival_json_coords' => $request->jsongeolocation
        ]);

        return 1;
    }

    public function backarrival(Request $request)
    {
        Job::where('id',$request->job_id)->update([
            'arrival_datetime' => null,
            'arrival_latitud' => null,
            'arrival_longitud' => null,
            'arrival_coords_status' => '0',
            'arrival_json_coords' => null
        ]);

        return 1;
    }

    public function closed(Request $request)
    {
        $request->validate([
                'id' => ['required'],    
                'client_id' => ['required'],
                'closed_job_observation' => ['required']
            ],
            [
                'required' => 'El campo es requerido.',
            ]
        );

        Job::where('id',$request->id)->update([
            'closed_datetime' => Carbon::now(),
            'closed_latitud' => $request->latitude,
            'closed_longitud' => $request->longitude,
            'closed_coords_status' => 1,
            'closed_json_coords' => $request->jsongeolocation,
            'closed_job_observation' => $request->closed_job_observation
        ]);
        $this->addfiles($request, $request->id);

        return back();
    }

    public function addnote(Request $request)
    {
        Jobs_Note::create([
            'jobs_id' => $request->job_id,
            'note' => $request->note,
            'latitud' => $request->latitud,
            'longitud' => $request->longitud,
            'json_coords' => $request->jsongeolocation
        ]);

        return 1;
    }

    public function getnotes($job_id)
    {
        $permissions = Session::get('user')['permissions']['jobs'];
        $respuesta['permissions'] = $permissions;

        $notes = Jobs_Note::where('jobs_id',$job_id)
            ->selectraw("id,jobs_id, note, DATE_FORMAT(created_at,'%d/%m/%y %H:%i') as created, created_at" )
            ->orderby('created_at','desc')
            ->get();

        $respuesta['datos'] = $notes;
        return $respuesta;
    }
    
    public function destroynote($id)
    {
        Jobs_Note::find($id)->update([
            'deleted_at' => Carbon::now()
        ]);

        return 1;
    }

    public function onlyaddfiles(Request $request)
    {
        $this->addfiles($request, $request->id);
        return back();
    }

    protected function addfiles(Request $request, $job_id)
    {
        if ($request->hasFile('images')) {
            $file = $request->file('images');

            foreach ($file as $attachment) {
                $n = 10;
                $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $randomString = '';
                for ($i = 0; $i < $n; $i++) { $index = random_int(0, strlen($characters) - 1); $randomString .= $characters[$index];}

                $path = $attachment->storeAs('local', $job_id.'_'.time().'_'.$randomString.'.'.$attachment->getClientOriginalExtension());

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
    
}
