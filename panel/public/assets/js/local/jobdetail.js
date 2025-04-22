var controladorTiempo = 3000;
var valorbuscado = '';
$(document).ready(function() {
    navigator.geolocation.getCurrentPosition(geosuccess);
    $('body').on('click','.create-job',function(){ 
        $('#name').val('');
        $('#description').val('');
        $('#createjob').modal('show');

        navigator.geolocation.getCurrentPosition(geosuccess);
    });
    $('body').on('click','.update-job',function(){ 
        $("#lightgalleryEditNone").empty();
        $("#lightgalleryEdit").empty();     
        $('#formeditjob').attr('action',app_url+"/jobs/"+$(this).data('id'));

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
        $('#modal-footer-edit-job').addClass('d-none');

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

                $('#modal-body-edit-job').removeClass('d-none');
                $('#modal-footer-edit-job').removeClass('d-none');
            }
        }).always(function() {
            $('#modal-body-edit-job-roller').addClass('d-none');
            navigator.geolocation.getCurrentPosition(geosuccess);
        });
    });
    $('body').on('click','.read-job',function(){ 
        $("#lightgalleryShow").empty();
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

                viewjob(data,form,'showjob');
                viewfiles(data,'lightgalleryShow');

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
                $('#formdestroy').attr('action',app_url+"/jobs/"+$(this).data('id'));
                $('#formdestroy').submit();
            }
        });
    });
    $('body').on('click',"#btn-create-job",function () {
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
            toastr["error"]("Debe completar los datos correctamente.")
        } else {
            document.getElementById("formnewjob").submit();
        }
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
        });
    });
    $('body').on('click',".backarrival", function(){
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
    $('body').on('click',".addfiles", function(){
        var idtarea = $(this).data('id');
        var nombre = $(this).data('name');

        $("#lightgalleryFilesNone").empty();
        $("#lightgalleryFiles").empty();     

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

        $.ajax({contenttype : 'application/json; charset=utf-8',
          url : $('meta[name="app_url"]').attr('content')+'/jobs/'+idtarea+'/edit',
          type : 'GET',
          done : function(response) { },
          error : function(jqXHR,textStatus,errorThrown) {  },
          success : function(data) {

            viewjob(data,form,'closedjob');
            viewfiles(data,'lightgalleryClosed');
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
    $('.bs-searchbox').children().keyup(function (e) {
        valor = this.value;
        if ($($($(e.target)).parent().parent().parent()[0]).hasClass('searchvar')) {            
            console.log(valor.length);
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
                if(id_elemento == 'lightgalleryEdit'){
                    imagen += `<span class="btn-danger-pro" 
                        style=" position: relative; top: -25px; right: -40px;"
                        onclick="deleteimg(this,${this.id},'${id_elemento}')">
                        <i class="fas fa-trash"></i>
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
                var option = `<option value="${this.id}">${this.address_detail ?? ''} ${this.address_street} ${this.address_nro ?? ''} ${this.city ?? ''}</option>`;
                $('#address_id').append(option);
            });
            $('#address_id').selectpicker('refresh');
            
        }
    }).always(function() {
        $('#spinner2').addClass('d-none');
    });

}

function scaleImage(inputnew,id_elemento) {    
    $("#"+id_elemento).empty();
    if(id_elemento!='lightgallery'){
        $("#"+id_elemento).append($("#"+id_elemento+"None").html());
    }

    form = inputnew.form;
    idform = form.id;
    inputfiles = form.elements['images[]'];
    inputnone = 0;
    $( inputfiles ).each(function( i, input ) { if (input.files.length == 0) {inputnone++;} });
    $( inputfiles ).each(function( i, input ) {
        if (input.files.length > 0) {
            for (let index = 0; index < input.files.length; index++) {
                var cant = input.files.length;
                let file = input.files[index];
                if (file != undefined) {
                    let filetype = file.type;
                    let reader = new FileReader();
                    reader.addEventListener("load", function () {
                        let image = new Image();
        
                        image.addEventListener("load", function () {
                            let width = Math.floor(image.width / 2);      // Make it an integer, just in case
                            let height = Math.floor(image.height / 2);    // Make it an integer, just in case
        
                            let canvas = document.createElement("canvas");
                            canvas.width = width;
                            canvas.height = height;
        
                            let context = canvas.getContext("2d");
                            context.drawImage(image, 0, 0, width, height);
        
                            let url = canvas.toDataURL(filetype, 0.6);
                            let link = document.createElement("img");
                            link.src = url;

                            let imagen = `<div class="text-center" style="width: 120px;"> 

                                <a class="gallery" href="${url}">
                                    <img src="${url}" style='border-radius:.5rem; height: 100px; width: 100px;'>
                                </a>
                                
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
            if(inputnone < 1){
                $(inputnew.parentNode.parentNode.parentNode).append(`<div class="col-12 mb-2">
                    <div style="position: relative;padding: 0;">
                        <input class="form-control form-control-sm" type="file" name="images[]" accept="video/*,image/*" onchange="scaleImage(this);">
                        <span class="btn-danger-pro" style="position: absolute; height: 100%; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-pack: center;-ms-flex-pack: center;justify-content: center;top: 4px;right: 10px; " onclick="this.parentNode.children[0].value='';scaleImage(this.parentNode.children[0]);">
                            <span><i class="fas fa-trash"></i></span>
                        </span>
                    </div>
                </div>`);
                inputnone++;
            }
        }
    });
}

function galleryimagen(id_elemento) {
	$("#"+id_elemento).lightGallery({
		selector: 'div .gallery',
		zoom: true,
		download: false,
	});
}

function deleteimg(e,idjob,id_elemento){
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
                        <i class="fas fa-trash"></i>
                    </span>
                <div>`;
                
                $("#"+id_elemento+"None").append(imagen);     
            })
            var gallery = $('#'+id_elemento)
                gallery.lightGallery();
                gallery.data('lightGallery').destroy(true);
                galleryimagen(id_elemento);  

        }
    });
}
