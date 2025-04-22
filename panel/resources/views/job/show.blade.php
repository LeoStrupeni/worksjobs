<div class="modal fade" id="showjob" tabindex="-1" aria-hidden="true" >
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" style="min-width: 90%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Ver Tarea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-none" id="modal-body-show-job-error">
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
            <div class="modal-body" id="modal-body-show-job-roller">
                <div style="display:block;" class="text-center">
                    <br>
                    <br>
                    <div class="lds-roller"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
                    <br>
                    <br>
                    <br>
                </div>
            </div>
            <div class="modal-body" id="modal-body-show-job">
                <form id="formshowjob">
                    <div class="row">
                        <div class="col-12 col-md-4">
                            <div class="mb-2">
                                <label for="client_name" class="form-label mb-0 ps-3 fw-bold">Cliente</label>
                                <input type="text" class="form-control" name="client_name" readonly>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="mb-2">
                                <label for="client_addres_name" class="form-label mb-0 ps-3 fw-bold">Domicilio</label>
                                <input type="text" class="form-control" name="client_addres_name" readonly>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="mb-2">
                                <label for="visit_datetime" class="form-label mb-0 ps-3 fw-bold">Fecha y hora de visita</label>
                                <input type="datetime-local" class="form-control validate" name="visit_datetime" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-8">
                            <div class="mb-2">
                                <label for="job_description" class="form-label mb-0 ps-3 fw-bold">Descripcion de trabajo</label>
                                <textarea class="form-control validate" name="job_description" rows="8" readonly></textarea>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 text-center">
                            <div class="mb-2 arrival_coords_title d-none">
                                <label class="form-label mb-0 ps-3 fw-bold">Ubicación de arribo</label>
                                <iframe class="d-none arrival_coords" src="https://www.google.com/maps/embed/v1/place?key={{Session::get('user.google_api_key')}}&q=-32.9515008,-60.6430357" width="100%" height="200" style="border:0;" allowfullscreen="" loading="lazy"    referrerpolicy="no-referrer-when-downgrade"></iframe>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12 col-md-8">
                            <div class="mb-2">
                                <label for="closed_job_observation" class="form-label mb-0 ps-3 fw-bold">Observaciones de cierre</label>
                                <textarea class="form-control validate" name="closed_job_observation" rows="8" readonly></textarea>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 text-center">
                            <div class="mb-2 closed_coords_title d-none">
                                <label class="form-label mb-0 ps-3 fw-bold">Ubicación de cierre</label>
                                <iframe class="d-none closed_coords" src="https://www.google.com/maps/embed/v1/place?key={{Session::get('user.google_api_key')}}&q=-32.9515008,-60.6430357" width="100%" height="200" style="border:0;" allowfullscreen="" loading="lazy"    referrerpolicy="no-referrer-when-downgrade"></iframe>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-2">
                                <label for="client_name" class="form-label mb-0 ps-3 fw-bold">Fecha y hora de arribo</label>
                                <input type="datetime-local" class="form-control validate" name="arrival_datetime" readonly>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-2">
                                <label for="visit_datetime" class="form-label mb-0 ps-3 fw-bold">Fecha y hora de cierre</label>
                                <input type="datetime-local" class="form-control validate" name="closed_datetime" readonly>
                            </div>
                        </div>
                    </div>
                    <div id="lightgalleryShow" class="row justify-content-start">

                    </div>
                </form>
            </div>
        </div>
    </div>
</div>