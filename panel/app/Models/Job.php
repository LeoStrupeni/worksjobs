<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Job extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'jobs';
    protected $primaryKey = 'id';
    protected $keyType = 'integer';
    public $autoincrement = true;
    public $timestamps = true;
    public $guarded = [];
    
    // Relación con cliente (SOLO clientes ACTIVOS)
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id')
            ->where('is_active', 1);
    }
    
    // Relación con archivos (jobs_files usa job_id)
    public function files()
    {
        return $this->hasMany(Jobs_file::class, 'job_id');
    }
    
    // Relación con notas (jobs_notes usa jobs_id)
    public function notes()
    {
        return $this->hasMany(Jobs_Note::class, 'jobs_id');
    }

    // Relación con técnicos asignados (tabla pivote job_technicians)
    public function technicians()
    {
        return $this->belongsToMany(\App\Models\User::class, 'job_technicians', 'job_id', 'user_id')->withTimestamps();
    }

    // Relación con productos
    public function products()
    {
        return $this->hasMany(JobProduct::class, 'job_id');
    }

    /**
     * Query centralizado para obtener jobs con toda la lógica de negocio
     * Usado tanto en web (JobController) como en API (ApiJobController)
     * 
     * @return \Illuminate\Database\Query\Builder
     */
    public static function getJobsQuery()
    {
        return DB::table('jobs as C')
            ->leftJoin('clients as CL', 'C.client_id', '=', 'CL.id')
            ->leftJoin('clients_address as CA', 'C.client_addres_id', '=', 'CA.id')
            ->whereNull('C.deleted_at')
            ->selectRaw("
                C.id,
                CL.first_name AS client_first_name, 
                CL.last_name AS client_last_name,
                CL.email AS client_email,
                CL.phone1 AS client_phone,
                CONCAT(CL.first_name, ' ', IFNULL(CL.last_name, '')) AS client_name,
                CONCAT(IFNULL(CA.address_street,''),' ',
                       IFNULL(CA.address_nro,''),' ',
                       IFNULL(CA.city,'')) AS client_addres_name,
                IFNULL(CA.address_street, '') AS address_street,
                IFNULL(CA.address_nro, '') AS address_number,
                IFNULL(CA.address_apartament, '') AS address_apartment,
                IFNULL(CA.city, '') AS address_city,
                IFNULL(CA.state, '') AS address_state,
                IFNULL(CA.country, '') AS address_country,
                IFNULL(CA.address_detail, '') AS address_detail,
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
                CASE WHEN C.closed_datetime IS NOT NULL THEN 'Cerrado' 
                    WHEN C.arrival_datetime IS NOT NULL THEN 'En Lugar'
                ELSE 'Pendiente' END as status,
                CASE WHEN C.closed_datetime IS NOT NULL THEN 3 
                    WHEN C.arrival_datetime IS NOT NULL THEN 1
                ELSE 2 END as estatusorder,
                C.visit_datetime as ordervisit,
                CASE WHEN C.closed_datetime IS NOT NULL THEN '#00274e' 
                    WHEN C.arrival_datetime IS NOT NULL THEN 'green'  
                    WHEN DATEDIFF(C.visit_datetime, NOW()) <= 0 THEN 'red' 
                    WHEN DATEDIFF(C.visit_datetime, NOW()) <= 5 THEN 'orange' 
                ELSE 'blue' END as vencimiento,
                CASE WHEN C.closed_datetime IS NOT NULL THEN '#00274e' 
                    WHEN C.arrival_datetime IS NOT NULL THEN 'green'  
                    WHEN DATEDIFF(C.visit_datetime, NOW()) <= 0 THEN 'red' 
                    WHEN DATEDIFF(C.visit_datetime, NOW()) <= 5 THEN 'orange' 
                ELSE 'blue' END as color_status,
                IFNULL(C.job_description,'') as job_description,
                SUBSTRING(C.job_description,1,20) as job_description_short,
                C.visit_datetime,
                C.arrival_datetime,
                C.closed_datetime,
                C.visit_latitud,
                C.visit_longitud,
                C.arrival_latitud,
                C.arrival_longitud,
                C.closed_latitud,
                C.closed_longitud,
                IFNULL(C.closed_job_observation,'') as closed_job_observation,
                SUBSTRING(IFNULL(C.closed_job_observation,''),1,20) as closed_job_observation_short,
                C.created_at,
                C.updated_at,
                C.client_id,
                C.archived,
                C.colppy_budget_id,
                C.colppy_budget_number
            ");
    }
}
