
$(document).ready(function() {
    callregister('/client/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
    $('body').on('click','.create',function(){ 
        $('#name').val('');
        $('#description').val('');
        $('#createclient').modal('show')}
    );
    $('body').on('click','.excel',function(){ 
        $('#excelclient').modal('show')}
    );
    

    $('body').on('click','.update',function(){ 
        $('#formeditclient').attr('action',app_url+"/client/"+$(this).data('id'));

        form = document.getElementById("formeditclient");
        $( form.elements ).each(function( index ) {
            if($(this).attr('name') != '_method' && $(this).attr('name') != '_token'){
                $(this).val('');
            } 
        });

        $('#editclient').modal('show');

        $('#modal-body-edit-client-roller').removeClass('d-none');
        $('#modal-body-edit-client-error').addClass('d-none');
        $('#modal-body-edit-client').addClass('d-none');
        $('#modal-footer-edit-client').addClass('d-none');

        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/client/'+$(this).data('id')+'/edit',
            type : 'GET',
            done : function(response) { $('#modal-body-edit-client-error').removeClass('d-none'); },
            error : function(jqXHR,textStatus,errorThrown) { $('#modal-body-edit-client-error').removeClass('d-none'); },
            success : function(data) {
                $.each( data , function( index, value ) {
                    $( form.elements ).each(function( b ) {
                        if($(this).attr('name') == index){
                            $(this).val(value);
                        }
                    });
                });
                $('#modal-body-edit-client').removeClass('d-none');
                $('#modal-footer-edit-client').removeClass('d-none');
            }
        }).always(function() {
            $('#modal-body-edit-client-roller').addClass('d-none');
        });
    });
    $('body').on('click','.read',function(){ 
        form = document.getElementById("formshowclient");
        $( form.elements ).each(function( index ) {
            $(this).val('');
        });
        $('#showclient').modal('show');

        $('#modal-body-show-client-roller').removeClass('d-none');
        $('#modal-body-show-client-error').addClass('d-none');
        $('#modal-body-show-client').addClass('d-none');

        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/client/'+$(this).data('id')+'/edit',
            type : 'GET',
            done : function(response) { $('#modal-body-edit-client-error').removeClass('d-none'); },
            error : function(jqXHR,textStatus,errorThrown) { $('#modal-body-edit-client-error').removeClass('d-none'); },
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

                $('#modal-body-show-client').removeClass('d-none');
            }
        }).always(function() {
            $('#modal-body-show-client-roller').addClass('d-none');
        });
    });
    $('body').on('click','.readaddress',function(){ 
        client_id=$(this).data('id');
        client = $(this).data('name');
        form = document.getElementById("formnewaddressclient");
        $( form.elements ).each(function( index ) {
            if($(this).attr('name') != '_method' 
                && $(this).attr('name') != '_token' 
                && $(this).attr('name') != 'country'
            ){
                $(this).val('');
            } 
        });
        $('#addressclient').modal('show');

        $('#tableaddress_roller').removeClass('d-none');
        $('#tableaddress_body').empty();
        $('#tableaddress_body').addClass('d-none');
        $('#tableaddress_error').addClass('d-none');
        $('#tableaddress_sindatos').addClass('d-none');
        $('#titleaddressclient').text('Domicilios de '+client);
    
        $('#client_id').val(client_id);

        $('#formnewaddressclient').attr('action',app_url+"/client/address");

        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/client/address/'+client_id,
            type : 'GET',
            done : function(response) { $('#tableaddress_error').removeClass('d-none'); },
            error : function(jqXHR,textStatus,errorThrown) { $('#tableaddress_error').removeClass('d-none'); },
            success : function(data) {
                body='';
                const formatter = new Intl.NumberFormat('en-US', {minimumFractionDigits: 2,maximumFractionDigits: 2,});
                $.each(data, function (key, val) {
                    body += `<tr id="${val.id}">
                        <td class="align-middle">${val.country ?? ''}</td>
                        <td class="align-middle">${val.state ?? ''}</td>
                        <td class="align-middle">${val.cp ?? ''}</td>
                        <td class="align-middle">${val.city ?? ''}</td>
                        <td class="align-middle">${val.address_street ?? ''}</td>
                        <td class="align-middle">${val.address_nro ?? ''}</td>
                        <td class="align-middle">${val.address_apartament ?? ''}</td>
                        <td class="align-middle">${val.address_detail ?? ''}</td>

                        <td class="align-middle">
                            <a href="javascript:void(0);" data-id="${val.id}" class="btn btn-link deleteaddres">
                                <i class="flaticon-delete"></i>
                            </a>
                        </td>
                    </tr>`;
                });
                $('#tableaddress_body').append(body);
                $('#tableaddress_body').removeClass('d-none');
            }
        }).always(function() {
            $('#tableaddress_roller').addClass('d-none');
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
            if (result.value) {
                $('#formdestroy').attr('action',app_url+"/client/"+$(this).data('id'));
                $('#formdestroy').submit();
            }
        });
    });
    $('body').on('click','.deleteaddres',function(){ 
        rolid=$(this).data('id');
        Swal.fire({
            title: "Borrar Domicilio",
            html: "Esta seguro que desea domicilio del Cliente?<br>No podrá revertir el cambio.",
            type: "question",
            showCancelButton: true,
            confirmButtonText: "Borrar",
            cancelButtonText: `Cancelar`,
        }).then((result) => {
            if (result.value) {
                $('#formdestroyaddress').attr('action',app_url+"/client/address/"+$(this).data('id'));
                $('#formdestroyaddress').submit();
            }
        });
    });
    
    $('body').on('click',"#btn-create-client",function () {
        var error = 0
        form = document.getElementById("formnewclient");

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
            document.getElementById("formnewclient").submit();
        }
    });
    $('body').on('click',"#btn-create-addressclient",function () {
        var error = 0
        form = document.getElementById("formnewaddressclient");

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
            document.getElementById("formnewaddressclient").submit();
        }
    });
    
    $('body').on('click',"#btn-update-client",function () {
        var error = 0

        var error = 0
        form = document.getElementById("formeditclient");

        $( form.getElementsByClassName('validate') ).each(function( index ) {
            if($( this ).val() == ''){
                $( this ).css('box-shadow', 'inset 0px 0px 2px 2px red');
                error++;
            } else {
                $( this ).css('box-shadow', '');
            }
        });
        if (error > 0) {
            toastr["error"]("Debe completar los datos correctamente para editar el Cliente.")
        } else {
            document.getElementById("formeditclient").submit();
        }
    });
    $('body').on('change',"#table_limit",function () {
        callregister('/client/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
    });
    $('body').on('click',".column_orden", function(){
        var name = $(this).data('name');
        orden = name+' ASC';

        if ($(this).hasClass('sorttable_sorted'))  { orden = name+' DESC';}

        $('#table_order').val(orden);
        if($('#table_filtrados').val() != $('#table_totales').val()){
            callregister('/client/table',1,$('#table_limit').val(),orden,'si')
        }
    });
    var controladorTiempo = 3000;
    $('#table_search').on('change, keyup',function() {            
        if($('#table_filtrados').val() != $('#table_totales').val()){   
            clearInterval(controladorTiempo);
            controladorTiempo = setInterval(function(){
                callregister('/client/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
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
        body += `<tr id="${val.id}">
            <td class="align-middle">${val.first_name} ${val.last_name ?? ''}</td>
            <td class="align-middle">${val.tipodoc} - ${val.num_doc}</td>
            <td class="align-middle">${val.email ?? ''}</td>
            <td class="align-middle">${val.phone1 ?? ''}</td>
            <td class="align-middle">${val.state ?? ''}</td>
            <td class="align-middle">${val.city ?? ''}</td>
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
                            <li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item readaddress" data-name="${val.first_name} ${val.last_name ?? ''}">
                                <i class="flaticon-car"></i> Domicilios
                            </a></li>`
                        }

                        if( data.permissions.includes('update') ) {
                            body += `<li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item update">
                                <i class="flaticon-upload"></i> Editar
                            </a></li>`
                        }

                        if ( data.permissions.includes('delete') ){
                            body += `<li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item delete" data-name="${val.first_name} ${val.last_name ?? ''}">
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

