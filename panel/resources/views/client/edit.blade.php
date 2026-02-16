<div class="modal fade" id="editclient" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border: none; border-radius: 20px;">
            <div class="modal-header text-white border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white fw-bold">
                    <i class="fas fa-edit me-2"></i>Editar Cliente
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-none bg-light" id="modal-body-edit-client-error">
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3 me-2"></i>
                    <h5 class="text-muted">Error al obtener la información. Por favor reintentelo o comuníquese con Soporte</h5>
                </div>
            </div>
            <div class="modal-body bg-light" id="modal-body-edit-client-roller">
                <div class="text-center py-5">
                    <div class="lds-roller"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
                </div>
            </div>
            <div class="modal-body bg-light d-none" id="modal-body-edit-client">
                <form action="" method="POST" id="formeditclient">
                    @csrf
                    @method("PUT")
                    <div class="row">
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="first_name" class="form-label mb-0 ps-3 fw-bold">Nombre / Razón Social</label>
                                <input type="text" class="form-control validate" name="first_name" required value="{{ old('first_name') }}">
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="last_name" class="form-label mb-0 ps-3 fw-bold">Apellido</label>
                                <input type="text" class="form-control" name="last_name" value="{{ old('last_name') }}">
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="type_doc" class="form-label mb-0 ps-3 fw-bold">Tipo Doc</label>
                                <select class="form-control validate" name="type_doc" style="width: 100%" required>
                                    <option value="1" selected>Dni</option>
                                    <option value="2">Cuil</option>
                                    <option value="3">Cuit</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="num_doc" class="form-label mb-0 ps-3 fw-bold">Num Doc</label>
                                <input type="text" class="form-control validate" name="num_doc" required value="{{ old('num_doc') }}">
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="email" class="form-label mb-0 ps-3 fw-bold">E-mail</label>
                                <input type="text" class="form-control" name="email" value="{{ old('email') }}">
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="phone1" class="form-label mb-0 ps-3 fw-bold">Teléfono 1</label>
                                <input type="text" class="form-control validate" name="phone1" value="{{ old('phone1') }}">
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="phone2" class="form-label mb-0 ps-3 fw-bold">Teléfono 2</label>
                                <input type="text" class="form-control" name="phone2" value="{{ old('phone2') }}">
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="country" class="form-label mb-0 ps-3 fw-bold">País</label>
                                <select class="form-control" name="country" style="width: 100%">
                                    @isset($countries)
                                        @foreach ($countries as $c)
                                            <option value="{{$c->country}}"@if ($c->country == 'Argentina') selected @endif>{{$c->country}}</option>
                                        @endforeach   
                                    @endisset
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="state" class="form-label mb-0 ps-3 fw-bold">Provincia</label>
                                <input type="text" class="form-control" name="state" value="{{ old('state') }}">
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="cp" class="form-label mb-0 ps-3 fw-bold">CP</label>
                                <input type="text" class="form-control" name="cp" value="{{ old('cp') }}">
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="city" class="form-label mb-0 ps-3 fw-bold">Ciudad</label>
                                <input type="text" class="form-control" name="city" value="{{ old('city') }}">
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="address_street" class="form-label mb-0 ps-3 fw-bold">Calle</label>
                                <input type="text" class="form-control" name="address_street" value="{{ old('address_street') }}">
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="address_nro" class="form-label mb-0 ps-3 fw-bold">Nro</label>
                                <input type="text" class="form-control" name="address_nro" value="{{ old('address_nro') }}">
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="address_apartament" class="form-label mb-0 ps-3 fw-bold">Piso / Dpto</label>
                                <input type="text" class="form-control" name="address_apartament" value="{{ old('address_apartament') }}">
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="address_detail" class="form-label mb-0 ps-3 fw-bold">Descripcion</label>
                                <input type="text" class="form-control" name="address_detail" value="{{ old('address_detail') }}">
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="mb-2">
                                <label for="other_obs" class="form-label mb-0 ps-3 fw-bold">Observaciones</label>
                                <input type="text" class="form-control" name="other_obs" value="{{ old('other_obs') }}">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-0 d-none" id="modal-footer-edit-client">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-success rounded-pill px-4" id="btn-update-client">
                    <i class="fas fa-check me-2"></i>Actualizar
                </button>
            </div>
        </div>
    </div>
</div>