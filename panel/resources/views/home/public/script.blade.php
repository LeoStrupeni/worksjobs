@section('script_by_page')
  <script src="https://www.google.com/recaptcha/api.js?render=6Les0IMqAAAAAC_7zVOcCiO_tzxxG7OGFkPwI4fI"></script>
  <script>
    $(document).ready(function(){
      $('#guardarconsulta').click(function(){
        grecaptcha.ready(function() {
          grecaptcha.execute('6Les0IMqAAAAAC_7zVOcCiO_tzxxG7OGFkPwI4fI', {action: 'valform'}).then(function(token) {
            $('#formcontacto').prepend('<input type="hidden" name="token" value="'+token+'">');
            $('#formcontacto').prepend('<input type="hidden" name="action" value="valform">');
            validacionform();
            // Add your logic to submit to your backend server here.
          });
        });
      });

      $('.select2').select2({
        placeholder: "Motivo",
        allowClear: true,
        width : 'resolve'
      });

      $(document).scroll(function(){
          let windowHeight = $(window).height()*0.8;
          // Recorrer cada elemento
          $('.animated_lefting').each(function(i) {
              if (!$(this).hasClass('lefting')) {
                  // Analizar posición del elemento actual
                  if($(document).scrollTop() >= $(this).offset().top - windowHeight) {
                      $(this).addClass('lefting');
                  } else {
                      // $(this).removeClass('lefting');
                  }
              }
              
          });

          $('.animated_scaleUpCenter').each(function(i) {
              if (!$(this).hasClass('scaleUpCenter')) {
                  // Analizar posición del elemento actual
                  if($(document).scrollTop() >= $(this).offset().top - windowHeight) {
                      $(this).addClass('scaleUpCenter');
                  } else {
                      // $(this).removeClass('scaleUpCenter');
                  }
              }
          });

          if($(document).scrollTop() == 0) {
              $('#navheaderpublic').addClass('sticky-top');
              $('#navheaderpublic').removeClass('fixed-top');
              $('#navheaderpublic').css('height','65px');
              $('.ir').css('height','65px');
              $('#navheaderlogo').prop('height','55');
              
          } else {
              $('#navheaderpublic').removeClass('sticky-top');
              $('#navheaderpublic').addClass('fixed-top');
              $('#navheaderpublic').css('height','50px');
              $('.ir').css('height','50px');
              $('#navheaderlogo').prop('height','40');
          }
          
      });

      if("{{$errors->any()}}" > 0 || "{{session('status')}}" != '') {
        $('html, body').animate({
          scrollTop: $("#contacto").offset().top
        }, 100);
      }

      $('.ir-arriba').click(function(){
        $('body, html').animate({
          scrollTop: '0px'
        }, 300);
      });

      $(window).scroll(function(){
        if( $(this).scrollTop() > 0 ){
          $('.ir-arriba').slideDown(300);
        } else {
          $('.ir-arriba').slideUp(300);
        }
      });
      
    });

    $( ".validate" ).on('focus',function(){$( this ).css('box-shadow','');})

    function validacionform(){
      var error = 0

      $( ".validate" ).each(function( index ) {
        if($( this ).val() == ''){
          $( this ).css('box-shadow', 'inset 0px 0px 2px 2px red');
          error++;
        } else if ($( this ).prop('name')=='nombre' && !regex_letras.test($( this ).val())) {
          $( this ).css('box-shadow', 'inset 0px 0px 2px 2px red');
          error++;
        } else if ($( this ).prop('name')=='email' && !regex_mail.test($( this ).val())) {
          $( this ).css('box-shadow', 'inset 0px 0px 2px 2px red');
          error++;
        } 
      });

      if (error > 0) {
        toastr["error"]("Debe completar los datos correctamente para que podamos contactarlo.")
      } else {
        document.getElementById("formcontacto").submit();
      }
    }

    $('body').on('mouseover','.ir', function(){
      $(this).addClass('btn-header-public');
    })
    $('body').on('mouseout','.ir', function(){
      $(this).removeClass('btn-header-public');
    })

    $('body').on('click','.ir',function(){
        href = $(this).data('href');
        $('html, body').animate({
            scrollTop: $("#"+href).offset().top - 60 
        }, 100);
    })
  </script>
@endsection