<script src="{{env('APP_URL')}}/assets/js/jquery/dist/jquery.js"></script>
<script src="{{env('APP_URL')}}/assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="{{env('APP_URL')}}/assets/plugins/moment/min/moment.min.js"></script>

<script src="{{env('APP_URL')}}/assets/plugins/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
<script src="{{env('APP_URL')}}/assets/plugins/bootstrap-datetime-picker/js/bootstrap-datetimepicker.min.js"></script>
<script src="{{env('APP_URL')}}/assets/plugins/bootstrap-daterangepicker/daterangepicker.js"></script>

<script src="{{env('APP_URL')}}/assets/plugins/sweetalert2/dist/sweetalert2.min.js"></script>
<script src="{{env('APP_URL')}}/assets/plugins/toastr/build/toastr.min.js"></script>

<script src="{{env('APP_URL')}}/assets/icons/fontawesome-free-6.7.0-web/js/all.min.js"></script>
<script src="{{env('APP_URL')}}/assets/plugins/select2/dist/js/select2.full.js"></script>
<script src="{{env('APP_URL')}}/assets/plugins/bootstrap-select-1.14.0-beta3/docs/docs/dist/js/bootstrap-select.js"></script>

<script src="{{env('APP_URL')}}/assets/js/tableAjaxLocal.js"></script>
<script src="{{env('APP_URL')}}/assets/plugins/sorttable/sorttable.js"></script>
<script src="{{env('APP_URL')}}/assets/plugins/lightGallery/js/lightgallery.min.js"></script>
<script src="{{env('APP_URL')}}/assets/plugins/lightGallery/js/lg-thumbnail.min.js"></script>
<script src="{{env('APP_URL')}}/assets/plugins/lightGallery/js/lg-fullscreen.min.js"></script>
<script src="{{env('APP_URL')}}/assets/plugins/lightGallery/js/lg-zoom.min.js"></script>

<script>
    $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    const app_url = "{{env('APP_URL')}}";

    const regex_mail = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
    const regex_letras = /^[A-Za-zÀ-ÖØ-öø-ÿ.!#$%&'*+/=?^_ ]*$/;
    const regex_numeros = /^([0-9])*$/;
    const regex_alfanumerico = /^([a-zA-ZÀ-ÖØ-öø-ÿ0-9.,!#$%&'*+/=?^_ `{|}~-])*$/;

    toastr.options = {
        "closeButton": false,
        "debug": false,
        "newestOnTop": false,
        "progressBar": false,
        "positionClass": "toast-bottom-left",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "3000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    }

    function convert64(event,input) {
		if (event.target.files.length > 0) {
			// var comprobante = document.getElementById("base64");
			// comprobante.setAttribute('value', event.target.files[0]);
            // $($(input).closest("form").children(".base64")[0]).attr('value', event.target.files[0]);
			var reader = new FileReader();

			reader.onload = function (e) {
				$($(input).closest("form").children(".base64")[0]).attr('value', e.target.result);
			};
			reader.readAsDataURL(input.files[0]);
		}
	}
    function mostrarOverlay() { $('body').append('<div id="_ovrly" class="loading-spinner">Loading&#8230;</div>'); }
</script>

<script>
    (() => {
        'use strict'
        const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.forEach(tooltipTriggerEl => {
            new bootstrap.Tooltip(tooltipTriggerEl)
        })
    })()

    $('body').on('mouseover','.btn-header-menu', function(){
        $($(this).children()[0]).addClass('show');
        $($(this).children()[1]).addClass('show');
    })
    $('body').on('mouseout','.btn-header-menu', function(){
        $($(this).children()[0]).removeClass('show');
        $($(this).children()[1]).removeClass('show');
    })
</script>
{{-- disponibles en todas las vistas --}}
<script>var google_api_key = "{{Session::get('user.google_api_key')}}";</script>
<script src="{{env('APP_URL')}}/assets/js/local/useredit.js"></script>
<script src="{{env('APP_URL')}}/assets/js/local/jobdetail.js"></script>
<script src="{{env('APP_URL')}}/assets/js/local/geolocalizacion.js"></script>