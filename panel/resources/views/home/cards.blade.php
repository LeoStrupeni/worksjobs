<div class="col-12 col-md-6 mb-3">
    <div class="card" style="background-color: #fffffff2;">
      <div class="card-body">
        <span class="d-flex float-end" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Estatus: {{$j->estatus}}">
          <i class="fas fa-circle fa-2x" style='color: {{$j->vencimiento}};'></i>
        </span>
        <h5 class="card-title text-center">{{$j->client_first_name}} {{$j->client_last_name}}</h5>
        <h6 class="card-subtitle mb-2 text-body-secondary">{{$j->visit_day}} {{$j->visit}}</h6>
        @if (Session::get('user')['roles'][0] == 'sistema' || Session::get('user')['roles'][0] == 'admin')
          <p class="card-text">
            {{$j->job_description_short}}  
            <span class="float-end">
              <button type="button" class="btn btn-link btn-description" data-content="{{$j->job_description}}"><i class="fas fa-eye"></i></button>
            </span>
          </p>
        @else
          <p class="card-text">
            {{$j->job_description}}  
          </p>
        @endif

        @if (in_array('read',Session::get('user')['permissions']['jobs']))
          <a href="javascript:void(0);" data-id="{{$j->id}}" class="btn btn-primary read" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Ver tarea">
            <i class="flaticon-eye"></i>
          </a>
        @endif

        @if (in_array('update',Session::get('user')['permissions']['jobs']) && $j->arrival == null)
          <a href="javascript:void(0);" data-id="{{$j->id}}" class="btn btn-primary update"
            data-bs-toggle="tooltip" data-bs-trigger="hover" title="Editar tarea">
              <i class="flaticon-upload"></i>
          </a>
        @endif

        @if (in_array('delete',Session::get('user')['permissions']['jobs']) && $j->arrival == null)
          <a href="javascript:void(0);" data-id="{{$j->id}}" class="btn btn-danger delete"
            data-name="{{$j->client_first_name.' '.$j->client_last_name.' del '.$j->visit_day.' '.$j->visit}}"
            data-bs-toggle="tooltip" data-bs-trigger="hover" title="Eliminar Tarea">
              <i class="flaticon-delete"></i>
          </a>
        @endif

        @if ($j->arrival == null)
          <a href="javascript:void(0);" data-id="{{$j->id}}" class="btn btn-warning markarrival"  data-bs-toggle="tooltip" data-bs-trigger="hover" title="Marcar Arribo">
            <i class="flaticon-home"></i>
          </a>
        @endif
        
        <a href="javascript:void(0);" data-id="{{$j->id}}" class="btn btn-success addnote" 
          data-name="{{$j->client_first_name.' '.$j->client_last_name.' del '.$j->visit_day.' '.$j->visit}}"
          data-bs-toggle="tooltip" data-bs-trigger="hover" title="Agregar nota">
          <i class="flaticon-upload"></i> 
        </a>

        @if($j->getnotes != 'no')
          <a href="javascript:void(0);" data-id="{{$j->id}}" class="btn btn-primary btn-notes" 
            data-name="{{$j->client_first_name}} {{$j->client_last_name}} del {{$j->visit_day}} {{$j->visit}}" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Ver notas">
              <i class="fa-solid fa-note-sticky"></i>
          </a>
        @endif

        @if (in_array('update',Session::get('user')['permissions']['jobs']))
          <a href="javascript:void(0);" data-id="{{$j->id}}" class="btn btn-success addfiles"
            data-name="{{$j->client_first_name}} {{$j->client_last_name}} del {{$j->visit_day}} {{$j->visit}}"
            data-bs-toggle="tooltip" data-bs-trigger="hover" title="Agregar imagenes">
              <i class="flaticon-photo-camera"></i>
          </a>
        @endif

        @if ($j->estatus != 'Cerrado')
          <a href="javascript:void(0);" data-id="{{$j->id}}" class="btn btn-dark closetask" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Cerrar Tarea" data-name="{{$j->client_first_name}} {{$j->client_last_name}} del {{$j->visit_day}} {{$j->visit}}">
            <i class="flaticon-book"></i>
          </a>
        @endif



      </div>
    </div>
  </div>