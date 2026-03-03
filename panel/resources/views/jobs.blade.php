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
                <div class="card border-0 shadow-sm" style="border-radius: 20px; overflow: hidden;">
                    {{-- Header con gradiente --}}
                    <div class="card-header border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div class="row align-items-center justify-content-between">
                            <div class="col">
                                <h5 class="mb-0 text-white fw-bold">
                                    <i class="fas fa-tasks me-2"></i>Listado de Trabajos
                                </h5>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-light btn-sm rounded-pill px-3 mx-1" onclick="callregister('/jobs/table',1,$('#table_limit').val(),$('#table_order').val(),'si')" title="Actualizar">
                                    <i class="fa-solid fa-arrows-rotate me-1"></i>Actualizar
                                </button>
                                @if (in_array('create',Session::get('user')['permissions']['jobs']))
                                    <button type="button" class="btn btn-light btn-sm rounded-pill px-3 mx-1 create-job" title="Crear trabajo">
                                        <i class="fa-solid fa-plus me-1"></i>Nuevo
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                
                    <div class="card-body bg-light p-3">

                        @include('Layout.errors')

                        <div class="row mb-3 align-items-center justify-content-between">
                            <div class="col-auto">
                                <label class="form-label mb-0 me-2 small text-muted">Mostrar:</label>
                                <select class="form-select form-select-sm d-inline-block" id="table_limit" style="width: auto; border-radius: 8px;">
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0" style="border-radius: 8px 0 0 8px;">
                                        <i class="flaticon2-search-1 text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0 ps-0" placeholder="Buscar trabajos..." id="table_search" style="border-radius: 0 8px 8px 0;">
                                </div>
                            </div>
                        </div>
                        
                        <div class="position-relative" style="border-radius: 12px; overflow: hidden; max-height: 55vh; overflow-y: auto; overflow-x: auto;">
                            <table class="table table-hover text-center sortable mb-0" id="table" style="background: white;">
                                <thead style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); position: sticky; top: 0; z-index: 10;">
                                    <tr>
                                        <th class="column_orden fw-bold text-dark" data-name="client_first_name" style="cursor: pointer; min-width: 150px; white-space: nowrap;">
                                            <i class="fas fa-user me-2"></i>Cliente
                                        </th>
                                        <th class="column_orden fw-bold text-dark" data-name="created_at" style="cursor: pointer; min-width: 200px; white-space: nowrap;">
                                            <i class="fas fa-calendar me-2"></i>Fechas
                                        </th>
                                        <th class="column_orden fw-bold text-dark" data-name="estatus" style="cursor: pointer; min-width: 120px; white-space: nowrap;">
                                            <i class="fas fa-flag me-2"></i>Estado
                                        </th>
                                        <th class="column_orden fw-bold text-dark" data-name="job_description" style="cursor: pointer; min-width: 250px; white-space: nowrap;">
                                            <i class="fas fa-clipboard me-2"></i>Descripción
                                        </th>
                                        <th class="sorttable_nosort fw-bold text-dark" style="min-width: 80px; white-space: nowrap;">
                                            <i class="fas fa-sticky-note me-2"></i>Notas
                                        </th>
                                        <th class="column_orden fw-bold text-dark" data-name="closed_job_observation" style="cursor: pointer; min-width: 250px; white-space: nowrap;">
                                            <i class="fas fa-comment me-2"></i>Observaciones
                                        </th>
                                        <th class="sorttable_nosort fw-bold text-dark" style="width:80px; min-width: 80px; white-space: nowrap;">
                                            <i class="fas fa-cog me-2"></i>
                                        </th>
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
                    
                    {{-- Footer con paginación --}}
                    <div class="card-footer bg-white border-0 pt-3">
                        <div class="row align-items-center">
                            <input type="hidden" id="table_order">
                            <input type="hidden" id="table_paginas">
                            <input type="hidden" id="table_filtrados">
                            <input type="hidden" id="table_totales">
                            <div class="col-lg-6 mb-2 mb-lg-0">
                                <small class="text-muted" id="table_info"></small>
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
    {{-- @include('job.create') --}} {{-- INCLUIDO EN ARCHIVO AVATAR --}}
    @include('job.edit')
    @include('job.show')
    @include('job.destroy')
    @include('job.descripcion')
    @include('job.notes')
    @include('job.closed')
    @include('job.files')
    @include('job.products')
@endsection

@section('script_by_page')
    <script src="{{env('APP_URL')}}/assets/js/local/job.js"></script>
@endsection



