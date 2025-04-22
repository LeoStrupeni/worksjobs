
$(document).ready(function() {
    callregister('/account/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
    $('body').on('click','.create',function(){ 
        $('#name').val('');
        $('#description').val('');
        $('#createaccount').modal('show')}
    );
    $('body').on('click','.update',function(){ 
        $('#formeditaccount').attr('action',app_url+"/account/"+$(this).data('id'));

        form = document.getElementById("formeditaccount");
        $( form.elements ).each(function( index ) {
            if($(this).attr('name') != '_method' && $(this).attr('name') != '_token'){
                $(this).val('');
            } 
        });

        $('#editaccount').modal('show');

        $('#modal-body-edit-account-roller').removeClass('d-none');
        $('#modal-body-edit-account-error').addClass('d-none');
        $('#modal-body-edit-account').addClass('d-none');
        $('#modal-footer-edit-account').addClass('d-none');

        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/account/'+$(this).data('id')+'/edit',
            type : 'GET',
            done : function(response) { $('#modal-body-edit-account-error').removeClass('d-none'); },
            error : function(jqXHR,textStatus,errorThrown) { $('#modal-body-edit-account-error').removeClass('d-none'); },
            success : function(data) {
                $.each( data , function( index, value ) {
                    $( form.elements ).each(function( b ) {
                        if($(this).attr('name') == index){
                            $(this).val(value);
                        }
                    });
                });
                $('#modal-body-edit-account').removeClass('d-none');
                $('#modal-footer-edit-account').removeClass('d-none');
            }
        }).always(function() {
            $('#modal-body-edit-account-roller').addClass('d-none');
        });
    });
    $('body').on('click','.read',function(){ 
        form = document.getElementById("formshowaccount");
        $( form.elements ).each(function( index ) {
            $(this).val('');
        });
        $('#showaccount').modal('show');

        $('#modal-body-show-account-roller').removeClass('d-none');
        $('#modal-body-show-account-error').addClass('d-none');
        $('#modal-body-show-account').addClass('d-none');

        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/account/'+$(this).data('id')+'/edit',
            type : 'GET',
            done : function(response) { $('#modal-body-edit-account-error').removeClass('d-none'); },
            error : function(jqXHR,textStatus,errorThrown) { $('#modal-body-edit-account-error').removeClass('d-none'); },
            success : function(data) {

                $.each( data , function( index, value ) {
                    $( form.elements ).each(function( b ) {
                        if($(this).attr('name') == index){
                            if(index == 'type_doc'){
                                switch(value) {
                                    case "1": $(this).val('Dni'); break;
                                    case "2": $(this).val('Cuil'); break;
                                    case "3": $(this).val('Cuit'); break;
                                    default: $(this).val('');
                                }
                            }else {
                                $(this).val(value);
                            }
                            $(this).css('box-shadow', 'inset 0px 0px 1px 1px green');
                        }
                    });
                });

                $('#modal-body-show-account').removeClass('d-none');
            }
        }).always(function() {
            $('#modal-body-show-account-roller').addClass('d-none');
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
                $('#formdestroy').attr('action',app_url+"/account/"+$(this).data('id'));
                $('#formdestroy').submit();
            }
        });
    });
    $('body').on('click',"#btn-create-account",function () {
        var error = 0
        form = document.getElementById("formnewaccount");

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
            document.getElementById("formnewaccount").submit();
        }
    });
    $('body').on('click',"#btn-update-account",function () {
        var error = 0

        var error = 0
        form = document.getElementById("formeditaccount");

        $( form.getElementsByClassName('validate') ).each(function( index ) {
            if($( this ).val() == ''){
                $( this ).css('box-shadow', 'inset 0px 0px 2px 2px red');
                error++;
            } else {
                $( this ).css('box-shadow', '');
            }
        });
        if (error > 0) {
            toastr["error"]("Debe completar los datos correctamente para editar la Cuenta.")
        } else {
            document.getElementById("formeditaccount").submit();
        }
    });
    $('body').on('change',"#table_limit",function () {
        callregister('/account/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
    });
    $('body').on('click',".column_orden", function(){
        var name = $(this).data('name');
        orden = name+' ASC';

        if ($(this).hasClass('sorttable_sorted'))  { orden = name+' DESC';}

        $('#table_order').val(orden);
        if($('#table_filtrados').val() != $('#table_totales').val()){
            callregister('/account/table',1,$('#table_limit').val(),orden,'si')
        }
    });
    var controladorTiempo = 3000;
    $('#table_search').on('change, keyup',function() {            
        if($('#table_filtrados').val() != $('#table_totales').val()){   
            clearInterval(controladorTiempo);
            controladorTiempo = setInterval(function(){
                callregister('/account/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
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
        typecta = '';
        if(val.type=='CA'){typecta='Caja de ahorro';}
        else if(val.type=='CC'){typecta='Cuenta corriente';}
        else if(val.type=='CR'){typecta='Cuenta remunerada';}

        typemoney='';
        if(val.type_money=='peso'){typemoney='$';}
        else if(val.type_money=='dolar'){typemoney='U$S';}

        body += `<tr id="${val.id}">
            <td class="align-middle">${val.account_holder}</td>
            <td class="align-middle">${val.name}</td>
            <td class="align-middle">${val.bank}</td>
            <td class="align-middle">${typecta}</td>
            <td class="align-middle">${typemoney}</td>
            <td class="align-middle">${val.number}</td>
            <td class="align-middle text-nowrap">${val.cbu}</td>
            <td class="align-middle text-nowrap">${val.alias}</td>
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

                        if ( data.permissions.includes('delete') ){
                            body += `<li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item delete" data-name="${val.first_name} ${val.last_name}">
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

