<?php

namespace App\Http\Controllers;

use App\Imports\ClientsImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExcelController extends Controller
{

    public function importaClientsExcel(Request $request)
    {
        $file = $request->file('archivo');
        if(isset($file)){
            Excel::import(new ClientsImport(), $file);
        }
        return back();    
    }
}
