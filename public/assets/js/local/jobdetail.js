var controladorTiempo = 3000;
var valorbuscado = '';
// Array global para mantener los archivos seleccionados
var selectedFiles = [];
// Variables para controlar peticiones AJAX en curso
var currentClientRequest = null;
var currentProductRequest = null;
var currentAddressRequest = null;
// Timestamp de la última búsqueda para garantizar que solo se ejecute la más reciente
var lastClientSearchTime = 0;
var lastProductSearchTime = 0;
$(document).ready(function() {
    getGeolocation();
    
    // Manejador para el formulario de archivos
    $('#formaddfilesjob').on('submit', function(e) {
        e.preventDefault();
        
        if (selectedFiles.length === 0) {
            toastr["warning"]("No hay archivos seleccionados para subir");
            closeSwal();
            return false;
        }
        
        var formData = new FormData();
        var jobId = $('#id_job_file').val();
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        
        // Agregar el token CSRF y el ID del trabajo
        formData.append('_token', csrfToken);
        formData.append('id', jobId);
        
        // Agregar solo los archivos no eliminados
        selectedFiles.forEach(function(item, index) {
            formData.append('images[]', item.file);
        });
        
        // Enviar con AJAX
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                toastr["success"]("Archivos subidos correctamente");
                $('#filesjob').modal('hide');
                
                // Recargar la tabla si estamos en la página de jobs
                if(window.location.href.includes('jobs')){
                    callregister('/jobs/table',1,$('#table_limit').val(),$('#table_order').val(),'si');
                } else {
                    window.location.reload();
                }
            },
            error: function(xhr, status, error) {
                toastr["error"]("Error al subir los archivos. Intente nuevamente.");
            }
        }).always(function() {
            closeSwal();
        });
        
        return false;
    });
    
    // Manejador para el botón de guardar archivos
    $('body').on('click', '#btn-submit-files', function() {
        showSavingAlert();
        $('#formaddfilesjob').submit();
    });
    
    // Manejador para el botón "Agregar más archivos"
    $('body').on('click', '#btn-add-more-files', function() {
        $('#file-input').val(''); // Limpiar el input para nueva selección
        $('#file-input-container').show();
        $('#file-input-help').show();
        $(this).hide();
    });
    
    // Manejador para el botón "Agregar más archivos" en modal de crear
    $('body').on('click', '#btn-add-more-files-create', function() {
        $('#file-input-create').val('');
        $('#file-input-container-create').show();
        $('#file-input-help-create').show();
        $(this).hide();
    });
    
    // Manejador para el botón "Agregar más archivos" en modal de editar
    $('body').on('click', '#btn-add-more-files-edit', function() {
        $('#file-input-edit').val('');
        $('#file-input-container-edit').show();
        $('#file-input-help-edit').show();
        $(this).hide();
    });
    
    $('body').on('click','.create-job',function(){ 
        $('#name').val('');
        $('#description').val('');
        
        // Resetear el array de archivos y elementos de UI para crear
        selectedFiles = [];
        $('#lightgallery').empty();
        $('#file-input-create').val('');
        $('#file-input-container-create').show();
        $('#file-input-help-create').show();
        $('#btn-add-more-files-create').hide();
        updateFilesCounter('create');

        // Resetear select de técnicos
        $('#technician_ids_create').selectpicker('deselectAll');
        $('#technician_ids_create').selectpicker('refresh');
        
        $('#createjob').modal('show');

        getGeolocation();
    });
    $('body').on('click','.update-job',function(){ 
        $("#lightgalleryEditNone").empty();
        $("#lightgalleryEdit").empty();

        var editJobId = $(this).data('id');
        var baseUrl = (typeof app_url !== 'undefined' && app_url)
            ? app_url
            : $('meta[name="app_url"]').attr('content');
        $('#formeditjob').attr('action', baseUrl + "/jobs/" + editJobId);
        $('#formeditjob').attr('data-job-id', editJobId);
        
        // Resetear el array de archivos y elementos de UI para editar
        selectedFiles = [];
        $('#file-input-edit').val('');
        $('#file-input-container-edit').show();
        $('#file-input-help-edit').show();
        $('#btn-add-more-files-edit').hide();
        updateFilesCounter('edit');

        form = document.getElementById("formeditjob");
        var countimg = 0;
        $( form.elements ).each(function( index ) {
            var fieldName = $(this).attr('name');
            var fieldId = $(this).attr('id');
            
            // No limpiar: _method, _token, ni campos de productos
            if(fieldName != '_method' && fieldName != '_token' && 
               fieldId != 'quantity_edit' && fieldId != 'unit_type_edit' && fieldId != 'product_id_edit'){
                $(this).val('');
            } 

            if($(this).attr('name') == 'images[]'){
                if(countimg==0){countimg++;}
                else {this.parentNode.parentNode.remove();}
            }
        });

        $('#editjob').modal('show');

        $('#modal-body-edit-job-roller').removeClass('d-none');
        $('#modal-body-edit-job-error').addClass('d-none');
        $('#formeditjob').addClass('d-none');
        $('#modal-foot-edit-job').addClass('d-none');

        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/jobs/'+$(this).data('id')+'/edit',
            type : 'GET',
            done : function(response) { $('#modal-body-edit-job-error').removeClass('d-none'); },
            error : function(jqXHR,textStatus,errorThrown) { $('#modal-body-edit-job-error').removeClass('d-none'); },
            success : function(data) {
                $('#address_id_e').empty();
                $('#address_id_e').selectpicker('render');
                $.each( data.address , function( index, value ) {
                    var option = `<option value="${this.id}">${this.address_detail ?? ''} ${this.address_street} ${this.address_nro ?? ''} ${this.city ?? ''}</option>`;
                    $('#address_id_e').append(option);
                });
                $('#address_id_e').selectpicker('refresh');

                viewjob(data,form,'editjob');
                viewfiles(data,'lightgalleryEdit');

                const canEditTimes = data.permissions
                    && data.permissions.jobs
                    && data.permissions.jobs.includes('times');
                if (canEditTimes) {
                    $('#job-times-permission-card').removeClass('d-none');

                    const hasClosedDatetime = data.job && !!data.job.closed_datetime;
                    $('#closed_datetime_edit').prop('disabled', !hasClosedDatetime);
                    $('#closed_datetime_edit_help').toggleClass('d-none', hasClosedDatetime);
                    if (!hasClosedDatetime) {
                        $('#closed_datetime_edit').val('');
                    }
                } else {
                    $('#job-times-permission-card').addClass('d-none');
                    $('#arrival_datetime_edit').val('');
                    $('#closed_datetime_edit').val('');
                    $('#closed_datetime_edit').prop('disabled', false);
                    $('#closed_datetime_edit_help').addClass('d-none');
                }

                // Poblar técnicos asignados en el select de edición
                setTechnicianSelect('#technician_ids_edit', data.technicians);

                // Cargar productos relacionados
                loadProductsToEdit(data.products);

                $('#formeditjob').removeClass('d-none');
                $('#modal-foot-edit-job').removeClass('d-none');
            }
        }).always(function() {
            $('#modal-body-edit-job-roller').addClass('d-none');
            getGeolocation();
        });
    });
    $('body').on('click','.read-job',function(){ 
        $("#lightgalleryShow").empty();
        form = document.getElementById("formshowjob");
        $( form.elements ).each(function( index ) {
            $(this).val('');
        });
        
        // Resetear tabs a la primera pestaña (Información General)
        $('#info-tab').tab('show');
        
        // Guardar el ID del trabajo en el modal
        const jobId = $(this).data('id');
        $('#showjob').data('job-id', jobId);
        
        $('#showjob').modal('show');

        $('#modal-body-show-job-roller').removeClass('d-none');
        $('#modal-body-show-job-error').addClass('d-none');
        $('#modal-body-show-job').addClass('d-none');

        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/jobs/'+jobId+'/edit',
            type : 'GET',
            done : function(response) { $('#modal-body-edit-job-error').removeClass('d-none'); },
            error : function(jqXHR,textStatus,errorThrown) { $('#modal-body-edit-job-error').removeClass('d-none'); },
            success : function(data) {

                viewjob(data,form,'showjob');
                viewfiles(data,'lightgalleryShow');

                // Mostrar técnicos asignados en el panel de show
                renderTechniciansShow(data.technicians);

                // Mostrar productos relacionados
                renderProductsShow(data.products);
                
                // Guardar datos del trabajo para el PDF
                currentJobDataForPdf = data;
                
                // Controlar visibilidad del botón de PDF según permisos
                if (data.permissions && data.permissions.pdf && data.permissions.pdf.includes('create')) {
                    $('#btn-generate-pdf').show();
                } else {
                    $('#btn-generate-pdf').hide();
                }
                
                // Controlar visibilidad del botón de compartir según permisos
                const hasSharePermission = data.permissions && data.permissions.share && data.permissions.share.includes('create');
                if (!hasSharePermission) {
                    $('#btn-share-selected-lightgalleryShow').hide();
                }

                $('#modal-body-show-job').removeClass('d-none');
            }
        }).always(function() {
            $('#modal-body-show-job-roller').addClass('d-none');
        });
    });
    $('body').on('click','.delete-job',function(){ 
        rolid=$(this).data('id');
        Swal.fire({
            title: "Borrar tarea",
            html: "Esta seguro que desea eliminar la tarea "+$(this).data('name')+"?<br>No podrá revertir el cambio.",
            type: "question",
            showCancelButton: true,
            confirmButtonText: "Borrar",
            cancelButtonText: `Cancelar`,
        }).then((result) => {
            if (result.dismiss != 'cancel') {
                showSavingAlert();
                $('#formdestroy').attr('action',app_url+"/jobs/"+$(this).data('id'));
                $('#formdestroy').submit();
            }
        });
    });
    $('body').on('click',"#btn-create-job",function () {
        $('#btn-create-job').prop('disabled','disabled');
        showSavingAlert();
        var error = 0
        form = document.getElementById("formnewjob");

        $( form.getElementsByClassName('validate') ).each(function( index ) {
            if($(this).prop('name') != undefined){
                if($( this ).val() == ''){
                    $( this ).css('box-shadow', 'inset 0px 0px 2px 2px red');
                    error++;
                } else {
                    $( this ).css('box-shadow', '');
                }
            }
        });
            
        if (error > 0) {
            $('#btn-create-job').prop('disabled',false);
            closeSwal();
            toastr["error"]("Debe completar los datos correctamente.")
        } else {
            document.getElementById("formnewjob").submit();
        }
    });
    $('body').on('click',"#btn-update-job",function () {
        $('#btn-update-job').prop('disabled','disabled');
        showSavingAlert();
        var error = 0
        form = document.getElementById("formeditjob");

        // Fallback: si por algun motivo action viene vacio, lo reconstruimos
        var formAction = $('#formeditjob').attr('action');
        if (!formAction || formAction === '') {
            var fallbackJobId = $('#formeditjob').attr('data-job-id');
            var baseUrl = (typeof app_url !== 'undefined' && app_url)
                ? app_url
                : $('meta[name="app_url"]').attr('content');

            if (fallbackJobId && baseUrl) {
                $('#formeditjob').attr('action', baseUrl + "/jobs/" + fallbackJobId);
            }
        }

        $( form.getElementsByClassName('validate') ).each(function( index ) {
            if($( this ).val() == ''){
                $( this ).css('box-shadow', 'inset 0px 0px 2px 2px red');
                error++;
            } else {
                $( this ).css('box-shadow', '');
            }
        });
        if (error > 0) {
            $('#btn-update-job').prop('disabled',false);
            closeSwal();
            toastr["error"]("Debe completar los datos correctamente para editar el jobe.")
        } else {
            document.getElementById("formeditjob").submit();
        }
    });
    $('body').on('click',".btn-description", function(){
        $('#description-job-body').empty();
        $('#descriptionjob').modal('show');
        content = $(this).data('content');
        if (content != '') {
            $('#description-job-body').html(content.replaceAll('\n','<br>'));
        } 
    });
    $('body').on('click',".markarrival", function(){
        showSavingAlert();
        var idtarea = $(this).data('id');
        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/jobs/markarrival',
            type : 'POST',
            data: {
                arrival_latitud	: $('input[name="latitude"]').val(),
                arrival_longitud: $('input[name="longitude"]').val(),
                jsongeolocation : $('input[name="jsongeolocation"]').val(),
                job_id          : idtarea
            },
            done : function(response) { toastr["error"]("Error al marcar el arribo reintentelo."); },
            error : function(jqXHR,textStatus,errorThrown) { toastr["error"]("Error al marcar el arribo reintentelo."); },
            success : function(data) {
                toastr["success"]("Arribo marcado correctamente.");
                if(window.location.href.includes('jobs') ){
                    callregister('/jobs/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
                } else {
                    window.location.reload();
                }
            }
        }).always(function() {
            closeSwal();
        });
    });
    
    $('body').on('click',".archive-job", function(){
        var idtarea = $(this).data('id');
        Swal.fire({
            title: '¿Archivar tarea?',
            text: "La tarea se ocultará del home pero seguirá disponible en la tabla de tareas",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, archivar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.value) {
                showSavingAlert();
                $.ajax({
                    contenttype : 'application/json; charset=utf-8',
                    url : $('meta[name="app_url"]').attr('content')+'/jobs/archive/'+idtarea,
                    type : 'POST',
                    data: { 
                        archived : 1,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    done : function(response) { toastr["error"]("Error al archivar la tarea, reintentelo."); },
                    error : function(jqXHR,textStatus,errorThrown) { toastr["error"]("Error al archivar la tarea, reintentelo."); },
                    success : function(data) {
                        toastr["success"]("Tarea archivada correctamente.");
                        if(window.location.href.includes('jobs') ){
                            callregister('/jobs/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
                        } else {
                            window.location.reload();
                        }
                    }
                }).always(function() {
                    closeSwal();
                });
            }
        });
    });
    
    $('body').on('click',".toggle-archive", function(){
        var idtarea = $(this).data('id');
        var currentArchived = $(this).data('archived');
        var newArchived = currentArchived == 1 ? 0 : 1;
        var action = newArchived == 1 ? 'archivar' : 'desarchivar';
        
        Swal.fire({
            title: `¿${action.charAt(0).toUpperCase() + action.slice(1)} tarea?`,
            text: newArchived == 1 ? "La tarea se ocultará del home pero seguirá visible aquí" : "La tarea volverá a mostrarse en el home",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: `Sí, ${action}`,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.value) {
                showSavingAlert();
                $.ajax({
                    contenttype : 'application/json; charset=utf-8',
                    url : $('meta[name="app_url"]').attr('content')+'/jobs/archive/'+idtarea,
                    type : 'POST',
                    data: { 
                        archived : newArchived,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    done : function(response) { toastr["error"](`Error al ${action} la tarea, reintentelo.`); },
                    error : function(jqXHR,textStatus,errorThrown) { toastr["error"](`Error al ${action} la tarea, reintentelo.`); },
                    success : function(data) {
                        toastr["success"](data.message);
                        callregister('/jobs/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
                    }
                }).always(function() {
                    closeSwal();
                });
            }
        });
    });
    
    $('body').on('click',".backarrival", function(){
        showSavingAlert();
        var idtarea = $(this).data('id');
        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/jobs/backarrival',
            type : 'POST',
            data: { job_id : idtarea},
            done : function(response) { toastr["error"]("Error al volver la tarea a pendiente, reintentelo."); },
            error : function(jqXHR,textStatus,errorThrown) { toastr["error"]("Error al volver la tarea a pendiente, reintentelo."); },
            success : function(data) {
                toastr["success"]("Se borró el marcado de la llegada de la tarea.");
                if(window.location.href.includes('jobs') ){
                    callregister('/jobs/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
                } else {
                    window.location.reload();
                }
            }
        }).always(function() {
            closeSwal();
        });
    });
    $('body').on('click',".addnote", function(){
        var idtarea = $(this).data('id');
        var nombre = $(this).data('name');
        getGeolocation();
        Swal.fire({
            text: "Agregar nota a la tarea "+nombre,
            input: "textarea",
            inputLabel: "Nota",
            inputPlaceholder: "Ingrese el detalle ...",
            inputAttributes: {
                "aria-label": "Ingrese el detalle"
            },
            showCancelButton: true,
            confirmButtonText: "Guardar Nota",
            cancelButtonText: "Cancelar",
        }).then((text) => {

            if(text.dismiss == 'cancel'){
            
            } else {
                if (text.value != '') {
                    showSavingAlert();
                    $.ajax({contenttype : 'application/json; charset=utf-8',
                        url : $('meta[name="app_url"]').attr('content')+'/jobs/addnote',
                        type : 'POST',
                        data: {
                            latitud	        : $('input[name="latitude"]').val(),
                            longitud        : $('input[name="longitude"]').val(),
                            jsongeolocation : $('input[name="jsongeolocation"]').val(),
                            job_id          : idtarea,
                            note            : text.value    
                        },
                        done : function(response) { toastr["error"]("Error al guardar la nota, reintentelo."); },
                        error : function(jqXHR,textStatus,errorThrown) { toastr["error"]("Error al guardar la nota, reintentelo."); },
                        success : function(data) {
                            toastr["success"]("Nota guardada correctamente.");

                            // Habilitar el botón de ver notas para esta tarea
                            var btnNotes = $(`.btn-notes[data-id="${idtarea}"]`);
                            btnNotes.prop('disabled', false);
                            btnNotes.css({'opacity': '1', 'cursor': 'pointer'});

                            if(window.location.href.includes('jobs') ){
                                callregister('/jobs/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
                            }
                        }
                    }).always(function() {
                        closeSwal();
                    });
                } else {
                    toastr["error"]("No hay detalle para guardar sobre la nota.");
                }
            }
        });

    });
    // Manejador para el botón "Agregar más archivos"
    $('body').on('click', '#btn-add-more-files', function() {
        $('#file-input').val(''); // Limpiar el input para nueva selección
        $('#file-input-container').show();
        $('#file-input-help').show();
        $('#btn-add-more-files').hide();
    });
    
    $('body').on('click','.addfiles', function(){
        var idtarea = $(this).data('id');
        var nombre = $(this).data('name');

        $("#lightgalleryFilesNone").empty();
        $("#lightgalleryFiles").empty();
        
        // Resetear el array de archivos y el contador
        selectedFiles = [];
        updateFilesCounter('files');
        
        // Resetear visibilidad de elementos
        $('#file-input-container').show();
        $('#file-input-help').show();
        $('#btn-add-more-files').hide();
        $('#file-input').val('');

        form = document.getElementById("formeditjob");
        var countimg = 0;
        $( form.elements ).each(function( index ) {
            if($(this).attr('name') != '_method' && $(this).attr('name') != '_token'){
                $(this).val('');
            } 

            if($(this).attr('name') == 'images[]'){
                if(countimg==0){countimg++;}
                else {this.parentNode.parentNode.remove();}
            }
        });

        $('#titlefilesjob').text(nombre);
        $('#id_job_file').val(idtarea);    

        $('#loadingFiles').removeClass('d-none');
        $('#filesjob').modal('show');

        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/jobs/'+idtarea+'/edit',
            type : 'GET',
            done : function(response) {  },
            error : function(jqXHR,textStatus,errorThrown) {  },
            success : function(data) {
                
                viewfiles(data,'lightgalleryFiles');
            }
        }).always(function() {
            $('#loadingFiles').addClass('d-none');
        });

    });
    $('body').on('click',".btn-notes", function(){
        var idtarea = $(this).data('id');
        var nombre = $(this).data('name');

        // Guardar el ID de la tarea en el modal para uso posterior
        $('#viewjobsnotes').data('job-id', idtarea);
        
        $('#viewjobsnotes').modal('show');
        $('#titlenotas').text(nombre);
        $('#tablenotes_body').empty();

        $('#modal-body-view-jobsnotes-roller').removeClass('d-none');
        $('#modal-body-view-jobsnotes-error').addClass('d-none');
        $('#modal-body-view-jobsnotes').addClass('d-none');

        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/jobs/notes/'+idtarea,
            type : 'GET',
            done : function(response) { 
                $('#modal-body-view-jobsnotes-error').removeClass('d-none');
                $('#modal-body-view-jobsnotes').addClass('d-none');
            },
            error : function(jqXHR,textStatus,errorThrown) { 
                $('#modal-body-view-jobsnotes-error').removeClass('d-none');
                $('#modal-body-view-jobsnotes').addClass('d-none');
            },
            success : function(response) {
                body='';
                $.each(response.data, function (key, val) {
                    body += `<tr>
                        <td class="text-wrap text-start">${val.note.replaceAll('\n','<br>')}</td>
                        <td class="align-middle">${val.created}</td>
                        <td class="align-middle">
                            <a href="javascript:void(0);" data-id="${val.id}" class="btn btn-sm btn-danger deletenote">
                                <i class="flaticon-delete me-2"></i>
                            </a>
                        </td>
                    </tr>`;
                });
                $('#tablenotes_body').append(body);
                $('#modal-body-view-jobsnotes-error').addClass('d-none');
                $('#modal-body-view-jobsnotes').removeClass('d-none');
            }
        }).always(function() {
            $('#modal-body-view-jobsnotes-roller').addClass('d-none');
        });
    });
    $('body').on('click','.deletenote',function(){ 
        var idtarea = $(this).data('id');
        var parent = this.closest('tr');
        Swal.fire({
            title: "Borrar nota de tarea",
            html: "Esta seguro que desea eliminar la nota de la tarea?<br>No podrá revertir el cambio.",
            type: "question",
            showCancelButton: true,
            confirmButtonText: "Borrar",
            cancelButtonText: `Cancelar`,
        }).then((result) => {
            if (result.dismiss != 'cancel') {
                showSavingAlert();
                parent.remove();
                
                // Verificar si quedan más notas después de eliminar
                var remainingNotes = $('#tablenotes_body tr').length;
                var jobId = $('#viewjobsnotes').data('job-id');
                
                $.ajax({contenttype : 'application/json; charset=utf-8',
                    url : $('meta[name="app_url"]').attr('content')+'/jobs/destroynote/'+idtarea,
                    type : 'GET',
                    success : function(data) {
                        toastr["warning"]("Nota eliminada correctamente.");
                        
                        // Si no quedan más notas, cerrar modal y deshabilitar botón
                        if (remainingNotes === 0) {
                            $('#viewjobsnotes').modal('hide');
                            var btnNotes = $(`.btn-notes[data-id="${jobId}"]`);
                            btnNotes.prop('disabled', true);
                            btnNotes.css({'opacity': '0.3', 'cursor': 'not-allowed'});
                        }
                        
                        if(window.location.href.includes('jobs') ){
                            callregister('/jobs/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
                        }
                    }
                }).always(function() {
                    closeSwal();
                });
            }
        });
    });
    $('body').on('click','.closetask',function(){ 
        var idtarea = $(this).data('id');
        var nombre = $(this).data('name');
        
        // Obtener geolocalización primero
        getGeolocation();
        
        // Mostrar confirmación simple
        Swal.fire({
            title: '¿Cerrar tarea?',
            text: "¿Está seguro que desea cerrar esta tarea: " + nombre + "?",
            type: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cerrar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.value === true) {
                showSavingAlert();
                
                // Leer los valores de geolocalización de los inputs
                var latitude = $('input[name="latitude"]').val();
                var longitude = $('input[name="longitude"]').val();
                var jsongeolocation = $('input[name="jsongeolocation"]').val();
                
                $.ajax({
                    url: app_url + '/jobs/closed',
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        id: idtarea,
                        latitude: latitude,
                        longitude: longitude,
                        jsongeolocation: jsongeolocation
                    },
                    success: function(response) {
                        closeSwal();
                        toastr["success"]("Tarea cerrada correctamente");
                        if(window.location.href.includes('jobs')){
                            callregister('/jobs/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        closeSwal();
                        toastr["error"]("Error al cerrar la tarea");
                    }
                });
            }
        });
    });
    $('body').on('click',"#btn-closed-job",function () {
        var error = 0

        var error = 0
        form = document.getElementById("formclosedjob");

        $( form.getElementsByClassName('validate') ).each(function( index ) {
            if($( this ).val() == ''){
                $( this ).css('box-shadow', 'inset 0px 0px 2px 2px red');
                error++;
            } else {
                $( this ).css('box-shadow', '');
            }
        });

        // Validar que se seleccionó al menos un técnico
        var techSelected = $('#technician_ids_closed').val();
        if (!techSelected || techSelected.length === 0) {
            $('#technician_ids_closed_error').removeClass('d-none');
            error++;
        } else {
            $('#technician_ids_closed_error').addClass('d-none');
        }

        if (error > 0) {
            toastr["error"]("Complete la observación y seleccione al menos un técnico para cerrar la tarea.")
        } else {
            showSavingAlert();
            document.getElementById("formclosedjob").submit();
        }
    });
    
    $('.bs-searchbox').children().keyup(function (e) {
        valor = this.value;
        if ($($($(e.target)).parent().parent().parent()[0]).hasClass('searchvar')) {            
            // Obtener el ID del select original desde el botón de bootstrap-select
            let bootstrapSelectDiv = $(e.target).closest('.bootstrap-select');
            let selectId = bootstrapSelectDiv.find('button').attr('data-id') || '';
            
            let isProductSearch = selectId.indexOf('product_id') !== -1;
            let searchType = isProductSearch ? 'products' : 'clients';
            
            // console.log('Búsqueda detectada:', { selectId, isProductSearch, searchType, valor });
            
            if(valorbuscado != valor && valor.length > 0){
                valorbuscado = valor;

                clearInterval(controladorTiempo);
                controladorTiempo = setInterval(function(){
                    if (searchType === 'clients') {
                        // Cancelar petición anterior si existe
                        if (currentClientRequest) {
                            currentClientRequest.abort();
                        }
                        
                        // Marcar timestamp de esta búsqueda
                        var searchTimestamp = Date.now();
                        lastClientSearchTime = searchTimestamp;
                        
                        // Búsqueda de clientes
                        let selectClients = $('select#client_id');
                        selectClients.find('option').remove(); 
                        $('#client_id').empty();
                        $('#client_id').selectpicker('render');
                        $('#spinner1').removeClass('d-none');

                        currentClientRequest = $.ajax({
                            url: "/api/searchvar",
                            type: 'POST',
                            data: {
                                search: valor,
                                tipo: 'clients'
                            },
                            success : function(data) {
                                // Solo procesar si esta es la búsqueda más reciente
                                if (searchTimestamp !== lastClientSearchTime) {
                                    return;
                                }
                                
                                datos = data;
                                $.each(datos, function() {
                                    var option = `<option value="${this.id}">${this.first_name} ${this.last_name ?? ''}</option>`;
                                    selectClients.append(option);
                                });
                                selectClients.selectpicker('refresh');
                                
                                // Si hay resultados, cargar automáticamente los domicilios del primer cliente
                                if (datos.length > 0) {
                                    getAddress(datos[0].id);
                                }
                            }
                        }).always(function() {
                            $('#spinner1').addClass('d-none');
                            currentClientRequest = null;
                        });
                    } else {
                        // Cancelar petición anterior si existe
                        if (currentProductRequest) {
                            currentProductRequest.abort();
                        }
                        
                        // Marcar timestamp de esta búsqueda
                        var searchTimestamp = Date.now();
                        lastProductSearchTime = searchTimestamp;
                        
                        // Búsqueda de productos
                        let spinnerId = selectId.replace('product_id', 'spinner_product');
                        
                        let selectProducts = $('#' + selectId);
                        selectProducts.find('option').remove();
                        selectProducts.empty();
                        selectProducts.selectpicker('render');
                        $('#' + spinnerId).removeClass('d-none').addClass('d-inline-block');

                        currentProductRequest = $.ajax({
                            url: "/api/searchvar",
                            type: 'POST',
                            data: {
                                search: valor,
                                tipo: 'products'
                            },
                            success : function(data) {
                                // Solo procesar si esta es la búsqueda más reciente
                                if (searchTimestamp !== lastProductSearchTime) {
                                    return;
                                }
                                
                                datos = data;
                                $.each(datos, function() {
                                    var option = `<option value="${this.id}">${this.codigo} - ${this.descripcion}</option>`;
                                    selectProducts.append(option);
                                });
                                selectProducts.selectpicker('refresh');
                            },
                            error: function(xhr, status, error) {
                                if (error !== 'abort') {
                                    // console.error('Error en búsqueda de productos:', error);
                                }
                            }
                        }).always(function() {
                            $('#' + spinnerId).removeClass('d-inline-block').addClass('d-none');
                            currentProductRequest = null;
                        });
                    }
                    
                    clearInterval(controladorTiempo);
                }, 400);
            }
        }
    });

    // Refrescar selectpicker de clientes después de seleccionar para corregir visualización
    $('body').on('change', '#client_id', function() {
        $(this).selectpicker('refresh');
        // Cargar domicilios del cliente seleccionado
        var clientId = $(this).val();
        if (clientId) {
            getAddress(clientId);
        }
    });

    // Exponer función al scope global para uso desde otros archivos
    window.viewjob = function(data,form,origen){
        $.each( data.job , function( index, value ) {
            $( form.elements ).each(function( b ) {
                if($(this).attr('name') == index){
                    $(this).val(value);
                    if(origen == 'showjob'){
                        $(this).css('box-shadow', 'inset 0px 0px 1px 1px green');
                    }
                    
                }
            });
            if(origen == 'showjob'){
                var urlmap="https://www.google.com/maps/embed/v1/place?key="+google_api_key+"&q=";
                if(index == 'arrival_coords' || index == 'closed_coords'){
                    if (value != null) {
                        $(form.getElementsByClassName(index)[0]).attr('src',urlmap+value);
                        $(form.getElementsByClassName(index)[0]).removeClass('d-none');
                        $('.'+index+'_title').removeClass('d-none');
                    } else {
                        $(form.getElementsByClassName(index)[0]).addClass('d-none');
                        $('.'+index+'_title').addClass('d-none');
                    }   
                }
            }
        });

    }

    // Exponer función al scope global para uso desde otros archivos
    window.viewfiles = function(data,id_elemento){
        // Verificar permisos de compartir
        const hasSharePermission = data.permissions && data.permissions.share && data.permissions.share.includes('create');
        
        // Agregar botones de acción global antes de las imágenes
        let actionButtons = `
            <div class="col-12 mb-3" id="images-action-bar-${id_elemento}">
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="selectAll-${id_elemento}" 
                            onchange="toggleSelectAllImages('${id_elemento}')">
                        <label class="form-check-label" for="selectAll-${id_elemento}">
                            <strong>Seleccionar todas</strong>
                        </label>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" onclick="downloadSelectedImages(event, '${id_elemento}')" 
                        id="btn-download-selected-${id_elemento}" disabled>
                        <i class="fas fa-download me-1"></i>Descargar seleccionadas
                    </button>`;
        
        // Solo agregar botón de compartir si tiene permisos
        if (hasSharePermission) {
            actionButtons += `
                    <button type="button" class="btn btn-sm btn-success" onclick="shareSelectedImages(event, '${id_elemento}')" 
                        id="btn-share-selected-${id_elemento}" disabled>
                        <i class="fas fa-share-alt me-1"></i>Compartir seleccionadas
                    </button>`;
        }
        
        actionButtons += `
                    <span class="badge bg-secondary" id="selected-count-${id_elemento}">0 seleccionadas</span>
                </div>
            </div>`;
        
        $("#"+id_elemento).append(actionButtons);

        $.each( data.files , function( index, value ) {
            let imagen = `<div class="text-center" style="width: 120px; position: relative;">
                <div class="position-relative d-inline-block">
                    <!-- Checkbox para selección -->
                    <div class="position-absolute" style="top: 5px; left: 5px; z-index: 10;">
                        <input class="form-check-input image-checkbox" type="checkbox" 
                            data-image-url="/storage/${this.name}" 
                            data-image-name="${this.original_name || this.name}"
                            data-element="${id_elemento}"
                            onchange="updateImageSelection('${id_elemento}')" 
                            style="width: 20px; height: 20px; cursor: pointer;">
                    </div>
                    <a class="gallery" href="${this.url_web}">
                        <img src="${this.url_web}" style='border-radius:.5rem; height: 100px; width: 100px;'>
                    </a>`;
                    
                    // Botón de eliminar (solo en ciertos contextos) - en la esquina como antes
                    if(id_elemento == 'lightgalleryEdit' || id_elemento == 'lightgalleryFiles'){
                        imagen += `<span class="btn-danger-pro" 
                            style="position: absolute; top: -10px; right: -10px; cursor: pointer; z-index: 10;"
                            onclick="deleteimg(this,${this.id},'${id_elemento}')">
                            <i class="fas fa-trash"></i>
                        </span>`;
                    }
                    
                imagen += `</div></div>`;

            $("#"+id_elemento).append(imagen);     
            if(id_elemento == 'lightgalleryEdit'){ $("#"+id_elemento+"None").append(imagen); }
            if(id_elemento == 'lightgalleryClosed'){ $("#"+id_elemento+"None").append(imagen); }
            if(id_elemento == 'lightgalleryFiles'){ $("#"+id_elemento+"None").append(imagen); }
        })

        var gallery = $('#'+id_elemento)
            gallery.lightGallery();
            gallery.data('lightGallery').destroy(true);
            galleryimagen(id_elemento);  

    }
});

/**
 * Setea los técnicos seleccionados en un selectpicker múltiple.
 * @param {string} selector - Selector jQuery del select
 * @param {Array}  technicians - Array de {id, name} desde el servidor
 */
function setTechnicianSelect(selector, technicians) {
    var $select = $(selector);
    $select.selectpicker('deselectAll');
    if (technicians && technicians.length > 0) {
        var ids = technicians.map(function(t) { return String(t.id); });
        $select.find('option').each(function() {
            if (ids.indexOf($(this).val()) !== -1) {
                $(this).prop('selected', true);
            }
        });
    }
    $select.selectpicker('refresh');
}

/**
 * Muestra los técnicos asignados en el modal show.
 * Si no hay ninguno, oculta el contenedor; si hay, lo muestra con badges.
 */
function renderTechniciansShow(technicians) {
    var $container = $('#technicians_show_container');
    var $body = $('#technicians_show_body');
    $body.empty();

    if (!technicians || technicians.length === 0) {
        $container.addClass('d-none');
        return;
    }

    $container.removeClass('d-none');
    var html = '';
    $.each(technicians, function(i, t) {
        html += '<span class="badge bg-secondary fs-6 px-3 py-2">' +
                    '<i class="fas fa-hard-hat me-2"></i>' + t.name +
                '</span>';
    });
    $body.html(html);
}

function getAddress(client_id) {
    // Cancelar petición anterior si existe
    if (currentAddressRequest) {
        currentAddressRequest.abort();
    }
    
    $('#address_id').empty();
    $('#address_id').selectpicker('render');
    $('#spinner2').removeClass('d-none');

    currentAddressRequest = $.ajax({
        // contenttype: 'application/json; charset=utf-8',
        url: "/api/searchvar",
        type: 'POST',
        data: {
            search: client_id,
            tipo: 'address'
        },
        success : function(data) {
            datos = data;
            
            // Si hay más de 1 domicilio, agregar opción vacía
            if (datos.length > 1) {
                $('#address_id').append('<option></option>');
            }
            
            $.each(datos, function() {
                var option = `<option value="${this.id}">${this.address_street} ${this.address_nro ?? ''} ${this.city ?? ''} ${this.address_detail ?? ''}</option>`;
                $('#address_id').append(option);
            });
            
            // Si hay solo 1 domicilio, seleccionarlo automáticamente
            if (datos.length === 1) {
                $('#address_id').val(datos[0].id);
            }
            
            $('#address_id').selectpicker('refresh');
            
        }
    }).always(function() {
        $('#spinner2').addClass('d-none');
        currentAddressRequest = null;
    });

}

function scaleImage(inputnew,id_elemento) {    
    // No limpiar el contenedor para mantener las imágenes anteriores
    
    form = inputnew.form;
    idform = form.id;
    inputfiles = form.elements['images[]'];
    inputnone = 0;
    
    // Determinar qué contador usar según el elemento
    var counterType = 'files'; // Por defecto para lightgalleryFiles
    if(id_elemento === 'lightgallery') {
        counterType = 'create';
    } else if(id_elemento === 'lightgalleryEdit') {
        counterType = 'edit';
    }
    
    // NO reiniciar el array - agregar los nuevos archivos a los existentes
    
    $( inputfiles ).each(function( i, input ) { if (input.files.length == 0) {inputnone++;} });
    $( inputfiles ).each(function( i, input ) {
        if (input.files.length > 0) {
            // Agregar todos los archivos al array con IDs únicos
            for (let index = 0; index < input.files.length; index++) {
                selectedFiles.push({
                    file: input.files[index],
                    index: selectedFiles.length, // Índice basado en el tamaño actual
                    id: Date.now() + Math.random() // ID único para cada archivo
                });
            }
            
            // Ocultar el input y mostrar botón para agregar más según el tipo
            if(counterType === 'create') {
                $('#file-input-container-create').hide();
                $('#file-input-help-create').hide();
                $('#btn-add-more-files-create').show();
            } else if(counterType === 'edit') {
                $('#file-input-container-edit').hide();
                $('#file-input-help-edit').hide();
                $('#btn-add-more-files-edit').show();
            } else {
                $('#file-input-container').hide();
                $('#file-input-help').hide();
                $('#btn-add-more-files').show();
            }
            
            // Actualizar el contador
            updateFilesCounter(counterType);
            
            // Calcular el índice inicial para los nuevos archivos
            var startIndex = selectedFiles.length - input.files.length;
            
            // Procesar cada archivo para preview
            for (let index = 0; index < input.files.length; index++) {
                var cant = input.files.length;
                let file = input.files[index];
                // Usar el índice correcto del array global
                let fileId = selectedFiles[startIndex + index].id;
                
                if (file != undefined) {
                    let filetype = file.type;
                    let reader = new FileReader();
                    reader.addEventListener("load", function () {
                        let image = new Image();
        
                        image.addEventListener("load", function () {
                            let width = Math.floor(image.width / 2);
                            let height = Math.floor(image.height / 2);
        
                            let canvas = document.createElement("canvas");
                            canvas.width = width;
                            canvas.height = height;
        
                            let context = canvas.getContext("2d");
                            context.drawImage(image, 0, 0, width, height);
        
                            let url = canvas.toDataURL(filetype, 0.6);
                            let link = document.createElement("img");
                            link.src = url;

                            let imagen = `<div class="text-center preview-file-item" data-file-id="${fileId}" style="width: 120px; position: relative;"> 
                                <a class="gallery" href="${url}">
                                    <img src="${url}" style='border-radius:.5rem; height: 100px; width: 100px;'>
                                </a>
                                <span class="btn-danger-pro" 
                                    style="position: relative; top: -25px; right: -40px; cursor: pointer;"
                                    onclick="removePreviewFile(${fileId}, '${id_elemento}')">
                                    <i class="fas fa-times"></i>
                                </span>
                            <div>`;
        
                            $("#"+id_elemento).append(imagen);
        
                            var gallery = $("#"+id_elemento)
                            gallery.lightGallery();
                            gallery.data('lightGallery').destroy(true);
                            galleryimagen(id_elemento);
        
                        });
                        image.src = reader.result;
                    });
                    reader.readAsDataURL(file);
                }
            }
        }
    });
}

// Función para eliminar un archivo individual del preview
function removePreviewFile(fileId, id_elemento) {
    // Eliminar del array de archivos seleccionados
    selectedFiles = selectedFiles.filter(item => item.id !== fileId);
    
    // Determinar qué contador usar según el elemento
    var counterType = 'files';
    if(id_elemento === 'lightgallery') {
        counterType = 'create';
    } else if(id_elemento === 'lightgalleryEdit') {
        counterType = 'edit';
    }
    
    // Eliminar del DOM
    $(`.preview-file-item[data-file-id="${fileId}"]`).remove();
    
    // Actualizar la galería
    var gallery = $("#"+id_elemento);
    if (gallery.data('lightGallery')) {
        gallery.data('lightGallery').destroy(true);
    }
    if (selectedFiles.length > 0) {
        galleryimagen(id_elemento);
    }
    
    // Actualizar el contador
    updateFilesCounter(counterType);
    
    // Mostrar mensaje indicando cuántos archivos quedan
    if (selectedFiles.length > 0) {
        toastr["info"](`${selectedFiles.length} archivo(s) seleccionado(s)`);
    } else {
        toastr["info"]("No hay archivos seleccionados");
    }
}

// Función para actualizar el contador de archivos
function updateFilesCounter(type) {
    type = type || 'files'; // Por defecto 'files' si no se especifica
    
    var count = selectedFiles.length;
    var counterId = '#files-count';
    var containerId = '#files-counter';
    var inputContainerId = '#file-input-container';
    var helpId = '#file-input-help';
    var buttonId = '#btn-add-more-files';
    
    // Determinar qué elementos usar según el tipo
    if(type === 'create') {
        counterId = '#files-count-create';
        containerId = '#files-counter-create';
        inputContainerId = '#file-input-container-create';
        helpId = '#file-input-help-create';
        buttonId = '#btn-add-more-files-create';
    } else if(type === 'edit') {
        counterId = '#files-count-edit';
        containerId = '#files-counter-edit';
        inputContainerId = '#file-input-container-edit';
        helpId = '#file-input-help-edit';
        buttonId = '#btn-add-more-files-edit';
    }
    
    $(counterId).text(count);
    
    if (count > 0) {
        $(containerId).show();
    } else {
        $(containerId).hide();
        // Si no hay archivos, mostrar el input nuevamente
        $(inputContainerId).show();
        $(helpId).show();
        $(buttonId).hide();
    }
}

function galleryimagen(id_elemento) {
	$("#"+id_elemento).lightGallery({
		selector: 'div .gallery',
		zoom: true,
		download: false,
	});
}

function deleteimg(e,idjob,id_elemento){
    showSavingAlert();
    $.ajax({contenttype : 'application/json; charset=utf-8',
        url : $('meta[name="app_url"]').attr('content')+'/jobs/destroyfile/'+idjob,
        type : 'GET',
        success : function(data) {
            
            toastr["warning"]("Archivo eliminado correctamente.");
            e.parentNode.remove();

            $.each(data, function(index, value) {
                // CAMBIAMOS /storage/${this.name} POR ${this.url_web}
                let imagen = `<div class="text-center" style="width: 120px;"> 
                    <a class="gallery" href="${this.url_web}" data-src="${this.url_web}">
                        <img src="${this.url_web}" style='border-radius:.5rem; height: 100px; width: 100px;'>
                    </a>
                    <span class="btn-danger-pro" 
                        style="position: relative; top: -25px; right: -40px; cursor: pointer;"
                        onclick="deleteimg(this, ${this.id}, '${id_elemento}')">
                        <i class="fas fa-trash me-2"></i>
                    </span>
                </div>`;
                
                $("#" + id_elemento + "None").append(imagen);     
            });
            var gallery = $('#'+id_elemento)
                gallery.lightGallery();
                gallery.data('lightGallery').destroy(true);
                galleryimagen(id_elemento);  

        }
    }).always(function() {
        closeSwal();
    });
}

/**
 * Sincronizar productos desde Colppy manualmente
 * Se ejecuta al hacer clic en el botón "Sincronizar Productos" en los modales
 */
function syncProductsManual() {
    // Mostrar notificación de inicio
    Swal.fire({
        title: 'Sincronizando...',
        html: 'Por favor espere mientras se sincronizan los productos desde Colppy.<br><small class="text-muted">Esto puede tardar varios segundos...</small>',
        type: 'info',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    // Llamar al endpoint de sincronización (ruta web, no API)
    $.ajax({
        url: app_url + '/products/sync-colppy-now',
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        timeout: 120000, // 2 minutos de timeout
        success: function(response) {
            if (response.success) {
                const datos = response.datos;
                Swal.fire({
                    title: '¡Sincronización Completa!',
                    html: `
                        <div class="text-start">
                            <p class="mb-2"><strong>Resultado:</strong></p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-plus-circle text-success me-2"></i>Productos nuevos: ${datos.nuevos}</li>
                                <li><i class="fas fa-sync-alt text-primary me-2"></i>Productos actualizados: ${datos.actualizados}</li>
                                <li><i class="fas fa-check-circle text-info me-2"></i>Total procesados: ${datos.total}</li>
                                ${datos.errores > 0 ? `<li><i class="fas fa-exclamation-triangle text-warning me-2"></i>Errores: ${datos.errores} (ver logs)</li>` : ''}
                            </ul>
                        </div>
                    `,
                    type: 'success',
                    confirmButtonText: 'Cerrar'
                });
            } else {
                Swal.fire({
                    title: 'Error',
                    text: response.message || 'No se pudo sincronizar los productos',
                    type: 'error',
                    confirmButtonText: 'Cerrar'
                });
            }
        },
        error: function(xhr) {
            let errorMessage = 'Error al sincronizar productos. Por favor intente nuevamente.';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.status === 401) {
                errorMessage = 'No tiene permisos para realizar esta acción';
            } else if (xhr.status === 500) {
                errorMessage = 'Error del servidor. Por favor revise los logs o contacte al administrador';
            } else if (xhr.status === 0) {
                errorMessage = 'Error de conexión. Verifique su conexión a internet';
            }
            
            Swal.fire({
                title: 'Error',
                html: errorMessage,
                type: 'error',
                confirmButtonText: 'Cerrar'
            });
        }
    });
}

// ==================== FUNCIONES DE PRODUCTOS ====================
// Array global para mantener los productos seleccionados
var selectedProducts = [];
var productUniqueIdCounter = 0;

/**
 * Agregar producto a la tarea
 * @param {string} mode - 'create' o 'edit'
 */
function addProductToJob(mode) {
    // console.log('addProductToJob llamado con mode:', mode);
    
    const productSelect = $(`#product_id_${mode}`);
    const productId = productSelect.val();
    const productText = productSelect.find('option:selected').text();
    const unitType = $(`#unit_type_${mode}`).val();
    const quantity = parseFloat($(`#quantity_${mode}`).val());

    // console.log('Datos:', { productId, productText, unitType, quantity });

    if (!productId) {
        toastr["warning"]("Debe seleccionar un producto");
        return;
    }

    if (quantity <= 0 || isNaN(quantity)) {
        toastr["warning"]("La cantidad debe ser mayor a 0");
        return;
    }

    // Verificar si el producto ya está agregado
    const exists = selectedProducts.some(p => String(p.product_id) === String(productId) && p.mode === mode);
    if (exists) {
        toastr["warning"]("Este producto ya está agregado a la tarea");
        return;
    }

    // Agregar producto al array
    const product = {
        unique_id: ++productUniqueIdCounter,
        product_id: String(productId),
        codigo: productText.split(' - ')[0],
        descripcion: productText.split(' - ')[1] || productText,
        unit_type: unitType,
        quantity: quantity,
        mode: mode
    };
    selectedProducts.push(product);
    // console.log('Producto agregado. Array actual:', selectedProducts);

    // Renderizar la lista de productos
    renderProductsList(mode);

    // Limpiar campos
    productSelect.val('').selectpicker('refresh');
    $(`#unit_type_${mode}`).val('Unidad');
    $(`#quantity_${mode}`).val('1.00');

    toastr["success"]("Producto agregado correctamente");
}

/**
 * Eliminar producto de la lista
 * @param {number} uniqueId - ID único del producto en el array
 * @param {string} mode - 'create' o 'edit'
 */
function removeProductFromJob(uniqueId, mode) {
    selectedProducts = selectedProducts.filter(p => p.unique_id !== uniqueId);
    renderProductsList(mode);
    toastr["info"]("Producto eliminado");
}

/**
 * Renderizar la lista de productos
 * @param {string} mode - 'create' o 'edit'
 */
function renderProductsList(mode) {
    // console.log('renderProductsList llamado con mode:', mode);
    
    const productsForMode = selectedProducts.filter(p => p.mode === mode);
    const listContainer = $(`#products_list_${mode}`);
    const hiddenContainer = $(`#products_hidden_${mode}`);

    // console.log('Productos para este modo:', productsForMode);
    // console.log('Lista container existe:', listContainer.length > 0);
    // console.log('Hidden container existe:', hiddenContainer.length > 0);

    if (productsForMode.length === 0) {
        listContainer.empty().removeClass('mt-3');
        hiddenContainer.empty();
        return;
    }

    // Renderizar lista visual
    let html = '<div class="table-responsive"><table class="table table-sm table-hover"><thead class="table-light"><tr><th>Código</th><th>Descripción</th><th>Tipo</th><th class="text-end">Cantidad</th><th class="text-center">Acción</th></tr></thead><tbody>';
    
    productsForMode.forEach(product => {
        html += `
            <tr>
                <td class="align-middle"><strong>${product.codigo}</strong></td>
                <td class="align-middle">${product.descripcion}</td>
                <td class="align-middle"><span class="badge bg-secondary">${product.unit_type}</span></td>
                <td class="text-end align-middle">${parseFloat(product.quantity).toFixed(2)}</td>
                <td class="text-center align-middle">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeProductFromJob(${product.unique_id}, '${mode}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    listContainer.html(html).addClass('mt-3');

    // Agregar campos ocultos para envío del formulario
    hiddenContainer.empty();
    productsForMode.forEach((product, index) => {
        hiddenContainer.append(`
            <input type="hidden" name="products[${index}][product_id]" value="${product.product_id}">
            <input type="hidden" name="products[${index}][unit_type]" value="${product.unit_type}">
            <input type="hidden" name="products[${index}][quantity]" value="${product.quantity}">
        `);
    });
    
    // console.log('Lista renderizada correctamente');
}

/**
 * Cargar productos al editar una tarea
 * @param {array} products - Array de productos
 */
function loadProductsToEdit(products) {
    // Limpiar productos anteriores del modo edit
    selectedProducts = selectedProducts.filter(p => p.mode !== 'edit');

    if (!products || products.length === 0) {
        renderProductsList('edit');
        return;
    }

    products.forEach(product => {
        selectedProducts.push({
            unique_id: ++productUniqueIdCounter,
            product_id: String(product.product_id),
            codigo: product.codigo,
            descripcion: product.descripcion,
            unit_type: product.unit_type,
            quantity: parseFloat(product.quantity),
            mode: 'edit'
        });
    });

    renderProductsList('edit');
}

/**
 * Renderizar productos en el modal de ver tarea
 * @param {array} products - Array de productos
 */
function renderProductsShow(products) {
    const container = $('#products_show_container');
    const tbody = $('#products_show_tbody');

    if (!products || products.length === 0) {
        container.hide();
        return;
    }

    container.show();
    tbody.empty();

    products.forEach(product => {
        tbody.append(`
            <tr>
                <td><strong>${product.codigo}</strong></td>
                <td>${product.descripcion}</td>
                <td><span class="badge bg-secondary">${product.unit_type}</span></td>
                <td class="text-end">${parseFloat(product.quantity).toFixed(2)}</td>
            </tr>
        `);
    });
}

// Limpiar productos al crear nueva tarea
$(document).on('click', '.create-job', function() {
    selectedProducts = selectedProducts.filter(p => p.mode !== 'create');
    renderProductsList('create');
});

// Limpiar productos al cerrar modales
$('#createjob').on('hidden.bs.modal', function () {
    selectedProducts = selectedProducts.filter(p => p.mode !== 'create');
});

$('#editjob').on('hidden.bs.modal', function () {
    selectedProducts = selectedProducts.filter(p => p.mode !== 'edit');
});

// ============================================
// FLATPICKR - Fecha y hora con intervalos de 15 min
// ============================================
var pickerCreate = null;
var pickerEdit = null;
var pickerEditArrival = null;
var pickerEditClosed = null;

// Configuración común de Flatpickr
var flatpickrConfig = {
    enableTime: true,
    dateFormat: "d/m/Y H:i",
    time_24hr: true,
    locale: "es",
    minuteIncrement: 15,
    allowInput: true,
    clickOpens: true,
    disableMobile: true,
    onChange: function(selectedDates, dateStr, instance) {
        setTimeout(function() {
            highlightBusinessHoursFlatpickr(instance);
        }, 50);
    }
};

// Inicializar Flatpickr cuando se abra el modal de CREAR
$('#createjob').on('shown.bs.modal', function () {
    var inputCreate = document.getElementById('visit_datetime_create');
    
    if (inputCreate && typeof flatpickr !== 'undefined') {
        if (pickerCreate) {
            pickerCreate.destroy();
            pickerCreate = null;
        }
        pickerCreate = flatpickr(inputCreate, flatpickrConfig);
    }
    
    // Refrescar selectpicker de cliente con un pequeño delay
    // para asegurar que el valor ya establecido se refleje visualmente
    setTimeout(function() {
        $('#client_id').selectpicker('refresh');
    }, 100);
});

// Inicializar Flatpickr cuando se abra el modal de EDITAR
$('#editjob').on('shown.bs.modal', function () {
    var inputEdit = document.getElementById('visit_datetime_edit');
    var inputArrivalEdit = document.getElementById('arrival_datetime_edit');
    var inputClosedEdit = document.getElementById('closed_datetime_edit');
    
    if (inputEdit && typeof flatpickr !== 'undefined') {
        if (pickerEdit) {
            pickerEdit.destroy();
            pickerEdit = null;
        }
        pickerEdit = flatpickr(inputEdit, flatpickrConfig);
    }

    if (inputArrivalEdit && typeof flatpickr !== 'undefined') {
        if (pickerEditArrival) {
            pickerEditArrival.destroy();
            pickerEditArrival = null;
        }
        pickerEditArrival = flatpickr(inputArrivalEdit, flatpickrConfig);
    }

    if (inputClosedEdit && typeof flatpickr !== 'undefined') {
        if (pickerEditClosed) {
            pickerEditClosed.destroy();
            pickerEditClosed = null;
        }
        pickerEditClosed = flatpickr(inputClosedEdit, flatpickrConfig);
    }
});

// Destruir al cerrar modales
$('#createjob').on('hidden.bs.modal', function () {
    if (pickerCreate) {
        pickerCreate.destroy();
        pickerCreate = null;
    }
    selectedProducts = selectedProducts.filter(p => p.mode !== 'create');
});

$('#editjob').on('hidden.bs.modal', function () {
    if (pickerEdit) {
        pickerEdit.destroy();
        pickerEdit = null;
    }
    if (pickerEditArrival) {
        pickerEditArrival.destroy();
        pickerEditArrival = null;
    }
    if (pickerEditClosed) {
        pickerEditClosed.destroy();
        pickerEditClosed = null;
    }
    selectedProducts = selectedProducts.filter(p => p.mode !== 'edit');
});

// Función para resaltar visualmente las horas laborales (8-17) en Flatpickr
function highlightBusinessHoursFlatpickr(instance) {
    if (!instance || !instance.calendarContainer) return;
    
    var hourInput = instance.calendarContainer.querySelector('.flatpickr-hour');
    if (!hourInput) return;
    
    var currentHour = parseInt(hourInput.value);
    
    if (!isNaN(currentHour)) {
        if (currentHour < 8 || currentHour > 17) {
            hourInput.setAttribute('data-non-work', 'true');
            hourInput.setAttribute('title', 'Fuera del horario laboral sugerido (8-17)');
        } else {
            hourInput.removeAttribute('data-non-work');
            hourInput.setAttribute('title', 'Horario laboral (8-17)');
        }
    }
}

// Evento para abrir modal de agregar productos directamente a una tarea
$('body').on('click', '.addproducts-job', function () {
    const jobId = $(this).data('id');
    const jobName = $(this).data('name');
    
    // Limpiar productos del modo 'add'
    selectedProducts = selectedProducts.filter(p => p.mode !== 'add');
    renderProductsList('add');
    
    // Configurar modal
    $('#formaddproducts').attr('action', app_url + "/jobs/" + jobId + "/products");
    $('#addproducts_task_name').text(jobName);
    
    // Abrir modal inmediatamente con spinner
    $('#modal-body-addproducts-roller').removeClass('d-none');
    $('#modal-body-addproducts').addClass('d-none');
    $('#modal-foot-addproducts').addClass('d-none');
    $('#addproducts').modal('show');
    
    // Cargar productos actuales de la tarea
    $.ajax({
        url: app_url + '/jobs/' + jobId + '/edit',
        type: 'GET',
        success: function(data) {
            // Cargar productos existentes
            if (data.products && data.products.length > 0) {
                data.products.forEach(product => {
                    selectedProducts.push({
                        unique_id: ++productUniqueIdCounter,
                        product_id: String(product.product_id),
                        codigo: product.codigo,
                        descripcion: product.descripcion,
                        unit_type: product.unit_type,
                        quantity: parseFloat(product.quantity),
                        mode: 'add'
                    });
                });
                renderProductsList('add');
            }
        },
        error: function(xhr, status, error) {
            // console.error('Error al cargar productos:', error);
            toastr["error"]("Error al cargar los productos de la tarea");
        },
        complete: function() {
            // Ocultar spinner y mostrar contenido
            $('#modal-body-addproducts-roller').addClass('d-none');
            $('#modal-body-addproducts').removeClass('d-none');
            $('#modal-foot-addproducts').removeClass('d-none');
        }
    });
});

/**
 * Seleccionar/deseleccionar todas las imágenes
 */
function toggleSelectAllImages(id_elemento) {
    const selectAllCheckbox = document.getElementById('selectAll-' + id_elemento);
    const checkboxes = document.querySelectorAll('#' + id_elemento + ' .image-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    updateImageSelection(id_elemento);
}

/**
 * Actualizar el estado de los botones según la selección
 */
function updateImageSelection(id_elemento) {
    const checkboxes = document.querySelectorAll('#' + id_elemento + ' .image-checkbox');
    const selectedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
    const totalCount = checkboxes.length;
    
    // Actualizar contador
    document.getElementById('selected-count-' + id_elemento).textContent = selectedCount + ' seleccionadas';
    
    // Habilitar/deshabilitar botones
    const downloadBtn = document.getElementById('btn-download-selected-' + id_elemento);
    const shareBtn = document.getElementById('btn-share-selected-' + id_elemento);
    
    if (selectedCount > 0) {
        downloadBtn.disabled = false;
        shareBtn.disabled = false;
    } else {
        downloadBtn.disabled = true;
        shareBtn.disabled = true;
    }
    
    // Actualizar checkbox "Seleccionar todas"
    const selectAllCheckbox = document.getElementById('selectAll-' + id_elemento);
    if (selectedCount === totalCount) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
    } else if (selectedCount > 0) {
        selectAllCheckbox.indeterminate = true;
    } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    }
}

/**
 * Descargar las imágenes seleccionadas
 */
function downloadSelectedImages(event, id_elemento) {
    // Prevenir comportamiento por defecto y propagación
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const checkboxes = document.querySelectorAll('#' + id_elemento + ' .image-checkbox:checked');
    
    if (checkboxes.length === 0) {
        toastr["warning"]("Selecciona al menos una imagen");
        return;
    }
    
    toastr["info"]("Descargando " + checkboxes.length + " imagen(es)...");
    
    // Descargar cada imagen con un pequeño delay para evitar bloqueo del navegador
    checkboxes.forEach((checkbox, index) => {
        setTimeout(() => {
            const imageUrl = checkbox.getAttribute('data-image-url');
            const imageName = checkbox.getAttribute('data-image-name');
            const fullUrl = window.location.origin + imageUrl;
            
            const a = document.createElement('a');
            a.href = fullUrl;
            a.download = imageName || 'imagen_' + (index + 1) + '.jpg';
            a.target = '_blank';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }, index * 300); // 300ms entre cada descarga
    });
    
    setTimeout(() => {
        toastr["success"]("Descargas iniciadas");
    }, checkboxes.length * 300);
}

/**
 * Compartir las imágenes seleccionadas
 */
async function shareSelectedImages(event, id_elemento) {
    // Prevenir comportamiento por defecto y propagación
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const checkboxes = document.querySelectorAll('#' + id_elemento + ' .image-checkbox:checked');
    
    if (checkboxes.length === 0) {
        toastr["warning"]("Selecciona al menos una imagen");
        return;
    }
    
    // Obtener URLs de las imágenes seleccionadas
    const imageUrls = Array.from(checkboxes).map(cb => {
        return {
            url: window.location.origin + cb.getAttribute('data-image-url'),
            name: cb.getAttribute('data-image-name')
        };
    });
    
    // Verificar si el navegador soporta Web Share API con archivos
    if (navigator.canShare && navigator.share) {
        try {
            toastr["info"]("Preparando " + imageUrls.length + " imagen(es) para compartir...");
            
            // Descargar todas las imágenes como blobs
            const filesPromises = imageUrls.map(async (img, index) => {
                const response = await fetch(img.url);
                const blob = await response.blob();
                return new File([blob], img.name || `imagen_${index + 1}.jpg`, { type: blob.type });
            });
            
            const files = await Promise.all(filesPromises);
            
            // Verificar si puede compartir archivos
            if (navigator.canShare({ files })) {
                await navigator.share({
                    title: 'Imágenes de Tarea',
                    text: 'Compartiendo ' + files.length + ' imagen(es) de la tarea',
                    files: files
                });
                
                toastr["success"]("Imágenes compartidas exitosamente");
            } else {
                throw new Error('No se pueden compartir archivos');
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                // console.error('Error al compartir:', error);
                // Fallback: copiar URLs al portapapeles
                fallbackShareMultiple(imageUrls);
            }
        }
    } else {
        // Fallback para navegadores sin Web Share API
        fallbackShareMultiple(imageUrls);
    }
}

/**
 * Método alternativo para compartir múltiples imágenes (copiar URLs al portapapeles)
 */
function fallbackShareMultiple(imageUrls) {
    const urls = imageUrls.map(img => img.url).join('\n');
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(urls).then(() => {
            toastr["info"]("URLs de las imágenes copiadas al portapapeles");
        }).catch(err => {
            // console.error('Error al copiar al portapapeles:', err);
            toastr["error"]("No se pudo compartir las imágenes");
        });
    } else {
        // Método antiguo para copiar al portapapeles
        const textArea = document.createElement('textarea');
        textArea.value = urls;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            toastr["info"]("URLs de las imágenes copiadas al portapapeles");
        } catch (err) {
            // console.error('Error al copiar:', err);
            toastr["error"]("No se pudo compartir las imágenes");
        }
        document.body.removeChild(textArea);
    }
}

// Limpiar productos al cerrar modal
$('#addproducts').on('hidden.bs.modal', function () {
    selectedProducts = selectedProducts.filter(p => p.mode !== 'add');
});

// Submit del formulario de agregar productos
$('#formaddproducts').on('submit', function(e) {
    e.preventDefault();
    
    const productsForAdd = selectedProducts.filter(p => p.mode === 'add');
    
    if (productsForAdd.length === 0) {
        toastr["warning"]("Debe agregar al menos un producto");
        return false;
    }
    
    // Enviar formulario
    this.submit();
});

// ============================================
// FUNCIONES PARA GENERACIÓN DE PDF
// ============================================

let currentJobDataForPdf = null;

/**
 * Abre el modal de configuración de PDF
 */
function openPdfConfigModal() {
    // Obtener el ID del trabajo actual desde el modal
    const jobId = $('#showjob').data('job-id');
    
    if (!jobId) {
        toastr["error"]("No se pudo obtener el ID del trabajo");
        return;
    }
    
    // Verificar que tengamos los datos del trabajo cargados
    if (!currentJobDataForPdf) {
        toastr["error"]("No hay datos del trabajo disponibles");
        return;
    }
    
    // Cargar notas del trabajo si no están en los datos actuales
    if (!currentJobDataForPdf.notes) {
        showSavingAlert();
        
        $.ajax({
            url: '/jobs/notes/' + jobId,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                // El endpoint devuelve {data: [...notas]}
                currentJobDataForPdf.notes = response.data || [];
                populatePdfConfigModal(currentJobDataForPdf);
                $('#pdfConfigModal').modal('show');
            },
            error: function(xhr) {
                // Aunque falle, permitir abrir el modal sin notas
                currentJobDataForPdf.notes = [];
                populatePdfConfigModal(currentJobDataForPdf);
                $('#pdfConfigModal').modal('show');
            }
        }).always(function() {
            closeSwal();
        });
    } else {
        // Llenar el modal de configuración con los datos
        populatePdfConfigModal(currentJobDataForPdf);
        
        // Abrir el modal de configuración
        $('#pdfConfigModal').modal('show');
    }
}

/**
 * Llena el modal de configuración con las notas e imágenes del trabajo
 */
function populatePdfConfigModal(jobData) {
    // Limpiar selecciones previas
    $('#notes-selection').empty();
    $('#images-selection').empty();
    
    // Guardar job ID en el modal (puede estar en jobData.id o jobData.job.id)
    const jobId = jobData.id || jobData.job.id;
    $('#pdfConfigModal').data('job-id', jobId);
    
    // Llenar notas
    if (jobData.notes && jobData.notes.length > 0) {
        jobData.notes.forEach((note, index) => {
            // El endpoint devuelve 'created' ya formateado
            const formattedDate = note.created || '';
            
            const noteHtml = `
                <div class="form-check mb-2">
                    <input class="form-check-input note-checkbox" type="checkbox" value="${note.id}" id="note_${note.id}">
                    <label class="form-check-label" for="note_${note.id}">
                        <small class="text-muted">${formattedDate}</small><br>
                        <span class="text-truncate d-inline-block" style="max-width: 400px;">${note.note || ''}</span>
                    </label>
                </div>
            `;
            $('#notes-selection').append(noteHtml);
        });
    } else {
        $('#notes-selection').html('<p class="text-muted mb-0"><small>No hay notas disponibles</small></p>');
        $('#include_notes').prop('disabled', true).prop('checked', false);
    }
    
    // Llenar imágenes
    if (jobData.files && jobData.files.length > 0) {
        jobData.files.forEach((file, index) => {
            // Usar el campo correcto según lo que devuelve el backend (name o ruta)
            const imagePath = file.name || file.ruta || '';
            if (!imagePath) {
                // console.warn('Archivo sin ruta:', file);
                return; // Saltar este archivo
            }
            
            // Si el nombre tiene extensión es archivo local, sino es ID de Google Drive
            const hasExtension = imagePath.includes('.');
            const imageUrl = hasExtension
                ? '/storage/' + imagePath.replace(/\\/g, '/')
                : '/api/drive-file/' + imagePath;
            const imageHtml = `
                <div class="col-4 col-md-3">
                    <div class="position-relative image-selector" data-file-id="${file.id}">
                        <img src="${imageUrl}" class="img-fluid rounded" style="cursor: pointer; border: 3px solid #198754; transition: all 0.3s;" onclick="toggleImageSelection(${file.id})">
                        <input type="checkbox" class="image-checkbox position-absolute" value="${file.id}" id="image_${file.id}" checked style="display: none;">
                        <div class="position-absolute top-0 end-0 m-2 bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;" id="check_${file.id}">
                            <i class="fas fa-check"></i>
                        </div>
                    </div>
                </div>
            `;
            $('#images-selection').append(imageHtml);
        });
        
        // NO es necesario marcar visualmente ya que se agregan con el estilo correcto
    } else {
        $('#images-selection').html('<p class="text-muted mb-0"><small>No hay imágenes disponibles</small></p>');
        $('#include_images').prop('disabled', true).prop('checked', false);
    }
}

/**
 * Alterna la selección de una imagen en el modal de PDF
 */
function toggleImageSelection(fileId) {
    const checkbox = $(`#image_${fileId}`);
    const img = $(`.image-selector[data-file-id="${fileId}"] img`);
    const checkIcon = $(`#check_${fileId}`);
    const container = $(`.image-selector[data-file-id="${fileId}"]`);
    
    // Toggle checkbox
    checkbox.prop('checked', !checkbox.prop('checked'));
    
    // Actualizar visualización
    if (checkbox.prop('checked')) {
        img.css({
            'border-color': '#198754',
            'opacity': '1'
        });
        checkIcon.show();
        container.removeClass('image-deselected');
    } else {
        img.css({
            'border-color': '#dc3545',
            'opacity': '0.4'
        });
        checkIcon.hide();
        container.addClass('image-deselected');
    }
}

/**
 * Habilita/deshabilita la sección de selección de notas
 */
function toggleNotesSection() {
    const includeNotes = $('#include_notes').prop('checked');
    
    if (includeNotes) {
        $('#notes-selection .form-check-input').prop('disabled', false);
        $('#notes-selection').removeClass('opacity-50');
    } else {
        $('#notes-selection .form-check-input').prop('disabled', true);
        $('#notes-selection').addClass('opacity-50');
    }
}

/**
 * Habilita/deshabilita la sección de selección de imágenes
 */
function toggleImagesSection() {
    const includeImages = $('#include_images').prop('checked');
    
    if (includeImages) {
        $('#images-selection .image-selector').css('pointer-events', 'auto');
        $('#images-selection').removeClass('opacity-50');
    } else {
        $('#images-selection .image-selector').css('pointer-events', 'none');
        $('#images-selection').addClass('opacity-50');
    }
}

/**
 * Selecciona/deselecciona todas las notas
 */
function toggleAllNotes() {
    const allChecked = $('.note-checkbox:checked').length === $('.note-checkbox').length;
    $('.note-checkbox').prop('checked', !allChecked);
}

/**
 * Selecciona/deselecciona todas las imágenes
 */
function toggleAllImages() {
    const allChecked = $('.image-checkbox:checked').length === $('.image-checkbox').length;
    
    $('.image-checkbox').each(function() {
        const fileId = $(this).val();
        const shouldCheck = !allChecked;
        
        $(this).prop('checked', shouldCheck);
        
        const img = $(`.image-selector[data-file-id="${fileId}"] img`);
        const checkIcon = $(`#check_${fileId}`);
        const container = $(`.image-selector[data-file-id="${fileId}"]`);
        
        if (shouldCheck) {
            img.css({
                'border-color': '#198754',
                'opacity': '1'
            });
            checkIcon.show();
            container.removeClass('image-deselected');
        } else {
            img.css({
                'border-color': '#dc3545',
                'opacity': '0.4'
            });
            checkIcon.hide();
            container.addClass('image-deselected');
        }
    });
}

/**
 * Genera el PDF con la configuración seleccionada
 * @param {string} action - 'view' para abrir en nueva pestaña, 'download' para descargar
 */
function sanitizePdfFilenamePart(value) {
    return String(value || '')
        .trim()
        .replace(/[\\/:*?"<>|]+/g, '')
        .replace(/\s+/g, '_')
        .replace(/_+/g, '_')
        .replace(/^_+|_+$/g, '');
}

function formatPdfVisitDateForFilename(dateValue) {
    if (!dateValue) {
        return '';
    }

    var rawDate = String(dateValue).trim();
    var normalized = rawDate.split('T')[0].split(' ')[0];

    if (/^\d{4}-\d{2}-\d{2}$/.test(normalized)) {
        var partsIso = normalized.split('-');
        return partsIso[0].slice(-2) + '-' + partsIso[1] + '-' + partsIso[2];
    }

    if (/^\d{2}\/\d{2}\/\d{2,4}$/.test(normalized)) {
        var partsLocal = normalized.split('/');
        var day = partsLocal[0].padStart(2, '0');
        var month = partsLocal[1].padStart(2, '0');
        var year = partsLocal[2].length === 4 ? partsLocal[2].slice(-2) : partsLocal[2];
        return year + '-' + month + '-' + day;
    }

    var parsedDate = new Date(rawDate);
    if (!isNaN(parsedDate.getTime())) {
        var yy = String(parsedDate.getFullYear()).slice(-2);
        var mm = String(parsedDate.getMonth() + 1).padStart(2, '0');
        var dd = String(parsedDate.getDate()).padStart(2, '0');
        return dd + '-' + mm + '-' + yy;
    }

    return normalized;
}

function buildPdfFileName(jobData, jobId) {
    var clientName = jobData.client_name ||
        (jobData.job && jobData.job.client_name) ||
        (jobData.client && [jobData.client.first_name, jobData.client.last_name].filter(Boolean).join(' ')) ||
        'cliente';

    var orderNumber = jobData.colppy_budget_number ||
        jobData.order_number ||
        jobData.job_number ||
        jobData.nro_orden ||
        (jobData.job && (jobData.job.colppy_budget_number || jobData.job.order_number || jobData.job.job_number)) ||
        jobId;

    var visitDate = formatPdfVisitDateForFilename(
        jobData.visit_datetime ||
        (jobData.job && jobData.job.visit_datetime) ||
        jobData.visit ||
        (jobData.job && jobData.job.visit)
    );

    var fileNameParts = [
        sanitizePdfFilenamePart(clientName),
        sanitizePdfFilenamePart(orderNumber),
        sanitizePdfFilenamePart(visitDate)
    ].filter(function(part) {
        return part !== '';
    });

    return fileNameParts.join('_') + '.pdf';
}

function generatePDF(action) {
    action = action || 'view'; // Por defecto abrir en pestaña
    
    const jobId = $('#pdfConfigModal').data('job-id');
    
    if (!jobId) {
        toastr["error"]("No se pudo obtener el ID del trabajo");
        return;
    }
    
    // Recopilar configuración
    const config = {
        include_description: $('#include_description').prop('checked'),
        include_products: $('#include_products').prop('checked'),
        include_technicians: $('#include_technicians').prop('checked'),
        include_arrival_time: $('#include_arrival_time').prop('checked'),
        include_departure_time: $('#include_departure_time').prop('checked'),
        include_notes: $('#include_notes').prop('checked'),
        include_images: $('#include_images').prop('checked')
    };
    
    // Obtener IDs de notas seleccionadas
    if (config.include_notes) {
        const selectedNoteIds = [];
        $('.note-checkbox:checked').each(function() {
            selectedNoteIds.push($(this).val());
        });
        config.note_ids = selectedNoteIds;
    }
    
    // Obtener IDs de imágenes seleccionadas
    if (config.include_images) {
        const selectedImageIds = [];
        $('.image-checkbox:checked').each(function() {
            selectedImageIds.push($(this).val());
        });
        config.image_ids = selectedImageIds;
    }
    
    // Validar que se haya seleccionado al menos algo
    const hasContent = config.include_description || 
                       config.include_products || 
                       config.include_technicians || 
                       config.include_arrival_time || 
                       config.include_departure_time || 
                       (config.include_notes && config.note_ids && config.note_ids.length > 0) ||
                       (config.include_images && config.image_ids && config.image_ids.length > 0);
    
    if (!hasContent) {
        toastr["warning"]("Debe seleccionar al menos un elemento para incluir en el PDF");
        return;
    }
    
    // Mostrar loading en el botón correspondiente
    const $viewBtn = $('#pdfConfigModal .btn-primary');
    const $downloadBtn = $('#pdfConfigModal .btn-success');
    const $activeBtn = action === 'download' ? $downloadBtn : $viewBtn;
    const originalBtnText = $activeBtn.html();
    const loadingText = action === 'download' ? 'Descargando PDF...' : 'Generando PDF...';
    
    // Deshabilitar ambos botones
    $viewBtn.prop('disabled', true);
    $downloadBtn.prop('disabled', true);
    $activeBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>' + loadingText);
    
    // Llamar al endpoint para generar el PDF
    $.ajax({
        url: '/jobs/' + jobId + '/generate-pdf',
        method: 'POST',
        data: config,
        dataType: 'json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.success && response.pdf) {
                // Generar nombre de archivo con cliente, orden y fecha de visita
                const fileName = buildPdfFileName(currentJobDataForPdf || {}, jobId);
                
                // Ejecutar acción según el parámetro
                if (action === 'download') {
                    downloadPDF(response.pdf, fileName);
                    toastr["success"]("PDF descargado exitosamente");
                } else {
                    openPDFInNewTab(response.pdf, fileName);
                    toastr["success"]("PDF abierto en nueva pestaña");
                }
                
                // Cerrar el modal
                $('#pdfConfigModal').modal('hide');
            } else {
                toastr["error"]("Error al generar el PDF");
            }
        },
        error: function(xhr) {
            toastr["error"]("Error al generar el PDF");
            // console.error(xhr);
        },
        complete: function() {
            // Restaurar botones
            $activeBtn.prop('disabled', false).html(originalBtnText);
            if (action === 'download') {
                $viewBtn.prop('disabled', false);
            } else {
                $downloadBtn.prop('disabled', false);
            }
        }
    });
}

/**
 * Descarga un PDF desde datos base64
 */
function downloadPDF(base64Data, fileName) {
    try {
        // Convertir base64 a blob
        const byteCharacters = atob(base64Data);
        const byteNumbers = new Array(byteCharacters.length);
        
        for (let i = 0; i < byteCharacters.length; i++) {
            byteNumbers[i] = byteCharacters.charCodeAt(i);
        }
        
        const byteArray = new Uint8Array(byteNumbers);
        const blob = new Blob([byteArray], { type: 'application/pdf' });
        
        // Crear enlace de descarga
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = fileName;
        
        // Simular clic para descargar
        document.body.appendChild(link);
        link.click();
        
        // Limpiar
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    } catch (error) {
        // console.error('Error al descargar PDF:', error);
        toastr["error"]("Error al descargar el PDF");
    }
}

/**
 * Abre un PDF en una nueva pestaña desde datos base64
 */
function openPDFInNewTab(base64Data, fileName) {
    try {
        // Convertir base64 a blob
        const byteCharacters = atob(base64Data);
        const byteNumbers = new Array(byteCharacters.length);
        
        for (let i = 0; i < byteCharacters.length; i++) {
            byteNumbers[i] = byteCharacters.charCodeAt(i);
        }
        
        const byteArray = new Uint8Array(byteNumbers);
        const blob = new Blob([byteArray], { type: 'application/pdf' });
        
        // Crear URL del blob
        const url = window.URL.createObjectURL(blob);
        
        // Abrir en nueva pestaña
        window.open(url, '_blank');
        
        // Limpiar URL después de un tiempo (dar tiempo a que se abra)
        setTimeout(() => {
            window.URL.revokeObjectURL(url);
        }, 1000);
    } catch (error) {
        // console.error('Error al abrir PDF:', error);
        toastr["error"]("Error al abrir el PDF");
    }
}
