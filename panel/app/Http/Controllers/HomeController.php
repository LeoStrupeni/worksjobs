<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Job;
use App\Models\Jobs_Note;
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

            $controllerJobs = New JobController;
            $query = $controllerJobs->querydata();

            if(Session::get('user')['roles'][0] == 'sistema' || Session::get('user')['roles'][0] == 'admin'){
                $query .= " AND (
                    CAST(C.visit_datetime as DATE) BETWEEN DATE(NOW()) and DATE_ADD(DATE(NOW()),INTERVAL 7 DAY)
                    OR CAST(C.visit_datetime as DATE) < DATE(NOW())
                    OR (C.arrival_datetime IS NOT NULL AND C.closed_datetime IS NULL)   
                ) 
                ORDER BY estatusorder ASC, ordervisit ASC";
            } else {
                $query .= " AND (
                    CAST(C.visit_datetime as DATE) BETWEEN DATE(NOW()) and DATE_ADD(DATE(NOW()),INTERVAL 3 DAY)
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

            $clients = Client::limit(20)->get();
            $google_api_key = DB::table('configs')->where('name','google_api_key')->first();
            return view("home", compact("jobs","clients","google_api_key"));
        }
        return redirect()->route('login');
    }
}
