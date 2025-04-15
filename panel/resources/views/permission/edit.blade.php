<div class="modal fade" id="editpermission" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Editar Permisos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-none" id="modal-body-edit-permission-error">
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
            <div class="modal-body" id="modal-body-edit-permission-roller">
                <div style="display:block;" class="text-center">
                    <br>
                    <br>
                    <div class="lds-roller"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
                    <br>
                    <br>
                    <br>
                </div>
            </div>
            <div class="modal-body d-none" id="modal-body-edit-permission">
                <form action="" method="POST" id="formeditpermission">
                    @csrf
                    @method('PUT')
                    <div class="mb-2">
                        <label for="name" class="form-label mb-0 ps-3">Nombre</label>
                        <input type="text" class="form-control validate" name="name" id="e_name" placeholder="" required value="{{ old('name') }}">
                    </div>

                </form>
            </div>
            <div class="modal-footer d-none" id="modal-footer-edit-permission">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn-update-permission">Actualizar</button>
            </div>
        </div>
    </div>
</div>