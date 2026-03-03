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

                // Cargar productos relacionados
                loadProductsToEdit(data.products);

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

                // Mostrar productos relacionados
                renderProductsShow(data.products);

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
                                    console.error('Error en búsqueda de productos:', error);
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
            console.error('Error al cargar productos:', error);
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
