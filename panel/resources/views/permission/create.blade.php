<div class="modal fade" id="createpermission" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Nuevo Permiso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
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
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn-create-permission">Guardar</button>
            </div>
        </div>
    </div>
</div>