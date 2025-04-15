$(document).ready(function() {
    callregister('/roles/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
    $('body').on('click','.create',function(){ 
        $('#name').val('');
        $('#description').val('');
        $('#createrol').modal('show')}
    );
    $('body').on('click','.update',function(){ 
        $('#formeditrol').attr('action',app_url+"/roles/"+$(this).data('id'));

        $('#e_name').val('');
        $('#e_description').val('');
        $('#editrol').modal('show');

        $('#modal-body-edit-rol-roller').removeClass('d-none');
        $('#modal-body-edit-rol-error').addClass('d-none');
        $('#modal-body-edit-rol').addClass('d-none');
        $('#modal-footer-edit-rol').addClass('d-none');

        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/roles/'+$(this).data('id')+'/edit',
            type : 'GET',
            done : function(response) { $('#modal-body-edit-rol-error').removeClass('d-none'); },
            error : function(jqXHR,textStatus,errorThrown) { $('#modal-body-edit-rol-error').removeClass('d-none'); },
            success : function(data) {
                $('#e_name').val(data.name);
                $('#e_description').val(data.e_description);
                $('#modal-body-edit-rol').removeClass('d-none');
                $('#modal-footer-edit-rol').removeClass('d-none');
            }
        }).always(function() {
            $('#modal-body-edit-rol-roller').addClass('d-none');
        });
    });
    $('body').on('click','.read',function(){ 
        $('#s_name').val('');
        $('#s_description').val('');
        $('#showrol').modal('show');

        $('#modal-body-show-rol-roller').removeClass('d-none');
        $('#modal-body-show-rol-error').addClass('d-none');
        $('#modal-body-show-rol').addClass('d-none');

        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/roles/'+$(this).data('id')+'/edit',
            type : 'GET',
            done : function(response) { $('#modal-body-edit-rol-error').removeClass('d-none'); },
            error : function(jqXHR,textStatus,errorThrown) { $('#modal-body-edit-rol-error').removeClass('d-none'); },
            success : function(data) {
                $('#s_name').val(data.name);
                $('#s_description').val(data.email);
                $('#modal-body-show-rol').removeClass('d-none');
            }
        }).always(function() {
            $('#modal-body-show-rol-roller').addClass('d-none');
        });
    });

    $('body').on('click','.readusers',function(){ 
        $('#showrolusers').modal('show');
        $('#table_bodyusers').empty();
        $('#modal-body-show-rolusers-roller').removeClass('d-none');
        $('#modal-body-show-rolusers-error').addClass('d-none');
        $('#modal-body-show-rolusers-sindatos').addClass('d-none');
        $('#modal-body-show-rol').addClass('d-none');

        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/roles/users/'+$(this).data('id'),
            type : 'GET',
            done : function(response) { $('#modal-body-edit-rolusers-error').removeClass('d-none'); },
            error : function(jqXHR,textStatus,errorThrown) { $('#modal-body-edit-rolusers-error').removeClass('d-none'); },
            success : function(data) {
                if(data.datos.length == 0){$('#modal-body-show-rolusers-sindatos').removeClass('d-none');}
                else { 
                    body='';
                    $.each(data.datos, function (key, val) {
                        imagen = val.imagen;
                        if(imagen == ''){imagen = app_url+"/assets/media/avatar.jpg"}
                        body += `<tr id="${val.id}">
                            <td class="align-middle"><img class="profile-pic-table" src="${imagen}"/></td>
                            <td class="align-middle">${val.name}</td>
                            <td class="align-middle">${val.email}</td>
                            <td class="align-middle">
                                <button class="btn w-100 ${val.rolname != 'sistema' ? (val.estatus == 'Activo' ? 'btn-success' : 'btn-danger') : 'btn-secondary'} btn-sm">${val.estatus}</button>
                            </td>
                        </tr>`;
                    });
                    $('#table_bodyusers').append(body);

                    $('#modal-body-show-rolusers').removeClass('d-none');
                }
            }
        }).always(function() {
            $('#modal-body-show-rolusers-roller').addClass('d-none');
        });
    });
    
    $('body').on('click','.readpermissions',function(){ 
        $('#showrolpermissions').modal('show');
        $('#table_body_rolpermissions').empty();
        $('#modal-body-show-rolpermissions-roller').removeClass('d-none');
        $('#modal-body-show-rolpermissions-error').addClass('d-none');
        $('#modal-body-show-rolpermissions-sindatos').addClass('d-none');
        var rolid = $(this).data('id');
        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/permission/'+rolid,
            type : 'GET',
            done : function(response) { $('#modal-body-edit-rolpermissions-error').removeClass('d-none'); },
            error : function(jqXHR,textStatus,errorThrown) { $('#modal-body-edit-rolpermissions-error').removeClass('d-none'); },
            success : function(data) {
                if(data.datos.length == 0){$('#modal-body-show-rolpermissions-sindatos').removeClass('d-none');}
                else { 
                    body='';
                    $.each(data.datos, function (key, val) {
                        var disabled = '';
                        if(!data.permissions.includes('update') ) {
                            disabled = 'disabled';
                        }
                        body += `<tr id="${val.general}">
                            <td class="align-middle text-start ps-3">${val.general}</td>
                            <td class="align-middle text-center">
                                <form>
                                    <input type="hidden" name="rolid" value="${rolid}">
                                    <input type="hidden" name="general" value="${val.general}">
                                    <input type="hidden" name="tipo" value="create">
                                    <input class="form-check-input changepermission" onchange="mostrarOverlay()" type="checkbox" name="permission" value="create" ${val.p_create == 1 ? 'checked' : ''} ${disabled}>
                                </form>
                            </td>
                            <td class="align-middle text-center">
                                <form>
                                    <input type="hidden" name="rolid" value="${rolid}">
                                    <input type="hidden" name="general" value="${val.general}">
                                    <input type="hidden" name="tipo" value="read">
                                    <input class="form-check-input changepermission" onchange="mostrarOverlay()" type="checkbox" name="permission" value="read" ${val.p_read == 1 ? 'checked' : ''} ${disabled}>
                                </form>
                            </td>
                            <td class="align-middle text-center">
                                <form>
                                    <input type="hidden" name="rolid" value="${rolid}">
                                    <input type="hidden" name="general" value="${val.general}">
                                    <input type="hidden" name="tipo" value="update">
                                    <input class="form-check-input changepermission" onchange="mostrarOverlay()" type="checkbox" name="permission" value="update" ${val.p_update == 1 ? 'checked' : ''} ${disabled}>
                                </form>
                            </td>
                            <td class="align-middle text-center">
                                <form>
                                    <input type="hidden" name="rolid" value="${rolid}">
                                    <input type="hidden" name="general" value="${val.general}">
                                    <input type="hidden" name="tipo" value="delete">
                                    <input class="form-check-input changepermission" onchange="mostrarOverlay()" type="checkbox" name="permission" value="delete" ${val.p_delete == 1 ? 'checked' : ''} ${disabled}>
                                </form>
                            </td>
                        </tr>`;
                    });
                    $('#table_body_rolpermissions').append(body);

                    $('#modal-body-show-rolpermissions').removeClass('d-none');
                }
            }
        }).always(function() {
            $('#modal-body-show-rolpermissions-roller').addClass('d-none');
        });
    });
    $('body').on('change',".changepermission",function () {
        form = $($(this).parent());
        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/roles/permission/update',
            type : 'POST',
            data: form.serializeArray(),
            done : function(response) { toastr["error"]("Error al actualizar el permiso"); },
            error : function(jqXHR,textStatus,errorThrown) { toastr["error"]("Error al actualizar el permiso"); },
            success : function(data) { toastr["info"]("Permiso actualizado."); }
        }).always(function() {
            $('#_ovrly').remove();
        });
    });
    
    $('body').on('click','.delete',function(){ 
        rolid=$(this).data('id');
        Swal.fire({
            title: "Borrar Usuario",
            html: "Esta seguro que desea eliminar al usuario "+$(this).data('name')+"?<br>No podrá revertir el cambio.",
            type: "question",
            showCancelButton: true,
            confirmButtonText: "Borrar",
            cancelButtonText: `Cancelar`,
        }).then((result) => {
            if (result.dismiss != 'cancel') {
                $('#formdestroy').attr('action',app_url+"/roles/"+$(this).data('id'));
                $('#formdestroy').submit();
            }
        });
    });
    $('body').on('click','.estatus',function(){ 
        element = this;
        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/roles/'+$(this).data('id'),
            type : 'GET',
            done : function(response) { toastr["error"]("Error al cambiar el estado del Rol.") },
            error : function(jqXHR,textStatus,errorThrown) { toastr["error"]("Error al cambiar el estado del Rol.") },
            success : function(data) {
                toastr["success"]("Estado del Rol actualizado.");
                if($(element).hasClass('btn-danger')){
                    $(element).removeClass('btn-danger').addClass('btn-success').text('Activo');
                }else {
                    $(element).removeClass('btn-success').addClass('btn-danger').text('Inactivo');
                }
            }
        });
    });
    $('body').on('click',"#btn-create-rol",function () {
        var error = 0

        $( ".validate" ).each(function( index ) {
            if($( this ).val() == ''){
                $( this ).css('box-shadow', 'inset 0px 0px 2px 2px red');
                error++;
            } else if ($( this ).prop('name')=='nombre' && !regex_letras.test($( this ).val())) {
                $( this ).css('box-shadow', 'inset 0px 0px 2px 2px red');
                error++;
            } 
        });

        if (error > 0) {
            toastr["error"]("Debe completar los datos correctamente para generar el nuevo usuario.")
        } else {
            document.getElementById("formnewrol").submit();
        }
    });
    $('body').on('click',"#btn-update-rol",function () {
        var error = 0

        $( ".e_validate" ).each(function( index ) {
            if($( this ).val() == ''){
                $( this ).css('box-shadow', 'inset 0px 0px 2px 2px red');
                error++;
            } else if ($( this ).prop('name')=='nombre' && !regex_letras.test($( this ).val())) {
                $( this ).css('box-shadow', 'inset 0px 0px 2px 2px red');
                error++;
            }
        });
        if (error > 0) {
            toastr["error"]("Debe completar los datos correctamente para generar editar el Rol.")
        } else {
            document.getElementById("formeditrol").submit();
        }
    });
    $('body').on('change',"#table_limit",function () {
        callregister('/roles/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
    });
    $('body').on('click',".column_orden", function(){
        var name = $(this).data('name');
        orden = name+' ASC';

        if ($(this).hasClass('sorttable_sorted'))  { orden = name+' DESC';}

        $('#table_order').val(orden);
        if($('#table_filtrados').val() != $('#table_totales').val()){
            callregister('/roles/table',1,$('#table_limit').val(),orden,'si')
        }
    });
    var controladorTiempo = 3000;
    $('#table_search').on('change, keyup',function() {            
        if($('#table_filtrados').val() != $('#table_totales').val()){   
            clearInterval(controladorTiempo);
            controladorTiempo = setInterval(function(){
                callregister('/roles/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
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
        imagen = val.imagen;
        if(imagen == ''){imagen = app_url+"/assets/media/avatar.jpg"}
        body += `<tr id="${val.id}">
            <td class="align-middle">${val.name}</td>
            <td class="align-middle">${val.description ?? ''}</td>
            <td class="align-middle">
                <button class="btn w-100 ${val.name != 'sistema' ? (val.estatus == 'Activo' ? 'btn-success' : 'btn-danger') : 'btn-secondary'} btn-sm ${data.permissions.includes('update') && val.name != 'sistema' ? 'estatus' : ''}" data-id="${val.id}">${val.estatus}</button>
            </td>
            <td class="align-middle">

                <div class="dropdown">
                    <button class="btn btn-link dropdown-toggle-menu-body text-success" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-ellipsis"></i>
                    </button>
                    <ul class="dropdown-menu" >`;
                        if( data.permissions.includes('read') ) {
                            body += `<li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item read">
                                <i class="flaticon-eye"></i> Ver
                            </a></li>
                            <li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item readusers">
                                <i class="flaticon-users-1"></i> Ver Usuarios
                            </a></li>
                            <li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item readpermissions">
                                <i class="flaticon-users-1"></i> Ver permisos
                            </a></li>`
                        }

                        if( data.permissions.includes('update') ) {
                            body += `<li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item update">
                                <i class="flaticon-upload"></i> Editar
                            </a></li>`
                        }

                        if ( data.permissions.includes('delete') && val.name != 'sistema' ){
                            body += `<li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item delete" data-name="${val.name}">
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
}