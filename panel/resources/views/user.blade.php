@extends('layout')

@section('link_by_page')

<link href="{{env('APP_URL')}}/assets/css/avatar.css" rel="stylesheet" type="text/css" />

@endsection
@section('style_by_page')
<style>
    .my-dropdown-toggle::after {
        content: none;
    }

    .active>.page-link, .page-link.active {
        background-color: var(--bs-green)!important;
        border-color: var(--bs-white)!important;
    }

    .page-link {
        background-color: var(--bs-teal)!important;
        border: var(--bs-pagination-border-width) solid var(--bs-white)!important;
        color: var(--bs-white)!important;
    }

</style>

@endsection

@section('Content')
    <div class="container-fluid">
        <div class="row justify-content-center my-4">
            <div class="col-12 col-lg-10">
                <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
                    <div class="card-header text-white border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div class="row align-items-center justify-content-between">
                            <div class="col">
                                <h5 class="mb-0 fw-bold d-flex align-items-center">
                                    <span class="rounded-circle bg-white bg-opacity-25 p-2 me-2">
                                        <i class="fa-solid fa-users"></i>
                                    </span>
                                    Listado de Usuarios
                                </h5>
                            </div>
                            <div class="col text-end">
                                <button type="button" class="btn btn-light btn-sm rounded-pill px-3 mx-1" onclick="callregister('/users/table',1,$('#table_limit').val(),$('#table_order').val(),'si')" title="Actualizar">
                                    <i class="fa-solid fa-arrows-rotate me-1"></i>Actualizar
                                </button>
                                @if (in_array('create',Session::get('user')['permissions']['users']))
                                    <button type="button" class="btn btn-light btn-sm rounded-pill px-3 mx-1 create" title="Nuevo usuario">
                                        <i class="fa-solid fa-plus me-1"></i>Nuevo
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-body">

                        @include('Layout.errors')

                        <div class="row my-3 align-items-center justify-content-between">
                            <div class="col-3 col-xl-1">
                                <select class="form-select" id="table_limit">
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                            <div class="col-7 col-lg-4">
                                <div class="w-100 float-end" style="position: relative;padding: 0;">
									<input type="text" class="form-control" placeholder="¿Qué buscas?" id="table_search">
									<span style="position: absolute; height: 100%; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-pack: center;-ms-flex-pack: center;justify-content: center;top: 7px;width: 3.2rem;right: 0;">
										<span><i class="flaticon2-search-1"></i></span>
									</span>
								</div>
                            </div>
                        </div>
                        
                        <div style="max-height: 55vh; overflow-y: auto; border-radius: 10px;">
                            <table class="table table-sm table-hover text-center sortable" id="table">
                                <thead style="position: sticky; top: 0; z-index: 10; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <tr class="text-white">
                                        <th class="sorttable_nosort" style="white-space: nowrap;">Imagen</th>
                                        <th class="column_orden" data-name="name" style="white-space: nowrap;">Nombre</th>
                                        <th class="column_orden" data-name="email" style="white-space: nowrap;">E-mail</th>
                                        <th class="column_orden" data-name="rol" style="white-space: nowrap;">Rol</th>
                                        <th class="column_orden" data-name="estatus" style="white-space: nowrap;">Estado</th>
                                        <th class="sorttable_nosort" style="width:3%; white-space: nowrap;">Acciones</th>
                                    </tr>
                                </thead>
                        <tbody id="table_body">

                        </tbody>
                        <tbody id="table_roller">
                            <tr>
                                <td colspan="5">
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

                        <tbody id="table_error" class="d-none">
                            <tr>
                                <td colspan="5">
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

                        <tbody id="table_sindatos" class="d-none">
                            <tr>
                                <td colspan="5">
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
                        <div class="row align-items-center mt-3">
                            <input type="hidden" id="table_order">
                            <input type="hidden" id="table_paginas">
                            <input type="hidden" id="table_filtrados">
                            <input type="hidden" id="table_totales">
                            <div class="col-lg-6" id="table_info">

                            </div>
                            <div class="col-lg-6" id="table_pagination">

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- @include('home.foot') --}}
    @include('user.create')
    {{-- @include('user.edit')  --}}{{-- INCLUIDO EN ARCHIVO AVATAR --}}
    @include('user.show')
    @include('user.destroy')
@endsection

@section('script_by_page')
    <script src="{{env('APP_URL')}}/assets/js/local/user.js"></script>
@endsection



