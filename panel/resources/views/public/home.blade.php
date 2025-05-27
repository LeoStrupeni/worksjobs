<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>Strupeni Electronica</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Strupeni Electronica">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app_url" content="{{env('APP_URL')}}">
    <meta http-equiv="Cache-control" content="no-cache">

    <!--begin::Icons -->
    <link href="{{env('APP_URL')}}/assets/icons/line-awesome/css/line-awesome.css" rel="stylesheet" type="text/css" />
    <link href="{{env('APP_URL')}}/assets/icons/flaticon/flaticon.css" rel="stylesheet" type="text/css" />
    <link href="{{env('APP_URL')}}/assets/icons/fontawesome-free-6.7.0-web/css/all.min.css" rel="stylesheet" type="text/css" />

    <!--begin::Styles -->
    <link href="{{env('APP_URL')}}/assets/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{env('APP_URL')}}/assets/media/icono.ico" rel="icon"/>

    <!--begin::Fonts -->
    <style>
        @font-face {
            font-family: TitilliumWeb-Light;
            src: url("{{env('APP_URL')}}/assets/fonts/TitilliumWeb-Light.ttf") format('truetype');
            font-weight: lighter;
            font-style: inherit;
        }
        @font-face {
            font-family: TitilliumWeb-Regular;
            src: url("{{env('APP_URL')}}/assets/fonts/TitilliumWeb-Regular.ttf") format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: TitilliumWeb-SemiBold;
            src: url("{{env('APP_URL')}}/assets/fonts/TitilliumWeb-SemiBold.ttf") format('truetype');
            font-weight:bold;
            font-style: normal;
        }

        * {
            font-family: TitilliumWeb-Regular, TitilliumWeb-Light, TitilliumWeb-SemiBold !important;
        }

    </style>

    <style>
        .bg-se-primary {
            background-color: #00274e !important; }
        .bg-se-secondary {
            background-color: #fafafa !important; }
        .bg-se-success {
            background-color: #60d075 !important; }

        .bg-se-rgb-primary {
            background-color: rgb(0, 39, 78) !important; }
        .bg-se-rgb-secondary {
            background-color: rgb(250, 250, 250) !important; }
        .bg-se-rgb-success {
            background-color: rgb(96, 208, 117) !important; }

        .text-se-success {
            color: #60d075 !important; }

        a.text-se-success:hover, a.text-se-success:focus {
            color: rgba(96, 208, 117, .8) !important; }

        .btn-se-success {
            color: #fff;
            background-color: #60d075;
            border-color: #60d075; }
        .btn-se-success:hover {
            color: #fff;
            background-color: rgba(96, 208, 117, .8) !important;
            border-color: rgba(96, 208, 117, .8) !important; }
        .btn-se-success:focus, .btn-se-success.focus {
            -webkit-box-shadow: 0 0 0 0.2rem rgba(96, 208, 117, .8) !important;;
            box-shadow: 0 0 0 0.2rem rgba(96, 208, 117, .8) !important; }
        .btn-se-success.disabled, .btn-se-success:disabled {
            color: #fff;
            background-color: #0abb87;
            border-color: #0abb87; }
        .btn-se-success:not(:disabled):not(.disabled):active, .btn-se-success:not(:disabled):not(.disabled).active,
        .show > .btn-se-success.dropdown-toggle {
            color: #fff;
            background-color: #078b64;
            border-color: #077e5b; }
        .btn-se-success:not(:disabled):not(.disabled):active:focus, .btn-se-success:not(:disabled):not(.disabled).active:focus,
        .show > .btn-se-success.dropdown-toggle:focus {
            -webkit-box-shadow: 0 0 0 0.2rem rgba(47, 197, 153, 0.5);
            box-shadow: 0 0 0 0.2rem rgba(47, 197, 153, 0.5); }

        .hover-success:hover {
            color: #60d075 !important;
        }
        .hover-white:hover {
            color: white !important;
        }
        .btn-se-slider {
            background-color: #60d075 !important;
            width: 10px!important;
            height: 10px!important;
            border-radius: 50% !important;
        }

        .carousel-se-caption {
            position: absolute;
            right: 15%;
            top: 30%;
            left: 15%;
            padding-top: 1.25rem;
            padding-bottom: 1.25rem;
            color: #fff;
        }
    </style>

    

</head>
<body>
    <div class="offcanvas offcanvas-start bg-se-primary" data-bs-scroll="false" tabindex="-1" id="offcanvasMenu" aria-labelledby="offcanvasMenuLabel">
        <div class="offcanvas-header" data-bs-theme="dark">
            <img src="{{env('APP_URL')}}/assets/media/icono.ico" alt="Logo" height="60">
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body">
            <div>
            
            </div>
            <ul class="list-unstyled ps-0">
                <li class="mb-2">
                    <a href="javascript:;" class="btn p-0 border-0 me-3 text-white text-uppercase hover-success">inicio</a>
                </li>
                <li class="mb-2">
                    <a href="javascript:;" class="btn p-0 border-0 me-3 text-white text-uppercase hover-success">nuestros servicios</a>
                </li>

                <li class="mb-2">
                    <a href="javascript:;" class="btn p-0 border-0 me-3 text-white text-uppercase hover-success">nosotros</a>
                </li>

                <li class="mb-2">
                    <a href="javascript:;" class="btn p-0 border-0 me-3 text-white text-uppercase hover-success">contacto</a>
                </li>

                <li class="border-top my-3"></li>
                <li class="mb-2">
                        <a href="javascript:;" class="btn p-0 border-0 ms-3">
                        <i class="flaticon-facebook-logo-button text-se-success hover-white"></i>
                    </a>
                    <a href="javascript:;" class="btn p-0 border-0 mx-2">
                        <i class="flaticon-instagram-logo text-se-success hover-white"></i>
                    </a>
                    <a href="javascript:;" class="btn p-0 border-0">
                        <i class="flaticon-linkedin text-se-success hover-white"></i>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <nav class="navbar navbar-expand-lg p-0 bg-se-primary" style="height: 10vh;">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home.index') }}">
                <img src="{{env('APP_URL')}}/assets/media/icono.ico" alt="Logo" height="60">
            </a>

            <div class="btn-group d-lg-none" role="group">
                <button type="button" class="btn btn-link rounded-circle p-0 " data-bs-toggle="offcanvas" href="#offcanvasMenu" >
                    <i class="fa-solid fa-bars text-se-success hover-white"></i>
                </button>
            </div>
            <div class="collapse navbar-collapse justify-content-end">
                <ul class="navbar-nav">
                    <a href="javascript:;" class="btn p-0 border-0 me-3 text-white text-uppercase hover-success">inicio</a>
                    <a href="javascript:;" class="btn p-0 border-0 mx-3 text-white text-uppercase hover-success">nuestros servicios</a>
                    <a href="javascript:;" class="btn p-0 border-0 mx-3 text-white text-uppercase hover-success">nosotros</a>
                    <a href="javascript:;" class="btn p-0 border-0 mx-3 text-white text-uppercase hover-success">contacto</a>
                    <a href="javascript:;" class="btn p-0 border-0 ms-3">
                        <i class="flaticon-facebook-logo-button text-se-success hover-white"></i>
                    </a>
                    <a href="javascript:;" class="btn p-0 border-0 mx-2">
                        <i class="flaticon-instagram-logo text-se-success hover-white"></i>
                    </a>
                    <a href="javascript:;" class="btn p-0 border-0">
                        <i class="flaticon-linkedin text-se-success hover-white"></i>
                    </a>
                </ul>
            </div>

        </div>
    </nav>
    {{--  --}}
    <div id="carouselstrupeni" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-pause="false">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#carouselstrupeni" data-bs-slide-to="0" class="btn-se-slider mx-2 active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#carouselstrupeni" data-bs-slide-to="1" class="btn-se-slider mx-2" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#carouselstrupeni" data-bs-slide-to="2" class="btn-se-slider mx-2" aria-label="Slide 3"></button>
        </div>
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="{{env('APP_URL')}}/assets/media/imagenes/Home-Header-1.png" style="height: 90vh;" class="d-block w-100" alt="...">
                <div class="carousel-se-caption d-none d-md-block">
                    <p class="text-start fs-1" style="font-size: xxx-large!important;">Llevamos la seguridad de<br>tu empresa <span class="text-se-success">al siguiente nivel.</span></p>
                    <a href="javascript:;" class="btn btn-se-success  px-3 py-2 text-uppercase">conocé nuestros servicios</a>
                </div>
            </div>
            <div class="carousel-item">
                <img src="{{env('APP_URL')}}/assets/media/imagenes/Home-Innovación.png" style="height: 90vh;" class="d-block w-100" alt="...">
                <div class="carousel-se-caption d-none d-md-block">
                    <p class="text-start fs-1" style="font-size: xxx-large!important;">Innovación</p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="{{env('APP_URL')}}/assets/media/imagenes/Home-Header-1.png" style="height: 90vh;" class="d-block w-100" alt="...">
                <div class="carousel-se-caption d-none d-md-block">
                    <br>
                    <br>
                    <a href="javascript:;" class="btn btn-se-success  px-3 py-2 text-uppercase">contactanos</a>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="row justify-content-center align-items-center my-3">
            <div class="col-5 p-4">
                <p class="text-start fs-1 text-uppercase text-se-success">Innovación y Soluciones para Empresas</p>
                <p class="text-start m-0" style="text-align: justify !important;line-height: 17px !important;">
                    En Strupeni Tecnologías, llevamos más de 20 años desarrollando soluciones tecnológicas que impulsan el crecimiento y la eficiencia de las empresas. Nuestra experiencia nos permite entender las necesidades de cada cliente y ofrecer herramientas innovadoras que optimizan procesos, mejoran la productividad y potencian la transformación digital.
                    <br><br>
                    Nos especializamos en proporcionar tecnología adaptada a los desafíos del mundo empresarial, asegurando soluciones confiables, escalables y a la vanguardia del mercado.
                    <br><br>
                    Si buscas un aliado estratégico para llevar tu empresa al siguiente nivel, en Strupeni Tecnologías estamos listos para ayudarte.
                </p>
                <a href="javascript:;" class="btn btn-se-success mt-4 px-3 py-2 text-uppercase">Conocé nuestra historia</a>
            </div>
            <div class="col-5 p-5">
                <img src="{{env('APP_URL')}}/assets/media/imagenes/Home-Innovación.png" class="d-block w-100" alt="...">
            </div>
        </div>
    </div>
    <div class="container-fluid bg-se-secondary">
        <div class="row my-3">
            <div class="col">
                <p class="text-center fs-1 text-uppercase text-se-success">nuestros servicios</p>
            </div>
        </div>
        
    </div>

    <script src="{{env('APP_URL')}}/assets/js/jquery/dist/jquery.js"></script>
    <script src="{{env('APP_URL')}}/assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="{{env('APP_URL')}}/assets/plugins/moment/min/moment.min.js"></script>
    <script src="{{env('APP_URL')}}/assets/icons/fontawesome-free-6.7.0-web/js/all.min.js"></script>
</body>
</html>