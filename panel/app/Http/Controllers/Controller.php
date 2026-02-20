<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    public function getloginrol()
    {
        if (Session::get('user') != null) {
            return true;
        } else {
            return false;
        }
    }

    protected function validate_recaptcha(Request $request)
    {
        $token = $request->input('token');
        $action = $request->input('action');

        if (empty($token) || empty($action)) {
            throw ValidationException::withMessages([
                'recaptcha' => ('Captcha vencido.')
            ]);
        }

        $recaptcha_key_secret = DB::table('configs')->where('name','recaptcha_key_secret')->value('value') ?? '';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"https://www.google.com/recaptcha/api/siteverify");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('secret' => $recaptcha_key_secret, 'response' => $token)));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $arrResponse = json_decode($response, true);

        // verify the response
        if(
            $arrResponse["success"] != '1' || 
            $arrResponse["action"] != $action || 
            $arrResponse["score"] < 0.5
        ) {
            throw ValidationException::withMessages([
                'recaptcha' => ('Captcha vencido.')
            ]);
        }
    }
}
