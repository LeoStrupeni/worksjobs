@extends('layout')

@section('link_by_page')
@endsection

@section('style_by_page')
    <style>
        a {
            color: rgb(var(--bs-success-rgb));
            display:inline-block;
            text-decoration: none;
            font-weight: 400;
        }
        h2 {
            text-align: center;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            display:inline-block;
            margin: 40px 8px 10px 8px;
            color: #cccccc;
        }
        .wrapper {
            display: flex;
            align-items: center;
            flex-direction: column;
            justify-content: center;
            width: 100%;
            min-height: 100vh;
            padding: 20px;
        }
        #formContent {
            -webkit-border-radius: 10px 10px 10px 10px;
            border-radius: 10px 10px 10px 10px;
            background: #00274e;
            padding: 30px;
            width: 90%;
            max-width: 450px;
            position: relative;
            padding: 0px;
            -webkit-box-shadow: 0 30px 60px 0 rgba(0,0,0,0.3);
            box-shadow: 0 30px 60px 0 rgba(0,0,0,0.3);
            text-align: center;
        }
        #formFooter {
            background-color: #00274e;
            border-top: 1px solid #00274e;
            padding: 25px;
            text-align: center;
            -webkit-border-radius: 0 0 10px 10px;
            border-radius: 0 0 10px 10px;
        }
        h2.inactive {
            color: #cccccc;
        }
        h2.active {
            color: #0d0d0d;
            border-bottom: 2px solid rgb(var(--bs-danger-rgb)) !important;
        }
        input[type=button], input[type=submit], input[type=reset]  {
            background-color: rgb(var(--bs-success-rgb));
            border: none;
            color: white;
            padding: 15px 80px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            text-transform: uppercase;
            font-size: 13px;
            -webkit-box-shadow: 0 10px 30px 0 rgb(var(--bs-success-rgb));
            box-shadow: 0 10px 30px 0 rgb(var(--bs-success-rgb));
            -webkit-border-radius: 5px 5px 5px 5px;
            border-radius: 5px 5px 5px 5px;
            margin: 5px 20px 40px 20px;
            -webkit-transition: all 0.3s ease-in-out;
            -moz-transition: all 0.3s ease-in-out;
            -ms-transition: all 0.3s ease-in-out;
            -o-transition: all 0.3s ease-in-out;
            transition: all 0.3s ease-in-out;
        }

        input[type=button]:hover, input[type=submit]:hover, input[type=reset]:hover  {
            background-color: rgba(var(--bs-success-rgb),.8)
        }

        input[type=button]:active, input[type=submit]:active, input[type=reset]:active  {
            -moz-transform: scale(0.95);
            -webkit-transform: scale(0.95);
            -o-transform: scale(0.95);
            -ms-transform: scale(0.95);
            transform: scale(0.95);
        }

        input[type=text],input[type=email],input[type=password] {
            background-color: #f6f6f6;
            border: none;
            color: #0d0d0d;
            padding: 15px 32px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 5px;
            width: 85%;
            border: 2px solid #f6f6f6;
            -webkit-transition: all 0.5s ease-in-out;
            -moz-transition: all 0.5s ease-in-out;
            -ms-transition: all 0.5s ease-in-out;
            -o-transition: all 0.5s ease-in-out;
            transition: all 0.5s ease-in-out;
            -webkit-border-radius: 5px 5px 5px 5px;
            border-radius: 5px 5px 5px 5px;
        }

        input[type=text]:focus {
            background-color: #fff;
            border-bottom: 2px solid #5fbae9;
        }
        input[type=password]:focus {
            background-color: #fff;
            border-bottom: 2px solid #5fbae9;
        }

        input[type=text]:placeholder {
            color: #cccccc;
        }
        input[type=password]:placeholder {
            color: #cccccc;
        }


        /* ANIMATIONS */

        /* Simple CSS3 Fade-in-down Animation */
        .fadeInDown {
            -webkit-animation-name: fadeInDown;
            animation-name: fadeInDown;
            -webkit-animation-duration: 1s;
            animation-duration: 1s;
            -webkit-animation-fill-mode: both;
            animation-fill-mode: both;
        }

        @-webkit-keyframes fadeInDown {
            0% {
                opacity: 0;
                -webkit-transform: translate3d(0, -100%, 0);
                transform: translate3d(0, -100%, 0);
            }
            100% {
                opacity: 1;
                -webkit-transform: none;
                transform: none;
            }
        }

        @keyframes fadeInDown {
            0% {
                opacity: 0;
                -webkit-transform: translate3d(0, -100%, 0);
                transform: translate3d(0, -100%, 0);
            }
            100% {
                opacity: 1;
                -webkit-transform: none;
                transform: none;
            }
        }

        /* Simple CSS3 Fade-in Animation */
        @-webkit-keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
        @-moz-keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
        @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }

        .fadeIn {
            opacity:0;
            -webkit-animation:fadeIn ease-in 1;
            -moz-animation:fadeIn ease-in 1;
            animation:fadeIn ease-in 1;

            -webkit-animation-fill-mode:forwards;
            -moz-animation-fill-mode:forwards;
            animation-fill-mode:forwards;

            -webkit-animation-duration:1s;
            -moz-animation-duration:1s;
            animation-duration:1s;
        }

        .fadeIn.first {
            -webkit-animation-delay: 0.4s;
            -moz-animation-delay: 0.4s;
            animation-delay: 0.4s;
        }

        .fadeIn.second {
            -webkit-animation-delay: 0.6s;
            -moz-animation-delay: 0.6s;
            animation-delay: 0.6s;
        }

        .fadeIn.third {
            -webkit-animation-delay: 0.8s;
            -moz-animation-delay: 0.8s;
            animation-delay: 0.8s;
        }

        .fadeIn.fourth {
            -webkit-animation-delay: 1s;
            -moz-animation-delay: 1s;
            animation-delay: 1s;
        }

        /* Simple CSS3 Fade-in Animation */
        .underlineHover:after {
            display: block;
            left: 0;
            bottom: -10px;
            width: 0;
            height: 2px;
            background-color: rgb(0,0,0,0.9);
            content: "";
            transition: width 0.2s;
        }

        .underlineHover:hover {
            color: rgb(var(--bs-danger-rgb))!important;
        }

        .underlineHover:hover:after{
            width: 100%;
        }



        /* OTHERS */

        *:focus {
            outline: none;
        }

        #icon {
            width:60%;
        }


        .tooltip {
            position: relative;
            display: inline-block;
        }
        
        .tooltip .tooltiptext {
            visibility: hidden;
            width: 140px;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 150%;
            left: 50%;
            margin-left: -75px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .tooltip .tooltiptext::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #555 transparent transparent transparent;
        }
        
        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        .form-check-input:checked {
            background-color: green!important;
            border-color: green!important;
        }

    </style>
@endsection

@section('Content')

    <div class="wrapper fadeInDown">
        <div id="formContent">
            <div class="fadeIn first">
                <img src="{{env('APP_URL')}}/assets/media/Logo.png" id="icon" alt="User Icon" class="rounded-circle my-3"/>
            </div>

            @if ($errors->any())
                <div class="row justify-content-center">
                    <div class="col-10">
                        <div class="alert alert-danger px-1" role="alert">
                            <ul class="m-0">
                                @foreach ($errors->all() as $error)
                                    <li class="text-start">{{$error}}</li>
                                @endforeach    
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
            
        <!-- Login Form -->
            <form id="loginForm" action="" method="POST">
                @csrf
                <input type="text" id="Email" class="fadeIn second" name="email" placeholder="Email" value="{{old('email')}}" autofocus required>
                {{-- @error('email') <small class="text-danger fst-italic"> {{$message}} </small> @enderror  --}}
                <input type="password" id="password" class="fadeIn third" name="password" placeholder="Password" required>
                {{-- @error('password') <small class="text-danger fst-italic">  {{$message}} </small>  @enderror --}}
                <label class="text-white my-2">
                    <input class="form-check-input" type="checkbox" name="remember">
                    Recuerdame
                </label><br>

                <input type="submit" class="fadeIn fourth" value="Ingresar">
            </form>
        <!-- Remind Passowrd -->
            <div id="formFooter">
                <a class="underlineHover" href="/password/reset">¿Olvidaste tu Contraseña?</a> <br>
            </div>
        </div>
    </div>

@endsection

@section('script_by_page')
@endsection
