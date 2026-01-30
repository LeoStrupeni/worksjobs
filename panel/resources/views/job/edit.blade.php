<div class="modal fade" id="editjob" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border: none; border-radius: 20px;">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0;">
                <h5 class="modal-title text-white fw-bold">
                    <i class="fas fa-edit me-2"></i>Editar Tarea
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-none" id="modal-body-edit-job-error">
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h5 class="text-muted">Error al obtener la información. Por favor reintentelo o comuníquese con Soporte</h5>
                </div>
            </div>
            <div class="modal-body" id="modal-body-edit-job-roller">
                <div class="text-center py-5">
                    <div class="lds-roller"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
                </div>
            </div>
            <div class="modal-body bg-light" id="modal-body-edit-job">
                <form action="" method="POST" id="formeditjob" enctype="multipart/form-data">
                    @csrf
                    @method("PUT")
                    <input type="hidden" name="latitude">
                    <input type="hidden" name="longitude">
                    <input type="hidden" name="jsongeolocation">
                    <input type="hidden" name="client_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
                                <div class="card-header bg-white border-0 pt-3">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-3">
                                            <i class="fas fa-user fa-lg text-primary"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold">Información del Cliente</h6>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label text-muted small mb-1">Cliente</label>
                                        <input type="text" class="form-control" name="client_name" value="{{ old('client_name') }}" readonly style="background-color: #e9ecef;">
                                    </div>
                                    <div>
                                        <label class="form-label text-muted small mb-1">Domicilio</label>
                                        <select class="form-control selectpicker" name="address_id" style="width: 100%" id="address_id_e" data-none-selected-text="Seleccione un domicilio" required>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
                                <div class="card-header bg-white border-0 pt-3">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-info bg-opacity-10 p-2 me-3">
                                            <i class="fas fa-calendar-alt fa-lg text-info"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold">Fecha y Hora</h6>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <label class="form-label text-muted small mb-1">Fecha y hora de visita</label>
                                    <input type="datetime-local" class="form-control validate" name="visit_datetime" value="{{ old('visit_datetime') }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                                <div class="card-header bg-white border-0 pt-3">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-success bg-opacity-10 p-2 me-3">
                                            <i class="fas fa-clipboard-list fa-lg text-success"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold">Descripción del Trabajo</h6>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <textarea class="form-control validate" name="job_description" rows="5" placeholder="Escriba la descripción del trabajo...">{{ old('job_description') }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                                <div class="card-header bg-white border-0 pt-3">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-danger bg-opacity-10 p-2 me-3">
                                            <i class="fas fa-images fa-lg text-danger"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold">Cargar Archivos / Imágenes</h6>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div style="position: relative;padding: 0;">
                                        <input class="form-control" type="file" name="images[]" accept="video/*,image/*" onchange="scaleImage(this,'lightgalleryEdit');">
                                        <span class="btn-danger-pro" style="position: absolute; height: 100%; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-pack: center;-ms-flex-pack: center;justify-content: center;top: 4px;right: 10px;" onclick="this.parentNode.children[0].value='';scaleImage(this.parentNode.children[0],'lightgalleryEdit');">
                                            <span><i class="fas fa-trash"></i></span>
                                        </span>
                                    </div>
                                    <div id="lightgalleryEditNone" class="d-none"></div>
                                    <div id="lightgalleryEdit" class="row g-3 mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 bg-light" id="modal-foot-edit-job">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn-update-job">Guardar</button>
            </div>
        </div>
    </div>
</div>