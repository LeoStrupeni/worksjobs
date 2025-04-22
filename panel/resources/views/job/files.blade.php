<div class="modal fade" id="filesjob" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="titlefilesjob"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{route('job.files')}}" method="POST" id="formaddfilesjob" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" id="id_job_file">
                    <div class="row">
                        <p class="form-label ps-3 fw-bold">Cargar archivos / imagenes</p>
                        <div class="col-12 mb-2">
                            <div style="position: relative;padding: 0;">
                                <input class="form-control form-control-sm" type="file" name="images[]" accept="video/*,image/*" onchange="scaleImage(this,'lightgalleryFiles');">
                                <span class="btn-danger-pro" style="position: absolute; height: 100%; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-pack: center;-ms-flex-pack: center;justify-content: center;top: 4px;right: 10px; " onclick="this.parentNode.children[0].value='';scaleImage(this.parentNode.children[0],'lightgalleryFiles');">
                                    <span><i class="fas fa-trash"></i></span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div id="lightgalleryFilesNone" class="d-none">

                    </div>
                    <div id="lightgalleryFiles" class="row justify-content-start">

                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="$('#formaddfilesjob').submit();">Guardar</button>
            </div>
        </div>
    </div>
</div>