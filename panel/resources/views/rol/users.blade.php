<div class="modal fade" id="showrolusers" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="min-width: 90%;">
        <div class="modal-content" style="border: none; border-radius: 20px;">
            <div class="modal-header text-white border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px 20px 0 0;">
                <h5 class="modal-title fw-bold" id="exampleModalLabel">
                    <i class="fas fa-users me-2"></i>Ver Usuarios
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-none bg-light" id="modal-body-show-rolusers-error">
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3 me-2"></i>
                    <h5 class="text-muted">Error al obtener la información. Por favor reintentelo o comuníquese con Soporte</h5>
                </div>
            </div>
            <div class="modal-body d-none bg-light" id="modal-body-show-rolusers-sindatos">
                <div class="text-center py-5">
                    <i class="fas fa-info-circle fa-3x text-info mb-3 me-2"></i>
                    <h5 class="text-muted">No hay usuarios con este Rol</h5>
                </div>
            </div>
            <div class="modal-body bg-light" id="modal-body-show-rolusers-roller">
                <div class="text-center py-5">
                    <div class="lds-roller"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
                </div>
            </div>
            <div class="modal-body d-none bg-light" id="modal-body-show-rolusers">
                <div class="table-responsive">
                    <table class="table table-sm table-hover text-center sortable">
                        <thead>
                            <tr>
                                <th>Imagen</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody id="table_bodyusers">

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>