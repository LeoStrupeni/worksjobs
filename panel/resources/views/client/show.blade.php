<div class="modal fade" id="showclient" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border: none; border-radius: 20px;">
            <div class="modal-header text-white border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white fw-bold">
                    <i class="fas fa-eye me-2"></i>Ver Cliente
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-none bg-light" id="modal-body-show-client-error">
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3 me-2"></i>
                    <h5 class="text-muted">Error al obtener la información. Por favor reintentelo o comuníquese con Soporte</h5>
                </div>
            </div>
            <div class="modal-body bg-light" id="modal-body-show-client-roller">
                <div class="text-center py-5">
                    <div class="lds-roller"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
                </div>
            </div>
            <div class="modal-body bg-light d-none" id="modal-body-show-client">
                <form id="formshowclient">
                    <div class="row">
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="first_name" class="form-label mb-0 ps-3 fw-bold">Nombre / Razón Social</label>
                                <input type="text" class="form-control" name="first_name" readonly>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="last_name" class="form-label mb-0 ps-3 fw-bold">Apellido</label>
                                <input type="text" class="form-control" name="last_name" readonly>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="type_doc" class="form-label mb-0 ps-3 fw-bold">Tipo Doc</label>
                                <input type="text" class="form-control" name="type_doc" readonly>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="num_doc" class="form-label mb-0 ps-3 fw-bold">Num Doc</label>
                                <input type="text" class="form-control" name="num_doc" readonly>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="email" class="form-label mb-0 ps-3 fw-bold">E-mail</label>
                                <input type="text" class="form-control" name="email" readonly>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="phone1" class="form-label mb-0 ps-3 fw-bold">Teléfono 1</label>
                                <input type="text" class="form-control" name="phone1" readonly>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="phone2" class="form-label mb-0 ps-3 fw-bold">Teléfono 2</label>
                                <input type="text" class="form-control" name="phone2" readonly>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="country" class="form-label mb-0 ps-3 fw-bold">País</label>
                                <input type="text" class="form-control" name="country" readonly>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="state" class="form-label mb-0 ps-3 fw-bold">Provincia</label>
                                <input type="text" class="form-control" name="state" readonly>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="cp" class="form-label mb-0 ps-3 fw-bold">CP</label>
                                <input type="text" class="form-control" name="cp" readonly>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="city" class="form-label mb-0 ps-3 fw-bold">Ciudad</label>
                                <input type="text" class="form-control" name="city" readonly>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="address_street" class="form-label mb-0 ps-3 fw-bold">Calle</label>
                                <input type="text" class="form-control" name="address_street" readonly>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="address_nro" class="form-label mb-0 ps-3 fw-bold">Nro</label>
                                <input type="text" class="form-control" name="address_nro" readonly>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="address_apartament" class="form-label mb-0 ps-3 fw-bold">Piso / Dpto</label>
                                <input type="text" class="form-control" name="address_apartament" readonly>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="address_detail" class="form-label mb-0 ps-3 fw-bold">Descripcion</label>
                                <input type="text" class="form-control" name="address_detail" readonly>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="other_obs" class="form-label mb-0 ps-3 fw-bold">Observaciones</label>
                                <input type="text" class="form-control" name="other_obs" readonly>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>