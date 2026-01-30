<div class="modal fade" id="viewjobsnotes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border: none; border-radius: 20px;">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); border-radius: 20px 20px 0 0;">
                <h5 class="modal-title text-white fw-bold" id="titlenotas">
                    <i class="fas fa-sticky-note me-2"></i>Notas de la Tarea
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-none" id="modal-body-view-jobsnotes-error">
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h5 class="text-muted">Error al obtener la información. Por favor reintentelo o comuníquese con Soporte</h5>
                </div>
            </div>
            <div class="modal-body" id="modal-body-view-jobsnotes-roller">
                <div class="text-center py-5">
                    <div class="lds-roller"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
                </div>
            </div>
            <div class="modal-body bg-light d-none" id="modal-body-view-jobsnotes">
                <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                    <div class="card-header bg-white border-0 pt-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-warning bg-opacity-10 p-2 me-3">
                                <i class="fas fa-list fa-lg text-warning"></i>
                            </div>
                            <h6 class="mb-0 fw-bold">Lista de Notas</h6>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover text-center sortable" id="tablenotes">
                                <thead class="table-light">
                                    <tr>
                                        <th class="column_orden">Nota</th>
                                        <th class="column_orden">Fecha</th>
                                        <th class="sorttable_nosort" style="width:3%;"></th>
                                    </tr>
                                </thead>
                                <tbody id="tablenotes_body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>