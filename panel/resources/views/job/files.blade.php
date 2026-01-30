<div class="modal fade" id="filesjob" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border: none; border-radius: 20px;">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 20px 20px 0 0;">
                <h5 class="modal-title text-white fw-bold" id="titlefilesjob">
                    <i class="fas fa-images me-2"></i>Agregar Imágenes
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <form action="{{route('job.files')}}" method="POST" id="formaddfilesjob" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" id="id_job_file">
                    <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                        <div class="card-header bg-white border-0 pt-3">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-danger bg-opacity-10 p-2 me-3">
                                    <i class="fas fa-cloud-upload-alt fa-lg text-danger"></i>
                                </div>
                                <h6 class="mb-0 fw-bold">Cargar Archivos / Imágenes</h6>
                            </div>
                        </div>
                        <div class="card-body">
                            <div style="position: relative;padding: 0;">
                                <input class="form-control" type="file" name="images[]" accept="video/*,image/*" onchange="scaleImage(this,'lightgalleryFiles');">
                                <span class="btn-danger-pro" style="position: absolute; height: 100%; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-pack: center;-ms-flex-pack: center;justify-content: center;top: 4px;right: 10px; " onclick="this.parentNode.children[0].value='';scaleImage(this.parentNode.children[0],'lightgalleryFiles');">
                                    <span><i class="fas fa-trash"></i></span>
                                </span>
                            </div>
                            <div id="lightgalleryFilesNone" class="d-none"></div>
                            <div id="lightgalleryFiles" class="row g-3 mt-2"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="$('#formaddfilesjob').submit();">Guardar</button>
            </div>
        </div>
    </div>
</div>