<div class="modal fade" id="editjob" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Tarea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="POST" id="formeditjob">
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
                        <label for="visit_datetime" class="form-label mb-0 ps-3 fw-bold">Fecha y hora de visita</label>
                        <input type="datetime-local" class="form-control validate" name="visit_datetime" value="{{ old('visit_datetime') }}" required>
                    </div>
                    <div class="mb-2">
                        <label for="job_description" class="form-label mb-0 ps-3 fw-bold">Descripcion de trabajo</label>
                        <textarea class="form-control validate" name="job_description" rows="10">{{ old('job_description') }}</textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn-update-job">Guardar</button>
            </div>
        </div>
    </div>
</div>