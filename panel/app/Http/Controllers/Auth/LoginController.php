<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    public function login(Request $request)
    {   
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

            Session::put('user.roles', Auth::user()->roles->pluck('name') );
            // Session::put('user.permissions', Auth::user()->getPermissionsViaRoles()->pluck('name') );
            $this->getpermissions();

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
