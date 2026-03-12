$(document).ready(function() {
    callregister('/jobs/table',1,$('#table_limit').val(),$('#table_order').val(),'si')

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
});

function tableregister(data, page, callpaginas, url_query){
    body='';
    const formatter = new Intl.NumberFormat('en-US', {minimumFractionDigits: 2,maximumFractionDigits: 2,});

    // console.log('Verificando campo archived:', data.datos.length > 0 ? data.datos[0].archived : 'No hay datos');

    $.each(data.datos, function (key, val) {
        body += `<tr id="${val.id}" style="border-bottom: 1px solid #f0f0f0;">
            <td class="align-middle">
                <div class="d-flex flex-column align-items-center gap-1">
                    <span class="badge bg-se-primary rounded-pill px-3 py-2" style="font-size: 1rem;">${val.id}</span>`;
                    if (val.colppy_budget_number) {
                        body += `<span class="badge bg-success rounded-pill px-2 py-1" style="font-size: 0.7rem;" title="Factura Colppy asociada">
                            <i class="fas fa-file-invoice me-1"></i>P: #${val.colppy_budget_number}
                        </span>`;
                    }
                body += `</div>
            </td>
            <td class="align-middle fw-bold">${val.client_first_name} ${val.client_last_name ?? ''}</td>
            <td class="text-start py-3 align-middle">
                <div class="d-flex flex-column gap-1">
                    <small class="text-muted" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-placement="top" data-bs-content="Fecha de Creación">
                        <i class="fas fa-circle me-2"></i>${val.created} <span class="text-secondary">(${val.created_day})</span>
                    </small>
                    <small class="text-primary" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-placement="top" data-bs-content="Fecha de Visita">
                        <i class="fas fa-circle me-2"></i>${val.visit} <span class="text-secondary">(${val.visit_day})</span>
                    </small>`
                if (val.arrival != null) {
                    body += `<small class="text-success" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-placement="top" data-bs-content="Fecha de Arribo a lugar">
                        <i class="fas fa-circle me-2"></i>${val.arrival} <span class="text-secondary">(${val.arrival_day})</span>
                    </small>`
                }
                if (val.closed != null) {
                    body += `<small class="text-danger" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-placement="top" data-bs-content="Fecha de Cierre Visita">
                        <i class="fas fa-circle me-2"></i>${val.closed} <span class="text-secondary">(${val.closed_day})</span>
                    </small>`
                }
            body += `</div>
            </td>
            <td class="align-middle">
                <span class="badge rounded-pill px-3 py-2" style="background-color: ${val.vencimiento}; font-size: 0.75rem;">
                    ${val.estatus}
                </span>
            </td>
            <td class="text-start px-3 align-middle">
                <div class="d-flex align-items-center">
                    <span class="text-truncate me-2" style="max-width: 200px;">${val.job_description_short}</span>
                    <button type="button" class="btn btn-sm btn-outline-primary btn-description" data-content="${val.job_description ?? ''}" title="Ver descripción completa">
                        <i class="fas fa-eye"></i>
                    </button>`;

                if (val.images_count > 0 && data.permissions.includes('update') ) {
                    body += `<button type="button" class="btn btn-sm btn-outline-secondary addfiles ms-1" data-id="${val.id}" data-name="${val.client_first_name ?? ''} ${val.client_last_name ?? ''} del ${val.visit_day ?? ''} ${val.visit ?? ''}" title="Ver imágenes">
                        <i class="flaticon-photo-camera"></i>
                    </button>`
                }
                body+= `</div>
            </td>
            <td class="align-middle">`
                if(val.getnotes != 'no'){
                    body+= `<button type="button" class="btn btn-sm btn-primary btn-notes" data-id="${val.id}" data-name="${val.client_first_name ?? ''} ${val.client_last_name ?? ''} del ${val.visit_day ?? ''} ${val.visit ?? ''}" title="Ver notas">
                        <i class="flaticon-notes"></i>
                    </button>`;
                } else {
                    body+= `<span class="text-muted">-</span>`;
                }
            body += `</td>
            <td class="text-start px-3 align-middle">
                <div class="d-flex align-items-center">`
                if (val.closed_job_observation != '') {
                    body += `<span class="text-truncate me-2" style="max-width: 200px;">${val.closed_job_observation_short}</span>
                    <button type="button" class="btn btn-sm btn-outline-primary btn-description" data-content="${val.closed_job_observation ?? ''}" title="Ver observación completa">
                        <i class="fas fa-eye me-2"></i>
                    </button>`
                } else {
                    body += `<span class="text-muted">-</span>`
                }
            body += `</div>
            </td>
            <td class="align-middle">
                <div class="dropdown">
                    <button class="btn btn-sm btn-light dropdown-toggle-menu-body" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                    </button>
                    <ul class="dropdown-menu shadow-sm" >`;

                        if( data.permissions.includes('read') ) {
                            body += `<li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item read-job">
                                <i class="flaticon-eye me-2"></i>Ver Detalles
                            </a></li>`
                        }

                        if ( val.arrival == null && val.closed == null){
                            body += `<li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item markarrival">
                                    <i class="flaticon-home me-2"></i>Marcar Arribo
                                    </a>
                                </li>`
                        } 

                        body += `<li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item addnote" data-name="${val.client_first_name ?? ''} ${val.client_last_name ?? ''} del ${val.visit_day ?? ''} ${val.visit ?? ''}">
                            <i class="flaticon-upload me-2"></i>Agregar Nota
                        </a></li>`;
                        
                        if (val.getnotes != 'no') {
                            body += `<li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item btn-notes" data-name="${val.client_first_name ?? ''} ${val.client_last_name ?? ''} del ${val.visit_day ?? ''} ${val.visit ?? ''}">
                                <i class="flaticon-notes me-2"></i>Ver Notas
                            </a></li>`;
                        }

                        if( data.permissions.includes('update') && val.arrival == null && val.closed == null) {
                            body += `<li>
                                <a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item update-job">
                                    <i class="flaticon-upload me-2"></i>Editar
                                </a>
                            </li>`;
                        }

                        if( data.permissions.includes('update') ) {
                            body += `<li>
                                <a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item addfiles" data-name="${val.client_first_name ?? ''} ${val.client_last_name ?? ''} del ${val.visit_day ?? ''} ${val.visit ?? ''}">
                                    <i class="flaticon-photo-camera me-2"></i>Agregar Imágenes
                                </a>
                            </li>`;
                        }

                        // Agregar productos (solo si NO está cerrado Y archivado)
                        const isClosedAndArchived = val.closed != null && val.archived == 1;
                        if( data.permissions.includes('update') && !isClosedAndArchived ) {
                            body += `<li>
                                <a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item addproducts-job" data-name="${val.client_first_name ?? ''} ${val.client_last_name ?? ''} del ${val.visit_day ?? ''} ${val.visit ?? ''}">
                                    <i class="fas fa-box me-2"></i>Agregar Productos
                                </a>
                            </li>`;
                        }

                        if ( data.permissions.includes('delete') && val.arrival == null && val.closed == null){
                            body += `<li><hr class="dropdown-divider"></li>
                            <li><a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item text-danger delete-job" data-name="${val.client_first_name ?? ''} ${val.client_last_name ?? ''} del ${val.visit_day ?? ''} ${val.visit ?? ''}">
                                <i class="flaticon-delete me-2"></i>Eliminar
                            </a></li>`
                        }

                        const specialRoles = data.special_role_ids || [];
                        const userRoleId = data.user_role_id;
                        const isSpecialRole = specialRoles.includes(userRoleId);
                        
                        if (isSpecialRole) {
                            if ( data.permissions.includes('update') && val.arrival != null && val.closed == null) {
                                body += `<li>
                                    <a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item backarrival">
                                        <i class="flaticon-reply me-2"></i>Volver a Pendiente
                                    </a>
                                </li>`;
                            }  
                        }
                        
                        if ( val.arrival != null && val.closed == null){
                            body += `<li>
                                <a href="javascript:void(0);" data-id="${val.id}" class="dropdown-item text-success closetask" data-name="${val.client_first_name ?? ''} ${val.client_last_name ?? ''} del ${val.visit_day ?? ''} ${val.visit ?? ''}">
                                    <i class="flaticon-book me-2"></i>Cerrar Tarea
                                </a>
                            </li>`;
                        }

                        // Opción de archivar para cualquier tarea (se sigue mostrando en la tabla)
                        if ( data.permissions.includes('update')) {
                            const archivedText = val.archived == 1 ? 'Desarchivar' : 'Archivar';
                            const archivedIcon = val.archived == 1 ? 'flaticon-upload' : 'fas fa-archive';
                            body += `<li><hr class="dropdown-divider"></li>
                            <li><a href="javascript:void(0);" data-id="${val.id}" data-archived="${val.archived}" class="dropdown-item toggle-archive">
                                <i class="${archivedIcon} me-2"></i>${archivedText}
                            </a></li>`;
                        }
                    body += `</ul>
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
