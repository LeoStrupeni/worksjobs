<div class="modal fade" id="createrol" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border: none; border-radius: 20px;">
            <div class="modal-header text-white border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white fw-bold">
                    <i class="fa-solid fa-shield-alt me-2"></i>Nuevo Rol
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <form action="" method="POST" id="formnewrol">
                    @csrf
                    <div class="mb-2">
                        <label for="name" class="form-label mb-0 ps-3">Nombre</label>
                        <input type="text" class="form-control validate" name="name" id="name" placeholder="" required value="{{ old('name') }}">
                    </div>
                    <div class="mb-2">
                        <label for="description" class="form-label mb-0 ps-3">Descripcion</label>
                        <input type="text" class="form-control" name="description" id="description" required value="{{ old('description') }}">
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-success rounded-pill px-4" id="btn-create-rol">
                    <i class="fas fa-check me-1"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>