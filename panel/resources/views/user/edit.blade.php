<div class="modal fade" id="edituser" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border: none; border-radius: 20px;">
            <div class="modal-header text-white border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white fw-bold">
                    <i class="fas fa-edit me-2"></i>Editar Usuario
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-none bg-light" id="modal-body-edit-user-error">
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3 me-2"></i>
                    <h5 class="text-muted">Error al obtener la información. Por favor reintentelo o comuníquese con Soporte</h5>
                </div>
            </div>
            <div class="modal-body bg-light" id="modal-body-edit-user-roller">
                <div class="text-center py-5">
                    <div class="lds-roller"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
                </div>
            </div>
            <div class="modal-body bg-light d-none" id="modal-body-edit-user">
                <form action="" enctype="multipart/form-data" id="formedituser" method="POST">
                    @csrf
                    @method('PUT')
                    {{-- <input type="hidden" name="base64" class="base64"> --}}
                    <div class="row justify-content-evenly">
                        <div class="col-9">
                            <div class="mb-2">
                                <label for="name" class="form-label mb-0 ps-3">Nombre</label>
                                <input type="text" class="form-control e_validate" name="name" id="e_name" placeholder="" required value="{{ old('name') }}">
                            </div>
                            <div class="mb-2">
                                <label for="email" class="form-label mb-0 ps-3">Email</label>
                                <input type="email" class="form-control e_validate" name="email" id="e_email" placeholder="name@example.com" required value="{{ old('email') }}">
                            </div>
                            <div class="mb-2">
                                <label for="rol" class="form-label mb-0 ps-3">Rol</label>
                                <select class="form-control e_validate" name="rol" id="e_rol" style="width: 100%" required>
                                    <option></option>
                                    @foreach ($roles as $rol)
                                        <option value="{{ $rol->id }}">{{ $rol->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <p class="mb-1">
                                <a class="btn btn-link" data-bs-toggle="collapse" href="#changepass" role="button" aria-expanded="false" aria-controls="collapseExample">
                                    Haga click se desea actualizar la contraseña.
                                </a>
                            </p>
                            <div class="collapse" id="changepass">
                                <div class="mb-2">
                                    <label for="password" class="form-label mb-0 ps-3">Nueva Contraseña</label>
                                    <div class="w-100 float-end mb-2" style="position: relative;padding: 0;">
                                        <input type="password" name="password" id="e_password" class="form-control" >
                                        <span style="position: absolute; height: 100%; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-pack: center;-ms-flex-pack: center;justify-content: center;top: 7px;width: 3.2rem;right: 0;">
                                            <span><i class="fa-solid fa-eye verpass me-2" style="cursor: pointer;"></i></span>
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="password" class="form-label mb-0 ps-3">Confirmar nueva contraseña</label>
                                    <input type="password" name="password_confirmation" id="e_password_confirmation" class="form-control">
                                    <small style="font-style: italic;" class="ps-3 password form-text text-danger"></small>
                                </div>
                            </div>
                        </div>
                        <div class="col-3 align-self-center">
                            <div class="avatar">
                                <img class="profile-pic" id="imagen-user-edit" src=""/>
                                <label class="avatar_upload">
                                    <i class="fa fa-pen me-2"></i>
                                    <input class="file-upload d-none" type="file" name="profile_avatar" accept="image/*" 
                                        {{-- onchange="convert64(event,this);" --}}
                                    >
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-0 d-none" id="modal-footer-edit-user">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-success rounded-pill px-4" id="btn-update-user">
                    <i class="fas fa-check me-2"></i>Actualizar
                </button>
            </div>
        </div>
    </div>
</div>