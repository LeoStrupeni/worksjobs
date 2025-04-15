<div class="modal fade" id="showrolusers" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="min-width: 90%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Ver Usuarios</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-none" id="modal-body-show-rolusers-error">
                <div style="display:block;" class="text-center">
                    <br>
                    <br>
                    <div class="alert alert-info m-0 justify-content-center" role="alert">
                        <h5 class="m-0">Error al obtener la informacion. Por favor reintentelo o comuniquese con Soporte</h5>
                    </div>
                    <br>
                    <br>
                </div>
            </div>
            <div class="modal-body d-none" id="modal-body-show-rolusers-sindatos">
                <div style="display:block;" class="text-center">
                    <br>
                    <br>
                    <div class="alert alert-info m-0 justify-content-center" role="alert">
                        <h5 class="m-0">No hay usuarios con este Rol</h5>
                    </div>
                    <br>
                    <br>
                </div>
            </div>
            <div class="modal-body" id="modal-body-show-rolusers-roller">
                <div style="display:block;" class="text-center">
                    <br>
                    <br>
                    <div class="lds-roller"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
                    <br>
                    <br>
                    <br>
                </div>
            </div>
            <div class="modal-body d-none" id="modal-body-show-rolusers">
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