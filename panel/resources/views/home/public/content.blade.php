@section('Content')

  <!-- Boton hacia arriba -->
  <button type="button" class="btn btn-sm btn-success rounded-circle ir-arriba">
    <i class="flaticon2-arrow-up"></i>
  </button>
  
  <div class="carousel slide carousel-fade" data-bs-ride="carousel">
    <div class="carousel-inner">
      <div class="carousel-item active">
        <img src="{{env('APP_URL')}}/assets/media/carousel/ej1.jpg" class="d-block w-100 img-carousel" alt="...">
        {{-- <div class="carousel-caption d-none d-md-block">
          <h5>First slide label</h5>
          <p>Some representative placeholder content for the first slide.</p>
        </div> --}}
      </div>
      <div class="carousel-item">
        <img src="{{env('APP_URL')}}/assets/media/carousel/ej2.png" class="d-block w-100 img-carousel" alt="...">
        {{-- <div class="carousel-caption d-none d-md-block">
          <h5>Second slide label</h5>
          <p>Some representative placeholder content for the second slide.</p>
        </div> --}}
      </div>
      <div class="carousel-item">
        <img src="{{env('APP_URL')}}/assets/media/carousel/ej3.jpg" class="d-block w-100 img-carousel" alt="...">
        {{-- <div class="carousel-caption d-none d-md-block">
          <h5>Third slide label</h5>
          <p>Some representative placeholder content for the third slide.</p>
        </div> --}}
      </div>
    </div>
  </div>

  <div class="container my-5" id="tramites">
    <div class="row justify-content-center" >
      <div class="col-12">
        <div class="card animated_lefting" style="background-color: rgb(204 204 204 / 90%) !important;">
          <div class="card-body p-4">
            <h2 class="card-title text-center">Trámites</h2>
            <hr style="color: white;">
  
            <div class="row justify-content-evenly">
              <div class="col-12 col-md-5">
                <h3 class="text-center fw-bold">Quiero ser Italiano</h3>
                <hr style="color: red;width: 15% !important;border-top-width: initial;">
  
                <p style="text-align: justify;"><strong>La ciudadanía italiana se otorga sin límites de generaciones</strong></p>
  
                <p style="text-align: justify;">De acuerdo con la ley N. 91 de 1992, el hijo de padres Ciudadanos Italianos también es italiano, aunque haya nacido en el extranjero. Por lo que, los descendientes varones de emigrantes italianos pueden ser declarados Ciudadanos Italianos por filiación. Por otro lado, la transmisión por línea materna, sólo es posible para los niños nacidos después del 1 de enero de 1948.</p>
  
                <p style="text-align: justify;">Podés tramitar la ciudadanía de tres maneras:</p>
  
                <ul class="list-group list-group-flush list-group-numbered mb-3">
                  <li class="list-group-item bg-transparent border-0">Presentando la documentación (con turno previo) en el Consulado Italiano de tu jurisdicción.</li>
                  <li class="list-group-item bg-transparent border-0">Vía judicial.</li>
                  <li class="list-group-item bg-transparent border-0">Presencial en Italia.</li>
                </ul>
  
                <p style="text-align: justify;"><span style="font-weight: 600;">Los requisitros generales para empezar son:</span></p>
                <p style="text-align: justify;">· Asegurarte de tener un antepasado italiano.</p>
                <p style="text-align: justify;">· Demostrar que la cadena de transmisión de la Ciudadanía no se haya interrumpido, lo cual es confirmado mediante el Certificado de la Cámara Nacional Electoral (CNE).</p>
                <p style="text-align: justify;">· A partir de allí, nosotros te facilitamos la localización de las actas correspondientes para realizar el trámite.</p>
  
                <p style="text-align: justify;"><span style="color: #0c6c34; font-weight: 600;">Si no sos italiano te ofrecemos consultar toda la serie de trámites y formularios a rellenar para poder serlo:</span></p>
  
              </div>
              <div class="col-12 col-md-5">
                <h3 class="text-center fw-bold">Soy italiano</h3>
                <hr style="color: red;width: 15% !important;border-top-width: initial;">
                <p style="text-align: justify;">Como ciudadano italiano tenés la obligación fundamental de tener actualizados tus datos.<br>El registro es obligatorio desde 1988, según la ley N. 470.</p>
                <p style="text-align: justify;"><span style="font-weight: 600;">¿Estás inscripto en el AIRE?</span><br>Nosotros lo hacemos por vos.<br>El AIRE es el Anagrafe de los italianos residentes en el Exterior.</p>
                <p style="text-align: justify;"><span style="font-weight: 600;">¿Te mudaste?</span><br>Tramitamos tu cambio de domicilio.</p><p><span style="font-weight: 600;">¿Necesitás actualizar tu estado civil?</span><br>(Nacimiento, Matrimonio, Divorcio, Defunción).<br>Preparamos tu documentación.</p>
                <p style="text-align: justify;"><span style="color: #0c6c34; font-weight: 600;">Toda la documentación traducida es avalada por un Traductor Público Nacional matriculado.</span></p>
  
                <p style="text-align: justify;"><span style="color: #ff0a0a; font-weight: 600;">Si sos italiano te ofrecemos consultar toda la serie de trámites, formularios y validaciones que podés realizar junto a nosotros:<br></span></p>
  
              </div>
            </div>
  
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="container" id="nosotros">
    <div class="row justify-content-center animated_scaleUpCenter" >
      <div class="col-12">
        <div class="card " style="background-color: rgb(204 204 204 / 90%) !important;">
          <div class="card-body p-4">
            <h2 class="card-title text-center">Especialistas en la ciudadanía judicial y factibilidad</h2>
            <hr style="color: white;">
  
            <div class="row justify-content-evenly">
              <div class="col-12 col-md-5">
                <p style="text-align: justify;">Somos un estudio integral con 30 años de trayectoria que te otorga la posibilidad de investigar tus orígenes reconstruyendo el Árbol Genealógico de tu Familia.</p>
                <p>&nbsp;</p>
                <p style="text-align: justify;">Es nuestra especialidad buscar a tus antepasados italianos y organizar la documentación para que puedas tramitar tu pasaporte al mundo.</p>
                <p>&nbsp;</p>
                <p style="text-align: justify;">Nuestro objetivo es ayudarte a resolver tus problemas para que lo puedas obtener. Brindamos asistencia y asesoramiento parcial o completo.</p>
  
              </div>
              <div class="col-12 col-md-5">
                <img src="{{env('APP_URL')}}/assets/media/pasaporteItaliano.png" alt="" class="img-fluid">
              </div>
            </div>
  
          </div>
        </div>
  
        <div class="col-12 fondonosotros">
          <div class="row p-5 justify-content-end">
            <div class="col-12 col-md-5 p-4" style=" background-color: rgb(204 204 204 / 50%) !important;">
              <h4 class="text-center mb-4">LA CIUDADANÍA ITALIANA SE OTORGA SIN LÍMITES DE GENERACIONES </h4>
              <p style="text-align: justify;">De acuerdo con la ley N. 91 de 1992, el hijo de padres Ciudadanos Italianos también es italiano, aunque haya nacido en el extranjero. Por lo que, los descendientes varones de emigrantes italianos pueden ser declarados Ciudadanos Italianos por filiación. Por otro lado, la transmisión por línea materna, solo es posible para los niños nacidos después del 1 de enero de 1948.</p>
            </div>
          </div>
          
        </div>
      </div>
    </div>
  </div>

  <div class="container my-5" id="novedades">
    <div class="row justify-content-center animated_scaleUpCenter" >
      <div class="col-12">
        <div class="card" style="background-color: rgb(204 204 204 / 90%) !important;">
          <div class="card-body p-sm-3 p-md-5 ">
            <div class="row">
              <div class="col-12 col-md-6">
                <h4 class="card-title text-center">VIDEOCONSULTAS</h4>
                <h5 class="card-subtitle mb-2 text-muted">Consulta online con un asesor</h5>

                <p style="text-align: justify;">Disponemos de un nuevo e innovador servicio de <b>consultas online</b>, que se suma a nuestros canales de atención tradicional mediante el cual brindamos asistencia a quienes se encuentren interesados en tramitar la ciudadanía.&nbsp;</p>
                <p style="text-align: justify;">¿Necesitás ayuda para tramitar la <b>Ciudadanía Italiana en Argentina</b>&nbsp;o&nbsp;<b>Italia</b>? Nosotros podemos ayudarte!</p>
              </div>
              <div class="col-12 col-md-6">
                <img src="{{env('APP_URL')}}/assets/media/meet.jpg" alt="" class="img-fluid rounded ">
              </div>
            </div>
            
          </div>
        </div>
  
      </div>
    </div>
  </div>

  <div class="container" id="quienessomos">
    <div class="row justify-content-center" >
      <div class="col-12">
        <div class="card" style="background-color: rgb(204 204 204 / 90%) !important;">
          <div class="card-body p-sm-3 p-md-5 ">
            <div class="row">
              <div class="col-12 col-md-4 text-center">
                <h2 class="card-title text-center mb-4">Quienes somos</h2>
                <button type="button" class="btn btn-success rounded-circle">
                  <i class="flaticon-facebook-letter-logo"></i>
                </button>
                <button type="button" class="btn btn-success rounded-circle">
                  <i class="flaticon-instagram-logo"></i>
                </button>
                <button type="button" class="btn btn-success rounded-circle">
                  <i class="flaticon-twitter-logo"></i>
                </button>
                <button type="button" class="btn btn-success rounded-circle">
                  <i class="flaticon-youtube"></i>
                </button>
                <h4 class="mt-4" style="text-align: justify;">
                  Hace 13 años que trabajamos para cumplir el sueño de ser italiano/a a mucha gente.
                </h4>
              </div>
              <div class="col-12 col-md-8 ps-4">
                <p style="text-align: justify;">Somos un grupo de profesionales comprometidos con nuestras raíces italianas y con gran sentido de pertenencia. Trabajamos de manera conjunta con las instituciones argentinas e italianas para facilitar el acceso a la ciudadanía italiana y mejorar la gestión del proceso.</p>
                <p style="text-align: justify;">Nuestra modalidad de trabajo colaborativa se adapta a las necesidades de cada uno de nuestros clientes. Utilizamos mecanismos de investigación confiables y por demás eficientes que tienen por objeto encontrar la información precisa y requerida para la obtención de la ciudadanía.&nbsp;<span style="font-size: 16px;">De esta manera logramos disminuir los plazos que un proceso ordinario conlleva y podemos asegurar un servicio de calidad y excelencia.</span></p>
                <p style="text-align: justify;">Valoramos la confianza depositada por nuestros clientes y nos esforzamos por brindar una experiencia en consonancia.</p>
              </div>
            </div>
            
          </div>
        </div>

      </div>
    </div>
  </div>

  <footer class="bg-dark mt-3 py-5 p-lg-5" id="contacto">
    <div class="container-lg">
      <div class="row justify-content-center">
        <div class="col-10 col-md-4 my-3">
          <h5 Class="text-white text-left text-md-center">Ciudadanía Italiana</h5>
          <hr style="color: red;border-top-width: initial;">
          <ul class="list-group">
            <li class="list-group-item bg-transparent border-0 text-white">
              <div class="row">
                <div class="col-4 col-md-2 ">
                  <img src="{{env('APP_URL')}}/assets/media/argentina_flags.png" height="25">  
                </div>
                <div class="col-8 col-md-10 ps-0 pt-1">
                  En Argentina
                </div>
              </div>  
            </li>
            <li class="list-group-item bg-transparent border-0 text-white">
              <div class="row">
                <div class="col-4 col-md-2">
                  <img src="{{env('APP_URL')}}/assets/media/italy_flag.png" height="25">  
                </div>
                <div class="col-8 col-md-10 ps-0 pt-1">
                  En Italia
                </div>
              </div>
            </li>
            <li class="list-group-item bg-transparent border-0 text-white">
              <div class="row">
                <div class="col-4 col-md-2">
                  <img src="{{env('APP_URL')}}/assets/media/palace.png" height="25">  
                </div>
                <div class="col-8 col-md-10 ps-0 pt-1">
                  Via Materna
                </div>
              </div>
            </li>
          </ul>
        </div>
        <div class="col-10 col-md-4 my-3">
          <h5 Class="text-white text-left text-md-center">Servicios Individuales</h5>
          <hr style="color: red;border-top-width: initial;">
          <ul class="list-group">
            <li class="list-group-item bg-transparent border-0 text-white py-0 pe-0">
              <button type="button" class="btn btn-sm btn-link p-0 text-white">Verificación de Elegibilidad</button>
            </li>
            <li class="list-group-item bg-transparent border-0 text-white py-0 pe-0">
              <button type="button" class="btn btn-sm btn-link p-0 text-white">Búsqueda de Actas</button>
            </li>
            <li class="list-group-item bg-transparent border-0 text-white py-0 pe-0">
              <button type="button" class="btn btn-sm btn-link p-0 text-white">Traducciones</button>
            </li>
            <li class="list-group-item bg-transparent border-0 text-white py-0 pe-0">
              <button type="button" class="btn btn-sm btn-link p-0 text-white">Asesoramiento Legal</button>
            </li>
            <li class="list-group-item bg-transparent border-0 text-white py-0 pe-0">
              <button type="button" class="btn btn-sm btn-link p-0 text-white">Reconstrución de Partidas</button>
            </li>
            <li class="list-group-item bg-transparent border-0 text-white py-0 pe-0">
              <button type="button" class="btn btn-sm btn-link p-0 text-white">Actualización de Anágrafe</button>
            </li>
            <li class="list-group-item bg-transparent border-0 text-white py-0 pe-0">
              <button type="button" class="btn btn-sm btn-link p-0 text-white">Rectificación de documentos</button>
            </li>
            <li class="list-group-item bg-transparent border-0 text-white py-0 pe-0">
              <button type="button" class="btn btn-sm btn-link p-0 text-white">Genealogía Familiar</button>
            </li>
          </ul>
        </div>
        <div class="col-10 col-md-4 my-3">
          <h5 Class="text-white text-left text-md-center">Contacto</h5>
          <hr style="color: red;border-top-width: initial;">
          <ul class="list-group">
            <li class="list-group-item bg-transparent border-0 text-white">
              <a href="mailto:juliacsegurov@gmail.com" target="_blank" class="text-white">
                <div class="row">
                  <div class="col-4 col-md-2 text-center">
                    <i class="fa-regular fa-envelope"></i> 
                  </div>
                  <div class="col-8 col-md-10 ps-0">
                    Email
                  </div>
                </div>
              </a>
            </li>
            <li class="list-group-item bg-transparent border-0 text-white">
              <a href="https://api.whatsapp.com/send?phone=+5493413725249&text=Hola,%20necesito%20informaci%C3%B3n%20sobre%20la%20ciudadan%C3%ADa%20italiana." target="_blank" class="text-white">
                <div class="row">
                  <div class="col-4 col-md-2 text-center">
                    <i class="fa-brands fa-whatsapp"></i>
                  </div>
                  <div class="col-8 col-md-10 ps-0">
                    WhatsApp
                  </div>
                </div>
              </a>
            </li>
            <li class="list-group-item bg-transparent border-0 text-white">
              <a href="https://www.instagram.com/ciudadaniaitaliana.it/" target="_blank" class="text-white">
                <div class="row">
                  <div class="col-4 col-md-2 text-center">
                    <i class="fa-brands fa-instagram"></i>
                  </div>
                  <div class="col-8 col-md-10 ps-0">
                    Instagram
                  </div>
                </div>
              </a>
            </li>

          </ul>
        </div>
        <div class="col-11 mt-5">
          <h3 class="text-center text-white"> Formulario de contacto </h3>

          @include('Layout.errors')

          @if (session('status'))
            <div class="row justify-content-center">
              <div class="col-10">
                <div class="alert alert-success px-1">
                  {{ session('status') }}
                </div>
              </div>
            </div>
          @endif

          <form id="formcontacto" action="{{ route('contact.store') }}" method="POST">
            @csrf
            <div class="row">
              <div class="col-12 col-md-6">
                <div class="mb-2">
                  <input type="text" class="form-control validate" name="nombre" placeholder="Nombre" required value="{{ old('nombre') }}">
                </div>
                <div class="mb-2">
                  <input type="email" class="form-control validate" name="email" placeholder="Correo Electrónico" required value="{{ old('email') }}">
                </div>
                <div class="mb-2">
                  <input type="text" class="form-control validate" name="telefono" placeholder="Teléfono" required value="{{ old('telefono') }}">
                </div>
                <div class="mb-2">
                  <select class="form-control select2 validate" name="motivo" title="Motivo" style="width: 100%" required>
                    <option></option>
                    <option value="CI">Ciudadanía</option>
                    <option value="PA">Pasaporte</option>
                    <option value="BP">Búsqueda de Partidas</option>
                    <option value="TR">Traducciones</option>
                    <option value="OT">Otros</option>
                  </select>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="">
                  <textarea class="form-control validate" name="consulta" rows="5" placeholder="Escribínos tu consulta, mientras más detallada, más concreta será tu respuesta." required>{{ old('consulta') }}</textarea>
                </div>
                <div class="d-grid gap-2 mt-1">
                  <button type="button" class="btn btn-success g-recaptcha" id="guardarconsulta">
                    Consultar
                  </button>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </footer>
  
  @include('home.foot')
@endsection