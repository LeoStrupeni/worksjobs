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
        // return $this->belongsToMany(\App\Models\User::class, 'job_technicians', 'job_id', 'user_id')->withTimestamps();
        return $this->belongsToMany(\App\Models\User::class, 'job_technicians', 'job_id', 'user_id')
                ->using(JobTechnicians::class)
                ->withPivot('created_by', 'updated_by', 'deleted_by')
                ->withTimestamps()
                ->wherePivotNull('deleted_at');
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

    public static function getJobsQueryExcel()
    {
        return DB::table('jobs as C')
            ->leftJoin('clients as CL', 'C.client_id', '=', 'CL.id')
            ->leftjoin('clients_address as CA', 'C.client_addres_id', '=', 'CA.id')
            ->leftJoin('jobs_notes as N', function($join) {
                $join->on('C.id', '=', 'N.jobs_id')->whereNull('N.deleted_at');
            })
            ->leftJoin('users as UN', 'N.created_by', '=', 'UN.id') // Usuario que creó la nota
            ->leftJoin('job_technicians as T', function($join) {
                $join->on('C.id', '=', 'T.job_id')->whereNull('T.deleted_at');
            })
            ->leftJoin('users as S', 'T.user_id', '=', 'S.id') // Técnicos asignados
            ->leftJoin('job_products as P', function($join) {
                $join->on('C.id', '=', 'P.job_id')->whereNull('P.deleted_at');
            })
            ->leftJoin('products as PR', 'P.product_id', '=', 'PR.id') // Productos asignados
            ->leftJoin('jobs_files as F', function($join) {
                $join->on('C.id', '=', 'F.job_id')->whereNull('F.deleted_at');
            })

            ->whereNull('C.deleted_at')
            ->whereNull('N.deleted_at')
            ->selectRaw("C.id , 
                C.job_description, 
                CASE WHEN C.archived = 1 THEN 'Archivado'
                    WHEN C.closed_datetime IS NOT NULL THEN 'Completado'
                    WHEN C.arrival_datetime IS NOT NULL THEN 'En Proceso'
                ELSE 'Pendiente' END as status,
                DATE_FORMAT(C.created_at,'%d/%m/%y %H:%i') as created,
                DATE_FORMAT(C.visit_datetime,'%d/%m/%y %H:%i') as visit,
                DATE_FORMAT(C.arrival_datetime,'%d/%m/%y %H:%i') as arrival,
                DATE_FORMAT(C.closed_datetime,'%d/%m/%y %H:%i') as closed,
                IFNULL(DATE_FORMAT(C.updated_at,'%d/%m/%y %H:%i'), '') as updated,

                C.visit_datetime, 
                C.arrival_datetime, 
                C.closed_datetime, 

                CONCAT_WS(' ', TRIM(CL.first_name), TRIM(CL.last_name)) as client_name, 
                CL.phone1 AS client_phone1,
                CL.phone2 AS client_phone2,
                CL.num_doc AS client_document,

                CONCAT(IFNULL(CA.address_street,''),' ',IFNULL(CA.address_nro,''),' ',IFNULL(CA.address_apartament,''),' ',IFNULL(CA.city,'')) AS client_addres_name,
                COUNT(DISTINCT F.id) as cant_archivos,
                IF(COUNT(S.id) > 0, CONCAT('* ', GROUP_CONCAT(DISTINCT S.name SEPARATOR '\n* ')), NULL) as tecnicos,
                IF(COUNT(P.id) > 0, CONCAT('* ', GROUP_CONCAT(DISTINCT CONCAT(PR.descripcion, ' (Cant: ', IFNULL(P.quantity, 1), ')') SEPARATOR '\n* ')), NULL) as productos,
                IF(COUNT(N.id) > 0, GROUP_CONCAT(
                    DISTINCT CONCAT(
                        IFNULL(UN.name, 'Sistema'), ' ',    
                        DATE_FORMAT(N.created_at, '%d/%m/%y %H:%i'), ': ', 
                        TRIM(REPLACE(REPLACE(N.note, '\r', ''), '\n', ' '))
                    ) SEPARATOR '\r\n\r\n'
                ), NULL) as notas,
                JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'usuario', IFNULL(UN.name, 'Sistema'),
                        'fecha', DATE_FORMAT(N.created_at, '%d/%m/%y %H:%i'),
                        'texto', TRIM(REPLACE(REPLACE(N.note, '\r', ''), '\n', ' '))
                    )
                ) as notas_json
            ")
            ->groupBy('C.id');
    }

    protected static function booted()
    {
        // Al crear: registramos quién lo hace
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        // Al actualizar: registramos quién edita
        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        // Al eliminar (Soft Delete): registramos quién lo borra antes de que se oculte
        static::deleting(function ($model) {
            if (auth()->check()) {
                $model->deleted_by = auth()->id();
                $model->save(); // Guardamos el cambio en la base de datos
            }
        });
    }
}
