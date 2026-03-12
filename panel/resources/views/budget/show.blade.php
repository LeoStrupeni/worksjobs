    <!-- Modal Detalle de Factura -->
    <div class="modal fade" id="modalDetalleFactura" tabindex="-1" aria-labelledby="modalDetalleFacturaLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h5 class="modal-title text-white" id="modalDetalleFacturaLabel">
                        <i class="fa-solid fa-file-invoice me-2"></i>Detalle de Presupuesto
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Loading spinner -->
                    <div id="detalleLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-3 text-muted">Obteniendo información de Colppy...</p>
                    </div>

                    <!-- Contenido del detalle -->
                    <div id="detalleContent" class="d-none">
                        <!-- Información general -->
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fa-solid fa-info-circle me-2"></i>Información General</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <strong>Nro. Factura:</strong> <span id="detalle-nroFactura"></span>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <strong>Fecha:</strong> <span id="detalle-fechaFactura"></span>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <strong>Tipo Comprobante:</strong> <span id="detalle-tipoComprobante"></span>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <strong>Estado:</strong> <span id="detalle-estado" class="badge"></span>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <strong>Condición de Pago:</strong> <span id="detalle-condicionPago"></span>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <strong>Fecha de Pago:</strong> <span id="detalle-fechaPago"></span>
                                    </div>
                                    <div class="col-12 mb-2">
                                        <strong>Descripción:</strong> <span id="detalle-descripcion"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Items de la factura -->
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fa-solid fa-list me-2"></i>Items de la Factura</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Descripción</th>
                                                <th class="text-center">Cantidad</th>
                                                <th class="text-end">Precio Unit.</th>
                                                <th class="text-center">IVA %</th>
                                                <th class="text-end">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody id="detalle-items">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Totales -->
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fa-solid fa-calculator me-2"></i>Totales</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-sm table-borderless">
                                            <tr>
                                                <td><strong>Neto Gravado:</strong></td>
                                                <td class="text-end" id="detalle-netoGravado"></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Neto No Gravado:</strong></td>
                                                <td class="text-end" id="detalle-netoNoGravado"></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Total IVA:</strong></td>
                                                <td class="text-end" id="detalle-totalIVA"></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="alert alert-success mb-0">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong class="fs-5">Total Factura:</strong>
                                                <span class="fs-4 fw-bold" id="detalle-totalFactura"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Error -->
                    <div id="detalleError" class="d-none text-center py-5">
                        <i class="fa-solid fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                        <p class="text-muted" id="detalleErrorMsg"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" id="detalle-pdfLink" class="btn btn-primary me-auto d-none" target="_blank">
                        <i class="fa-solid fa-file-pdf me-2"></i>Ver PDF
                    </a>
                    <div class="flex-grow-1">
                        <button type="button" class="btn btn-success" id="modal-generarTarea">
                            <i class="flaticon-add me-2"></i>Generar Tarea
                        </button>
                        <button type="button" class="btn btn-info text-white" id="modal-asociarTarea">
                            <i class="flaticon-share me-2"></i>Asociar a Tarea
                        </button>
                        <button type="button" class="btn btn-warning d-none" id="modal-verTareas">
                            <i class="fas fa-tasks me-2"></i>Ver Tareas Asociadas (<span id="modal-cantidadTareas">0</span>)
                        </button>
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>