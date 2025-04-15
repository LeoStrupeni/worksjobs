<div class="container-fluid">
  @php
    $tipos = ['En Lugar' => 'alert-success','Pendiente' => 'alert-primary','Cerrado' => 'alert-dark'];
  @endphp
  @foreach ($tipos as $tipo => $alert)
    <div class="row my-4">
      <div class="col-12">
        <div class="alert {{$alert}} py-2" role="alert">
          {{$tipo}}
        </div>
      </div>
      @foreach ($jobs as $j)
        @if ($j->estatus == $tipo)
          @include('home.cards')
        @endif
      @endforeach
    </div>
  @endforeach
</div>