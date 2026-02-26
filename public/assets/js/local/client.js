// Caché global para clientes de Colppy (modo híbrido)
window.clientesData = {};

$(document).ready(function() {
    // Configurar token CSRF global para todas las peticiones AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    callregister('/client/table',1,$('#table_limit').val(),$('#table_order').val(),'si')    
    
    // Funciones de debug para Colppy
    // Cargar estadísticas al inicio (solo si existe el botón)
    if ($('#btn-sync-stats').length > 0) {
        loadSyncStats();
    }

    // Botón: Ver estadísticas
    $('body').on('click','#btn-sync-stats', function() {
        loadSyncStats();
    });

    // Botón: Sincronizar ahora
    $('body').on('click','#btn-sync-now', function() {
        const $btn = $(this);
        const originalHtml = $btn.html();
        
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Sincronizando...');
        $('#sync-stats-display').html('<span class="text-info"><i class="fa-solid fa-spinner fa-spin me-2"></i>Sincronizando con Colppy, por favor espere...</span>');

        $.ajax({
            url: $('meta[name="app_url"]').attr('content') + '/client/sync-colppy-now',
            type: 'POST',
            timeout: 120000, // 2 minutos de timeout
            success: function(response) {
                if (response.success) {
                    const datos = response.datos;
                    let mensaje = 'Sincronización exitosa: ';
                    mensaje += 'Total: ' + datos.total + ' | ';
                    mensaje += 'Nuevos: ' + datos.nuevos + ' | ';
                    mensaje += 'Actualizados: ' + datos.actualizados;
                    
                    if (datos.errores > 0) {
                        mensaje += ' | Errores: ' + datos.errores;
                        toastr["warning"](mensaje);
                    } else {
                        toastr["success"](mensaje);
                    }
                    
                    // Recargar la tabla de clientes
                    callregister('/client/table',1,$('#table_limit').val(),$('#table_order').val(),'si');
                    
                    // Recargar estadísticas
                    // setTimeout(loadSyncStats, 1000);
                } else {
                    toastr["error"]("Error al sincronizar: " + response.message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                let errorMsg = 'Error al sincronizar';
                if (textStatus === 'timeout') {
                    errorMsg = 'La sincronización está tardando más de lo esperado. Puede estar procesándose en segundo plano.';
                } else if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    errorMsg = jqXHR.responseJSON.message;
                }
                toastr["error"](errorMsg);
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
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
        const clientId = $(this).data('id');
        // Convertir el atributo data-colppy a boolean de forma robusta
        const colppyAttr = $(this).data('colppy');
        const isFromColppy = colppyAttr === true || colppyAttr === 'true' || colppyAttr === 1 || colppyAttr === '1';
        
        form = document.getElementById("formshowclient");
        $( form.elements ).each(function( index ) {
            $(this).val('');
        });
        $('#showclient').modal('show');

        $('#modal-body-show-client-roller').removeClass('d-none');
        $('#modal-body-show-client-error').addClass('d-none');
        $('#modal-body-show-client').addClass('d-none');

        // Si es cliente de Colppy y ya está en el cache, usar datos del cache
        if (isFromColppy && window.clientesData[clientId]) {
            const data = window.clientesData[clientId];
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
                        } else {
                            $(this).val(value || '');
                        }
                        // Color según origen
                        $(this).css('box-shadow', isFromColppy ? 
                            'inset 0px 0px 1px 1px blue' : 
                            'inset 0px 0px 1px 1px green'
                        );
                    }
                });
            });

            // Mostrar sección fiscal solo para clientes de Colppy
            if (isFromColppy) {
                $('#colppy-fiscal-section').removeClass('d-none');
            } else {
                $('#colppy-fiscal-section').addClass('d-none');
            }

            $('#modal-body-show-client').removeClass('d-none');
            $('#modal-body-show-client-roller').addClass('d-none');
        } else {
            // Clientes locales: hacer AJAX como siempre
            $.ajax({contenttype : 'application/json; charset=utf-8',
                url : $('meta[name="app_url"]').attr('content')+'/client/'+clientId+'/edit',
                type : 'GET',
                done : function(response) { $('#modal-body-show-client-error').removeClass('d-none'); },
                error : function(jqXHR,textStatus,errorThrown) { $('#modal-body-show-client-error').removeClass('d-none'); },
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
                                } else {
                                    $(this).val(value || '');
                                }
                                // Color según origen
                                $(this).css('box-shadow', isFromColppy ? 
                                    'inset 0px 0px 1px 1px blue' : 
                                    'inset 0px 0px 1px 1px green'
                                );
                            }
                        });
                    });

                    // Mostrar sección fiscal solo para clientes de Colppy
                    if (isFromColppy) {
                        $('#colppy-fiscal-section').removeClass('d-none');
                    } else {
                        $('#colppy-fiscal-section').addClass('d-none');
                    }

                    $('#modal-body-show-client').removeClass('d-none');
                }
            }).always(function() {
                $('#modal-body-show-client-roller').addClass('d-none');
            });
        }
    });
    $('body').on('click','.readaddress',function(){ 
        client_id=$(this).data('id');
        client = $(this).data('name');
        
        // Detectar si el cliente es de API o local
        const isApiClient = typeof client_id === 'string' && client_id.startsWith('colppy_');
        
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
        $('#is_api_client').val(isApiClient ? '1' : '0');

        $('#formnewaddressclient').attr('action',app_url+"/client/address");

        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/client/address/'+client_id,
            type : 'GET',
            done : function(response) { $('#tableaddress_error').removeClass('d-none'); },
            error : function(jqXHR,textStatus,errorThrown) { $('#tableaddress_error').removeClass('d-none'); },
            success : function(response) {
                if(response.datos && response.datos.length > 0) {
                    body='';
                    const formatter = new Intl.NumberFormat('en-US', {minimumFractionDigits: 2,maximumFractionDigits: 2,});
                    $.each(response.datos, function (key, val) {
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
                                    <i class="flaticon-delete me-2"></i>
                                </a>
                            </td>
                        </tr>`;
                    });
                    $('#tableaddress_body').append(body);
                    $('#tableaddress_body').removeClass('d-none');
                } else {
                    $('#tableaddress_sindatos').removeClass('d-none');
                }
            }
        }).always(function() {
            $('#tableaddress_roller').addClass('d-none');
        });
    });
    $('body').on('click','.delete',function(){ 
        rolid=$(this).data('id');
        Swal.fire({
            title: "Borrar Cliente",
            html: "Esta seguro que desea eliminar al cliente "+$(this).data('name')+"?<br>No podrá revertir el cambio.",
            type: "question",
            showCancelButton: true,
            confirmButtonText: "Borrar",
            cancelButtonText: `Cancelar`,
        }).then((result) => {
            if (result.value) {
                showSavingAlert();
                $('#formdestroy').attr('action',app_url+"/client/"+$(this).data('id'));
                $('#formdestroy').submit();
            }
        });
    });
    $('body').on('click','.deleteaddres',function(){ 
        var addressId = $(this).data('id');
        var isApiClient = $('#is_api_client').val(); // '1' si es API, '0' si es local
        
        Swal.fire({
            title: "Borrar Domicilio",
            html: "¿Está seguro que desea eliminar este domicilio?<br>No podrá revertir el cambio.",
            type: "question",
            showCancelButton: true,
            confirmButtonText: "Borrar",
            cancelButtonText: `Cancelar`,
        }).then((result) => {
            if (result.value) {
                // Obtener token del formulario existente
                var token = $('#formdestroyaddress').find('input[name="_token"]').val();
                showSavingAlert();
                // Hacer petición DELETE con AJAX
                $.ajax({
                    url: app_url + "/client/address/" + addressId,
                    type: 'DELETE',
                    data: {
                        _token: token,
                        is_api_client: isApiClient
                    },
                    success: function(response) {
                        toastr["success"]("Domicilio eliminado correctamente");
                        
                        // Recargar tabla de domicilios
                        var clientId = $('#client_id').val(); // Obtener del campo oculto
                        $('#tableaddress_roller').removeClass('d-none');
                        $('#tableaddress_body').empty();
                        $('#tableaddress_body').addClass('d-none');
                        $('#tableaddress_sindatos').addClass('d-none');
                        
                        $.ajax({
                            contenttype: 'application/json; charset=utf-8',
                            url: app_url + '/client/address/' + clientId,
                            type: 'GET',
                            success: function(response) {
                                if (response.datos && response.datos.length > 0) {
                                    var body = '';
                                    $.each(response.datos, function (key, val) {
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
                                                    <i class="flaticon-delete me-2"></i>
                                                </a>
                                            </td>
                                        </tr>`;
                                    });
                                    $('#tableaddress_body').append(body);
                                    $('#tableaddress_body').removeClass('d-none');
                                } else {
                                    $('#tableaddress_sindatos').removeClass('d-none');
                                }
                            }
                        }).always(function() {
                            $('#tableaddress_roller').addClass('d-none');
                        });
                    },
                    error: function(xhr, status, error) {
                        toastr["error"]("Error al eliminar el domicilio");
                        console.error("Error:", xhr.responseText);
                    }
                }).always(function() {
                    closeSwal();
                });
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
            showSavingAlert();
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
            showSavingAlert();
            document.getElementById("formeditclient").submit();
        }
    });
    $('body').on('change',"#table_limit",function () {
        callregister('/client/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
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
    // DEBUG: Ver datos que llegan
    // console.log('=== DEBUG CLIENTES ===');
    // console.log('Total clientes:', data.datos.length);
    // if (data.datos.length > 0) {
    //     console.log('Primer cliente:', data.datos[0]);
    //     console.log('is_from_colppy del primer cliente:', data.datos[0].is_from_colppy);
    // }
    $.each(data.datos, function (key, val) {
        // Detectar si el cliente viene de Colppy (más robusto)
        const isFromColppy = val.is_from_colppy === true || val.is_from_colppy === 1 || val.is_from_colppy === "1";
                // Guardar en cache si es de Colppy (para modo híbrido)
        if (isFromColppy) {
            window.clientesData[val.id] = val;
        }
                body += `<tr id="${val.id}">
            <td class="align-middle">${val.first_name} ${val.last_name ?? ''}</td>
            <td class="align-middle">${val.num_doc}</td>
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
                            body += `<li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item read" data-colppy="${isFromColppy}">
                                <i class="flaticon-eye me-2"></i>Ver
                            </a></li>
                            <li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item readaddress" data-name="${val.first_name} ${val.last_name ?? ''}">
                                <i class="flaticon-car me-2"></i>Domicilios
                            </a></li>`
                        }

                        // Solo mostrar Editar y Eliminar si NO viene de Colppy
                        if( !isFromColppy && data.permissions.includes('update') ) {
                            body += `<li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item update">
                                <i class="flaticon-upload me-2"></i>Editar
                            </a></li>`
                        }

                        if ( !isFromColppy && data.permissions.includes('delete') ){
                            body += `<li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item delete" data-name="${val.first_name} ${val.last_name ?? ''}">
                                <i class="flaticon-delete me-2"></i>Eliminar
                            </a></li>`
                        }
                        
                        // Mostrar indicador de origen Colppy
                        if (isFromColppy) {
                            body += `<li><hr class="dropdown-divider"></li>
                            <li><span class="dropdown-item-text text-muted small">
                                <i class="fa-solid fa-cloud me-2"></i>Desde Colppy
                            </span></li>`
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

/**
 * Cargar estadísticas de sincronización Colppy
 */
function loadSyncStats() {
    $('#sync-stats-display').html('<span class="text-muted"><i class="fa-solid fa-spinner fa-spin me-2"></i>Cargando estadísticas...</span>');
    
    $.ajax({
        url: $('meta[name="app_url"]').attr('content') + '/client/sync-stats',
        type: 'GET',
        dataType: 'json',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response && response.success === true) {
                const stats = response.stats;
                let html = '<div class="row text-start">';
                html += '<div class="col-6"><small><strong>Local Colppy:</strong> ' + stats.local_de_colppy + '</small></div>';
                html += '<div class="col-6"><small><strong>Colppy Total:</strong> ' + stats.colppy_total + '</small></div>';
                html += '</div>';
                
                if (stats.diferencia !== 0) {
                    html += '<div class="row mt-2"><div class="col-12">';
                    html += '<span class="badge bg-warning text-dark"><i class="fa-solid fa-exclamation-triangle me-1"></i>Diferencia detectada: ' + Math.abs(stats.diferencia) + ' cliente(s)</span>';
                    html += '</div></div>';
                } else {
                    html += '<div class="row mt-1"><div class="col-12">';
                    html += '<span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>Sincronizado correctamente</span>';
                    html += '</div></div>';
                }
                
                $('#sync-stats-display').html(html);
            } else {
                const errorMsg = response.message || 'Error desconocido';
                $('#sync-stats-display').html('<span class="text-danger"><i class="fa-solid fa-exclamation-triangle me-2"></i>Error: ' + errorMsg + '</span>');
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            let errorMsg = 'Error de conexión';
            if (jqXHR.status === 404) {
                errorMsg = 'Endpoint no encontrado (404)';
            } else if (jqXHR.status === 500) {
                errorMsg = 'Error del servidor (500)';
                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    errorMsg += ': ' + jqXHR.responseJSON.message;
                }
            } else if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                errorMsg = jqXHR.responseJSON.message;
            }
            
            $('#sync-stats-display').html('<span class="text-danger"><i class="fa-solid fa-exclamation-triangle me-2"></i>' + errorMsg + '</span>');
        }
    });
}
