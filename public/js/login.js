
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

    $('form#login-form').submit( function(evt) {
        const email   = App.f_trim($('input#email').val());
        const password = App.f_trim($('input#password').val());
        
        if( email.length === 0 || password.length === 0 ) {
            evt.preventDefault();
            return false;
        }

        // email
        let reg = /^[A-Za-z0-9][\w-.]+[A-Za-z0-9]@[A-Za-z0-9]([\w-.]+[A-Za-z0-9]\.)+([A-Za-z]){2,4}$/;
        if( reg.test( email ) === false ) {
            alert('Email is invalid!');
            $('input#email').focus();
            evt.preventDefault();
            return false;
        }

        // password
        reg = /(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,16}/;
        if( reg.test( password ) === false ) {
            alert("Password is invalid!");
            $('input#password').focus();
            evt.preventDefault();
            return false;
        }

        // Create a new element input, this will be our hashed password field. 
        const p = document.createElement("input");
        $("form#login-form").append( p );
        p.name = "pw";
        p.type = "hidden";
        p.value = SHA512( password );
        // Make sure the plaintext password doesn't get sent.
        $('input#password').val('');
    });
});