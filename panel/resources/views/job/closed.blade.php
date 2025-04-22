<div class="modal fade" id="closedjob" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="titleclosedjob"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{route('job.closed')}}" method="POST" id="formclosedjob" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="latitude">
                    <input type="hidden" name="longitude">
                    <input type="hidden" name="jsongeolocation">
                    <input type="hidden" name="client_id">
                    <input type="hidden" name="id">
                    <div class="mb-2">
                        <label for="client_addres_name" class="form-label mb-0 ps-3 fw-bold">Domicilio</label>
                        <input type="text" class="form-control" name="client_addres_name" readonly>
                    </div>
                    <div class="mb-2">
                        <label for="job_description" class="form-label mb-0 ps-3 fw-bold">Descripcion de trabajo</label>
                        <textarea class="form-control" name="job_description" rows="5" readonly></textarea>
                    </div>
                    <div class="mb-2">
                        <label for="closed_job_observation" class="form-label mb-0 ps-3 fw-bold">Observaciones de cierre</label>
                        <textarea class="form-control validate" name="closed_job_observation" rows="5"></textarea>
                    </div>

                    <div class="row">
                        <p class="form-label ps-3 fw-bold">Cargar archivos / imagenes</p>
                        <div class="col-12 mb-2">
                            <div style="position: relative;padding: 0;">
                                <input class="form-control form-control-sm" type="file" name="images[]" accept="video/*,image/*" onchange="scaleImage(this,'lightgalleryClosed');">
                                <span class="btn-danger-pro" style="position: absolute; height: 100%; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-pack: center;-ms-flex-pack: center;justify-content: center;top: 4px;right: 10px; " onclick="this.parentNode.children[0].value='';scaleImage(this.parentNode.children[0],'lightgalleryClosed');">
                                    <span><i class="fas fa-trash"></i></span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div id="lightgalleryClosedNone" class="d-none">

                    </div>
                    <div id="lightgalleryClosed" class="row justify-content-start">

                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn-closed-job">Guardar</button>
            </div>
        </div>
    </div>
</div>