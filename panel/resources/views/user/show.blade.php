<div class="modal fade" id="showuser" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border: none; border-radius: 20px;">
            <div class="modal-header text-white border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white fw-bold">
                    <i class="fas fa-eye me-2"></i>Ver Usuario
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-none bg-light" id="modal-body-show-user-error">
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h5 class="text-muted">Error al obtener la información. Por favor reintentelo o comuníquese con Soporte</h5>
                </div>
            </div>
            <div class="modal-body bg-light" id="modal-body-show-user-roller">
                <div class="text-center py-5">
                    <br>
                    <br>
                    <div class="lds-roller"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
                    <br>
                    <br>
                    <br>
                </div>
            </div>
            <div class="modal-body d-none" id="modal-body-show-user">
                <div class="row justify-content-evenly">
                    <div class="col-9">
                        <div class="mb-2">
                            <label for="name" class="form-label mb-0 ps-3">Nombre</label>
                            <input type="text" class="form-control" id="s_name" readonly>
                        </div>
                        <div class="mb-2">
                            <label for="email" class="form-label mb-0 ps-3">Email</label>
                            <input type="email" class="form-control" id="s_email" readonly>
                        </div>
                        <div class="mb-2">
                            <label for="rol" class="form-label mb-0 ps-3">Rol</label>
                            <select class="form-control" id="s_rol" style="width: 100%" disabled>
                                <option></option>
                                @foreach ($roles as $rol)
                                    <option value="{{ $rol->id }}">{{ $rol->name }}</option>
                                @endforeach
                            </select>
                        </div>                            
                    </div>
                    <div class="col-3 align-self-center">
                        <div class="avatar">
                            <img class="profile-pic" id="imagen-user-show" src=""/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>