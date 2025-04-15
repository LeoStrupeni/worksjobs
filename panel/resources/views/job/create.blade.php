<div class="modal fade" id="createjob" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Nueva Tarea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="POST" id="formnewjob">
                    @csrf
                    <input type="hidden" name="latitude">
                    <input type="hidden" name="longitude">
                    <input type="hidden" name="jsongeolocation">
                    <div class="mb-2">
                        <label for="client_id" class="form-label mb-0 ps-3 fw-bold">
                            Cliente
                            <span id="spinner1" class="d-none">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            </span>
                        </label>
                        <select class="form-control validate selectpicker searchvar" name="client_id" style="width: 100%" 
                            data-live-search="true" data-size="5" data-none-selected-text="Seleccione un cliente" data-none-results-text="No hay resultados coincidentes" id="client_id" required>
                                <option></option>
                            @foreach ($clients as $c)
                                <option value="{{$c->id}}">{{$c->first_name.' '.$c->last_name}}</option>
                            @endforeach
                        </select>
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
                <button type="button" class="btn btn-success" id="btn-create-job">Guardar</button>
            </div>
        </div>
    </div>
</div>