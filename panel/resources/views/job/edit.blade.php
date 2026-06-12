<div class="modal fade" id="editjob" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border: none; border-radius: 20px;">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0;">
                <h5 class="modal-title text-white fw-bold">
                    <i class="fas fa-edit me-2"></i>Editar Tarea
                </h5>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button type="button" class="btn btn-sm btn-success rounded-pill px-3" onclick="syncProductsManual()" title="Sincronizar productos desde Colppy">
                        <i class="fa-solid fa-sync me-1"></i>Sincronizar Productos
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body d-none" id="modal-body-edit-job-error">
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3 me-2"></i>
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
                    
                    <!-- Indicador de Geolocalización -->
                    {{-- <div class="alert alert-warning mb-3 d-flex align-items-center">
                        <i class="fas fa-map-marker-alt fa-2x me-2"></i>
                        <div class="flex-grow-1">
                            <strong>Actualización de Ubicación GPS</strong>
                            <p class="mb-0 small">Al editar la tarea, se actualizará la ubicación GPS desde donde se realiza esta modificación.</p>
                        </div>
                    </div> --}}
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100" style="border-radius: 15px;">
                                <div class="card-header bg-white border-0 pt-3">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-3">
                                            <i class="fas fa-user fa-lg text-primary me-2"></i>
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
                                            <i class="fas fa-calendar-alt fa-lg text-info me-2"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold">Fecha, Hora y Técnicos</h6>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <label for="visit_datetime_edit" class="form-label text-muted small mb-1">Fecha y hora de visita</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                        <input type="text" class="form-control validate" name="visit_datetime" id="visit_datetime_edit" value="{{ old('visit_datetime') }}" placeholder="dd/mm/yyyy HH:mm" required autocomplete="off">
                                    </div>

                                    <div class="mt-3">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-hard-hat me-1 text-secondary"></i>Técnicos asignados
                                        </label>
                                        <select class="form-control selectpicker" name="technician_ids[]" id="technician_ids_edit"
                                            title="Sin técnicos asignados" multiple
                                            data-selected-text-format="count > 1"
                                            data-count-selected-text="{0} técnico(s)">
                                            @foreach(Session::get('users') as $u)
                                                <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 d-none" id="job-times-permission-card">
                            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                                <div class="card-header bg-white border-0 pt-3">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-warning bg-opacity-10 p-2 me-3">
                                            <i class="fas fa-clock fa-lg text-warning me-2"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold">Edición Especial de Tiempos</h6>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="arrival_datetime_edit" class="form-label text-muted small mb-1">Fecha y hora de arribo</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-location-arrow"></i></span>
                                                <input type="text" class="form-control" name="arrival_datetime" id="arrival_datetime_edit" placeholder="dd/mm/yyyy HH:mm" autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="closed_datetime_edit" class="form-label text-muted small mb-1">Fecha y hora de cierre</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
                                                <input type="text" class="form-control" name="closed_datetime" id="closed_datetime_edit" placeholder="dd/mm/yyyy HH:mm" autocomplete="off">
                                            </div>
                                            <small class="text-muted d-none" id="closed_datetime_edit_help">
                                                Solo se puede editar si la tarea ya tiene cierre registrado.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                                <div class="card-header bg-white border-0 pt-3">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-success bg-opacity-10 p-2 me-3">
                                            <i class="fas fa-clipboard-list fa-lg text-success me-2"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold">Descripción del Trabajo</h6>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <textarea class="form-control validate" name="job_description" rows="5" placeholder="Escriba la descripción del trabajo...">{{ old('job_description') }}</textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Card Productos -->
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body">
                                <h6 class="card-title d-flex align-items-center mb-3">
                                    <span class="rounded-circle bg-primary bg-opacity-10 p-2 me-2">
                                        <i class="fas fa-box text-primary me-2"></i>
                                    </span>
                                    Productos Relacionados
                                </h6>
                                
                                <div class="row">
                                    <div class="col-md-5">
                                        <label for="product_id_edit" class="form-label fw-semibold">
                                            Producto
                                            <span id="spinner_product_edit" class="d-none">
                                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                            </span>
                                        </label>
                                        <select class="form-control selectpicker searchvar" 
                                            id="product_id_edit" 
                                            data-live-search="true" 
                                            data-size="4" 
                                            data-dropup-auto="false"
                                            data-none-selected-text="Seleccione un producto" 
                                            data-none-results-text="No hay resultados coincidentes">
                                            <option></option>
                                            @foreach (Session::get('products') as $p)
                                                <option value="{{$p->id}}">{{$p->codigo}} - {{$p->descripcion}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="unit_type_edit" class="form-label fw-semibold">Tipo de Unidad</label>
                                        <select class="form-control" id="unit_type_edit">
                                            <option value="Unidad">Unidad</option>
                                            <option value="Rollo">Rollo</option>
                                            <option value="Metros">Metros</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="quantity_edit" class="form-label fw-semibold">Cantidad</label>
                                        <input type="number" class="form-control" id="quantity_edit" min="0.01" step="0.01" value="1.00">
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="button" class="btn btn-primary w-100" onclick="addProductToJob('edit')">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Lista de productos agregados -->
                                <div id="products_list_edit">
                                    <!-- Los productos se agregarán aquí dinámicamente -->
                                </div>
                                
                                <!-- Productos ocultos para enviar en el formulario -->
                                <div id="products_hidden_edit"></div>
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
                                    <div id="file-input-container-edit" style="position: relative;padding: 0;">
                                        <input id="file-input-edit" class="form-control" type="file" name="images[]" accept="video/*,image/*" multiple onchange="scaleImage(this,'lightgalleryEdit');">
                                        <span class="btn-danger-pro" style="position: absolute; height: 100%; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-pack: center;-ms-flex-pack: center;justify-content: center;top: 4px;right: 10px;" onclick="this.parentNode.children[0].value='';scaleImage(this.parentNode.children[0],'lightgalleryEdit');">
                                            <span><i class="fas fa-trash me-2"></i></span>
                                        </span>
                                    </div>
                                    <button type="button" id="btn-add-more-files-edit" class="btn btn-outline-primary btn-sm mt-2" style="display: none;">
                                        <i class="fas fa-plus-circle me-1"></i>Agregar más archivos
                                    </button>
                                    <small id="file-input-help-edit" class="text-muted d-block mt-2">
                                        <i class="fas fa-info-circle me-1"></i>Puedes seleccionar múltiples archivos a la vez
                                    </small>
                                    <div id="files-counter-edit" class="mt-2" style="display: none; font-size: 0.85rem; color: #6c757d;">
                                        <i class="fas fa-images me-1"></i>
                                        <span id="files-count-edit">0</span> archivo(s) listo(s) para enviar
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