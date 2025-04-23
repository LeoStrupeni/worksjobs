<div class="modal fade" id="editjob" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Tarea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-none" id="modal-body-edit-job-error">
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
            <div class="modal-body" id="modal-body-edit-job-roller">
                <div style="display:block;" class="text-center">
                    <br>
                    <br>
                    <div class="lds-roller"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
                    <br>
                    <br>
                    <br>
                </div>
            </div>
            <div class="modal-body" id="modal-body-edit-job">
                <form action="" method="POST" id="formeditjob" enctype="multipart/form-data">
                    @csrf
                    @method("PUT")
                    <input type="hidden" name="latitude">
                    <input type="hidden" name="longitude">
                    <input type="hidden" name="jsongeolocation">
                    <input type="hidden" name="client_id">
                    <div class="mb-2">
                        <label for="client_name" class="form-label mb-0 ps-3 fw-bold">Cliente</label>
                        <input type="text" class="form-control" name="client_name"  value="{{ old('client_name') }}" readonly>
                    </div>
                    <div class="mb-2">
                        <label for="address_id_e" class="form-label mb-0 ps-3 fw-bold">
                            Domicilio
                        </label>
                        <select class="form-control selectpicker" name="address_id" style="width: 100%" id="address_id_e"  data-none-selected-text="Seleccione un domicilio" required>

                        </select>
                    </div>
                    <div class="mb-2">
                        <label for="visit_datetime" class="form-label mb-0 ps-3 fw-bold">Fecha y hora de visita</label>
                        <input type="datetime-local" class="form-control validate" name="visit_datetime" value="{{ old('visit_datetime') }}" required>
                    </div>
                    <div class="mb-2">
                        <label for="job_description" class="form-label mb-0 ps-3 fw-bold">Descripcion de trabajo</label>
                        <textarea class="form-control validate" name="job_description" rows="5">{{ old('job_description') }}</textarea>
                    </div>
                    <div class="row">
                        <p class="form-label ps-3 fw-bold">Cargar archivos / imagenes</p>
                        <div class="col-12 mb-2">
                            <div style="position: relative;padding: 0;">
                                <input class="form-control form-control-sm" type="file" name="images[]" accept="video/*,image/*" onchange="scaleImage(this,'lightgalleryEdit');">
                                <span class="btn-danger-pro" style="position: absolute; height: 100%; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-pack: center;-ms-flex-pack: center;justify-content: center;top: 4px;right: 10px; " onclick="this.parentNode.children[0].value='';scaleImage(this.parentNode.children[0],'lightgalleryEdit');">
                                    <span><i class="fas fa-trash"></i></span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div id="lightgalleryEditNone" class="d-none">

                    </div>
                    <div id="lightgalleryEdit" class="row justify-content-start">

                    </div>
                </form>
            </div>
            <div class="modal-footer"  id="modal-foot-edit-job">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn-update-job">Guardar</button>
            </div>
        </div>
    </div>
</div>