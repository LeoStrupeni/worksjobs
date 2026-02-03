<div class="container-fluid">
  @php
    $tipos = [
      'En Lugar' => ['color' => '#10b981', 'icon' => 'fa-tools'],
      'Pendiente' => ['color' => '#667eea', 'icon' => 'fa-clock'],
      'Cerrado' => ['color' => '#6b7280', 'icon' => 'fa-check-circle']
    ];
    $jobsCollection = collect($jobs);
  @endphp
  
  <div class="accordion accordion-flush" id="accordionJobs">
    @foreach ($tipos as $tipo => $config)
      @php
        $count = $jobsCollection->where('estatus', $tipo)->count();
        $accordionId = 'collapse' . str_replace(' ', '', $tipo);
        $isEnLugar = $tipo === 'En Lugar';
      @endphp
      
      <div class="accordion-item border-0 mb-3" style="border-radius: 15px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); background-color: transparent;">
        <h2 class="accordion-header" id="heading{{ str_replace(' ', '', $tipo) }}">
          <button class="accordion-button {{ $isEnLugar ? '' : 'collapsed' }} text-white fw-bold" 
                  type="button" 
                  data-bs-toggle="collapse" 
                  data-bs-target="#{{ $accordionId }}" 
                  aria-expanded="{{ $isEnLugar ? 'true' : 'false' }}" 
                  aria-controls="{{ $accordionId }}"
                  style="background: linear-gradient(135deg, {{ $config['color'] }} 0%, {{ $config['color'] }}dd 100%); border-radius: 15px; border: none; box-shadow: none;">
            <i class="fas {{ $config['icon'] }} me-2"></i>
            {{ $tipo }}
            <span class="badge bg-white text-dark ms-2 rounded-pill px-3">{{ $count }}</span>
          </button>
        </h2>
        <div id="{{ $accordionId }}" 
             class="accordion-collapse collapse {{ $isEnLugar ? 'show' : '' }}" 
             aria-labelledby="heading{{ str_replace(' ', '', $tipo) }}">
          <div class="accordion-body bg-transparent p-3">
            <div class="row">
              @forelse ($jobsCollection->where('estatus', $tipo) as $j)
                @include('home.cards-opcion2', ['j' => $j])
              @empty
                <div class="col-12">
                  <div class="row justify-content-center">
                    <div class="col-10 col-md-6 col-lg-4">
                      <div class="card" style="background-color: #ffffffd6!important;">
                        <div class="card-body">
                          <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay tareas en estado {{ $tipo }}</h5>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                </div>
              @endforelse
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>
</div>