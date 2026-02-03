<div class="modal fade" id="editrol" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border: none; border-radius: 20px;">
            <div class="modal-header text-white border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white fw-bold">
                    <i class="fas fa-edit me-2"></i>Editar Rol
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-none bg-light" id="modal-body-edit-rol-error">
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h5 class="text-muted">Error al obtener la información. Por favor reintentelo o comuníquese con Soporte</h5>
                </div>
            </div>
            <div class="modal-body bg-light" id="modal-body-edit-rol-roller">
                <div class="text-center py-5">
                    <div class="lds-roller"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
                </div>
            </div>
            <div class="modal-body bg-light d-none" id="modal-body-edit-rol">
                <form action="" method="POST" id="formeditrol">
                    @csrf
                    @method('PUT')
                    <div class="mb-2">
                        <label for="name" class="form-label mb-0 ps-3">Nombre</label>
                        <input type="text" class="form-control validate" name="name" id="e_name" placeholder="" required value="{{ old('name') }}">
                    </div>
                    <div class="mb-2">
                        <label for="description" class="form-label mb-0 ps-3">Descripcion</label>
                        <input type="text" class="form-control" name="description" id="e_description" required value="{{ old('description') }}">
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-0 d-none" id="modal-footer-edit-rol">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-success rounded-pill px-4" id="btn-update-rol">
                    <i class="fas fa-check me-1"></i> Actualizar
                </button>
            </div>
        </div>
    </div>
</div>