{{-- DISEÑO HÍBRIDA - TABS + ESTILO TARJETAS MODERNAS --}}
<div class="modal fade" id="showjob" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border: none; border-radius: 20px;">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0;">
                <h5 class="modal-title text-white fw-bold">
                    <i class="fas fa-tasks me-2"></i>Detalles de la Tarea
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            {{-- Estados de carga y error --}}
            <div class="modal-body d-none" id="modal-body-show-job-error">
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3 me-2"></i>
                    <h5 class="text-muted">Error al obtener la información. Por favor reintentelo o comuníquese con Soporte</h5>
                </div>
            </div>
            
            <div class="modal-body" id="modal-body-show-job-roller">
                <div class="text-center py-5">
                    <div class="lds-roller"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
                </div>
            </div>
            
            {{-- Contenido principal --}}
            <div class="modal-body p-0" id="modal-body-show-job">
                <form id="formshowjob">
                    {{-- Tabs de navegación --}}
                    <ul class="nav nav-tabs px-4 pt-3 bg-light" id="jobTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-content" type="button">
                                <i class="fas fa-info-circle me-2"></i>Información General
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="location-tab" data-bs-toggle="tab" data-bs-target="#location-content" type="button">
                                <i class="fas fa-map-marker-alt me-2"></i>Ubicaciones
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="images-tab" data-bs-toggle="tab" data-bs-target="#images-content" type="button">
                                <i class="fas fa-images me-2"></i>Imágenes
                            </button>
                        </li>
                    </ul>

                    {{-- Contenido de los tabs --}}
                    <div class="tab-content p-4 bg-light" id="jobTabsContent">
                        {{-- Tab 1: Información General --}}
                        <div class="tab-pane fade show active" id="info-content" role="tabpanel">
                            <div class="row g-3">
                                {{-- Tarjeta: Información del Cliente --}}
                                <div class="col-md-6">
                                    <div class="card h-100 border-0 shadow-sm" style="border-radius: 15px;">
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
                                                <label class="text-muted small mb-1">Cliente</label>
                                                <input type="text" class="form-control border-0 bg-light" name="client_name" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label class="text-muted small mb-1">Domicilio</label>
                                                <input type="text" class="form-control border-0 bg-light" name="client_addres_name" readonly>
                                            </div>
                                            <div>
                                                <label class="text-muted small mb-1">Fecha y hora de visita</label>
                                                <input type="text" class="form-control border-0 bg-light" name="visit_datetime" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Tarjeta: Descripción del Trabajo --}}
                                <div class="col-md-6">
                                    <div class="card h-100 border-0 shadow-sm" style="border-radius: 15px;">
                                        <div class="card-header bg-white border-0 pt-3">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-success bg-opacity-10 p-2 me-3">
                                                    <i class="fas fa-clipboard-list fa-lg text-success me-2"></i>
                                                </div>
                                                <h6 class="mb-0 fw-bold">Descripción del Trabajo</h6>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <textarea class="form-control border-0 bg-light" name="job_description" rows="9" readonly style="resize: none;"></textarea>
                                        </div>
                                    </div>
                                </div>

                                {{-- Tarjeta: Productos Relacionados --}}
                                <div class="col-12" id="products_show_container" style="display: none;">
                                    <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                                        <div class="card-header bg-white border-0 pt-3">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-3">
                                                    <i class="fas fa-box fa-lg text-primary me-2"></i>
                                                </div>
                                                <h6 class="mb-0 fw-bold">Productos Relacionados</h6>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div id="products_show_body" class="table-responsive">
                                                <table class="table table-sm table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Código</th>
                                                            <th>Descripción</th>
                                                            <th>Tipo de Unidad</th>
                                                            <th class="text-end">Cantidad</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="products_show_tbody">
                                                        {{-- llenado vía JS --}}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Tarjeta: Técnicos asignados --}}
                                <div class="col-md-6">
                                    <div class="card h-100 border-0 shadow-sm" style="border-radius: 15px;">
                                        <div class="card-header bg-white border-0 pt-3">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-warning bg-opacity-10 p-2 me-3">
                                                    <i class="fas fa-comment-alt fa-lg text-warning me-2"></i>
                                                </div>
                                                <h6 class="mb-0 fw-bold">Técnicos asignados</h6>
                                            </div>
                                        </div>
                                        <div class="card-body pt-1">
                                            {{-- Técnicos asignados (solo visible si hay técnicos) --}}
                                            <div id="technicians_show_container" class="d-none mb-2 pb-2" style="border-bottom: 1px dashed #dee2e6;">
                                                <label class="text-muted small mb-2">
                                                    <i class="fas fa-hard-hat me-2 text-secondary"></i>Técnicos asignados
                                                </label>
                                                <div id="technicians_show_body" class="d-flex flex-wrap gap-2">
                                                    {{-- llenado vía JS --}}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Tarjeta: Registro de Tiempos --}}
                                <div class="col-md-6">
                                    <div class="card h-100 border-0 shadow-sm" style="border-radius: 15px;">
                                        <div class="card-header bg-white border-0 pt-3">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-info bg-opacity-10 p-2 me-3">
                                                    <i class="fas fa-clock fa-lg text-info me-2"></i>
                                                </div>
                                                <h6 class="mb-0 fw-bold">Registro de Tiempos</h6>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="text-muted small mb-1">
                                                    <i class="fas fa-sign-in-alt me-2 text-success"></i>Fecha y hora de arribo
                                                </label>
                                                <input type="text" class="form-control border-0 bg-light" name="arrival_datetime" readonly>
                                            </div>
                                            <div>
                                                <label class="text-muted small mb-1">
                                                    <i class="fas fa-sign-out-alt me-2 text-danger"></i>Fecha y hora de cierre
                                                </label>
                                                <input type="text" class="form-control border-0 bg-light" name="closed_datetime" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tab 2: Ubicaciones --}}
                        <div class="tab-pane fade" id="location-content" role="tabpanel">
                            <div class="row g-3">
                                {{-- Tarjeta: Ubicación de Arribo --}}
                                <div class="col-md-6 arrival_coords_title d-none">
                                    <div class="card h-100 border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
                                        <div class="card-header bg-white border-0 pt-3">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-success bg-opacity-10 p-2 me-3">
                                                    <i class="fas fa-map-marker-alt fa-lg text-success me-2"></i>
                                                </div>
                                                <h6 class="mb-0 fw-bold">Ubicación de Arribo</h6>
                                            </div>
                                        </div>
                                        <div class="card-body p-0">
                                            <iframe class="arrival_coords d-none" 
                                                src="https://www.google.com/maps/embed/v1/place?key={{Session::get('user.google_api_key')}}&q=-32.9515008,-60.6430357" 
                                                width="100%" 
                                                height="400" 
                                                style="border:0;" 
                                                allowfullscreen="" 
                                                loading="lazy"
                                                referrerpolicy="no-referrer-when-downgrade">
                                            </iframe>
                                        </div>
                                    </div>
                                </div>

                                {{-- Tarjeta: Ubicación de Cierre --}}
                                <div class="col-md-6 closed_coords_title d-none">
                                    <div class="card h-100 border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
                                        <div class="card-header bg-white border-0 pt-3">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-dark bg-opacity-10 p-2 me-3">
                                                    <i class="fas fa-map-marker-alt fa-lg text-dark me-2"></i>
                                                </div>
                                                <h6 class="mb-0 fw-bold">Ubicación de Cierre</h6>
                                            </div>
                                        </div>
                                        <div class="card-body p-0">
                                            <iframe class="closed_coords d-none" 
                                                src="https://www.google.com/maps/embed/v1/place?key={{Session::get('user.google_api_key')}}&q=-32.9515008,-60.6430357" 
                                                width="100%" 
                                                height="400" 
                                                style="border:0;" 
                                                allowfullscreen="" 
                                                loading="lazy"
                                                referrerpolicy="no-referrer-when-downgrade">
                                            </iframe>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tab 3: Imágenes --}}
                        <div class="tab-pane fade" id="images-content" role="tabpanel">
                            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                                <div class="card-header bg-white border-0 pt-3">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-danger bg-opacity-10 p-2 me-3">
                                            <i class="fas fa-images fa-lg text-danger me-2"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold">Galería de Imágenes</h6>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="lightgalleryShow" class="row g-3"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            {{-- Footer con botón de generar PDF --}}
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btn-generate-pdf" onclick="openPdfConfigModal()" style="display: none;">
                    <i class="fas fa-file-pdf me-2"></i>Generar PDF
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal de Configuración de PDF --}}
<div class="modal fade" id="pdfConfigModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border: none; border-radius: 20px;">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 20px 20px 0 0;">
                <h5 class="modal-title text-white fw-bold">
                    <i class="fas fa-cog me-2"></i>Configurar PDF
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <form id="pdfConfigForm">
                    {{-- Información General --}}
                    <div class="card border-0 shadow-sm mb-3" style="border-radius: 15px;">
                        <div class="card-header bg-white border-0 pt-3">
                            <h6 class="mb-0 fw-bold text-primary">
                                <i class="fas fa-info-circle me-2"></i>Información General
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="include_description" checked>
                                <label class="form-check-label" for="include_description">
                                    Incluir descripción del trabajo
                                </label>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="include_products" checked>
                                <label class="form-check-label" for="include_products">
                                    Incluir productos relacionados
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="include_technicians" checked>
                                <label class="form-check-label" for="include_technicians">
                                    Incluir técnicos asignados
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Registro de Tiempos --}}
                    <div class="card border-0 shadow-sm mb-3" style="border-radius: 15px;">
                        <div class="card-header bg-white border-0 pt-3">
                            <h6 class="mb-0 fw-bold text-success">
                                <i class="fas fa-clock me-2"></i>Registro de Tiempos
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="include_arrival_time" checked>
                                <label class="form-check-label" for="include_arrival_time">
                                    Fecha y hora de llegada
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="include_departure_time" checked>
                                <label class="form-check-label" for="include_departure_time">
                                    Fecha y hora de salida
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Notas --}}
                    <div class="card border-0 shadow-sm mb-3" style="border-radius: 15px;">
                        <div class="card-header bg-white border-0 pt-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold text-info">
                                    <i class="fas fa-sticky-note me-2"></i>Notas
                                </h6>
                                <button type="button" class="btn btn-sm btn-link" onclick="toggleAllNotes()">
                                    Seleccionar todas
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="include_notes" checked onchange="toggleNotesSection()">
                                <label class="form-check-label" for="include_notes">
                                    <strong>Incluir notas</strong>
                                </label>
                            </div>
                            <div id="notes-selection" class="ps-3">
                                <!-- Se llenará dinámicamente con JavaScript -->
                            </div>
                        </div>
                    </div>

                    {{-- Imágenes --}}
                    <div class="card border-0 shadow-sm mb-3" style="border-radius: 15px;">
                        <div class="card-header bg-white border-0 pt-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold text-danger">
                                    <i class="fas fa-images me-2"></i>Imágenes
                                </h6>
                                <button type="button" class="btn btn-sm btn-link" onclick="toggleAllImages()">
                                    Seleccionar todas
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="include_images" checked onchange="toggleImagesSection()">
                                <label class="form-check-label" for="include_images">
                                    <strong>Incluir imágenes</strong>
                                </label>
                            </div>
                            <div id="images-selection" class="row g-2">
                                <!-- Se llenará dinámicamente con JavaScript -->
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="generatePDF('view')">
                    <i class="fas fa-eye me-2"></i>Ver PDF
                </button>
                <button type="button" class="btn btn-success" onclick="generatePDF('download')">
                    <i class="fas fa-file-download me-2"></i>Descargar PDF
                </button>
            </div>
        </div>
    </div>
</div>
