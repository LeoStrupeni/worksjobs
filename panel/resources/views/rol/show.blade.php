<div class="modal fade" id="showrol" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Ver Rol</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-none" id="modal-body-show-rol-error">
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
            <div class="modal-body" id="modal-body-show-rol-roller">
                <div style="display:block;" class="text-center">
                    <br>
                    <br>
                    <div class="lds-roller"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
                    <br>
                    <br>
                    <br>
                </div>
            </div>
            <div class="modal-body d-none" id="modal-body-show-rol">
                <div class="mb-2">
                    <label for="name" class="form-label mb-0 ps-3">Nombre</label>
                    <input type="text" class="form-control validate" name="name" id="s_name" placeholder="" required value="{{ old('name') }}">
                </div>
                <div class="mb-2">
                    <label for="description" class="form-label mb-0 ps-3">Descripcion</label>
                    <input type="text" class="form-control" name="description" id="s_description" required value="{{ old('description') }}">
                </div>
            </div>
        </div>
    </div>
</div>