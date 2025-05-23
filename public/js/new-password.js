$(function() {
    var error_code = getParameterByName('code');
    if ( typeof error_code == 'string' && error_code.length > 0 ) {
        var msg = "error";
        var style = "alert alert-danger";
        switch ( parseInt( error_code ) ) {
            case 1:
                msg = "Invalid input";
                break;
            default:
                msg = "Process fail";
        }
        $('#message').html('<div class="'+style+'" role="alert"><strong>'+msg+'</strong></div>');
    } else {
        $('#message').html('');
    }
    
    $('form#new-pw-form').submit( function(evt) {
        var password = f_trim( $('input#password').val().trim() );
        var password_confirm = f_trim( $('input#confirm-password').val().trim() );
        if( password.length == 0 ) {
            evt.preventDefault();
            return false;
        }
        // password
        reg = /(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,16}/;
        if( reg.test( password ) == false ) {
            alert("Password is invalid!");
            $('input#password').focus();
            evt.preventDefault();
            return false;
        }
        // confirm password
        if( password_confirm == null || password_confirm == "" ) {
            alert("Please confirm password again");
            $('input#confirm-password').focus();
            evt.preventDefault();
            return false;
        } else if( password_confirm != password ) {
            alert("Password confirm doesn't equal above!");
            $('input#confirm-password').focus();
            evt.preventDefault();
            return false;
        }
        // Create a new element input, this will be our hashed password field. 
        var p = document.createElement("input");
        $("form#new-pw-form").append( p );
        p.name = "pw";
        p.type = "hidden";
        p.value = SHA512( password );
        // Make sure the plaintext password doesn't get sent.
        $('input#password').val('');
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