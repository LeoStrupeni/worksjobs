<button type="button" class="btn btn-link rounded-circle p-0 me-3" data-bs-toggle="offcanvas" href="#offcanvasMenu" >
  <i class="fa-solid fa-sliders text-white"></i>
</button>

<div class="offcanvas offcanvas-start bg-se-primary" data-bs-scroll="false" tabindex="-1" id="offcanvasMenu" aria-labelledby="offcanvasMenuLabel">
    <div class="offcanvas-header">
        <img src="{{env('APP_URL')}}/assets/media/Logo.png" alt="Logo" height="100">
      <button type="button" class="btn-close btn-close-white opacity-100" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <div>
       
      </div>
      <ul class="list-unstyled ps-0">
        <li class="mb-2">
          <button class="btn btn-toggle d-inline-flex align-items-center rounded border-0 collapsed ms-2 text-white text-uppercase hover-success" data-bs-toggle="collapse" data-bs-target="#coc-administracion" aria-expanded="false">
            Administracion
          </button>
          <div class="collapse" id="coc-administracion">
            <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
              @if (in_array('read',Session::get('user')['permissions']['clients']))
                <li>
                  <a class="link-body-emphasis d-inline-flex text-decoration-none rounded ms-5 text-white text-uppercase hover-success" 
                  href="{{ route('client.index') }}">Clientes</a>
                </li>
              @endif
              @if (in_array('read',Session::get('user')['permissions']['jobs']))
                <li>
                  <a class="link-body-emphasis d-inline-flex text-decoration-none rounded ms-5 text-white text-uppercase hover-success" 
                  href="{{ route('jobs.index') }}">Tareas</a>
                </li>
              @endif
            </ul>
          </div>
        </li>
        @if (in_array('read',Session::get('user')['permissions']['users']) || 
            in_array('read',Session::get('user')['permissions']['roles']) ||
            in_array('read',Session::get('user')['permissions']['permissions']) ||
            in_array('read',Session::get('user')['permissions']['cms'])
        )
        <li class="mb-2">
          <button class="btn btn-toggle d-inline-flex align-items-center rounded border-0 collapsed ms-2 text-white text-uppercase hover-success" data-bs-toggle="collapse" data-bs-target="#coc-configuracion" aria-expanded="false">
            Configuración
          </button>
          <div class="collapse" id="coc-configuracion">
            <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
              @if (in_array('read',Session::get('user')['permissions']['users']))
                <li>
                  <a class="link-body-emphasis d-inline-flex text-decoration-none rounded ms-5 text-white text-uppercase hover-success" 
                    href="{{ route('users.index') }}">Usuarios</a>
                </li>
              @endif
              @if (in_array('read',Session::get('user')['permissions']['roles']))
                <li>
                  <a class="link-body-emphasis d-inline-flex text-decoration-none rounded ms-5 text-white text-uppercase hover-success" 
                    href="{{ route('roles.index') }}">Roles</a>
                </li>
              @endif
              @if (user_has_special_role())
                <li>
                  <a class="link-body-emphasis d-inline-flex text-decoration-none rounded ms-5 text-white text-uppercase hover-success" 
                    href="{{ route('permission.index') }}">Permisos</a>
                </li>
              @endif
              @if (in_array('read',Session::get('user')['permissions']['cms']))
                <li>
                  <a class="link-body-emphasis d-inline-flex text-decoration-none rounded ms-5 text-white text-uppercase hover-success" href="{{ route('cms.index') }}">CMS</a>
                </li>
                <li>
                  <a class="link-body-emphasis d-inline-flex text-decoration-none rounded ms-5 text-white text-uppercase hover-success" href="{{ route('cms.media') }}">Librería de Medios</a>
                </li>
              @endif
            </ul>
          </div>
        </li>
        @endif
        @if (in_array('create',Session::get('user')['permissions']['jobs']))
          <li class="mb-2">
            <button type="button" class="btn btn-toggle d-inline-flex align-items-center rounded border-0 ms-2 create-job text-white text-uppercase hover-success">Crear Tarea</button>
          </li>       
        @endif

        <li class="border-top my-3"></li>
        <li class="mb-2">
            <button class="btn btn-toggle d-inline-flex align-items-center rounded border-0 collapsed ms-2 text-white text-uppercase hover-success" data-bs-toggle="collapse" data-bs-target="#coc-account" aria-expanded="false">
                Usuario
            </button>
            <div class="collapse" id="coc-account">
                <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
                    <li><a href="#" class="link-body-emphasis d-inline-flex text-decoration-none rounded ms-5 text-white text-uppercase hover-success update-user" data-id="{{Auth::user()->id}}">Perfil</a></li>
                    <li>
                      <form action="/logout" method="post" style="display: inline;">
                          @csrf
                          <a class="link-body-emphasis d-inline-flex text-decoration-none rounded ms-5 text-white text-uppercase hover-success" href="#" onclick="this.closest('form').submit()">Logout</a>
                      </form>
                    </li>
                </ul>
            </div>
        </li>
      </ul>
    </div>
</div>