

$( async function() {
    // Initialize all things you need next steps
    await App.init(); 

    const error_code = App.getParameterByName('code');
    if ( typeof error_code == 'string' && error_code.length > 0 ) {
        let msg = response_decoder["PROC_TXT"][error_code] || "error!";
        $('#message').html('<div class="alert alert-danger" role="alert"><strong>'+msg+'</strong></div>');
    } else {
        $('#message').html('');
    }

    $('form#otp-pw-form').submit( function(evt) {
        const otp_code = App.f_trim($('input#otp-pw-input').val());
        if( otp_code.length === 0 ) {
            evt.preventDefault();
            return false;
        }
        // email
        const reg = /^[A-Za-z0-9]{6}$/;
        if( reg.test( otp_code ) === false ) {
            alert('OTP is invalid!');
            $('input#otp-pw-input').focus();
            $('input#otp-pw-input').val('');
            evt.preventDefault();
            return false;
        }
    });

    $('button#resend-xcode').click( function() {
        const uuid = $('input[name=token]').val();
        let ex_form = document.getElementById('otp-pw-form');
        if (ex_form) {
            const actionValue = ex_form.action;
            // create a new form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = actionValue+'/resent';
            const input_uuid = document.createElement('input');
            input_uuid.type = 'hidden';
            input_uuid.name = 'ex-token';
            input_uuid.value = uuid;
            form.appendChild( input_uuid );
            document.body.appendChild( form );
            form.submit();
        } else {
            window.location.replace('/login');
        }
    });
});
