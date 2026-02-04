<div class="modal fade" id="createjob" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border: none; border-radius: 20px;">
            <div class="modal-header text-white border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0;">
                <h5 class="modal-title text-white fw-bold">
                    <i class="fas fa-plus-circle me-2"></i>Nueva Tarea
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <form action="{{route('jobs.store')}}" method="POST" id="formnewjob" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="latitude">
                    <input type="hidden" name="longitude">
                    <input type="hidden" name="jsongeolocation">
                    
                    <!-- Indicador de Geolocalización -->
                    <div class="alert alert-info mb-3 d-flex align-items-center">
                        <i class="fas fa-map-marker-alt fa-2x me-3"></i>
                        <div class="flex-grow-1">
                            <strong>Registro de Ubicación GPS</strong>
                            <p class="mb-0 small">Se guardará automáticamente la ubicación desde donde se crea esta tarea. Si aparece una alerta de permisos, por favor acepte para registrar la ubicación.</p>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <!-- Card Cliente -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <h6 class="card-title d-flex align-items-center mb-3">
                                        <span class="rounded-circle bg-primary bg-opacity-10 p-2 me-2">
                                            <i class="fas fa-user text-primary"></i>
                                        </span>
                                        Información del Cliente
                                    </h6>
                                    <div class="mb-3">
                                        <label for="client_id" class="form-label fw-semibold">
                                            Cliente
                                            <span id="spinner1" class="d-none">
                                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                            </span>
                                        </label>
                                        <select class="form-control validate selectpicker searchvar" name="client_id" style="width: 100%" 
                                            data-live-search="true" data-size="5" data-none-selected-text="Seleccione un cliente" data-none-results-text="No hay resultados coincidentes" id="client_id" required onchange="getAddress(this.value)">
                                                <option></option>
                                            @foreach (Session::get('user.clients') as $c)
                                                <option value="{{$c->id}}">{{$c->first_name.' '.$c->last_name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-0">
                                        <label for="address_id" class="form-label fw-semibold">
                                            Domicilio
                                            <span id="spinner2" class="d-none">
                                                <div class="spinner-border spinner-border-sm text-primary"  role="status"></div>
                                            </span>
                                        </label>
                                        <select class="form-control validate selectpicker searchvar" name="address_id" style="width: 100%" id="address_id"  data-none-selected-text="Seleccione un domicilio"  required>

                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Fecha y Hora -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <h6 class="card-title d-flex align-items-center mb-3">
                                        <span class="rounded-circle bg-info bg-opacity-10 p-2 me-2">
                                            <i class="fas fa-calendar-alt text-info"></i>
                                        </span>
                                        Fecha y Hora
                                    </h6>
                                    <label for="visit_datetime" class="form-label fw-semibold">Fecha y hora de visita</label>
                                    <input type="datetime-local" class="form-control validate" name="visit_datetime" value="{{ old('visit_datetime') }}" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Descripción -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body">
                            <h6 class="card-title d-flex align-items-center mb-3">
                                <span class="rounded-circle bg-success bg-opacity-10 p-2 me-2">
                                    <i class="fas fa-file-alt text-success"></i>
                                </span>
                                Descripción del Trabajo
                            </h6>
                            <textarea class="form-control validate" name="job_description" rows="5" placeholder="Describe el trabajo a realizar...">{{ old('job_description') }}</textarea>
                        </div>
                    </div>

                    <!-- Card Archivos -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body">
                            <h6 class="card-title d-flex align-items-center mb-3">
                                <span class="rounded-circle bg-warning bg-opacity-10 p-2 me-2">
                                    <i class="fas fa-paperclip text-warning"></i>
                                </span>
                                Cargar Archivos / Imágenes
                            </h6>
                            <div class="mb-2">
                                <div style="position: relative;padding: 0;">
                                    <input class="form-control form-control-sm" type="file" name="images[]" accept="video/*,image/*" onchange="scaleImage(this,'lightgallery');">
                                    <span class="btn-danger-pro" style="position: absolute; height: 100%; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-pack: center;-ms-flex-pack: center;justify-content: center;top: 4px;right: 10px; " onclick="this.parentNode.children[0].value='';scaleImage(this.parentNode.children[0],'lightgallery');">
                                        <span><i class="fas fa-trash"></i></span>
                                    </span>
                                </div>
                            </div>
                            <div id="lightgallery" class="row justify-content-start">
                                
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-success rounded-pill px-4" id="btn-create-job">
                    <i class="fas fa-check me-1"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>