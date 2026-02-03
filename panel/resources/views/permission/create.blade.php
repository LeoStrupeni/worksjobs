<div class="modal fade" id="createpermission" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border: none; border-radius: 20px;">
            <div class="modal-header text-white border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white fw-bold">
                    <i class="fa-solid fa-key me-2"></i>Nuevo Permiso
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <form action="" method="POST" id="formnewpermission">
                    @csrf
                    <div class="mb-2">
                        <label for="name" class="form-label mb-0 ps-3">Nombre</label>
                        <input type="text" class="form-control validate" name="name" id="name" placeholder="" required value="{{ old('name') }}">
                    </div>
                    <div class="mb-2">
                        <label for="opciones" class="form-label mb-0 ps-3 w-100">Opciones</label>
                        <select class="form-control select2" multiple name="opciones[]" style="width: 100%">
                            <option value="create" selected>Crear</option>
                            <option value="read" selected>Leer</option>
                            <option value="update" selected>Actualizar</option>
                            <option value="delete" selected>Eliminar</option>
                        </select>
                        
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-success rounded-pill px-4" id="btn-create-permission">
                    <i class="fas fa-check me-1"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>