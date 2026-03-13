$(document).ready(function() {
    // Configurar token CSRF global para todas las peticiones AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    // Cargar datos iniciales
    callregister('/budgets/table',1,$('#table_limit').val(),$('#table_order').val(),'si');
    
    // Event handlers para dropdown actions
    $('body').on('click', '.ver-detalle-factura', function(e) {
        e.preventDefault();
        const idFactura = $(this).data('id');
        const tieneAsociacion = $(this).data('tiene-asociacion') || false;
        const idsTareasData = $(this).data('tareas') || '';
        const idsTareasStr = String(idsTareasData);
        const idsTareas = idsTareasStr ? idsTareasStr.split(',').map(id => parseInt(id)) : [];
        verDetalleFactura(idFactura, tieneAsociacion, idsTareas);
    });
    
    $('body').on('click', '.generar-tarea', function(e) {
        e.preventDefault();
        const idFactura = $(this).data('id');
        generarTarea(idFactura);
    });
    
    $('body').on('click', '.asociar-tarea', function(e) {
        e.preventDefault();
        const idFactura = $(this).data('id');
        asociarTareaExistente(idFactura);
    });
    
    $('body').on('click', '.ver-tareas', function(e) {
        e.preventDefault();
        const idsTareasStr = String($(this).data('tareas') || '');
        verTareasAsociadas(idsTareasStr);
    });
    
    // Event handlers para botones del modal
    $('body').on('click', '#modal-generarTarea', function(e) {
        e.preventDefault();
        const idFactura = $('#modalDetalleFactura').data('idFactura');
        generarTarea(idFactura);
    });
    
    $('body').on('click', '#modal-asociarTarea', function(e) {
        e.preventDefault();
        const idFactura = $('#modalDetalleFactura').data('idFactura');
        asociarTareaExistente(idFactura);
    });
    
    $('body').on('click', '#modal-verTareas', function(e) {
        e.preventDefault();
        const idsTareasStr = String($('#modalDetalleFactura').data('idsTareas') || '');
        verTareasAsociadas(idsTareasStr);
    });
});

/**
 * Inicializar tooltips de Bootstrap
 */
function initTooltips() {
    // Destruir tooltips existentes primero
    $('[data-bs-toggle="tooltip"]').tooltip('dispose');
    
    // Inicializar nuevos tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            boundary: 'viewport',
            placement: 'top'
        });
    });
}

/**
 * Convertir fecha formato YYYY-MM-DD a DD/MM/YYYY (formato argentino)
 */
function formatFechaArgentina(fecha) {
    if (!fecha || fecha === 'N/A') return 'N/A';
    
    // Si ya viene en formato DD/MM/YYYY, retornar as is
    if (fecha.includes('/')) return fecha;
    
    // Convertir de YYYY-MM-DD a DD/MM/YYYY
    const partes = fecha.split('-');
    if (partes.length === 3) {
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }
    
    return fecha;
}

/**
 * Renderizar tabla de presupuestos
 * Personalizado para mostrar datos de presupuestos desde Colppy
 */
function tableregister(data, page, callpaginas, url_query){
    $('#table_info').empty();
    $('#table_body').empty();

    if(callpaginas == 'si'){
        createPagination(data.paginastotal, page, callpaginas, url_query);
    }
    
    $('#table_info').append(data.infototal);
    
    for (let i = 0; i < data.datos.length; i++) {
        const presupuesto = data.datos[i];
        
        // Formatear fecha a formato argentino
        const fecha = formatFechaArgentina(presupuesto.fechaFactura);
        
        // Cliente y descripción completos para tooltips
        const nombreCliente = presupuesto.nombreCliente || 'Cliente desconocido';
        const descripcionCompleta = presupuesto.descripcion || '-';
        
        // Escapar comillas para evitar problemas en HTML
        const nombreClienteEscaped = nombreCliente.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        const descripcionEscaped = descripcionCompleta.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        
        // Badge de estado con color
        const estadoBadge = getBadgeEstado(presupuesto.idEstadoFactura, presupuesto.estadoDescripcion);
        
        // Verificar si está asociado a una tarea
        const tieneAsociacion = presupuesto.cantidadTareas > 0;
        const idsTareas = presupuesto.idsTareas || [];
        
        // Crear dropdown de opciones
        const opcionesDropdown = crearDropdownOpciones(presupuesto.idFactura, tieneAsociacion, idsTareas);
        
        let row = `
            <tr>
                <td style="white-space: nowrap;">${presupuesto.nroFactura || 'N/A'}</td>
                <td style="white-space: nowrap;">${fecha}</td>
                <td class="text-start">
                    <div class="text-truncate" style="max-width: 200px;" 
                         data-bs-toggle="tooltip" 
                         data-bs-placement="top" 
                         title="${nombreClienteEscaped}">
                        ${nombreCliente}
                    </div>
                </td>
                <td class="text-start">
                    <div class="text-truncate" style="max-width: 250px;" 
                         data-bs-toggle="tooltip" 
                         data-bs-placement="top" 
                         title="${descripcionEscaped}">
                        ${descripcionCompleta}
                    </div>
                </td>
                <td>${estadoBadge}</td>
                <td class="text-center">${opcionesDropdown}</td>
            </tr>
        `;
        
        $('#table_body').append(row);
    }
    
    // Inicializar tooltips después de agregar las filas
    initTooltips();
    
    // Inicializar dropdowns de Bootstrap (igual que en jobs)
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle-menu-body'));
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl, {
            boundary: document.querySelector('body'),
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
 * Formatear número con separadores de miles
 */
function formatNumber(num) {
    return parseFloat(num).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

/**
 * Obtener badge HTML según estado de factura
 */
function getBadgeEstado(idEstado, descripcion) {
    const badgeClass = `badge-estado-${idEstado}`;
    return `<span class="badge ${badgeClass} rounded-pill">${descripcion}</span>`;
}

/**
 * Crear dropdown de opciones para cada presupuesto (igual que en jobs)
 * Los botones Generar Tarea y Asociar a Tarea siempre están visibles
 * Si tiene tareas asociadas, se agrega el botón Ver Tareas Asociadas
 */
function crearDropdownOpciones(idFactura, tieneAsociacion, idsTareas = []) {
    const tareasStr = idsTareas.join(',');
    let opcionesHTML = `
        <div class="dropdown">
            <button class="btn btn-sm btn-light dropdown-toggle-menu-body" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-ellipsis-vertical"></i>
            </button>
            <ul class="dropdown-menu shadow-sm">
                <li>
                    <a href="javascript:void(0);" data-id="${idFactura}" data-tiene-asociacion="${tieneAsociacion}" data-tareas="${tareasStr}" class="dropdown-item ver-detalle-factura">
                        <i class="flaticon-eye me-2"></i>Ver Detalle
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>`;
    
    // Opciones de tareas - SIEMPRE VISIBLES
    opcionesHTML += `
                <li>
                    <a href="javascript:void(0);" data-id="${idFactura}" class="dropdown-item generar-tarea">
                        <i class="flaticon-add me-2"></i>Generar Tarea
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0);" data-id="${idFactura}" class="dropdown-item asociar-tarea">
                        <i class="flaticon-share me-2"></i>Asociar a Tarea
                    </a>
                </li>`;
    
    // Si tiene tareas asociadas, agregar opción para verlas
    if (tieneAsociacion && idsTareas.length > 0) {
        const tareasStr = idsTareas.join(',');
        opcionesHTML += `
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a href="javascript:void(0);" data-tareas="${tareasStr}" class="dropdown-item ver-tareas text-success">
                        <i class="fas fa-tasks me-2"></i>Ver Tareas Asociadas (${idsTareas.length})
                    </a>
                </li>`;
    }
    
    opcionesHTML += `
            </ul>
        </div>`;
    
    return opcionesHTML;
}

/**
 * Ver detalle de factura (modal con datos completos desde Colppy)
 */
function verDetalleFactura(idFactura, tieneAsociacion = false, idsTareas = []) {
    // Guardar información en el modal para los botones
    $('#modalDetalleFactura').data('idFactura', idFactura);
    $('#modalDetalleFactura').data('tieneAsociacion', tieneAsociacion);
    $('#modalDetalleFactura').data('idsTareas', idsTareas.join(','));
    
    // Configurar visibilidad del botón Ver Tareas Asociadas
    if (tieneAsociacion && idsTareas.length > 0) {
        $('#modal-verTareas').removeClass('d-none');
        $('#modal-cantidadTareas').text(idsTareas.length);
    } else {
        $('#modal-verTareas').addClass('d-none');
    }
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalDetalleFactura'));
    modal.show();
    
    // Mostrar loading, ocultar contenido y error
    $('#detalleLoading').removeClass('d-none');
    $('#detalleContent').addClass('d-none');
    $('#detalleError').addClass('d-none');
    $('#detalle-pdfLink').addClass('d-none');
    
    // Hacer petición AJAX
    $.ajax({
        url: `/budgets/detail/${idFactura}`,
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const info = response.data.infofactura;
                const items = response.data.itemsFactura;
                const pdfUrl = response.data.UrlFacturaPdf;
                
                // Llenar información general
                $('#detalle-nroFactura').text(info.nroFactura || 'N/A');
                $('#detalle-fechaFactura').text(info.fechaFactura || 'N/A');
                $('#detalle-tipoComprobante').text(`Tipo ${info.idTipoFactura || ''} - ${info.idTipoComprobante || ''}`);
                $('#detalle-estado').text(info.idEstadoFactura || 'N/A')
                    .removeClass().addClass('badge bg-info');
                $('#detalle-condicionPago').text(info.idCondiciónPago || 'N/A');
                $('#detalle-fechaPago').text(info.fechaPago || 'N/A');
                $('#detalle-descripcion').text(info.descripcion || '-');
                
                // Llenar items
                $('#detalle-items').empty();
                if (items && items.length > 0) {
                    items.forEach(item => {
                        const cantidad = parseFloat(item.Cantidad || 0);
                        const precioUnit = parseFloat(item.ImporteUnitario || 0);
                        const subtotal = cantidad * precioUnit;
                        
                        const row = `
                            <tr>
                                <td>${item.Descripcion || '-'}</td>
                                <td class="text-center">${cantidad.toFixed(2)}</td>
                                <td class="text-end">$${precioUnit.toFixed(2)}</td>
                                <td class="text-center">${item.IVA || '0'}%</td>
                                <td class="text-end">$${subtotal.toFixed(2)}</td>
                            </tr>
                        `;
                        $('#detalle-items').append(row);
                    });
                } else {
                    $('#detalle-items').append('<tr><td colspan="5" class="text-center text-muted">No hay items</td></tr>');
                }
                
                // Llenar totales
                $('#detalle-netoGravado').text('$' + parseFloat(info.netoGravado || 0).toFixed(2));
                $('#detalle-netoNoGravado').text('$' + parseFloat(info.netoNoGravado || 0).toFixed(2));
                $('#detalle-totalIVA').text('$' + parseFloat(info.totalIVA || 0).toFixed(2));
                $('#detalle-totalFactura').text('$' + parseFloat(info.totalFactura || 0).toFixed(2));
                
                // Configurar link PDF si existe
                if (pdfUrl) {
                    $('#detalle-pdfLink').attr('href', pdfUrl).removeClass('d-none');
                }
                
                // Mostrar contenido
                $('#detalleLoading').addClass('d-none');
                $('#detalleContent').removeClass('d-none');
            } else {
                mostrarErrorDetalle(response.message || 'No se pudieron obtener los datos');
            }
        },
        error: function(xhr, status, error) {
            // console.error('Error al obtener detalle:', error);
            mostrarErrorDetalle('Error de conexión al obtener el detalle de la factura');
        }
    });
}

/**
 * Mostrar mensaje de error en el modal
 */
function mostrarErrorDetalle(mensaje) {
    $('#detalleLoading').addClass('d-none');
    $('#detalleContent').addClass('d-none');
    $('#detalleError').removeClass('d-none');
    $('#detalleErrorMsg').text(mensaje);
}

/**
 * Generar nueva tarea desde presupuesto
 */
function generarTarea(idFactura) {
    // Mostrar loading personalizado
    showLoadingAlert('Cargando datos del presupuesto...', 'Preparando información desde Colppy');
    
    // Obtener detalle del presupuesto
    $.ajax({
        url: `/budgets/detail/${idFactura}`,
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const info = response.data.infofactura;
                const items = response.data.itemsFactura;
                const idClienteColppy = info.idCliente;
                
                // Buscar cliente haciendo una petición al servidor
                $.ajax({
                    url: '/api/clients/by-colppy-id',
                    type: 'POST',
                    data: { colppy_id: idClienteColppy },
                    success: function(clientResponse) {
                        if (clientResponse.success && clientResponse.client) {
                            const cliente = clientResponse.client;
                            
                            // Limpiar formulario y arrays
                            selectedProducts = selectedProducts.filter(p => p.mode !== 'create');
                            $('#lightgallery').empty();
                            $('#file-input-create').val('');
                            $('#technician_ids_create').selectpicker('deselectAll');
                            $('textarea[name="job_description"]').val('');
                            
                            // Establecer el idFactura y número de presupuesto en campos hidden
                            $('#colppy_budget_id_create').val(idFactura);
                            const nroPresupuesto = info.nroFactura1 + '-' + info.nroFactura2;
                            $('#colppy_budget_number_create').val(nroPresupuesto);
                            
                            // Buscar el cliente por ID usando la API searchvar
                            const $clientSelect = $('#client_id');
                            
                            $.ajax({
                                url: "/api/searchvar",
                                type: 'POST',
                                data: {
                                    search: cliente.id,
                                    tipo: 'clients'
                                },
                                success: function(searchData) {
                                    // Limpiar y llenar el select con los resultados
                                    $clientSelect.find('option').remove();
                                    $clientSelect.empty();
                                    
                                    // Agregar los clientes encontrados
                                    $.each(searchData, function() {
                                        var option = `<option value="${this.id}">${this.first_name} ${this.last_name ?? ''}</option>`;
                                        $clientSelect.append(option);
                                    });
                                    
                                    // Seleccionar el cliente específico
                                    $clientSelect.val(cliente.id);
                                    $clientSelect.selectpicker('refresh');
                                    
                                    // Obtener domicilios del cliente
                                    getAddress(cliente.id);
                                }
                            });
                            
                            // Pre-llenar descripción
                            $('textarea[name="job_description"]').val(info.descripcion || '');
                            
                            // Agregar productos desde los items de la factura
                            if (items && items.length > 0) {
                                // Hacer petición para obtener productos por idColppy
                                // Colppy devuelve el ID del producto en el campo 'idItem'
                                const idsColppy = items.map(item => item.idItem).filter(id => id);
                                
                                if (idsColppy.length > 0) {
                                    $.ajax({
                                        url: '/api/products/by-colppy-ids',
                                        type: 'POST',
                                        data: { colppy_ids: idsColppy },
                                        success: function(prodResponse) {
                                            if (prodResponse.success && prodResponse.products) {
                                                items.forEach(item => {
                                                    // Solo procesar items que tienen idItem (son productos de inventario)
                                                    if (!item.idItem) {
                                                        return;
                                                    }
                                                    
                                                    const producto = prodResponse.products.find(p => 
                                                        String(p.idcolppy) === String(item.idItem)
                                                    );
                                                    
                                                    if (producto) {
                                                        // Agregar al array de productos seleccionados
                                                        const product = {
                                                            unique_id: ++productUniqueIdCounter,
                                                            product_id: String(producto.id),
                                                            codigo: producto.codigo,
                                                            descripcion: producto.descripcion,
                                                            unit_type: 'Unidad',
                                                            quantity: parseFloat(item.Cantidad || 1),
                                                            mode: 'create'
                                                        };
                                                        selectedProducts.push(product);
                                                    } else {
                                                        // console.warn('Producto no encontrado en BD local:', item.Descripcion, 'ID Colppy:', item.idItem);
                                                    }
                                                });
                                                
                                                // Renderizar lista de productos
                                                renderProductsList('create');
                                            }
                                            
                                            // Cerrar alerta y abrir modal
                                            closeSwal();
                                            $('#createjob').modal('show');
                                            toastr.success('Datos del presupuesto cargados correctamente');
                                        },
                                        error: function() {
                                            // console.warn('Error al buscar productos, abriendo modal sin productos');
                                            closeSwal();
                                            $('#createjob').modal('show');
                                            toastr.warning('Cliente cargado. No se pudieron cargar los productos automáticamente');
                                        }
                                    });
                                } else {
                                    // No hay productos, abrir modal directamente
                                    closeSwal();
                                    $('#createjob').modal('show');
                                    toastr.success('Datos del presupuesto cargados correctamente');
                                }
                            } else {
                                // No hay items, abrir modal directamente
                                closeSwal();
                                $('#createjob').modal('show');
                                toastr.success('Datos del presupuesto cargados correctamente');
                            }
                            
                        } else {
                            closeSwal();
                            showErrorAlert('Cliente no encontrado', `El cliente con ID Colppy ${idClienteColppy} no está registrado en el sistema. Debe sincronizar los clientes desde Colppy primero.`);
                        }
                    },
                    error: function() {
                        closeSwal();
                        showErrorAlert('Error', 'No se pudo buscar el cliente en el sistema');
                    }
                });
                
            } else {
                closeSwal();
                showErrorAlert('Error', response.message || 'No se pudieron obtener los datos del presupuesto');
            }
        },
        error: function(xhr, status, error) {
            // console.error('Error al obtener detalle:', error);
            closeSwal();
            showErrorAlert('Error de conexión', 'No se pudo obtener el detalle del presupuesto desde Colppy');
        }
    });
}

/**
 * Asociar a tarea existente
 * Muestra modal con lista de tareas disponibles (solo pendientes/en lugar, sin presupuesto)
 */
function asociarTareaExistente(idFactura) {
    // Cerrar modal de detalle si está abierto
    const modalDetalle = bootstrap.Modal.getInstance(document.getElementById('modalDetalleFactura'));
    if (modalDetalle) {
        modalDetalle.hide();
    }
    
    // Mostrar loading
    showLoadingAlert('Cargando tareas disponibles...', 'Buscando tareas sin presupuesto asociado');
    
    // Obtener datos del presupuesto para mostrar número de factura
    $.ajax({
        url: `/budgets/detail/${idFactura}`,
        type: 'POST',
        dataType: 'json',
        success: function(budgetResponse) {
            const nroFactura = budgetResponse?.data?.infofactura?.nroFactura || idFactura;
            
            // Obtener tareas disponibles
            $.ajax({
                url: '/budgets/available-jobs',
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    closeSwal();
                    
                    if (response.success && response.jobs && response.jobs.length > 0) {
                        mostrarModalAsociarTareas(idFactura, nroFactura, response.jobs);
                    } else {
                        Swal.fire({
                            title: 'Sin tareas disponibles',
                            text: 'No hay tareas pendientes o en lugar sin presupuesto asociado.',
                            type: 'info',
                            confirmButtonText: 'Entendido'
                        });
                    }
                },
                error: function(xhr) {
                    closeSwal();
                    showErrorAlert('Error', 'No se pudieron cargar las tareas disponibles');
                }
            });
        },
        error: function() {
            // Si falla obtener el detalle, usar el ID como fallback
            $.ajax({
                url: '/budgets/available-jobs',
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    closeSwal();
                    
                    if (response.success && response.jobs && response.jobs.length > 0) {
                        mostrarModalAsociarTareas(idFactura, idFactura, response.jobs);
                    } else {
                        Swal.fire({
                            title: 'Sin tareas disponibles',
                            text: 'No hay tareas pendientes o en lugar sin presupuesto asociado.',
                            type: 'info',
                            confirmButtonText: 'Entendido'
                        });
                    }
                },
                error: function(xhr) {
                    closeSwal();
                    showErrorAlert('Error', 'No se pudieron cargar las tareas disponibles');
                }
            });
        }
    });
}

/**
 * Mostrar modal con lista de tareas para asociar
 */
function mostrarModalAsociarTareas(idFactura, nroFactura, jobs) {
    let html = `
        <div class="text-start">
            <p class="mb-3">Seleccione una o varias tareas para asociar al presupuesto <strong>${nroFactura}</strong>:</p>
            <div style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 8px; padding: 10px;">
                <table class="table table-hover table-sm mb-0">
                    <thead style="position: sticky; top: 0; background: white; z-index: 10;">
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="selectAllJobs" class="form-check-input">
                            </th>
                            <th>OT #</th>
                            <th>Cliente</th>
                            <th>Estado</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>`;
    
    jobs.forEach(job => {
        const descripcion = job.job_description ? 
            (job.job_description.length > 50 ? job.job_description.substring(0, 50) + '...' : job.job_description) : 
            'Sin descripción';
        
        const badgeClass = job.status === 'Pendiente' ? 'bg-warning' : 'bg-info';
        
        html += `
            <tr>
                <td>
                    <input type="checkbox" class="form-check-input job-checkbox" value="${job.id}">
                </td>
                <td><strong>${job.id}</strong></td>
                <td>${job.client_name || 'N/A'}</td>
                <td><span class="badge ${badgeClass} rounded-pill">${job.status}</span></td>
                <td class="small text-muted">${descripcion}</td>
            </tr>`;
    });
    
    html += `
                    </tbody>
                </table>
            </div>
            <div class="mt-3 text-muted small">
                <i class="fas fa-info-circle me-1"></i>
                Solo se muestran tareas pendientes o en lugar sin presupuesto asociado.
            </div>
        </div>`;
    
    Swal.fire({
        title: 'Asociar Tareas',
        html: html,
        type: 'question',
        width: 800,
        showCancelButton: true,
        confirmButtonText: 'Asociar Seleccionadas',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        didOpen: () => {
            // Handler para "seleccionar todas"
            $('#selectAllJobs').on('change', function() {
                $('.job-checkbox').prop('checked', $(this).prop('checked'));
            });
            
            // Handler para checkboxes individuales
            $('.job-checkbox').on('change', function() {
                const totalCheckboxes = $('.job-checkbox').length;
                const checkedCheckboxes = $('.job-checkbox:checked').length;
                $('#selectAllJobs').prop('checked', totalCheckboxes === checkedCheckboxes);
            });
        },
        preConfirm: () => {
            const selectedJobs = $('.job-checkbox:checked').map(function() {
                return parseInt($(this).val());
            }).get();
            
            if (selectedJobs.length === 0) {
                Swal.showValidationMessage('Debe seleccionar al menos una tarea');
                return false;
            }
            
            return selectedJobs;
        }
    }).then((result) => {
        if (result.value && Array.isArray(result.value)) {
            guardarAsociacionTareas(idFactura, nroFactura, result.value);
        }
    });
}

/**
 * Guardar asociación de tareas a presupuesto
 */
function guardarAsociacionTareas(idFactura, nroFactura, jobIds) {
    showLoadingAlert('Asociando tareas...', `Asociando ${jobIds.length} tarea(s) al presupuesto`);
    
    $.ajax({
        url: '/budgets/associate-jobs',
        type: 'POST',
        data: {
            budget_id: idFactura,
            budget_number: nroFactura,
            job_ids: jobIds
        },
        dataType: 'json',
        success: function(response) {
            closeSwal();
            
            if (response.success) {
                Swal.fire({
                    title: '¡Éxito!',
                    text: `${response.jobs_updated} tarea(s) asociada(s) correctamente al presupuesto ${nroFactura}`,
                    type: 'success',
                    confirmButtonText: 'Entendido'
                }).then(() => {
                    // Recargar la tabla de presupuestos
                    callregister('/budgets/table', 1, $('#table_limit').val(), $('#table_order').val(), 'si');
                });
            } else {
                showErrorAlert('Error', response.message || 'No se pudieron asociar las tareas');
            }
        },
        error: function(xhr) {
            closeSwal();
            let mensaje = 'Error al asociar tareas al presupuesto';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                mensaje = xhr.responseJSON.message;
            }
            
            showErrorAlert('Error', mensaje);
        }
    });
}

/**
 * Ver tareas asociadas a este presupuesto
 * Ahora abre el modal de detalle de tarea (#showjob) para cada tarea
 */
function verTareasAsociadas(idsTareasStr) {
    // Convertir a string por si acaso viene como número o array
    const idsTareasStrSafe = String(idsTareasStr || '');
    
    if (!idsTareasStrSafe || idsTareasStrSafe === 'undefined' || idsTareasStrSafe === 'null') {
        showErrorAlert('Error', 'No hay tareas asociadas');
        return;
    }
    
    const idsTareas = idsTareasStrSafe.split(',').map(id => parseInt(id)).filter(id => !isNaN(id));
    
    if (idsTareas.length === 0) {
        showErrorAlert('Error', 'No hay tareas asociadas');
        return;
    }
    
    // Si solo hay una tarea, abrir directamente el modal de detalle
    if (idsTareas.length === 1) {
        abrirModalDetalleTarea(idsTareas[0]);
        return;
    }
    
    // Si hay múltiples tareas, mostrar lista con botones para ver cada una
    let html = '<div class="text-start">';
    html += '<p class="fw-bold mb-3">Este presupuesto está asociado a las siguientes tareas:</p>';
    html += '<div class="list-group">';
    
    idsTareas.forEach(id => {
        html += `
            <button type="button" 
                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center ver-detalle-tarea-btn" 
                    data-job-id="${id}">
                <span>
                    <i class="fas fa-tasks me-2"></i>Tarea OT #${id}
                </span>
                <i class="fas fa-chevron-right"></i>
            </button>`;
    });
    
    html += '</div></div>';
    
    Swal.fire({
        title: `Tareas Asociadas (${idsTareas.length})`,
        html: html,
        type: 'info',
        width: 600,
        confirmButtonText: 'Cerrar'
    });
    
    // Bindear eventos DESPUÉS de mostrar el SweetAlert (no en .then que se ejecuta al cerrar)
    setTimeout(() => {
        // console.log('Bindeando eventos a botones de tareas...');
        $('.ver-detalle-tarea-btn').off('click').on('click', function() {
            // console.log('Click en ver detalle de tarea (modal tareas asociadas)');
            const jobId = $(this).data('job-id');
            // console.log('Job ID:', jobId);
            Swal.close();
            abrirModalDetalleTarea(jobId);
        });
        // console.log('Eventos bindeados. Botones encontrados:', $('.ver-detalle-tarea-btn').length);
    }, 100);
}

/**
 * Abrir modal de detalle de tarea existente (#showjob)
 * Usa el mismo mecanismo que el evento .read-job de jobdetail.js
 */
function abrirModalDetalleTarea(jobId) {
    // console.log('abrirModalDetalleTarea:', jobId);
    
    // Verificar que el modal exista
    if ($('#showjob').length === 0) {
        // console.error('Modal #showjob no encontrado en el DOM');
        Swal.fire({
            title: 'Error',
            text: 'No se pudo abrir el modal de detalle. Recargue la página e intente nuevamente.',
            type: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    // console.log('Modal #showjob encontrado');
    
    // Limpiar galería
    $("#lightgalleryShow").empty();
    
    // Obtener y resetear el formulario
    const form = document.getElementById("formshowjob");
    if (form) {
        $(form.elements).each(function(index) {
            $(this).val('');
        });
        // console.log('Formulario reseteado');
    } else {
        // console.warn('Formulario #formshowjob no encontrado');
    }
    
    // Resetear tabs a la primera pestaña (Información General)
    if ($('#info-tab').length > 0) {
        $('#info-tab').tab('show');
        // console.log('Tab info activado');
    }
    
    // Guardar el ID del trabajo en el modal
    $('#showjob').data('job-id', jobId);
    
    // Mostrar spinner de carga
    $('#modal-body-show-job-roller').removeClass('d-none');
    $('#modal-body-show-job-error').addClass('d-none');
    $('#modal-body-show-job').addClass('d-none');
    
    // console.log('Intentando abrir modal con Bootstrap 5...');
    
    // Mostrar el modal usando Bootstrap 5
    const modalElement = document.getElementById('showjob');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    
    // console.log('Modal.show() ejecutado');

    // Cargar datos de la tarea
    $.ajax({
        contenttype: 'application/json; charset=utf-8',
        url: $('meta[name="app_url"]').attr('content') + '/jobs/' + jobId + '/edit',
        type: 'GET',
        error: function(jqXHR, textStatus, errorThrown) {
            $('#modal-body-show-job-roller').addClass('d-none');
            $('#modal-body-show-job-error').removeClass('d-none');
            // console.error('Error al cargar tarea:', errorThrown);
        },
        success: function(data) {
            // console.log('Datos de tarea cargados:', data);
            
            // Verificar que existan las funciones necesarias de jobdetail.js
            if (typeof viewjob === 'function') {
                viewjob(data, form, 'showjob');
                // console.log('viewjob ejecutado');
            } else {
                // console.error('Función viewjob no disponible');
            }
            
            if (typeof viewfiles === 'function') {
                viewfiles(data, 'lightgalleryShow');
            }

            // Mostrar técnicos asignados
            if (typeof renderTechniciansShow === 'function') {
                renderTechniciansShow(data.technicians);
            }

            // Mostrar productos relacionados
            if (typeof renderProductsShow === 'function') {
                renderProductsShow(data.products);
            }
            // Guardar datos del trabajo para el PDF
            currentJobDataForPdf = data;
            
            // Controlar visibilidad del botón de PDF según permisos
            if (data.permissions && data.permissions.pdf && data.permissions.pdf.includes('create')) {
                $('#btn-generate-pdf').show();
            } else {
                $('#btn-generate-pdf').hide();
            }
            
            // Controlar visibilidad del botón de compartir según permisos
            const hasSharePermission = data.permissions && data.permissions.share && data.permissions.share.includes('create');
            if (!hasSharePermission) {
                $('#btn-share-selected-lightgalleryShow').hide();
            }

            $('#modal-body-show-job').removeClass('d-none');
        }
    }).always(function() {
        $('#modal-body-show-job-roller').addClass('d-none');
    });
}

/**
 * Mostrar alerta de error con SweetAlert
 */