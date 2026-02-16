<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Job;
use App\Models\Jobs_Note;
use App\Models\CmsSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class HomeController extends Controller
{
    public function index()
    {
        if(Auth::check()){
            $val = $this->getloginrol();
            if ($val == false){
                return redirect()->route('logout');     
            }

            // Usar directamente el query del modelo en lugar de llamar a JobController
            $query = Job::getJobsQuery()->toSql();

            if(user_has_special_role()){
                $query .= " AND C.archived = 0 AND (
                    CAST(C.visit_datetime as DATE) BETWEEN DATE(NOW()) and DATE_ADD(DATE(NOW()),INTERVAL 7 DAY)
                    OR CAST(C.visit_datetime as DATE) < DATE(NOW())
                    OR (C.arrival_datetime IS NOT NULL AND C.closed_datetime IS NULL)   
                ) 
                ORDER BY estatusorder ASC, ordervisit ASC";
            } else {
                $query .= " AND C.archived = 0 AND (
                    CAST(C.visit_datetime as DATE) BETWEEN DATE(NOW()) and DATE_ADD(DATE(NOW()),INTERVAL 3 DAY)
                    OR (C.arrival_datetime IS NULL AND C.closed_datetime IS NULL AND CAST(C.visit_datetime as DATE) < DATE(NOW())) 
                    OR (C.arrival_datetime IS NOT NULL AND C.closed_datetime IS NULL)   
                    OR  CAST(C.closed_datetime as DATE) BETWEEN DATE_ADD(DATE(NOW()),INTERVAL -1 DAY) and DATE_ADD(DATE(NOW()),INTERVAL 1 DAY)
                ) 
                ORDER BY estatusorder ASC, ordervisit ASC";
            }



            $jobs = DB::select($query);

            foreach ($jobs as $j) {
                $note =Jobs_Note::where('jobs_id',$j->id)->first();   
                $j->getnotes = $note ? 'si' : 'no';
            }

            return view("home", compact("jobs"));
        }
        
        // Cargar secciones del CMS para la vista pública
        $sections = CmsSection::where('is_active', true)
            ->orderBy('order')
            ->get()
            ->mapWithKeys(function ($section) {
                return [$section->slug => $section->config];
            })
            ->toArray();
        
        return view("public.home", compact('sections'));
        // return redirect()->route('login');
    }
}
