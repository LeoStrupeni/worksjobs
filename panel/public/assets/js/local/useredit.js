$(document).ready(function() {
    $('body').on('click','.update-user',function(){ 
        $('#formedituser').attr('action',app_url+"/users/"+$(this).data('id'));

        $('#e_name').val('');
        $('#e_email').val('');
        $("#e_rol").val(1).change();
        $('#e_password').val('');
        $('#e_password_confirmation').val('');
        $('#imagen-user-edit').attr('src', "");
        $('#edituser').modal('show');

        $('#modal-body-edit-user-roller').removeClass('d-none');
        $('#modal-body-edit-user-error').addClass('d-none');
        $('#modal-body-edit-user').addClass('d-none');
        $('#modal-footer-edit-user').addClass('d-none');

        $.ajax({contenttype : 'application/json; charset=utf-8',
            url : $('meta[name="app_url"]').attr('content')+'/users/'+$(this).data('id')+'/edit',
            type : 'GET',
            done : function(response) { $('#modal-body-edit-user-error').removeClass('d-none'); },
            error : function(jqXHR,textStatus,errorThrown) { $('#modal-body-edit-user-error').removeClass('d-none'); },
            success : function(data) {
                $('#e_name').val(data.name);
                $('#e_email').val(data.email);
                $("#e_rol").val(data.rolid).change();
                $('#e_password').val('');
                $('#e_password_confirmation').val('');
                $('.password').text('')
                $('#imagen-user-edit').attr('src', data.imagen);
                $('#modal-body-edit-user').removeClass('d-none');
                $('#modal-footer-edit-user').removeClass('d-none');
            }
        }).always(function() {
            $('#modal-body-edit-user-roller').addClass('d-none');
        });
    });
    $('body').on('click',"#btn-update-user",function () {
        var error = 0

        $( ".e_validate" ).each(function( index ) {
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
            toastr["error"]("Debe completar los datos correctamente para generar el nuevo usuario.")
        } else {
            document.getElementById("formedituser").submit();
        }
    });
});