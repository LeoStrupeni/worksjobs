<div class="modal fade" id="addressclient" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="titleaddressclient">Domicilios</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if (in_array('create',Session::get('user')['permissions']['clients']))
                <div class="accordion mb-3" id="accordionNewaddress">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed bg-info" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNewaddress" aria-expanded="false" aria-controls="collapseNewaddress">
                                Agregar nuevo domicilio al cliente
                            </button>
                        </h2>
                        <div id="collapseNewaddress" class="accordion-collapse collapse" data-bs-parent="#accordionNewaddress">
                            <div class="accordion-body">
                                <form action="" method="POST" id="formnewaddressclient">
                                    @csrf
                                    <input type="hidden" class="validate" name="client_id" id="client_id">
                                    <div class="row">
                                        <div class="col-12 col-lg-6">
                                            <div class="mb-2">
                                                <label for="country" class="form-label mb-0 ps-3 fw-bold">País</label>
                                                <select class="form-control" name="country" style="width: 100%">
                                                    @isset($countries)
                                                        @foreach ($countries as $c)
                                                            <option value="{{$c->country}}" @if ($c->country == 'Argentina') selected @endif>{{$c->country}}</option>
                                                        @endforeach   
                                                    @endisset
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-6">
                                            <div class="mb-2">
                                                <label for="state" class="form-label mb-0 ps-3 fw-bold">Provincia</label>
                                                <input type="text" class="form-control validate" name="state" value="{{ old('state') }}">
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-6">
                                            <div class="mb-2">
                                                <label for="cp" class="form-label mb-0 ps-3 fw-bold">CP</label>
                                                <input type="text" class="form-control validate" name="cp" value="{{ old('cp') }}">
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-6">
                                            <div class="mb-2">
                                                <label for="city" class="form-label mb-0 ps-3 fw-bold">Ciudad</label>
                                                <input type="text" class="form-control validate" name="city" value="{{ old('city') }}">
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-6">
                                            <div class="mb-2">
                                                <label for="address_street" class="form-label mb-0 ps-3 fw-bold">Calle</label>
                                                <input type="text" class="form-control validate" name="address_street" value="{{ old('address_street') }}">
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
                                                <label for="address_detail" class="form-label mb-0 ps-3 fw-bold">Detalles Domicilio</label>
                                                <input type="text" class="form-control" name="address_detail" value="{{ old('address_detail') }}">
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="d-grid gap-2">
                                                <button type="button" class="btn btn-info" id="btn-create-addressclient">Guardar</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-sm table-hover text-center sortable" id="table">
                        <thead>
                            <tr class="table-primary">
                                <th class="column_orden" data-name="country">Pais</th>
                                <th class="column_orden" data-name="state">Provincia</th>
                                <th class="column_orden" data-name="cp">Cp</th>
                                <th class="column_orden" data-name="city">Ciudad</th>
                                <th class="column_orden" data-name="address_street">Calle</th>
                                <th class="column_orden" data-name="address_nro">Nro</th>
                                <th class="column_orden" data-name="address_apartament">Piso / Dpto</th>
                                <th class="column_orden" data-name="address_detail">Detalles</th>
                                <th class="sorttableaddress_nosort" style="width:3%;"></th>
                            </tr>
                        </thead>
                        <tbody id="tableaddress_body">
                        </tbody>
                        <tbody id="tableaddress_roller">
                            <tr>
                                <td colspan="9">
                                    <div style="display:block;" class="text-center">
                                        <br>
                                        <br>
                                        <div class="lds-roller"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
                                        <br>
                                        <br>
                                        <br>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tbody id="tableaddress_error" class="d-none">
                            <tr>
                                <td colspan="9">
                                    <div style="display:block;" class="text-center">
                                        <br>
                                        <br>
                                        <div class="alert alert-info m-0 justify-content-center" role="alert">
                                            <h5 class="m-0">Error al obtener la informacion. Por favor reintentelo o comuniquese con Soporte</h5>
                                        </div>
                                        <br>
                                        <br>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tbody id="tableaddress_sindatos" class="d-none">
                            <tr>
                                <td colspan="9">
                                    <div style="display:block;" class="text-center">
                                        <br>
                                        <br>
                                        <div class="alert alert-warning m-0 justify-content-center" role="alert">
                                            <h5 class="m-0">No se encuentra registros con los filtros aplicados</h5>
                                        </div>
                                        <br>
                                        <br>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>