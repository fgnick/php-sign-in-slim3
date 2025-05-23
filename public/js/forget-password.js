$(function() {
    var error_code = getParameterByName('code');
    if ( typeof error_code == 'string' && error_code.length > 0 ) {
        var msg = "error";
        var style = "alert alert-danger";
        switch ( parseInt( error_code ) ) {
            case 0:
                style = "alert alert-success";
                msg = "Your password reset email is sent, please check it in you email box, and click reset URL to change the password!";
                break;
            case 1:
                msg = "Email is invalid, or you send request too many times in 1 min, plesse wait!";
                break;
            case 2:
                msg = "Confirming email error";
                break;
            default:
                msg = "Process fail";
        }
        $('#message').html('<div class="'+style+'" role="alert"><strong>'+msg+'</strong></div>');
    } else {
        $('#message').html('');
    }
    
    $('form#forget-pw-form').submit( function(evt) {
        var email = f_trim( $('input#email').val() );
        if( email.length == 0 ) {
            evt.preventDefault();
            return false;
        }
        // email
        var reg = /^[A-Za-z0-9][\w-.]+[A-Za-z0-9]@[A-Za-z0-9]([\w-.]+[A-Za-z0-9]\.)+([A-Za-z]){2,4}$/;
        if( reg.test( email ) == false ) {
            alert('Email is invalid!');
            $('input#email').focus();
            evt.preventDefault();
            return false;
        }
    });
});

function f_trim(x) 
{
    return x.replace(/^\s+|\s+$/gm,'');
}

function getParameterByName(name, url) 
{
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}