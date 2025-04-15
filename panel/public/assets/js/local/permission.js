
$(document).ready(function() {
    callregister('/permission/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
    $('body').on('click','.create',function(){ 

        $(".select2").select2({
            tags: true,
            width: 'resolve',
            dropdownParent: $('#createpermission'),
            allowClear: false
        }).on("select2:unselecting", function (e) {      
            e.preventDefault();
        });

        $('#name').val('');
        $('#createpermission').modal('show')}
    );
    $('body').on('click','.update',function(){ 
        $('#formeditpermission').attr('action',app_url+"/permission/"+$(this).data('id'));

        form = document.getElementById("formeditpermission");
        $( form.elements ).each(function( index ) {
            if($(this).attr('name') != '_method' && $(this).attr('name') != '_token'){
                $(this).val('');
            } 
        });

        $('#editpermission').modal('show');

        $('#modal-body-edit-permission-roller').removeClass('d-none');
        $('#modal-body-edit-permission-error').addClass('d-none');
        $('#modal-body-edit-permission').addClass('d-none');
        $('#modal-footer-edit-permission').addClass('d-none');

        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/permission/'+$(this).data('id')+'/edit',
            type : 'GET',
            done : function(response) { $('#modal-body-edit-permission-error').removeClass('d-none'); },
            error : function(jqXHR,textStatus,errorThrown) { $('#modal-body-edit-permission-error').removeClass('d-none'); },
            success : function(data) {
                $('#e_name').val(data.general);
                $('#modal-body-edit-permission').removeClass('d-none');
                $('#modal-footer-edit-permission').removeClass('d-none');
            }
        }).always(function() {
            $('#modal-body-edit-permission-roller').addClass('d-none');
        });
    });
    $('body').on('click','.delete',function(){ 
        permissionid=$(this).data('id');
        Swal.fire({
            title: "Borrar Permiso",
            html: "Esta seguro que desea eliminar el permiso "+$(this).data('name')+"?<br>No podrá revertir el cambio.",
            type: "question",
            showCancelButton: true,
            confirmButtonText: "Borrar",
            cancelButtonText: `Cancelar`,
        }).then((result) => {
            if (result.dismiss != 'cancel') {
                $('#formdestroy').attr('action',app_url+"/permission/"+$(this).data('id'));
                $('#formdestroy').submit();
            }
        });
    });
    $('body').on('click',"#btn-create-permission",function () {
        var error = 0
        form = document.getElementById("formnewpermission");

        $( form.getElementsByClassName('validate') ).each(function( index ) {
            if($( this ).val() == ''){
                $( this ).css('box-shadow', 'inset 0px 0px 2px 2px red');
                error++;
            } else {
                $( this ).css('box-shadow', '');
            }
        });

        if (error > 0) {
            toastr["error"]("Debe completar los datos correctamente.")
        } else {
            document.getElementById("formnewpermission").submit();
        }
    });
    $('body').on('click',"#btn-update-permission",function () {
        var error = 0

        var error = 0
        form = document.getElementById("formeditpermission");

        $( form.getElementsByClassName('validate') ).each(function( index ) {
            if($( this ).val() == ''){
                $( this ).css('box-shadow', 'inset 0px 0px 2px 2px red');
                error++;
            } else {
                $( this ).css('box-shadow', '');
            }
        });
        if (error > 0) {
            toastr["error"]("Debe completar los datos correctamente para editar el permissione.")
        } else {
            document.getElementById("formeditpermission").submit();
        }
    });
    $('body').on('change',"#table_limit",function () {
        callregister('/permission/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
    });
    $('body').on('click',".column_orden", function(){
        var name = $(this).data('name');
        orden = name+' ASC';

        if ($(this).hasClass('sorttable_sorted'))  { orden = name+' DESC';}

        $('#table_order').val(orden);
        if($('#table_filtrados').val() != $('#table_totales').val()){
            callregister('/permission/table',1,$('#table_limit').val(),orden,'si')
        }
    });
    var controladorTiempo = 3000;
    $('#table_search').on('change, keyup',function() {            
        if($('#table_filtrados').val() != $('#table_totales').val()){   
            clearInterval(controladorTiempo);
            controladorTiempo = setInterval(function(){
                callregister('/permission/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
                clearInterval(controladorTiempo); //Limpio el intervalo
            }, 800); 
           
        } else {
            _this = this;
            // Show only matching TR, hide rest of them
            $.each($("#table_body tr"), function() {
                if($(this).text().toLowerCase().indexOf($(_this).val().toLowerCase()) === -1)
                    $(this).hide();
                else
                    $(this).show();
            });
        }
    });
});

function tableregister(data, page, callpaginas, url_query){
    body='';
    const formatter = new Intl.NumberFormat('en-US', {minimumFractionDigits: 2,maximumFractionDigits: 2,});

    $.each(data.datos, function (key, val) {
        var permisos = val.listpermisos.split(',');
        
        var btnpermisos=`<div class="btn-group" role="group" aria-label="Basic example">`;

        $.each(permisos, function (key, val) {
            switch (val) {  
                case 'create':  btnpermisos+=`<div class="btn btn-primary rounded m-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Permiso para agregar"><i class="flaticon-plus"></i></div>`; break;
                case 'read':    btnpermisos+=`<div class="btn btn-primary rounded m-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Permiso para ver"><i class="flaticon-eye"></i></div>`; break;
                case 'update':  btnpermisos+=`<div class="btn btn-primary rounded m-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Permiso para actualizar"><i class="flaticon-upload"></i></div>`; break;
                case 'delete':  btnpermisos+=`<div class="btn btn-primary rounded m-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Permiso para Borrar"><i class="flaticon-delete"></i></div>`; break;
                default:        btnpermisos+=`<div class="btn btn-primary rounded m-1" data-bs-toggle="tooltip" data-bs-placement="top" title="otros"><i class="flaticon-info"></i></div>`; break;
            }
            
        });

        btnpermisos+=`</div>`;

        body += `<tr id="${val.general}">
            <td class="align-middle">${val.general} </td>
            <td class="align-middle">${btnpermisos}</td>
            <td class="align-middle">
                <div class="dropdown">
                    <button class="btn btn-link dropdown-toggle-menu-body text-success" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-ellipsis"></i>
                    </button>
                    <ul class="dropdown-menu" >`;

                        if( data.permissions.includes('update') ) {
                            body += `<li><a href="javascript:void(0);" data-id="${val.general}" class="dropdown-item update">
                                <i class="flaticon-upload"></i> Editar
                            </a></li>`
                        }

                        if ( data.permissions.includes('delete') ){
                            body += `<li><a href="javascript:void(0);" data-id="${val.general}" class="dropdown-item delete" data-name="${val.general}">
                                <i class="flaticon-delete"></i> Eliminar
                            </a></li>`
                        }
                body += `<ul></div>
            </td>
        </tr>`;
    });
    $('#table_body').append(body);
    $('#table_info').text(data.infototal);
    $('#table_filtrados').val(data.datos.length);
    $('#table_totales').val(data.totales);
    table_filtrados
    if(callpaginas=='si'){
        document.getElementById('table_pagination').innerHTML = createPagination(data.paginastotal, page, callpaginas, url_query);
    }

    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle-menu-body'))
        var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl, {
            boundary: document.querySelector('#inicio'),
            popperConfig: function (defaultBsPopperConfig) {
                return {
                    ...defaultBsPopperConfig,
                    placement: "bottom-end",
                    strategy: "fixed"
                };
            }
        })
    });

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
}

