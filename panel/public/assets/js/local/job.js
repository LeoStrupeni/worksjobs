var controladorTiempo = 3000;
var valorbuscado = '';

$(document).ready(function() {
    callregister('/jobs/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
    $('body').on('click','.create',function(){ 
        $('#name').val('');
        $('#description').val('');
        $('#createjob').modal('show');

        navigator.geolocation.getCurrentPosition(geosuccess);
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
    $('body').on('change',"#table_limit",function () {
        callregister('/jobs/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
    });
    $('body').on('click',".column_orden", function(){
        var name = $(this).data('name');
        orden = name+' ASC';

        if ($(this).hasClass('sorttable_sorted'))  { orden = name+' DESC';}

        $('#table_order').val(orden);
        if($('#table_filtrados').val() != $('#table_totales').val()){
            callregister('/jobs/table',1,$('#table_limit').val(),orden,'si')
        }
    });
    $('#table_search').on('change, keyup',function() {            
        if($('#table_filtrados').val() != $('#table_totales').val()){   
            clearInterval(controladorTiempo);
            controladorTiempo = setInterval(function(){
                callregister('/jobs/table',1,$('#table_limit').val(),$('#table_order').val(),'si')
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
                        contenttype: 'application/json; charset=utf-8',
                        url: "/api/searchvar",
                        type: 'GET',
                        data: {
                            search: valor,
                            tipo: 'clients'
                        },
                        success : function(data) {
                            datos = data;

                            $.each(datos, function() {
                                var option = `<option value="${this.id}">${this.first_name} ${this.last_names}</option>`;
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
});

function tableregister(data, page, callpaginas, url_query){
    body='';
    const formatter = new Intl.NumberFormat('en-US', {minimumFractionDigits: 2,maximumFractionDigits: 2,});

    $.each(data.datos, function (key, val) {
        body += `<tr id="${val.id}">
            <td class="align-middle">${val.client_first_name} ${val.client_last_name}</td>
            <td class="text-start py-2 align-middle">
                <p class="m-0 text-nowrap" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-placement="top" data-bs-content="Fecha de Creación">
                    <i class="fas fa-circle" style="color: black;"></i> ${val.created} (${val.created_day})
                </p>
                <p class="m-0 text-nowrap" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-placement="top" data-bs-content="Fecha de Visita">
                    <i class="fas fa-circle" style="color: blue;"></i> ${val.visit} (${val.visit_day})
                </p>`
                if (val.arrival != null) {
                    body += `<p class="m-0 text-nowrap" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-placement="top" data-bs-content="Fecha de Arribo a lugar">
                        <i class="fas fa-circle" style="color: green;"></i> ${val.arrival} (${val.arrival_day})
                    </p>`
                }
                if (val.closed != null) {
                    body += `<p class="m-0 text-nowrap" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-placement="top" data-bs-content="Fecha de Cierre Visita">
                        <i class="fas fa-circle" style="color: red;"></i> ${val.closed} (${val.closed_day})
                    </p>`
                }
            body += `</td>
            <td class="align-middle">
                <p class="m-0 text-nowrap">
                    <i class="fas fa-circle" style='color: ${val.vencimiento};'></i>
                    <br>${val.estatus}
                </p>
            </td>
            <td class="text-truncate text-start ps-3">
                ${val.job_description_short}
                <button type="button" class="btn btn-link p-0 btn-description" data-content="${val.job_description}"><i class="fas fa-eye"></i></button>
            </td>
            <td class="align-middle">`
                if(val.getnotes != 'no'){
                    body+= `<button type="button" class="btn btn-sm btn-primary btn-notes" data-id="${val.id}" data-name="${val.client_first_name} ${val.client_last_name} del ${val.visit_day} ${val.visit}" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-placement="top" data-bs-content="Ver notas">
                        <i class="fa-solid fa-note-sticky"></i>
                    </button>`;
                }
            body += `</td>
            <td class="text-truncate text-start ps-3">
                ${val.closed_job_observation_short}`
                if (val.closed_job_observation != '') {
                    body += `<button type="button" class="btn btn-link p-0 btn-description" data-content="${val.closed_job_observation}"><i class="fas fa-eye"></i></button>`
                }
            body += `</td>
            <td class="align-middle">
                <div class="dropdown">
                    <button class="btn btn-link dropdown-toggle-menu-body" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-ellipsis"></i>
                    </button>
                    <ul class="dropdown-menu" >`;

                        if ( val.arrival == null ){
                            body += `<li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item markarrival">
                                    <i class="flaticon-home"></i> Marcar Arribo
                                    </a>
                                </li>`
                        } else {
                            body += `<li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item addnote" data-name="${val.client_first_name} ${val.client_last_name} del ${val.visit_day} ${val.visit}">
                                <i class="flaticon-upload"></i> Agregar nota
                            </a></li>`
                        }

                        if( data.permissions.includes('read') ) {
                            body += `<li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item read">
                                <i class="flaticon-eye"></i> Ver
                            </a></li>`
                        }

                        if( data.permissions.includes('update') && val.arrival == null ) {
                            body += `<li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item update">
                                <i class="flaticon-upload"></i> Editar
                            </a></li>`
                        }

                        if ( data.permissions.includes('delete') && val.arrival == null ){
                            body += `<li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item delete" data-name="${val.client_first_name} ${val.client_last_name} del ${val.visit_day} ${val.visit}">
                                <i class="flaticon-delete"></i> Eliminar
                            </a></li>`
                        }

                        if ( val.arrival != null && val.closed == null){
                            
                            body += `<li>
                                <a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item closetask">
                                    <i class="flaticon-book"></i> Cerrar Tarea
                                </a>
                            </li>`;
                        }
                    body += `<ul>
                </div>
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

    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    })

}

