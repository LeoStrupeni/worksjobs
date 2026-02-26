var controladorTiempo = 3000;
var valorbuscado = '';
// Array global para mantener los archivos seleccionados
var selectedFiles = [];

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
        $('#formeditjob').attr('action',app_url+"/jobs/"+$(this).data('id'));
        
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
            if($(this).attr('name') != '_method' && $(this).attr('name') != '_token'){
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
        $('#modal-body-edit-job').addClass('d-none');
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

                // Poblar técnicos asignados en el select de edición
                setTechnicianSelect('#technician_ids_edit', data.technicians);

                $('#modal-body-edit-job').removeClass('d-none');
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
        
        $('#showjob').modal('show');

        $('#modal-body-show-job-roller').removeClass('d-none');
        $('#modal-body-show-job-error').addClass('d-none');
        $('#modal-body-show-job').addClass('d-none');

        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/jobs/'+$(this).data('id')+'/edit',
            type : 'GET',
            done : function(response) { $('#modal-body-edit-job-error').removeClass('d-none'); },
            error : function(jqXHR,textStatus,errorThrown) { $('#modal-body-edit-job-error').removeClass('d-none'); },
            success : function(data) {

                viewjob(data,form,'showjob');
                viewfiles(data,'lightgalleryShow');

                // Mostrar técnicos asignados en el panel de show
                renderTechniciansShow(data.technicians);

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
        $("#lightgalleryClosedNone").empty();
        $("#lightgalleryClosed").empty();
        form = document.getElementById("formclosedjob");
        $( form.elements ).each(function( index ) {
            if($(this).attr('name') != '_method' && $(this).attr('name') != '_token'){
                $(this).val('');
            } 
        });

        $('#titleclosedjob').text("Cerrar tarea: "+nombre);
        $('#closedjob').modal('show');

        $('#modal-body-closed-job-roller').removeClass('d-none');
        $('#modal-body-closed-job-error').addClass('d-none');
        $('#modal-body-closed-job').addClass('d-none');
        $('#modal-foot-closed-job').addClass('d-none');

        $.ajax({contenttype : 'application/json; charset=utf-8',
          url : $('meta[name="app_url"]').attr('content')+'/jobs/'+idtarea+'/edit',
          type : 'GET',
          done : function(response) { },
          error : function(jqXHR,textStatus,errorThrown) {  },
          success : function(data) {

            viewjob(data,form,'closedjob');
            viewfiles(data,'lightgalleryClosed');

            // Poblar técnicos asignados en el select de cierre
            setTechnicianSelect('#technician_ids_closed', data.technicians);
            $('#technician_ids_closed_error').addClass('d-none');

            $('#modal-body-closed-job').removeClass('d-none');
            $('#modal-foot-closed-job').removeClass('d-none');
          }
        }).always(function() {
            $('#modal-body-closed-job-roller').addClass('d-none');
            getGeolocation();
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
            // console.log(valor.length);
            if(valorbuscado != valor && valor.length > 0){
                valorbuscado = valor;

                clearInterval(controladorTiempo);
                controladorTiempo = setInterval(function(){
                    let selectClients = $('select#client_id');
                        selectClients.find('option').remove(); 
                    $('#client_id').empty();
                    $('#client_id').selectpicker('render');
                    $('#spinner1').removeClass('d-none');

                    $.ajax({
                        // contenttype: 'application/json; charset=utf-8',
                        url: "/api/searchvar",
                        type: 'POST',
                        data: {
                            search: valor,
                            tipo: 'clients'
                        },
                        success : function(data) {
                            datos = data;

                            $.each(datos, function() {
                                var option = `<option value="${this.id}">${this.first_name} ${this.last_name ?? ''}</option>`;
                                selectClients.append(option);
                            });
                            selectClients.selectpicker('refresh');
                            
                        }
                    }).always(function() {
                        $('#spinner1').addClass('d-none');
                    });
                    clearInterval(controladorTiempo); //Limpio el intervalo
                }, 400);
            }
        }
    });

    // Refrescar selectpicker de clientes después de seleccionar para corregir visualización
    $('body').on('change', '#client_id', function() {
        $(this).selectpicker('refresh');
    });

    function viewjob(data,form,origen){
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

    function viewfiles(data,id_elemento){
        $.each( data.files , function( index, value ) {
            let imagen = `<div class="text-center" style="width: 120px;">
                <a class="gallery" href="/storage/${this.name}">
                    <img src="/storage/${this.name}" style='border-radius:.5rem; height: 100px; width: 100px;'>
                </a>`
                if(id_elemento == 'lightgalleryEdit' || id_elemento == 'lightgalleryFiles'){
                    imagen += `<span class="btn-danger-pro" 
                        style=" position: relative; top: -25px; right: -40px; cursor: pointer;"
                        onclick="deleteimg(this,${this.id},'${id_elemento}')">
                        <i class="fas fa-trash me-2"></i>
                    </span>`;
                }    
            imagen += `<div>`;

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
    $('#address_id').empty();
    $('#address_id').selectpicker('render');
    $('#spinner2').removeClass('d-none');

    $.ajax({
        // contenttype: 'application/json; charset=utf-8',
        url: "/api/searchvar",
        type: 'POST',
        data: {
            search: client_id,
            tipo: 'address'
        },
        success : function(data) {
            $('#address_id').append('<option></option>');
            datos = data;
            $.each(datos, function() {
                var option = `<option value="${this.id}">${this.address_street} ${this.address_nro ?? ''} ${this.city ?? ''} ${this.address_detail ?? ''}</option>`;
                $('#address_id').append(option);
            });
            $('#address_id').selectpicker('refresh');
            
        }
    }).always(function() {
        $('#spinner2').addClass('d-none');
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

            $.each( data , function( index, value ) {
                let imagen = `<div class="text-center" style="width: 120px;"> 
                    <a class="gallery" href="/storage/${this.name}">
                        <img src="/storage/${this.name}" style='border-radius:.5rem; height: 100px; width: 100px;'>
                    </a>
                    <span class="btn-danger-pro" 
                        style=" position: relative; top: -25px; right: -40px;"
                        onclick="deleteimg(this,${this.id},'${id_elemento}')">
                        <i class="fas fa-trash me-2"></i>
                    </span>
                <div>`;
                
                $("#"+id_elemento+"None").append(imagen);     
            })
            var gallery = $('#'+id_elemento)
                gallery.lightGallery();
                gallery.data('lightGallery').destroy(true);
                galleryimagen(id_elemento);  

        }
    }).always(function() {
        closeSwal();
    });
}
