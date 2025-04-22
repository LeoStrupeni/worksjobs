@auth
    <nav class="navbar navbar-expand-lg p-0 sticky-top" style="background-color: #00274e !important; height: 65px;">
        <div class="container-fluid">
            <a class="navbar-brand m-0 ps-5" href="{{ route('home.index') }}">
                <img src="{{env('APP_URL')}}/assets/media/Logo.png" alt="Logo" height="60">
            </a>

            <div class="btn-group d-lg-none" role="group">
                @include('Layout/header/offcanvas')
                @include('Layout/header/avatar')
            </div>

            <div class="collapse navbar-collapse justify-content-start ms-5" id="navbarText">
                <ul class="navbar-nav" style="height: 65px;">
                    <li class="nav-item dropdown rounded btn-header-menu">
                        <button class="btn btn-lg text-white rounded h-100" data-bs-toggle="dropdown" aria-expanded="false">
                            Administracion
                        </button>
                        <ul class="dropdown-menu rounded p-0">
                            @if (in_array('read',Session::get('user')['permissions']['clients']))
                                <li><a class="dropdown-item rounded py-3" href="{{ route('client.index') }}">Clientes</a></li>    
                            @endif
                            @if (in_array('read',Session::get('user')['permissions']['jobs']))
                                <li><a class="dropdown-item rounded py-3" href="{{ route('jobs.index') }}">Tareas</a></li>
                            @endif
                        </ul>
                    </li>

                    @if (in_array('read',Session::get('user')['permissions']['users']) || 
                        in_array('read',Session::get('user')['permissions']['roles']) ||
                        in_array('read',Session::get('user')['permissions']['permissions'])
                    )
                    <li class="nav-item dropdown btn-header-menu">
                        <button class="btn btn-lg text-white rounded h-100" data-bs-toggle="dropdown" aria-expanded="false">
                            Configuración
                        </button>
                        <ul class="dropdown-menu rounded p-0">
                            @if (in_array('read',Session::get('user')['permissions']['users']))
                                <li><a class="dropdown-item rounded py-3" href="{{ route('users.index') }}">Usuarios</a></li>
                            @endif
                            @if (in_array('read',Session::get('user')['permissions']['roles']))
                                <li><a class="dropdown-item rounded py-3" href="{{ route('roles.index') }}">Roles</a></li>
                            @endif
                            @if (Session::get('user')['roles'][0] == 'sistema' || Session::get('user')['roles'][0] == 'admin')
                                <li><a class="dropdown-item rounded py-3" href="{{ route('permission.index') }}">Permisos</a></li>   
                            @endif
                        </ul>
                    </li>
                    @endif
                    @if (in_array('create',Session::get('user')['permissions']['jobs']))
                        <li class="nav-item dropdown btn-header-menu">
                            <button type="button" class="btn btn-lg text-white rounded h-100 create">Crear Tarea</button>
                        </li>       
                    @endif

                </ul>
            </div>
            <div class="d-none d-lg-block">
                @include('Layout/header/avatar')
            </div>
        </div>
    </nav>  
@endauth
