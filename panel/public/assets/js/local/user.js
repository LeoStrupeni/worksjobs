$(document).ready(function() {
    callregister('/users/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
    $('body').on('click','.create',function(){ 
        $('#name').val('');
        $('#email').val('');
        $("#rol").val(1).change();
        $('#password').val('');
        $('#password_confirmation').val('');
        $('#password_confirmation').css('box-shadow', '');
        $('#password').css('box-shadow', '');
        $('.password').text('')
        $('#imagen-user-create').attr('src', "");
        $('#createuser').modal('show')}
    );
    $('body').on('click','.update',function(){ 
        $('#formedituser').attr('action',app_url+"/users/"+$(this).data('id'));

        $('#e_name').val('');
        $('#e_email').val('');
        $("#e_rol").val(1).change();
        $('#e_password').val('');
        $('#e_password_confirmation').val('');
        $('#imagen-user-edit').attr('src', "");
        $('#edituser').modal('show');

        $('#modal-body-edit-user-roller').removeClass('d-none');
        $('#modal-body-edit-user-error').addClass('d-none');
        $('#modal-body-edit-user').addClass('d-none');
        $('#modal-footer-edit-user').addClass('d-none');

        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/users/'+$(this).data('id')+'/edit',
            type : 'GET',
            done : function(response) { $('#modal-body-edit-user-error').removeClass('d-none'); },
            error : function(jqXHR,textStatus,errorThrown) { $('#modal-body-edit-user-error').removeClass('d-none'); },
            success : function(data) {
                $('#e_name').val(data.name);
                $('#e_email').val(data.email);
                $("#e_rol").val(data.rolid).change();
                $('#e_password').val('');
                $('#e_password_confirmation').val('');
                $('.password').text('')
                $('#imagen-user-edit').attr('src', data.imagen);
                $('#modal-body-edit-user').removeClass('d-none');
                $('#modal-footer-edit-user').removeClass('d-none');
            }
        }).always(function() {
            $('#modal-body-edit-user-roller').addClass('d-none');
        });
    });
    $('body').on('click','.read',function(){ 
        $('#s_name').val('');
        $('#s_email').val('');
        $("#s_rol").val(1).change();
        $('#imagen-user-show').attr('src', "");
        $('#showuser').modal('show');

        $('#modal-body-show-user-roller').removeClass('d-none');
        $('#modal-body-show-user-error').addClass('d-none');
        $('#modal-body-show-user').addClass('d-none');

        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/users/'+$(this).data('id')+'/edit',
            type : 'GET',
            done : function(response) { $('#modal-body-edit-user-error').removeClass('d-none'); },
            error : function(jqXHR,textStatus,errorThrown) { $('#modal-body-edit-user-error').removeClass('d-none'); },
            success : function(data) {
                $('#s_name').val(data.name);
                $('#s_email').val(data.email);
                $("#s_rol").val(data.rolid).change();
                $('#imagen-user-show').attr('src', data.imagen);
                $('#modal-body-show-user').removeClass('d-none');
            }
        }).always(function() {
            $('#modal-body-show-user-roller').addClass('d-none');
        });
    });
    $('body').on('click','.delete',function(){ 
        userid=$(this).data('id');
        Swal.fire({
            title: "Borrar Usuario",
            html: "Esta seguro que desea eliminar al usuario "+$(this).data('name')+"?<br>No podrá revertir el cambio.",
            type: "question",
            showCancelButton: true,
            confirmButtonText: "Borrar",
            cancelButtonText: `Cancelar`,
            input: "text",
            inputPlaceholder: "Motivo",
            inputAttributes: { autocapitalize: "off" },
            }).then((result) => {
                if (result.dismiss != 'cancel') {
                   $('#d_descripcion').val(result.value);
                   $('#formdestroy').attr('action',app_url+"/users/"+$(this).data('id'));
                   $('#formdestroy').submit();
                }
            });
    });
    $('body').on('click','.estatus',function(){ 
        element = this;
        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/users/'+$(this).data('id'),
            type : 'GET',
            done : function(response) { toastr["error"]("Error al cambiar el estado del usuario.") },
            error : function(jqXHR,textStatus,errorThrown) { toastr["error"]("Error al cambiar el estado del usuario.") },
            success : function(data) {
                toastr["success"]("Estado del usuario actualizado.");
                if($(element).hasClass('btn-danger')){
                    $(element).removeClass('btn-danger').addClass('btn-success').text('Activo');
                }else {
                    $(element).removeClass('btn-success').addClass('btn-danger').text('Inactivo');
                }
            }
        });
    });
    $('body').on('click',".verpass",function () {
        if($($(this).parent().parent().parent().children()[0]).prop('type') == 'password'){
            $(this).removeClass('fa-eye').addClass('fa-eye-slash');
            $($(this).parent().parent().parent().children()[0]).prop('type','text');
        }else{
            $(this).removeClass('fa-eye-slash').addClass('fa-eye');
            $($(this).parent().parent().parent().children()[0]).prop('type','password');
        }
    });
    $('body').on('keyup',"#password, #password_confirmation",function () {
        if( $('#password').val() != $('#password_confirmation').val()){
            if($(this).prop('id') == 'password'){
                $('#password_confirmation').css('box-shadow', 'inset 0px 0px 2px 2px red');
            } else {
                $('#password').css('box-shadow', 'inset 0px 0px 2px 2px red');
            }
            $('.password').text('Las contraseñas no coinciden.')
        } else {
            $('#password_confirmation').css('box-shadow', '');
            $('#password').css('box-shadow', '');
            $('.password').text('');
        }
    });
    $('body').on('click',"#btn-create-user",function () {
        var error = 0

        $( ".validate" ).each(function( index ) {
            if($( this ).val() == ''){
                $( this ).css('box-shadow', 'inset 0px 0px 2px 2px red');
                error++;
            } else if ($( this ).prop('name')=='nombre' && !regex_letras.test($( this ).val())) {
                $( this ).css('box-shadow', 'inset 0px 0px 2px 2px red');
                error++;
            } else if ($( this ).prop('name')=='email' && !regex_mail.test($( this ).val())) {
                $( this ).css('box-shadow', 'inset 0px 0px 2px 2px red');
                error++;
            } 
        });

        if (error > 0) {
            toastr["error"]("Debe completar los datos correctamente para generar el nuevo usuario.")
        } else {
            document.getElementById("formnewuser").submit();
        }
    });
    $('body').on('click',"#btn-update-user",function () {
        var error = 0

        $( ".e_validate" ).each(function( index ) {
            if($( this ).val() == ''){
                $( this ).css('box-shadow', 'inset 0px 0px 2px 2px red');
                error++;
            } else if ($( this ).prop('name')=='nombre' && !regex_letras.test($( this ).val())) {
                $( this ).css('box-shadow', 'inset 0px 0px 2px 2px red');
                error++;
            } else if ($( this ).prop('name')=='email' && !regex_mail.test($( this ).val())) {
                $( this ).css('box-shadow', 'inset 0px 0px 2px 2px red');
                error++;
            } 
        });
        if (error > 0) {
            toastr["error"]("Debe completar los datos correctamente para generar el nuevo usuario.")
        } else {
            document.getElementById("formedituser").submit();
        }
    });
    $('body').on('change',"#table_limit",function () {
        callregister('/users/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
    });
    $('body').on('click',".column_orden", function(){
        var name = $(this).data('name');
        orden = name+' ASC';

        if ($(this).hasClass('sorttable_sorted'))  { orden = name+' DESC';}

        $('#table_order').val(orden);
        if($('#table_filtrados').val() != $('#table_totales').val()){
            callregister('/users/table',1,$('#table_limit').val(),orden,'si')
        }
    });
    var controladorTiempo = 3000;
    $('#table_search').on('change, keyup',function() {            
        if($('#table_filtrados').val() != $('#table_totales').val()){   
            clearInterval(controladorTiempo);
            controladorTiempo = setInterval(function(){
                callregister('/users/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
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
        if(imagen == '' || imagen == null){imagen = app_url+"/assets/media/avatar.png"}
        body += `<tr id="${val.id}">
            <td class="align-middle"><img class="profile-pic-table" src="${imagen}"/></td>
            <td class="align-middle">${val.name}</td>
            <td class="align-middle">${val.email}</td>
            <td class="align-middle">${val.rolname}</td>
            <td class="align-middle">
                <button class="btn w-100 ${val.rolname != 'sistema' ? (val.estatus == 'Activo' ? 'btn-success' : 'btn-danger') : 'btn-secondary'} btn-sm ${data.permissions.includes('update') && val.rolname != 'sistema' ? 'estatus' : ''}" data-id="${val.id}">${val.estatus}</button>
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
                            </a></li>`
                        }

                        if( data.permissions.includes('update') ) {
                            body += `<li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item update">
                                <i class="flaticon-upload"></i> Editar
                            </a></li>`
                        }

                        if ( data.permissions.includes('delete') && val.rolname != 'sistema'){
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

