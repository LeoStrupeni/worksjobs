<div class="modal fade" id="createuser" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="POST" enctype="multipart/form-data" id="formnewuser">
                    @csrf
                    <input type="hidden" name="base64" class="base64">
                    <div class="row justify-content-evenly">
                        <div class="col-9">
                            <div class="mb-2">
                                <label for="name" class="form-label mb-0 ps-3">Nombre</label>
                                <input type="text" class="form-control validate" name="name" id="name" placeholder="" required value="{{ old('name') }}">
                            </div>
                            <div class="mb-2">
                                <label for="email" class="form-label mb-0 ps-3">Email</label>
                                <input type="email" class="form-control validate" name="email" id="email" placeholder="name@example.com" required value="{{ old('email') }}">
                            </div>
                            <div class="mb-2">
                                <label for="rol" class="form-label mb-0 ps-3">Rol</label>
                                <select class="form-control validate" name="rol" style="width: 100%" required>
                                    <option></option>
                                    <option value="1">Admin</option>
                                    <option value="2" selected>Secretaria</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label for="password" class="form-label mb-0 ps-3">Contraseña</label>
                                <div class="w-100 float-end mb-2" style="position: relative;padding: 0;">
                                    <input type="password" name="password" id="password" class="form-control validate" >
                                    <span style="position: absolute; height: 100%; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-pack: center;-ms-flex-pack: center;justify-content: center;top: 7px;width: 3.2rem;right: 0;">
                                        <span><i class="fa-solid fa-eye verpass" style="cursor: pointer;"></i></span>
                                    </span>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label for="password" class="form-label mb-0 ps-3">Confirmar contraseña</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control validate">
                                <small style="font-style: italic;" class="ps-3 password form-text text-danger"></small>
                            </div>
                        </div>
                        <div class="col-3 align-self-center">
                            <div class="avatar">
                                <img class="profile-pic" id="imagen-user-create"  src=""/>
                                <label class="avatar_upload">
                                    <i class="fa fa-pen"></i>
                                    <input class="file-upload d-none" type="file" name="profile_avatar" accept="image/*" onchange="convert64(event,this);">
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn-create-user">Guardar</button>
            </div>
        </div>
    </div>
</div>