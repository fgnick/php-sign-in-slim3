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
            'passthrough' => [] // if you want to skip some path, please add it here. E.g. '/api/v1/your_path'
        ]),
        new Slim\Middleware\JwtAuthentication\RequestMethodRule([
            'passthrough' => ['OPTIONS']
        ])
    ],
    'header'   => $container->get('settings')['oauth']['header'],
    'regexp'   => '/(.*)/',
    'secure'   => require __DIR__ . '/../security/ssl/ssl_secur.php',// default is false,,  // 正式機運作的時候，最好轉成 true, 並且搭配 https
    //'relaxed'  => [ 'localhost', '127.0.0.1' ],  // 您可以列出多個開發服務器以放鬆安全性。 通過以下設置，localhost 和 dev.example.com 都允許傳入未加密(https -> http)的請求。
    'secret'   => $container->get('settings')['oauth']['api_secret'],
    'callback' => function($request, $response, $arguments) use ($container) {
        $container->jwt = $arguments['decoded']; // change StdClass object to decoded jwt contents Object if success.
        // dispatch jwt to different way
        // IMPORTANT: every api token must have channel name in jwt data parameter. if no, please fix it.
        if( isset( $container->jwt->aud ) ) {
            switch( $container->jwt->aud ) {
                case Gn\Lib\JwtPayload::PAYLOAD_AUTH_CHANNEL_USR: // get the member data via api token
                    $reg = new Gn\Fun\RegisterFun($container);
                    $container['usr'] = $reg->isApiAuth( $container->jwt->jti );
                    if ( $container['usr'] === false ) {
                        $this->setMessage('user access denied');
                        return false; // it will call error method with status 401
                    }
                    break;
                case Gn\Lib\JwtPayload::PAYLOAD_AUTH_CHANNEL_APP:
                    // If you want to use the API not only working for user authentication,
                    // but also working for others to connect to the service with authentication.
                    break;
                case Gn\Lib\JwtPayload::PAYLOAD_AUTH_CHANNEL_OUTSOURCE:
                    // If you want to use the internal API, 
                    // please set the channel name to "outsource" in the JWT payload.
                    //
                    // The payload have to be from the \Gn\Obj\JwtPayloadObj, such as below:
                    // payload: {
                    //     'iss'    => $this->iss,   // modify the issuer name --> encoder = decoder 一定是使用這個名字
                    //     'exp'    => $this->exp,   //(new \DateTime('now +24 hours'))->getTimeStamp(),// Expire default is a week
                    //     'sub'    => $this->sub,   // 如果一個用戶以 ID "user123" 登錄，那麼 JWT 中的 sub 聲明可能就是 "sub": "user123"。
                    //     'aud'    => $this->aud,   // Audience: the recipient for whom the JWT is intended. It can be a single recipient or an array of recipients. like a channel name
                    //     'nbf'    => $this->nbf,   // Not before. 也就是說雖然有發這個 JWT，但是在這個時間之前，這個token仍然是不可以使用的。
                    //     'iat'    => $this->iat,   // Issued at: time when the token was generated
                    //     'jti'    => $this->jti,   // A random uid --> 是一個 PHP 檔案，裡面如同 config 界定，哪個 internal 進入
                    //     'claims' => $this->claims // customized data. it is good to be an array or an object
                    // }
                    $internal_access_map = $container->get('settings')['oauth']['internal_access'];
                    if ( isset( $container->jwt->iss ) && isset( $internal_access_map[ $container->jwt->iss ] ) ) {
                        if ( isset( $container->jwt->jti ) && $internal_access_map[ $container->jwt->iss ]['uid'] === $container->jwt->jti ) {
                            return true;
                        }
                    }
                    $this->setMessage('internal API access denied');
                    return false;
                default:
                    $this->setMessage('token denied');
                    return false;   // 401 response
            }
        } else {
            $this->setMessage('token denied');
            //throw new \ErrorException( 'api token without channel' );
            return false;   // 401 response
        }
        return true;
    },
    'error' => function ( $request, $response, $arguments ) use ( $container ) {
        $data = [
            'status'  => 'token authorization deny',
            'message' => $arguments['message']
        ];
        return $response->withStatus( Slim\Http\StatusCode::HTTP_UNAUTHORIZED )
                        ->withHeader( 'Content-Type', 'application/json' )
                        ->write( json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) );
    }
]));

// @2024-03-25: 經過測試，這是因為同源策略（Same-Origin Policy）通常只適用於在網絡上通過 HTTP 或 HTTPS 協議加載的資源。
//              對於本地文件系統中的文件，瀏覽器不會強制執行同源策略，因此可能會省略 Origin 標頭。
//              所以這個功能只需要在正式機上營運的時候啟動，demo的時候，由於 Vue.js 的 proxy 問題，所以可能會部份被阻擋。
//              因此demo的時候可以先暫時註解不使用此功能
// NOTE: 發布程式的時候，這個 URL 就會啟用
if ( $container->get('settings')['mode'] === 'production' ) {
    $app->add(new Gn\AddSlimMiddleware\CorsMiddleware($container->get('settings')['app']['dns']['self']));
}
