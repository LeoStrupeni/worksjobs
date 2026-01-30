{{-- {{dd(Auth::user()->imagen)}} --}}
@extends('layout')

@auth
  @section('link_by_page')
    <link href="{{env('APP_URL')}}/assets/css/avatar.css" rel="stylesheet" type="text/css" />
  @endsection
  @section('style_by_page')
    <style>
        .my-dropdown-toggle::after {
            content: none;
        }

        .active>.page-link, .page-link.active {
            background-color: var(--bs-green)!important;
            border-color: var(--bs-white)!important;
        }

        .page-link {
            background-color: var(--bs-teal)!important;
            border: var(--bs-pagination-border-width) solid var(--bs-white)!important;
            color: var(--bs-white)!important;
        }

        /* Estilos para selectpicker en modal de editar */
        #modal-body-edit-job .bootstrap-select > .dropdown-toggle {
            border: 2px solid #0d6efd !important;
            border-radius: 8px !important;
            padding: 10px 40px 10px 12px !important;
            background-color: white !important;
            box-shadow: 0 .125rem .25rem rgba(0,0,0,.075) !important;
            font-weight: 500;
            height: auto !important;
            min-height: 44px;
        }

        #modal-body-edit-job .bootstrap-select > .dropdown-toggle:hover,
        #modal-body-edit-job .bootstrap-select > .dropdown-toggle:focus {
            background-color: #f8f9fa !important;
            border-color: #0a58ca !important;
        }

        #modal-body-edit-job .bootstrap-select > .dropdown-toggle::after {
            border-top: 0.4em solid #0d6efd !important;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
        }

        #modal-body-edit-job .bootstrap-select .filter-option-inner-inner {
            color: #212529;
        }

    </style>
  @endsection

  @section('Content')
    @include('home.content')
    {{-- @include('job.create') --}} {{-- INCLUIDO EN ARCHIVO AVATAR --}}
    @include('job.edit')
    @include('job.show')
    @include('job.destroy')
    @include('job.descripcion')
    @include('job.notes')
    @include('job.closed')
    @include('job.files')
  @endsection

  @section('script_by_page')
    <script>
      var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
      var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
          return new bootstrap.Popover(popoverTriggerEl)
      })

      var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
      var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
      })
    </script>
  @endsection
  
@else 
  @include('home.public.style')
  @include('home.public.content')
  @include('home.public.script')
@endauth