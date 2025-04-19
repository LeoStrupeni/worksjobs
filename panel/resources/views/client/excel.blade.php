<div class="modal fade" id="excelclient" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevos Clientes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <form action="{{route('importaExcelClient')}}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-6 text-center my-3 border-right">
                            <h4 class="my-3">Descargar plantilla</h4>
                            <a href="{{env('APP_URL')}}/excel/Plantilla_Alta_Clientes.xlsx">
                                <i class="fas fa-download fa-5x border border-primary rounded-circle p-5 text-primary" style="border-width: 10px !important;"></i>
                            </a>
                        </div>
                        <div class="col-6 text-center my-3">
                            <h4 class="my-3">Subir plantilla</h4>
                            <label for="archivo">
                                <i class="fas fa-upload fa-5x border border-primary rounded-circle p-5 text-primary" style="border-width: 10px !important;"></i>
                                <input type="file" name="archivo" id="archivo" class="d-none">
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success btn-block float-end" onclick="mostrarOverlay();">Guardar</button>
                </form>
							
			</div>
		</div>
	</div>
</div>
