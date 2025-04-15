<head>
    <meta charset="UTF-8">
    <title>Strupeni Jobs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Strupeni Jobs">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app_url" content="{{env('APP_URL')}}">
    <meta http-equiv="Cache-control" content="no-cache">

    <!--begin::Icons -->
    <link href="{{env('APP_URL')}}/assets/icons/line-awesome/css/line-awesome.css" rel="stylesheet" type="text/css" />
    <link href="{{env('APP_URL')}}/assets/icons/flaticon/flaticon.css" rel="stylesheet" type="text/css" />
    <link href="{{env('APP_URL')}}/assets/icons/flaticon2/flaticon.css" rel="stylesheet" type="text/css" />
    <link href="{{env('APP_URL')}}/assets/icons/fontawesome-free-6.7.0-web/css/all.min.css" rel="stylesheet" type="text/css" />

    <!--begin::Styles -->
    <link href="{{env('APP_URL')}}/assets/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{env('APP_URL')}}/assets/media/icono.ico" rel="icon"/>

    <!--begin::Plugings -->
    <link href="{{env('APP_URL')}}/assets/plugins/toastr/build/toastr.css" rel="stylesheet" type="text/css" />
    <link href="{{env('APP_URL')}}/assets/plugins/sweetalert2/dist/sweetalert2.css" rel="stylesheet" type="text/css" />

    <link href="{{env('APP_URL')}}/assets/plugins/bootstrap-datepicker/dist/css/bootstrap-datepicker3.css" rel="stylesheet" type="text/css" />
    <link href="{{env('APP_URL')}}/assets/plugins/bootstrap-datetime-picker/css/bootstrap-datetimepicker.css" rel="stylesheet" type="text/css" />
    <link href="{{env('APP_URL')}}/assets/plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css" />
    
    <link href="{{env('APP_URL')}}/assets/plugins/select2/dist/css/select2.css" rel="stylesheet" type="text/css" />
    <link href="{{env('APP_URL')}}/assets/plugins/bootstrap-select-1.14.0-beta3/docs/docs/dist/css/bootstrap-select.min.css" rel="stylesheet" type="text/css"/>
    
    <!--begin::Fonts -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');
        * {
            font-family: 'Poppins' !important;
        }
    </style>

    <style>
        .btn-header-menu:hover button {
            color: #fff;
            background-color: #000;
            border: 0px solid #000 !important; 
        }

        .dropdown-item{
            display:block;
            width:100%;
            padding:var(--bs-dropdown-item-padding-y) var(--bs-dropdown-item-padding-x);
            clear:both;
            font-weight:400;
            color:var(--bs-dropdown-link-color);
            text-align:inherit;
            text-decoration:none;
            white-space:nowrap;
            background-color:transparent;
            border:0;border-radius:var(--bs-dropdown-item-border-radius,0)
        }
        .dropdown-item:focus,.dropdown-item:hover{
            color:var(--bs-dropdown-link-hover-color);
            background-color:var(--bs-dropdown-link-hover-bg)
        }
        .dropdown-item.active,.dropdown-item:active{
            color:var(--bs-dropdown-link-active-color);
            text-decoration:none;background-color:var(--bs-dropdown-link-active-bg)
        }
    </style>
    <style>
        .dropdown-toggle { outline: 0; }

        .btn-toggle {
            padding: .25rem .5rem;
            font-weight: 600;
            color: var(--bs-emphasis-color);
            background-color: transparent;
        }
        .btn-toggle:hover,
            .btn-toggle:focus {
            color: rgba(var(--bs-emphasis-color-rgb), .85);
            background-color: var(--bs-tertiary-bg);
        }

        .btn-toggle::before {
            width: 1.25em;
            line-height: 0;
            content: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='rgba%280,0,0,.5%29' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 14l6-6-6-6'/%3e%3c/svg%3e");
            transition: transform .35s ease;
            transform-origin: .5em 50%;
        }

        [data-bs-theme="dark"] .btn-toggle::before {
            content: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='rgba%28255,255,255,.5%29' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 14l6-6-6-6'/%3e%3c/svg%3e");
        }

        .btn-toggle[aria-expanded="true"] {
            color: rgba(var(--bs-emphasis-color-rgb), .85);
            }
        .btn-toggle[aria-expanded="true"]::before {
            transform: rotate(90deg);
        }

        .btn-toggle-nav a {
            padding: .1875rem .5rem;
            margin-top: .125rem;
            margin-left: 1.25rem;
        }
        .btn-toggle-nav a:hover,
        .btn-toggle-nav a:focus {
            background-color: var(--bs-tertiary-bg);
        }

        .btn-header-public {
            background-color: #000 !important;
            color: white!important;
        }

    </style>

    @yield('link_by_page')

    <style>
        body {
            height: 100vh;
            /* background-color: #000; */
            background-image: url("{{env('APP_URL')}}/assets/media/fondo.jpg");
            background-origin: padding-box;
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
            background-attachment: fixed;
        }

    </style>

    <style>
        ::after,
        ::before {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        a {
            text-decoration: none;
        }

        li {
            list-style: none;
        }

        h1 {
            font-weight: 600;
            font-size: 1.5rem;
        }

        .wrapper {
            display: flex;
        }

        .main {
            min-height: 100vh;
            width: 100%;
            overflow: hidden;
            transition: all 0.35s ease-in-out;
            background-color: #fafbfe;
        }

        #sidebar {
            width: 70px;
            min-width: 70px;
            z-index: 1000;
            transition: all .25s ease-in-out;
            background-color: #0e2238;
            display: flex;
            flex-direction: column;
        }

        #sidebar.expand {
            width: 260px;
            min-width: 260px;
        }

        .toggle-btn {
            background-color: transparent;
            cursor: pointer;
            border: 0;
            padding: 1rem 1.5rem;
        }

        .toggle-btn i {
            font-size: 1.5rem;
            color: #FFF;
        }

        .sidebar-logo {
            margin: auto 0;
        }

        .sidebar-logo a {
            color: #FFF;
            font-size: 1.15rem;
            font-weight: 600;
        }

        #sidebar:not(.expand) .sidebar-logo,
        #sidebar:not(.expand) a.sidebar-link span {
            display: none;
        }

        .sidebar-nav {
            padding: 2rem 0;
            flex: 1 1 auto;
        }

        a.sidebar-link {
            padding: .625rem 1.625rem;
            color: #FFF;
            display: block;
            font-size: 0.9rem;
            white-space: nowrap;
            border-left: 3px solid transparent;
        }

        .sidebar-link i {
            font-size: 1.1rem;
            margin-right: .75rem;
        }

        a.sidebar-link:hover {
            background-color: rgba(255, 255, 255, .075);
            border-left: 3px solid #3b7ddd;
        }

        .sidebar-item {
            position: relative;
        }

        #sidebar:not(.expand) .sidebar-item .sidebar-dropdown {
            position: absolute;
            top: 0;
            left: 70px;
            background-color: #0e2238;
            padding: 0;
            min-width: 15rem;
            display: none;
        }

        #sidebar:not(.expand) .sidebar-item:hover .has-dropdown+.sidebar-dropdown {
            display: block;
            max-height: 15em;
            width: 100%;
            opacity: 1;
        }

        #sidebar.expand .sidebar-link[data-bs-toggle="collapse"]::after {
            border: solid;
            border-width: 0 .075rem .075rem 0;
            content: "";
            display: inline-block;
            padding: 2px;
            position: absolute;
            right: 1.5rem;
            top: 1.4rem;
            transform: rotate(-135deg);
            transition: all .2s ease-out;
        }

        #sidebar.expand .sidebar-link[data-bs-toggle="collapse"].collapsed::after {
            transform: rotate(45deg);
            transition: all .2s ease-out;
        }
    </style>

    <style>
        .lefting {
            animation: lefting 2s ease 0s 1 normal none;
        }
        @keyframes lefting {
            0% {
                animation-timing-function: ease-in;
                opacity: 0;
                transform: translateX(-250px);
            }

            38% {
                animation-timing-function: ease-out;
                opacity: 1;
                transform: translateX(0);
            }

            55% {
                animation-timing-function: ease-in;
                transform: translateX(-68px);
            }

            72% {
                animation-timing-function: ease-out;
                transform: translateX(0);
            }

            81% {
                animation-timing-function: ease-in;
                transform: translateX(-28px);
            }

            90% {
                animation-timing-function: ease-out;
                transform: translateX(0);
            }

            95% {
                animation-timing-function: ease-in;
                transform: translateX(-8px);
            }

            100% {
                animation-timing-function: ease-out;
                transform: translateX(0);
            }
        }
        .scaleUpCenter {
            animation: scaleUpCenter 1s ease 0s 1 normal both;
        }
        @keyframes scaleUpCenter {
            0% {
                transform: scale(0.5);
            }

            100% {
                transform: scale(1);
            }
        }

        #navheaderpublic, .ir, #navheaderlogo {
            transition: height 0.5s linear;
        }
    </style>

    <style>
        /* loading-spinner */
            .loading-spinner {
                position: fixed;
                z-index: 9999;
                overflow: show;
                margin: auto;
                top: 0;
                left: 0;
                bottom: 0;
                right: 0;
                width: 50px;
                height: 50px;
            }
            /* Transparent Overlay */
            .loading-spinner:before {
                content: '';
                display: block;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(255,255,255,0.5);
            }
            /* :not(:required) hides these rules from IE9 and below */
            .loading-spinner:not(:required) {
                /* hide "loading..." text */
                font: 0/0 a;
                color: transparent;
                text-shadow: none;
                background-color: transparent;
                border: 0;
            }
            .loading-spinner:not(:required):after {
                content: '';
                display: block;
                font-size: 10px;
                width: 50px;
                height: 50px;
                margin-top: -0.5em;

                border: 5px solid rgba(33, 150, 243, 1.0);
                border-radius: 100%;
                border-bottom-color: transparent;
                -webkit-animation: spinner 1s linear 0s infinite;
                animation: spinner 1s linear 0s infinite;
            }
            /* Animation */
            @-webkit-keyframes spinner {
                0% {
                    -webkit-transform: rotate(0deg);
                    -moz-transform: rotate(0deg);
                    -ms-transform: rotate(0deg);
                    -o-transform: rotate(0deg);
                    transform: rotate(0deg);
                }
                100% {
                    -webkit-transform: rotate(360deg);
                    -moz-transform: rotate(360deg);
                    -ms-transform: rotate(360deg);
                    -o-transform: rotate(360deg);
                    transform: rotate(360deg);
                }
            }
            @-moz-keyframes spinner {
                0% {
                    -webkit-transform: rotate(0deg);
                    -moz-transform: rotate(0deg);
                    -ms-transform: rotate(0deg);
                    -o-transform: rotate(0deg);
                    transform: rotate(0deg);
                }
                100% {
                    -webkit-transform: rotate(360deg);
                    -moz-transform: rotate(360deg);
                    -ms-transform: rotate(360deg);
                    -o-transform: rotate(360deg);
                    transform: rotate(360deg);
                }
            }
            @-o-keyframes spinner {
                0% {
                    -webkit-transform: rotate(0deg);
                    -moz-transform: rotate(0deg);
                    -ms-transform: rotate(0deg);
                    -o-transform: rotate(0deg);
                    transform: rotate(0deg);
                }
                100% {
                    -webkit-transform: rotate(360deg);
                    -moz-transform: rotate(360deg);
                    -ms-transform: rotate(360deg);
                    -o-transform: rotate(360deg);
                    transform: rotate(360deg);
                }
            }
            @keyframes spinner {
                0% {
                    -webkit-transform: rotate(0deg);
                    -moz-transform: rotate(0deg);
                    -ms-transform: rotate(0deg);
                    -o-transform: rotate(0deg);
                    transform: rotate(0deg);
                }
                100% {
                    -webkit-transform: rotate(360deg);
                    -moz-transform: rotate(360deg);
                    -ms-transform: rotate(360deg);
                    -o-transform: rotate(360deg);
                    transform: rotate(360deg);
                }
            }
        /* end loading-spinner */
        /* lds-roller */
            .lds-roller {
                display: inline-block;
                position: relative;
                width: 80px;
                height: 80px;
            }

            .lds-roller div {
                animation: lds-roller 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
                transform-origin: 40px 40px;
            }

            .lds-roller div:after {
                content: " ";
                display: block;
                position: absolute;
                width: 7px;
                height: 7px;
                border-radius: 50%;
                background: rgb(39, 57, 92);
                margin: -4px 0 0 -4px;
            }

            .lds-roller div:nth-child(1) {
                animation-delay: -0.036s;
            }

            .lds-roller div:nth-child(1):after {
                top: 63px;
                left: 63px;
            }

            .lds-roller div:nth-child(2) {
                animation-delay: -0.072s;
            }

            .lds-roller div:nth-child(2):after {
                top: 68px;
                left: 56px;
            }

            .lds-roller div:nth-child(3) {
                animation-delay: -0.108s;
            }

            .lds-roller div:nth-child(3):after {
                top: 71px;
                left: 48px;
            }

            .lds-roller div:nth-child(4) {
                animation-delay: -0.144s;
            }

            .lds-roller div:nth-child(4):after {
                top: 72px;
                left: 40px;
            }

            .lds-roller div:nth-child(5) {
                animation-delay: -0.18s;
            }

            .lds-roller div:nth-child(5):after {
                top: 71px;
                left: 32px;
            }

            .lds-roller div:nth-child(6) {
                animation-delay: -0.216s;
            }

            .lds-roller div:nth-child(6):after {
                top: 68px;
                left: 24px;
            }

            .lds-roller div:nth-child(7) {
                animation-delay: -0.252s;
            }

            .lds-roller div:nth-child(7):after {
                top: 63px;
                left: 17px;
            }

            .lds-roller div:nth-child(8) {
                animation-delay: -0.288s;
            }

            .lds-roller div:nth-child(8):after {
                top: 56px;
                left: 12px;
            }

            @keyframes lds-roller {
                0% {
                    transform: rotate(0deg);
                }

                100% {
                    transform: rotate(360deg);
                }
            }

        /* end lds-roller */
    </style>

    <style>
        /* .bg-white-opacity {
            --bs-bg-opacity: 0.8!important;
            background-color: rgba(var(--bs-white-rgb), var(--bs-bg-opacity)) !important;
        }

        .table-transparent {
            --bs-table-color: #000;
            --bs-table-bg: #ffffff;
            --bs-table-border-color: #ffffff;
            --bs-table-striped-bg: #ffffff;
            --bs-table-striped-color: #000;
            --bs-table-active-bg: #badce3;
            --bs-table-active-color: #000;
            --bs-table-hover-bg: #bfe2e9;
            --bs-table-hover-color: #000;
            --bs-bg-opacity: 0.8!important;
            color: var(--bs-table-color);
            border-color: var(--bs-table-border-color);
            background-color: rgba(var(--bs-white-rgb), var(--bs-bg-opacity)) !important;
        } */
    </style>
</head>