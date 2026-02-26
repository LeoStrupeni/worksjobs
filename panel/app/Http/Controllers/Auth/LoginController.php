<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Permission;
use App\Models\Rol;
use App\Models\User;
use App\Models\Config;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

use function Symfony\Component\String\b;

class LoginController extends Controller
{
    public function loginget()
    {
        return view('Auth.login', [
            'google_api_key' => DB::table('configs')->where('name','google_api_key')->first()->value,
            'recaptcha_key_site' => DB::table('configs')->where('name','recaptcha_key_site')->first()->value
        ]);
    }

    
    public function login(Request $request)
    {   
        $this->validate_recaptcha($request);

        $credentials = $request->validate([
                'email' => ['required','string'],
                'password' => ['required','string']
            ],
            [
                'required' => 'El campo es requerido',
                'string' => 'El campo debe ser de tipo alfanumérico',
                'email' => 'El campo no es un email',
            ]
        );

        $remember = $request->filled('remember');
        
        if(Auth::attempt(['email' => $request->email, 'password' => $request->password, 'estatus' => 1],$remember)) {
            $request->session()->regenerate();

            // Guardar roles por nombre
            Session::put('user.roles', Auth::user()->roles->pluck('name') );
            // Guardar si el usuario tiene un rol especial (is_system_role=1)
            $specialRole = Auth::user()->roles()->where('is_system_role', 1)->first();
            Session::put('user.is_system_role', $specialRole ? true : false);
            $this->getpermissions();

            $modo = Config::where('name', 'colppy_clientes_modo')->value('value') ?? 'local';
            
            switch ($modo) {
                case 'api':
                    $clients = Client::wherenull('deleted_at')
                            ->where('is_from_colppy', 1)
                            ->limit(20)->get();
                    break;
                case 'hibrido':
                    $clients = Client::wherenull('deleted_at')
                            ->limit(20)->get();
                    break;
                default:
                    $clients = Client::wherenull('deleted_at')
                        ->where(function($query) {
                            $query->where('is_from_colppy', '!=', 1)
                                  ->orWhereNull('is_from_colppy');
                        })
                        ->limit(20)->get();
            }    

            $google_api_key = DB::table('configs')->where('name','google_api_key')->value('value') ?? '';
            $recaptcha_key_site = DB::table('configs')->where('name','recaptcha_key_site')->value('value') ?? '';
            // $recaptcha_key_secret = DB::table('configs')->where('name','recaptcha_key_secret')->value('value') ?? '';

            Session::put('user.clients', $clients );
            Session::put('user.google_api_key', $google_api_key );
            Session::put('user.recaptcha_key_site', $recaptcha_key_site );
            // Session::put('user.recaptcha_key_secret', $recaptcha_key_secret );

            $_users = User::join('model_has_roles','users.id','=','model_has_roles.model_id')
                ->where('estatus', 1)
                ->whereNotIn('model_has_roles.role_id', [3,4])
            ->get();
            $users = array();
            foreach ($_users as $u) {
                $users[] = ['id' => $u->id, 'name' => $u->name];
            }
            Session::put('users', $users );
            return redirect()->intended('home')->with('status','estas logueado!');
        }

        // return back()->with('status','Error');
        throw ValidationException::withMessages([
            'email' => ('Credenciales incorrectas.')
        ]);

    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerate();
        return redirect()->to('/');
    }

    public function logoutGet()
    {
        Auth::logout();
        return redirect()->to('/');
    }

    protected function getpermissions()
    {
        $listpermissions = array();
        $permissions = Auth::user()->getPermissionsViaRoles();
        
        $_excludepermision = array();
        foreach ($permissions as $p) {
            $s = explode(' ',$p->name);
            $listpermissions[$s[1]][] = $s[0];
            if(!array_search($s[1],$_excludepermision)){array_push($_excludepermision, $s[1]);}
        }

        $_perm = Permission::wherenotin('general',$_excludepermision)->select('general')->groupby('general')->get();
        foreach ($_perm as $p) { $listpermissions[$p->general][] = 'not'; }

        Session::put('user.permissions', $listpermissions );
    }

}
