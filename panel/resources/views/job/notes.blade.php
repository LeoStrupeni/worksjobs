<div class="modal fade" id="viewjobsnotes" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" style="min-width: 80%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="titlenotas"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-none" id="modal-body-view-jobsnotes-error">
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
            <div class="modal-body" id="modal-body-view-jobsnotes-roller">
                <div style="display:block;" class="text-center">
                    <br>
                    <br>
                    <div class="lds-roller"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
                    <br>
                    <br>
                    <br>
                </div>
            </div>
            <div class="modal-body d-none" id="modal-body-view-jobsnotes">
                <table class="table table-bordered table-hover text-center sortable" id="tablenotes">
                    <thead>
                        <tr>
                            <th class="column_orden">Nota</th>
                            <th class="column_orden">Fecha</th>
                            <th class="sorttable_nosort" style="width:3%;"></th>
                        </tr>
                    </thead>
                    <tbody id="tablenotes_body">

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>