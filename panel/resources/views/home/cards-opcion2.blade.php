{{-- OPCIÓN 2: TARJETAS MODERNAS CON BADGES (Material Design) --}}
<div class="col-12 col-md-6 col-lg-4 mb-4">
    <div class="card h-100 shadow-sm border-0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden;">
      {{-- Header con badge de estado --}}
      <div class="card-header border-0 bg-white pb-0 pt-3">
        <div class="d-flex justify-content-between align-items-start">
          <span class="badge rounded-pill px-3 py-2" style="background-color: {{$j->vencimiento}}; font-size: 0.75rem;">
            {{$j->estatus}}
          </span>
          <div class="dropdown">
            <button class="btn btn-sm btn-link text-muted p-0" type="button" data-bs-toggle="dropdown">
              <i class="fas fa-ellipsis-v"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
              @if ($j->estatus != 'Cerrado')
                @if (Session::get('user')['roles'][0] == 'sistema' || Session::get('user')['roles'][0] == 'admin')
                  @if(in_array('update',Session::get('user')['permissions']['jobs']) && $j->arrival != null)
                    <li><a class="dropdown-item backarrival" href="javascript:void(0);" data-id="{{$j->id}}">
                      <i class="flaticon-reply me-2"></i>Volver a pendiente
                    </a></li>
                  @endif
                @endif

                @if (in_array('update',Session::get('user')['permissions']['jobs']) && $j->arrival == null)
                  <li><a class="dropdown-item update-job" href="javascript:void(0);" data-id="{{$j->id}}">
                    <i class="flaticon-upload me-2"></i>Editar
                  </a></li>
                @endif

                @if (in_array('delete',Session::get('user')['permissions']['jobs']) && $j->arrival == null)
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item text-danger delete-job" href="javascript:void(0);" data-id="{{$j->id}}" 
                    data-name="{{$j->client_first_name.' '.$j->client_last_name.' del '.$j->visit_day.' '.$j->visit}}">
                    <i class="flaticon-delete me-2"></i>Eliminar
                  </a></li>
                @endif
              @endif

              @if ($j->estatus == 'Cerrado' && in_array('update',Session::get('user')['permissions']['jobs']))
                <li><a class="dropdown-item archive-job" href="javascript:void(0);" data-id="{{$j->id}}">
                  <i class="fas fa-archive me-2"></i>Archivar
                </a></li>
              @endif
            </ul>
          </div>
        </div>
      </div>

      <div class="card-body pt-2">
        {{-- Título con altura fija y tooltip --}}
        <h5 class="card-title mb-2 fw-bold" 
            style="display: -webkit-box; 
                   -webkit-line-clamp: 2; 
                   -webkit-box-orient: vertical; 
                   overflow: hidden; 
                   text-overflow: ellipsis;
                   min-height: 2.4rem;
                   max-height: 2.4rem;
                   line-height: 1.2rem;
                   font-size: 0.95rem;"
            title="{{$j->client_first_name}} {{$j->client_last_name}}"
            data-bs-toggle="tooltip"
            data-bs-placement="top">
          {{$j->client_first_name}} {{$j->client_last_name}}
        </h5>
        
        {{-- Info en grid --}}
        <div class="row g-2 mb-3">
          <div class="col-12">
            <small class="text-muted d-flex align-items-center">
              <i class="fas fa-calendar-alt me-2" style="width: 16px;"></i>
              <span>{{$j->visit_day}} {{$j->visit}}</span>
            </small>
          </div>
        </div>

        {{-- Descripción con altura fija --}}
        @if (Session::get('user')['roles'][0] == 'sistema' || Session::get('user')['roles'][0] == 'admin')
          <p class="card-text small text-muted mb-2" 
             style="min-height: 1.3rem; 
                    max-height: 1.3rem; 
                    overflow: hidden;
                    display: -webkit-box;
                    -webkit-line-clamp: 1;
                    -webkit-box-orient: vertical;
                    line-height: 1.3rem;">
            {{$j->job_description_short}}
          </p>
          <button type="button" class="btn btn-link btn-sm p-0 mb-2 btn-description" data-content="{{$j->job_description}}">
            <i class="fas fa-eye me-1"></i>Ver descripción completa
          </button>
        @else
          <p class="card-text small text-muted mb-2" 
             style="min-height: 1.3rem; 
                    max-height: 1.3rem; 
                    overflow: hidden;
                    display: -webkit-box;
                    -webkit-line-clamp: 1;
                    -webkit-box-orient: vertical;
                    line-height: 1.3rem;">
            {{$j->job_description}}
          </p>
        @endif

        {{-- Acciones principales grandes con altura fija --}}
        <div class="d-flex flex-column gap-2" style="{{$j->estatus == 'Pendiente' ? 'min-height: 140px;' : ''}}">
          @if ($j->arrival == null && $j->estatus != 'Cerrado')
            <button data-id="{{$j->id}}" class="btn btn-warning markarrival d-flex align-items-center justify-content-center">
              <i class="flaticon-home me-2"></i>Marcar Arribo
            </button>
          @endif
          
          @if (in_array('read',Session::get('user')['permissions']['jobs']))
            <button data-id="{{$j->id}}" class="btn btn-primary read-job d-flex align-items-center justify-content-center">
              <i class="flaticon-eye me-2"></i>Ver Detalles
            </button>
          @endif

          @if ($j->estatus != 'Cerrado')
            <button data-id="{{$j->id}}" class="btn btn-dark closetask d-flex align-items-center justify-content-center" 
              data-name="{{$j->client_first_name}} {{$j->client_last_name}} del {{$j->visit_day}} {{$j->visit}}">
              <i class="flaticon-book me-2"></i>Cerrar Tarea
            </button>
          @endif
        </div>
      </div>

      {{-- Footer con acciones secundarias --}}
      <div class="card-footer bg-light border-0">
        <div class="d-flex justify-content-between">
          <button data-id="{{$j->id}}" class="btn btn-sm btn-outline-success addnote flex-fill me-1" 
            data-name="{{$j->client_first_name.' '.$j->client_last_name.' del '.$j->visit_day.' '.$j->visit}}"
            title="Agregar nota">
            <i class="flaticon-upload"></i>
          </button>

          <button data-id="{{$j->id}}" 
            class="btn btn-sm btn-outline-primary btn-notes flex-fill me-1" 
            data-name="{{$j->client_first_name}} {{$j->client_last_name}} del {{$j->visit_day}} {{$j->visit}}" 
            title="Ver notas"
            style="{{$j->getnotes == 'no' ? 'opacity: 0.3; cursor: not-allowed;' : ''}}"
            {{$j->getnotes == 'no' ? 'disabled' : ''}}>
            <i class="flaticon-notes"></i>
          </button>

          @if (in_array('update',Session::get('user')['permissions']['jobs']))
            <button data-id="{{$j->id}}" class="btn btn-sm btn-outline-success addfiles flex-fill"
              data-name="{{$j->client_first_name}} {{$j->client_last_name}} del {{$j->visit_day}} {{$j->visit}}"
              title="Agregar imágenes">
              <i class="flaticon-photo-camera"></i>
            </button>
          @endif
        </div>
      </div>
    </div>
  </div>
