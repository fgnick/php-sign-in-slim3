<?php
/**
 * Application route of Slim
 * 
 * @author Nick Feng
 * @since 1.0
 */
// for login
$app->group( '/login', function () { // [?code=[error code in integer]]
    $this->get( '',          Gn\AppRegister::class . ':GET_Login' );
    $this->post( '',         Gn\AppRegister::class . ':POST_Login' );
    $this->group( '/confirm', function () {
        $this->get( '',         Gn\AppRegister::class . ':GET_OneTimePassword' );       // OTP confirming
        $this->post( '',        Gn\AppRegister::class . ':POST_OneTimePassword' );
        $this->post( '/resent', Gn\AppRegister::class . ':POST_ResentOneTimePassword' );   // will redirect to the same page if it is success
    });
});

// for forget-password
$app->group( '/password', function () {
    $this->group( '/forget', function () {   // [?code=[error code in integer]]
        $this->get( '',  Gn\AppRegister::class . ':GET_ForgetPassword' );
        $this->post( '', Gn\AppRegister::class . ':POST_ForgetPassword' );
    });
    $this->group( '/new', function () {
        $this->get( '',  Gn\AppRegister::class . ':GET_NewPassword' ); // [?v={token}]
        $this->post( '', Gn\AppRegister::class . ':POST_NewPassword' );
    });
});

// use access token to get a new api token for client side
$app->get( '/api-token', Gn\AppRegister::class . ':GET_GetApiToken' );  // use ajax

// for logout
$app->get( '/logout', Gn\AppRegister::class . ':GET_Logout' );

// for website public asset without any authentication, but same-site
$app->group( '/asset', function () use ($app) {
    $app->get('/global/response-decoder.json', Gn\Reflection\PhpRespCodeDecoder::class); 
});

/**
 * for vue.js.
 * NOTE: it must be after all routes.
 */
$app->get( '/[{path:.*}]', Gn\AppVue::class );