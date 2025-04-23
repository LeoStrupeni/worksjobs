<div class="modal fade" id="edituser" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Editar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-none" id="modal-body-edit-user-error">
                <div style="display:block;" class="text-center">
                    <br>
                    <br>
                    <div class="alert alert-info m-0 justify-content-center" role="alert">
                        <h5 class="m-0">Error al obtener la informacion. Por favor reintentelo o comuniquese con Soporte</h5>
                    </div>
                    <br>
                    <br>
                </div>
            </div>
            <div class="modal-body" id="modal-body-edit-user-roller">
                <div style="display:block;" class="text-center">
                    <br>
                    <br>
                    <div class="lds-roller"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
                    <br>
                    <br>
                    <br>
                </div>
            </div>
            <div class="modal-body d-none" id="modal-body-edit-user">
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
                                    <option value="1">Admin</option>
                                    <option value="2" selected>Técnico</option>
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
                                            <span><i class="fa-solid fa-eye verpass" style="cursor: pointer;"></i></span>
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
                                    <i class="fa fa-pen"></i>
                                    <input class="file-upload d-none" type="file" name="profile_avatar" accept="image/*" 
                                        {{-- onchange="convert64(event,this);" --}}
                                    >
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer d-none" id="modal-footer-edit-user">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn-update-user">Actualizar</button>
            </div>
        </div>
    </div>
</div>