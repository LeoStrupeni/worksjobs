<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(Auth::check()){
            $val = $this->getloginrol();
            if ($val == false){
                return redirect()->route('logout');     
            }
            $roles = Rol::where('estatus', 1)->get();
            return view("user", compact('roles'));
        }
        return redirect()->route('login');
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {   
        $request->validate([
                'name' => ['required','string'],
                'email' => ['required','email','string','unique:users,email'],
                'password' => ['required','confirmed','string'],
                'rol' => ['required'],
            ],
            [
                'required' => 'El campo es requerido.',
                'string' => 'El campo debe ser de tipo alfanumérico.',
                'email' => 'El campo no es un email.',
                'confirmed' => 'Las contraseñas no coinciden.',
                'unique' => 'El mail ya se encuentra registrado.',
            ]
        );
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'estatus' => 1
        ]);

        $this->addAvatar($request, $user->id);

        $rol = Rol::find($request->rol);

        if(isset($rol)){
            $user->assignRole($rol->name);
        } else {
            $rolT = Rol::first();
            $user->assignRole($rolT->name);
        }
        
        return back();
    }

    public function show($id)
    {
        $user = User::find($id);

        if($user->estatus == 1){
            $user->estatus = 0;
            $user->save();
        }else {
            $user->estatus = 1;
            $user->save();
        }

        return 1;
    }

    public function edit($id)
    {
        $user = User::join('model_has_roles AS MR','users.id','MR.model_id')
            ->join('roles AS R','MR.role_id','R.id')
            ->selectRaw("users.id, users.name, users.email, R.id as rolid,R.name as rolname, users.imagen")
            ->where('users.id',$id)
            ->first();
        return $user;
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        $datos = array();
        if(isset($request->name) && $request->name != $user->name){
            $request->validate(['name' => ['required','string']],
                [ 'required' => 'El campo es requerido.','string' => 'El campo debe ser de tipo alfanumérico.']
            );
            $datos['name'] = $request->name;
        }
        if(isset($request->email) && $request->email != $user->email){
            $request->validate(['email' => ['required','email','string','unique:users,email']],
                [ 'required' => 'El campo es requerido.', 'string' => 'El campo debe ser de tipo alfanumérico.', 'email' => 'El campo no es un email.', 'unique' => 'El mail ya se encuentra registrado.']
            );
            $datos['email'] = $request->email;
        }

        $request->validate(['rol' => ['required']],
            [ 'required' => 'El campo es requerido.' ]
        );

        if(isset($request->password)){
            $request->validate([ 'password' => ['required','confirmed','string']],
                [ 'required' => 'El campo es requerido.', 'string' => 'El campo debe ser de tipo alfanumérico.', 'confirmed' => 'Las contraseñas no coinciden.']
            );

            $datos['password'] = Hash::make($request->password);
        }

        if(count($datos) > 0){
            User::where('id',$id)->update($datos);
        }

        $this->addAvatar($request, $id);

        $rol = Rol::find($request->rol);

        if(isset($rol)){
            $user->assignRole($rol->name);
        } else {
            $rolT = Rol::first();
            $user->assignRole($rolT->name);
        }
        
        return back();
    }

    public function destroy($id, Request $request)
    {
        User::find($id)->update([
            'deleted_at' => Carbon::now(), 
            'motivo_delete' => $request->motivo,
            'estatus' => 0
        ]);

        return back();
    }

    protected function addAvatar(Request $request, $user_id)
    {
        if ($request->hasFile('profile_avatar')) {
            $file = $request->file('profile_avatar');
            $path = $file->storeAs('public', 'avatar_'.$user_id.'_'.time().'.'.$file->getClientOriginalExtension());
            User::find($user_id)->update(['imagen'=>basename($path)]);
        }
    }
}
