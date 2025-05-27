function callregister(url_query,page,limit,table_orden,callpaginas){
    $('#table_error').addClass('d-none');
    $('#table_sindatos').addClass('d-none');
    $('#table_roller').removeClass('d-none');
    $('#table_info').empty();
    $('#table_body').empty();

    if(callpaginas=='si'){ $('#table_pagination').empty();}

    $.ajax({contenttype : 'application/json; charset=utf-8',
        data: {
            page	    : page,
            limit 	    : limit,
            order 	    : table_orden,
            search      : $('#table_search').val()
        },
        url : $('meta[name="app_url"]').attr('content')+url_query,
        type : 'POST',
        done : function(response) { $('#table_error').removeClass('d-none'); },
        error : function(jqXHR,textStatus,errorThrown) { $('#table_error').removeClass('d-none'); },
        success : function(data) {
            if(data.datos.length == 0){$('#table_sindatos').removeClass('d-none');}
            else { tableregister(data, page, callpaginas, url_query);}
        }
    }).always(function() {
        $('#table_roller').addClass('d-none');
    });
}


function createPagination(pages, page, callpaginas, url_query) {
    let str = '<ul class="pagination justify-content-end my-1">';
    let active;
    let pageactive = 1;
    let pageCutLow = page - 1;
    let pageCutHigh = page + 1;
    // Mostrar el botón Anterior sólo si se encuentra en una página que no sea la primera
    if (page > 1) {
        str += `<li class="page-item previous no" style="cursor: pointer;">
                    <a onclick="createPagination(`+ pages + `, ` + (page - 1) + `, 'no', '${url_query}')" class="page-link" aria-label="Anterior">Anterior</a>
                </li>`;
    }
    $('#table_paginas').val(pages);

    // Mostrar todos los elementos de paginación si hay menos de 6 páginas en total
    if (pages < 6) {
        for (let p = 1; p <= pages; p++) {
            active = page == p ? "active" : "no";
            if (page == p) { pageactive = p; }
            str += `<li class="page-item ` + active + `" style="${ active=='no' ? 'cursor: pointer;' : '' }" >
                <a class="page-link" ${ active=='no' ? `onclick="createPagination(` + pages + `, ` + p + `,'no', '${url_query}')"` : ''}>` + p + `</a>
            </li>`;
        }
    }
    // Usar "..." para contraer las páginas fuera de un rango determinado
    else {
        // Mostrar la primera página seguida de un "..." al principio de la sección de paginación (después del botón Anterior)
        if (page > 2) {
            str += `<li class="no page-item" style="cursor: pointer;">
                <a class="page-link" onclick="createPagination(` + pages + `, 1, 'no', '${url_query}')">1</a>
            </li>`;
            if (page > 3) {
                str += `<li class="page-item out-of-range"><a class="page-link">...</a></li>`;
            }
        }
        // Determina cuántas páginas se mostrarán después del índice de la página actual
        if (page === 1) { pageCutHigh += 2; }
        else if (page === 2) { pageCutHigh += 1; }
        // Determina cuántas páginas mostrar antes del índice de la página actual
        if (page === pages) { pageCutLow -= 2; }
        else if (page === pages - 1) { pageCutLow -= 1; }
        // Imprime los índices de las páginas que caen dentro del rango de pageCutLow y pageCutHigh
        for (let p = pageCutLow; p <= pageCutHigh; p++) {
            if (p === 0) { p += 1; }
            if (p > pages) { continue }
            active = page == p ? "active" : "no";
            if (page == p) { pageactive = p; }
            str += `<li class="page-item ` + active + `" style="${ active=='no' ? 'cursor: pointer;' : '' }">
                <a class="page-link" ${ active=='no' ? `onclick="createPagination(` + pages + `, ` + p + `,'no', '${url_query}')"` : ''}>` + p + `</a>
            </li>`;
        }
        // Mostrar la última página precedida por un "..." al final de la sección de paginación (antes del botón Siguiente)
        if (page < pages - 1) {
            if (page < pages - 2) {
                str += `<li class="page-item out-of-range"><a class="page-link">...</a></li>`;
            }
            str += `<li class="page-item no" style="cursor: pointer;">
                <a class="page-link" onclick="createPagination(` + pages + `, ` + pages + `,'no', '${url_query}')">` + pages + `</a>
            </li>`;
        }
    }
    // Mostrar el botón Siguiente sólo si se encuentra en una página que no sea la última
    if (page < pages) {
        str += `<li class="page-item next no" style="cursor: pointer;">
            <a class="page-link" onclick="createPagination(` + pages + `, ` + (page + 1) + `,'no', '${url_query}')">Siguiente</a>
        </li>`;
    }
    str += '</ul>';
    // Devuelve la cadena de paginación que se mostrará en las plantillas de pug
    document.getElementById('table_pagination').innerHTML = str;
    if(callpaginas=='no'){
        callregister(url_query,pageactive,$('#table_limit').val(),$('#table_order').val(),'no')
    }
    return str;

    
}