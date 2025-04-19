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
            <div class="col-12 col-lg-10 bg-white rounded p-2">
                <div class="row align-items-center  justify-content-between">
                    <div class="col">
                        <div class="navbar-brand ps-3 fs-5">Listado de trabajos</div>
                    </div>
                    <div class="col">
                        <button type="button" class="btn btn-danger float-end mx-1" onclick="callregister('/jobs/table',1,$('#table_limit').val(),$('#table_order').val(),'si')"><i class="fa-solid fa-arrows-rotate"></i></button>
                        @if (in_array('create',Session::get('user')['permissions']['jobs']))
                            <button type="button" class="btn btn-success float-end mx-1 create"><i class="fa-solid fa-plus"></i></button>
                        @endif
                    </div>
                </div>
                
                <hr class="m-1" style="color: red;">

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
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center sortable" id="table">
                        <thead>
                            <tr>
                                <th class="column_orden" data-name="client_first_name">Cliente</th>
                                <th class="column_orden" data-name="created_at">Fechas</th>
                                <th class="column_orden" data-name="estatus">Estado</th>
                                <th class="column_orden" data-name="job_description">Descripcion</th>
                                <th class="sorttable_nosort">Notas</th>
                                <th class="column_orden" data-name="closed_job_observation">Observaciones</th>
                                <th class="sorttable_nosort" style="width:3%;"></th>
                            </tr>
                        </thead>
                        <tbody id="table_body">

                        </tbody>
                        <tbody id="table_roller">
                            <tr>
                                <td colspan="7">
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
                                <td colspan="7">
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
                                <td colspan="7">
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
                <div class="row align-items-center">
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
    
    {{-- @include('home.foot') --}}
    @include('job.create')
    @include('job.edit')
    @include('job.show')
    @include('job.destroy')
    @include('job.descripcion')
    @include('job.notes')
@endsection

@section('script_by_page')
    <script>var google_api_key = "{{$google_api_key->value}}";</script>
    <script src="{{env('APP_URL')}}/assets/js/local/job.js"></script>
    <script src="{{env('APP_URL')}}/assets/js/local/jobdetail.js"></script>
    <script src="{{env('APP_URL')}}/assets/js/local/geolocalizacion.js"></script>
@endsection



