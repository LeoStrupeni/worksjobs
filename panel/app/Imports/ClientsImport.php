<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\Clients_Addres;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;

class ClientsImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        if ($row[0] != 'Nombre' && $row[0] != '') {
            
            // 0 => "Nombre"
            // 1 => "Apellido"
            // 2 => "Tipo dni"
            // 3 => "documento"
            // 4 => "email"
            // 5 => "telefono1"
            // 6 => "telefono2"
            // 7 => "pais"
            // 8 => "provincia"
            // 9 => "ciudad"
            // 10 => "cp"
            // 11 => "calle"
            // 12 => "numero"
            // 13 => "piso/dpto"
            // 14 => "obs domicilio"
            // 15 => "obs others"

            if($row[3] == 0){
                $exist=null;
            }else{
                $exist = Client::where('num_doc',$row[3])->first();
            }
            
            if (empty($exist)) {
                $client = Client::create([
                    'first_name' => $row[0],
                    'last_name' => $row[1],
                    'type_doc' => $row[2],
                    'num_doc' => $row[3],
                    'email' => $row[4],
                    'phone1' => $row[5],
                    'phone2' => $row[6],
                    'country' => $row[7],
                    'state' => $row[8],
                    'city' => $row[9],
                    'cp' => $row[10],
                    'address_street' => $row[11],
                    'address_nro' => $row[12],
                    'address_apartament' => $row[13],
                    'address_detail' => $row[14],
                    'other_obs' => $row[15],
                    'created_at' => Carbon::now()
                ]);
            } else {
                $client = Client::where('num_doc',$row[3])->first();
            }

            return new Clients_Addres([
                'client_id' => $client->id,
                'country' => $row[7],
                'state' => $row[8],
                'city' => $row[9],
                'cp' => $row[10],
                'address_street' => $row[11],
                'address_nro' => $row[12],
                'address_apartament' => $row[13],
                'address_detail' => $row[14],
                'other_obs' => $row[15],
                'created_at' => Carbon::now()
            ]);            
        }
    }
}
