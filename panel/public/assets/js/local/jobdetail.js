$(document).ready(function() {
    $('body').on('click','.update',function(){ 
        $('#formeditjob').attr('action',app_url+"/jobs/"+$(this).data('id'));

        form = document.getElementById("formeditjob");
        $( form.elements ).each(function( index ) {
            if($(this).attr('name') != '_method' && $(this).attr('name') != '_token'){
                $(this).val('');
            } 
        });

        $('#editjob').modal('show');

        $('#modal-body-edit-job-roller').removeClass('d-none');
        $('#modal-body-edit-job-error').addClass('d-none');
        $('#modal-body-edit-job').addClass('d-none');
        $('#modal-footer-edit-job').addClass('d-none');

        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/jobs/'+$(this).data('id')+'/edit',
            type : 'GET',
            done : function(response) { $('#modal-body-edit-job-error').removeClass('d-none'); },
            error : function(jqXHR,textStatus,errorThrown) { $('#modal-body-edit-job-error').removeClass('d-none'); },
            success : function(data) {
                $.each( data , function( index, value ) {
                    $( form.elements ).each(function( b ) {
                        if($(this).attr('name') == index){
                            $(this).val(value);
                        }
                    });
                });
                $('#modal-body-edit-job').removeClass('d-none');
                $('#modal-footer-edit-job').removeClass('d-none');
            }
        }).always(function() {
            $('#modal-body-edit-job-roller').addClass('d-none');
            navigator.geolocation.getCurrentPosition(geosuccess);
        });
    });
    $('body').on('click','.read',function(){ 
        form = document.getElementById("formshowjob");
        $( form.elements ).each(function( index ) {
            $(this).val('');
        });
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

                $.each( data , function( index, value ) {
                    $( form.elements ).each(function( b ) {
                        if($(this).attr('name') == index){
                            $(this).val(value);
                            $(this).css('box-shadow', 'inset 0px 0px 1px 1px green');
                        }
                    });
=";
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
                });

                $('#modal-body-show-job').removeClass('d-none');
            }
        }).always(function() {
            $('#modal-body-show-job-roller').addClass('d-none');
        });
    });
    $('body').on('click','.delete',function(){ 
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
                $('#formdestroy').attr('action',app_url+"/jobs/"+$(this).data('id'));
                $('#formdestroy').submit();
            }
        });
    });
    $('body').on('click',"#btn-update-job",function () {
        var error = 0

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
        var idtarea = $(this).data('id');
        navigator.geolocation.getCurrentPosition(geosuccess);
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
                }
            }
        });
    });
    $('body').on('click',".addnote", function(){
        var idtarea = $(this).data('id');
        var nombre = $(this).data('name');
        navigator.geolocation.getCurrentPosition(geosuccess);
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

                            if(window.location.href.includes('jobs') ){
                                callregister('/jobs/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
                            }
                        }
                    });
                } else {
                    toastr["error"]("No hay detalle para guardar sobre la nota.");
                }
            }
        });

    });
    $('body').on('click',".btn-notes", function(){
        var idtarea = $(this).data('id');
        var nombre = $(this).data('name');

        $('#viewjobsnotes').modal('show');
        $('#titlenotas').text(nombre);
        $('#tablenotes_body').empty();

        $('#modal-body-view-jobsnotes-roller').removeClass('d-none');
        $('#modal-body-view-jobsnotes-error').addClass('d-none');
        $('#modal-body-view-jobsnotes').addClass('d-none');

        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/jobs/notes/'+idtarea,
            type : 'GET',
            done : function(response) { $('#modal-body-view-jobsnotes-error').removeClass('d-none'); },
            error : function(jqXHR,textStatus,errorThrown) { $('#modal-body-view-jobsnotes-error').removeClass('d-none'); },
            success : function(data) {
                body='';
                $.each(data.datos, function (key, val) {
                    body += `<tr>
                        <td class="text-wrap text-start">${val.note.replaceAll('\n','<br>')}</td>
                        <td class="align-middle">${val.created}</td>
                        <td class="align-middle">`
                        if ( data.permissions.includes('delete') ){
                            body += `<a href="javascript:void(0);" data-id="${val.id}" class="btn btn-sm btn-danger deletenote">
                                <i class="flaticon-delete"></i>
                            </a>`;
                        }
                        body += `</td>
                    </tr>`;
                });
                $('#tablenotes_body').append(body);
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
                parent.remove();
                $.ajax({contenttype : 'application/json; charset=utf-8',
                    url : $('meta[name="app_url"]').attr('content')+'/jobs/destroynote/'+idtarea,
                    type : 'GET',
                    success : function(data) {
                        toastr["warning"]("Nota eliminada correctamente.");
                        if(window.location.href.includes('jobs') ){
                            callregister('/jobs/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
                        }
                    }
                });

            }
        });
    });

    $('body').on('click','.closetask',function(){ 
        var idtarea = $(this).data('id');
        var nombre = $(this).data('name');
        form = document.getElementById("formclosedjob");
        $( form.elements ).each(function( index ) {
            $(this).val('');
        });

        $('#titleclosedjob').text("Cerrar tarea: "+nombre);
        $('#closedjob').modal('show');

        $.ajax({contenttype : 'application/json; charset=utf-8',
          url : $('meta[name="app_url"]').attr('content')+'/jobs/'+idtarea+'/edit',
          type : 'GET',
          done : function(response) { },
          error : function(jqXHR,textStatus,errorThrown) {  },
          success : function(data) {
            $.each( data , function( index, value ) {
                $( form.elements ).each(function( b ) {
                    if($(this).attr('name') == index){
                        $(this).val(value);
                    }
                });
            });
            navigator.geolocation.getCurrentPosition(geosuccess);
          }
        }).always(function() {
          // $('#modal-body-show-job-roller').addClass('d-none');
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
        if (error > 0) {
            toastr["error"]("Complete la observación para cerrar la tarea.")
        } else {
            document.getElementById("formclosedjob").submit();
        }
    });
});