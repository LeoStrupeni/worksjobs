<div class="modal fade" id="closedjob" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border: none; border-radius: 20px;">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border-radius: 20px 20px 0 0;">
                <h5 class="modal-title text-white fw-bold" id="titleclosedjob">
                    <i class="fas fa-check-circle me-2"></i>Cerrar Tarea
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-none" id="modal-body-closed-job-error">
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3 me-2"></i>
                    <h5 class="text-muted">Error al obtener la información. Por favor reintentelo o comuníquese con Soporte</h5>
                </div>
            </div>
            <div class="modal-body" id="modal-body-closed-job-roller">
                <div class="text-center py-5">
                    <div class="lds-roller"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
                </div>
            </div>
            <div class="modal-body bg-light" id="modal-body-closed-job">
                <form action="{{route('job.closed')}}" method="POST" id="formclosedjob" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="latitude">
                    <input type="hidden" name="longitude">
                    <input type="hidden" name="jsongeolocation">
                    <input type="hidden" name="client_id">
                    <input type="hidden" name="id">
                    
                    <!-- Indicador de Geolocalización -->
                    {{-- <div class="alert alert-success mb-3 d-flex align-items-center">
                        <i class="fas fa-map-marker-alt fa-2x me-2"></i>
                        <div class="flex-grow-1">
                            <strong>Registro de Ubicación GPS de Cierre</strong>
                            <p class="mb-0 small">Se guardará la ubicación GPS desde donde se cierra esta tarea.</p>
                        </div>
                    </div> --}}
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                                <div class="card-header bg-white border-0 pt-3">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-3">
                                            <i class="fas fa-map-marker-alt fa-lg text-primary me-2"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold">Información de la Tarea</h6>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="text-muted small mb-1">Domicilio</label>
                                        <input type="text" class="form-control border-0 bg-light" name="client_addres_name" readonly>
                                    </div>
                                    <div>
                                        <label class="text-muted small mb-1">Descripción de trabajo</label>
                                        <textarea class="form-control border-0 bg-light" name="job_description" rows="5" readonly style="resize: none;"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                                <div class="card-header bg-white border-0 pt-3">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-success bg-opacity-10 p-2 me-3">
                                            <i class="fas fa-comment-alt fa-lg text-success me-2"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold">Observaciones de Cierre</h6>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <textarea class="form-control bg-white validate" name="closed_job_observation" rows="5" style="resize: none;"></textarea>
                                    
                                    {{-- Técnicos asignados (Obligatorio) --}}
                                    <div class="mt-3 pt-3" style="border-top: 1px dashed #dee2e6;">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-hard-hat me-2 text-secondary"></i>Técnicos <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-control selectpicker" name="technician_ids[]" id="technician_ids_closed"
                                            title="Seleccione al menos un técnico..." multiple
                                            data-selected-text-format="count > 1"
                                            data-count-selected-text="{0} técnico(s)">
                                            @foreach(Session::get('users') as $u)
                                                <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <div id="technician_ids_closed_error" class="text-danger small mt-1 d-none">
                                            <i class="fas fa-exclamation-circle me-1"></i>Debe seleccionar al menos un técnico.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                                <div class="card-header bg-white border-0 pt-3">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-danger bg-opacity-10 p-2 me-3">
                                            <i class="fas fa-images fa-lg text-danger me-2"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold">Cargar Archivos / Imágenes</h6>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div style="position: relative;padding: 0;">
                                        <input class="form-control" type="file" name="images[]" accept="video/*,image/*" onchange="scaleImage(this,'lightgalleryClosed');">
                                        <span class="btn-danger-pro" style="position: absolute; height: 100%; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-pack: center;-ms-flex-pack: center;justify-content: center;top: 4px;right: 10px; " onclick="this.parentNode.children[0].value='';scaleImage(this.parentNode.children[0],'lightgalleryClosed');">
                                            <span><i class="fas fa-trash me-2"></i></span>
                                        </span>
                                    </div>
                                    <div id="lightgalleryClosedNone" class="d-none"></div>
                                    <div id="lightgalleryClosed" class="row g-3 mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 bg-light" id="modal-foot-closed-job">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn-closed-job">Guardar</button>
            </div>
        </div>
    </div>
</div>