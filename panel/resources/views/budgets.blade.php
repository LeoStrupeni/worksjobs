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

    .badge-estado-1 { background-color: #6c757d; color: white; }
    .badge-estado-2 { background-color: #28a745; color: white; }
    .badge-estado-3 { background-color: #dc3545; color: white; }
    .badge-estado-4 { background-color: #ffc107; color: black; }
    .badge-estado-5 { background-color: #17a2b8; color: white; }
    
    /* Asegurar que el texto se trunque en una sola línea */
    .text-truncate {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        cursor: help;
    }
    
    /* Estilo para tooltips más legibles */
    .tooltip-inner {
        max-width: 350px;
        text-align: left;
    }
    
    /* Estilos para dropdown (igual que en jobs) */
    .dropdown-toggle-menu-body {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        border: none;
        background-color: transparent;
    }
    
    .dropdown-toggle-menu-body:hover {
        background-color: #f8f9fa;
    }
    
    .dropdown-toggle-menu-body::after {
        display: none;
    }
    
    .dropdown-menu {
        font-size: 0.9rem;
        min-width: 200px;
    }
    
    .dropdown-item {
        padding: 0.5rem 1rem;
        transition: background-color 0.2s;
    }
    
    .dropdown-item:hover {
        background-color: #f8f9fa;
    }
    
    .dropdown-item i {
        width: 20px;
        text-align: center;
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
                            <div class="col-12 col-md-6">
                                <h5 class="mb-0 fw-bold d-flex align-items-center">
                                    <span class="rounded-circle bg-white bg-opacity-25 p-2 me-2">
                                        <i class="fa-solid fa-file-invoice-dollar"></i>
                                    </span>
                                    Presupuestos Borradores (Colppy)
                                </h5>
                            </div>
                            <div class="col-12 col-md-6 text-end">
                                <button type="button" class="btn btn-light btn-sm rounded-pill px-3 mx-1" onclick="callregister('/budgets/table',1,$('#table_limit').val(),$('#table_order').val(),'si')" title="Actualizar">
                                    <i class="fa-solid fa-arrows-rotate me-1"></i>Actualizar
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">

                        @include('Layout.errors')

                        {{-- <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-info-circle me-2"></i>
                                <div>
                                    <strong>Datos desde Colppy:</strong> Los presupuestos se consultan directamente desde la API de Colppy.
                                    Solo se muestran presupuestos borradores (nro >= 0002-00000000).
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div> --}}

                        <div class="row my-3 align-items-center justify-content-between">
                            <div class="col-3 col-xl-2">
                                <select class="form-select" id="table_limit">
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                            <div class="col-7 col-lg-4">
                                <div class="w-100 float-end" style="position: relative;padding: 0;">
									<input type="text" class="form-control" placeholder="Buscar por nro. factura..." id="table_search">
									<span style="position: absolute; height: 100%; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-pack: center;-ms-flex-pack: center;justify-content: center;top: 7px;width: 3.2rem;right: 0;">
										<span><i class="flaticon2-search-1"></i></span>
									</span>
								</div>
                            </div>
                        </div>
                        
                        <div style="max-height: 60vh; overflow-y: auto; border-radius: 10px;">
                            <table class="table table-sm table-hover text-center sortable" id="table">
                                <thead style="position: sticky; top: 0; z-index: 10; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <tr class="text-white">
                                        <th class="column_orden" data-name="nroFactura" style="white-space: nowrap;">Nro. Factura</th>
                                        <th class="column_orden" data-name="fechaFactura" style="white-space: nowrap;">Fecha</th>
                                        <th class="column_orden" data-name="nombreCliente" style="white-space: nowrap;">Cliente</th>
                                        <th style="white-space: nowrap;">Descripción</th>
                                        <th class="column_orden" data-name="estadoDescripcion" style="white-space: nowrap;">Estado</th>
                                        <th style="white-space: nowrap; width: 80px;">Opciones</th>
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
                                            <h5 class="m-0">Error al obtener la información desde Colppy. Por favor reintente o comuníquese con Soporte</h5>
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
                                            <h5 class="m-0">No se encontraron presupuestos borradores en Colppy</h5>
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

    @include('budget.show')
@endsection

@section('script_by_page')
    <script src="{{env('APP_URL')}}/assets/js/local/budget.js"></script>
@endsection
