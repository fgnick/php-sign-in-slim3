<?php
/**
 * Application middleware of Slim
 * 
 * Filter the request without JWT Authentication. Return error message for not accessed request.
 *
 * JSON Web Tokens(JWT) are essentially passwords. You should treat them as such, and you should always use HTTPS.
 * If the middleware detects insecure usage over HTTP it will throw a RuntimeException.
 * This rule is relaxed for requests on local host.
 * To allow insecure usage you must enable it manually by setting secure to false.
 *
 * @author Nick Feng
 * @since 1.0
 */
$app->add( new Slim\Middleware\JwtAuthentication([
    'rules' => [
        new Slim\Middleware\JwtAuthentication\RequestPathRule([
            'path' => '/',
            'passthrough' => [
                '/login', 
                '/password',
                '/asset'
            ]
        ]),
        new Slim\Middleware\JwtAuthentication\RequestMethodRule([
            'passthrough' => ['OPTIONS']
        ])
    ],
    'environment' => $container->get('settings')['oauth']['environment'],
    'cookie'      => $container->get('settings')['oauth']['access_token_cookie'],
    'secure'      => require __DIR__ . '/../security/ssl/ssl_secur.php',// default is false, // 正式機運作的時候，最好轉成 true, 搭配 https
    //'relaxed'     => [ 'localhost', '127.0.0.1' ], // 您可以列出多個開發服務器以放鬆安全性。 通過以下設置，localhost 和 dev.example.com 都允許傳入未加密(https -> http)的請求。
    'secret'      => $container->get('settings')['oauth']['access_secret'],
    'callback'    => function ($request, $response, $arguments) use ($container) {
        $container->jwt = $arguments['decoded']; // change StdClass object to decoded jwt contents array if success.
        // get the member data via access token
        $reg = new Gn\Fun\RegisterFun($container);
        $container['usr'] = $reg->isLogged($container->jwt->jti, true);
        if( $container['usr'] === false ) {
            return false;   // it will go to the error function step
        }
        return true;
    },
    'error' => function ( $request, $response, $arguments ) use ( $container ) {
        $cookie = \Gn\Lib\GnCookie::setWebCookie_v2(
            $container->get('settings')['oauth']['access_token_cookie'],
            '',
            $_SERVER['SERVER_NAME'],
            '/',
            -1,
            $container->get('settings')['oauth']['cookie_secur']
        );
        // post or ajax get here.
        if ( $request->isXhr() || !$request->isGet() ) {
            $data = [
                'status'  => 'authorization deny',
                'message' => $arguments['message']
            ];
            return $response->withStatus( Slim\Http\StatusCode::HTTP_UNAUTHORIZED )
                            ->withHeader( 'Content-Type', 'application/json' )
                            ->withHeader( 'Set-Cookie', $cookie )
                            ->write( json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) );
        }
        
        return $response->withStatus( Slim\Http\StatusCode::HTTP_FOUND )
                        ->withHeader( 'Set-Cookie', $cookie )
                        ->withHeader( 'Location', $container->get( 'settings' )['app']['url']['login'] );
    }
]));