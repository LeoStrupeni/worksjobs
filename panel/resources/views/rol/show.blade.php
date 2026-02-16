<div class="modal fade" id="showrol" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border: none; border-radius: 20px;">
            <div class="modal-header text-white border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white fw-bold">
                    <i class="fas fa-eye me-2"></i>Ver Rol
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-none bg-light" id="modal-body-show-rol-error">
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3 me-2"></i>
                    <h5 class="text-muted">Error al obtener la información. Por favor reintentelo o comuníquese con Soporte</h5>
                </div>
            </div>
            <div class="modal-body bg-light" id="modal-body-show-rol-roller">
                <div class="text-center py-5">
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