<div class="modal fade" id="showuser" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Ver Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-none" id="modal-body-show-user-error">
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
            <div class="modal-body" id="modal-body-show-user-roller">
                <div style="display:block;" class="text-center">
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
                            <select class="form-control" id="s_rol" style="width: 100%">
                                <option></option>
                                <option value="1">Admin</option>
                                <option value="2" selected>Secretaria</option>
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