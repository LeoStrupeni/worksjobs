<div class="dropdown">
    @php
        $imagen = Auth::user()->imagen;
        if($imagen == '' || $imagen == null){$imagen = env('APP_URL')."/assets/media/avatar.png";}
    @endphp
    <button type="button" class="btn border-0 rounded-circle p-0" data-bs-toggle="dropdown" aria-expanded="true">
        <img src="{{$imagen}}" class="rounded-circle" height="48">
        <i class="fa-solid fa-caret-down text-white" style="position: relative;left: -15px;bottom: -20px;"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end rounded p-0">
        <li>
            <a class="dropdown-item rounded py-3 update-user" data-id="{{Auth::user()->id}}" href="javascript:void(0);">Perfil</a>
        </li>
        <li>
            <form action="/logout" method="get" style="display: inline;">
                @csrf
                <a class="dropdown-item rounded py-3" href="javascript:void(0);" onclick="this.closest('form').submit()">Logout</a>
            </form>
        </li>
    </ul>
</div>
