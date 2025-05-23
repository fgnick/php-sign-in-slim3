<?php
namespace Gn;

use UnexpectedValueException;
use DomainException;
use ErrorException;
use Exception;

use Gn\Lib\GnCookie;
use Gn\Lib\JwtPayload;
use Gn\Fun\RegisterFun;
use Gn\Ctl\AppBasicCtl;
use Gn\Interfaces\MailerInterface;

// from Slim
use Firebase\JWT\JWT;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\StatusCode;
use Psr\Http\Message\ResponseInterface;

/**
 * Member controller of system
 * 
 * NOTE: work for website page.
 *
 * @author Nick Feng
 * @since 1.0
 */
class AppRegister extends AppBasicCtl implements MailerInterface
{
    /**
     * settings of Slim framework.
     * 
     * @var array
     */
    protected $settings;

    /**
     * Constructor.
     *
     * @param Container $container
     * @throws ErrorException
     */
    public function __construct( Container $container )
    {
        parent::__construct( $container );
        $this->settings = $this->container->get('settings');
    }

    /**
     * Detect JWT token string is right or not.
     * NOTE: if there is any exception, it will be logged.
     *
     * @param string $token
     * @param string $secret
     * @return bool|object payload object or false on failure.
     * @author Nick
     */
    private function isToken( string $token, string $secret )
    {
        try {
            return JWT::decode( $token, $secret, [ $this->settings['oauth']['algorithm'] ] );
        } catch ( UnexpectedValueException $e ) {
            $this->container->logger->error( 'token unexpected value exception: ' . $e->getMessage() );
            return false;
        } catch ( DomainException $e ) {
            $this->container->logger->error( 'token domain exception: ' . $e->getMessage() );
            return false;
        } catch ( Exception $e ) {
            $this->container->logger->error( 'token exception: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Get a random token string for register form to identify the form is coming from system or not.
     *
     * @param string $secret
     * @return string
     * @throws Exception
     * @author Nick
     */
    private function randRegisterToken( string $secret ): string
    {

        return JWT::encode( 
            JwtPayload::genPayload( $this->settings['oauth']['issuer'], 'now 5 minutes' ), 
            $secret, 
            $this->settings['oauth']['algorithm']
         );
    }

    /**
     *
     * @param string $cookie_name
     * @return string
     * @throws ErrorException
     * @author Nick
     */
    private function removeAccessTokenCookie ( string $cookie_name ): string
    {
        return GnCookie::setWebCookie_v2(
            $cookie_name,
            '',
            '',
            '/',
            -1,
            $this->settings['oauth']['cookie_secur'] 
        );
    }

    /**
     * url: /login
     *
     * Login page controller.
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     * @throws ErrorException
     * @throws Exception
     * @author Nick
     * @method GET
     */
    public function GET_Login ( Request $request, Response $response, array $args )
    {
        // Check whether the user is logged out via the access token cookie.
        $cookies = $request->getCookieParams();
        $token   = $cookies[ $this->settings['oauth']['access_token_cookie'] ] ?? null;
        if ( !empty( $token ) ) {
            $payload = self::isToken( $token, $this->settings['oauth']['access_secret'] );
            if ( $payload !== false ) {
                $register = new RegisterFun( $this->container );
                if ( $register->isLogged( $payload->jti ) !== false ) {
                    // if there is an access token, redirect to next page.
                    return $response->withStatus( StatusCode::HTTP_FOUND )
                                    ->withHeader( 'Location', '/' );
                } else { // when process comes here, it means the form random token is illegal or expired.
                    // remove the token in illegal/expired.
                    $cookie = self::removeAccessTokenCookie( $this->settings['oauth']['access_token_cookie'] );
                    $response = $response->withHeader( 'Set-Cookie', $cookie );
                    //$response->withAddedHeader( 'Set-Cookie', $cookie );
                }
            }
        }
        $args[ $this->argsOutputName ] = [
            'token' => self::randRegisterToken( $this->settings['oauth']['register_secret'] )
        ];
        return $this->renderer->render( $response, 'login.phtml', $args );
    }

    /**
     * url: /login
     *
     * request from login page.
     * post params:
     * {
     *     email: string,
     *     pw:   string in sha512. It cannot be a readable string.
     *     remember: '1'|'0'
     *     token: string from the login page generated from php response
     * }
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws ErrorException
     * @throws Exception
     * @author Nick
     * @method POST
     */
    public function POST_Login ( Request $request, Response $response, array $args ): Response
    {
        $email        = $request->getParsedBodyParam( 'email' );
        $password     = $request->getParsedBodyParam( 'pw' );
        $remember_flg = (int)$request->getParsedBodyParam( 'remember', 0 );
        $rand_token   = $request->getParsedBodyParam( 'token' );
        if( !$this->isReferred() ) {
            return $response->withStatus( StatusCode::HTTP_BAD_REQUEST )
                            ->write( static::HTTP_RESP_MSG[ StatusCode::HTTP_BAD_REQUEST ] );
        } else if ( !is_string( $email ) || empty( $email ) ) {
            return $response->withStatus( StatusCode::HTTP_BAD_REQUEST )
                            ->write( static::HTTP_RESP_MSG[ StatusCode::HTTP_BAD_REQUEST ] );
        } else if ( !is_string( $password ) || empty( $password ) ) {
            return $response->withStatus( StatusCode::HTTP_BAD_REQUEST )
                            ->write( static::HTTP_RESP_MSG[ StatusCode::HTTP_BAD_REQUEST ] );
        } else if ( $remember_flg !== 0 && $remember_flg !== 1 ) {
            return $response->withStatus( StatusCode::HTTP_BAD_REQUEST )
                            ->write( static::HTTP_RESP_MSG[ StatusCode::HTTP_BAD_REQUEST ] );
        } else if ( !is_string( $rand_token ) || !self::isToken( $rand_token, $this->settings['oauth']['register_secret'] ) ) {
            return $response->withStatus( StatusCode::HTTP_UNAUTHORIZED )
                            ->write( static::HTTP_RESP_MSG[ StatusCode::HTTP_UNAUTHORIZED ] );
        }

        // @2024-12-25:
        // IMPORTANT: 因為發現有人會同時開啟兩個 login page，而使用兩個不同的帳號做登入，彼此會搶 access token
        //            Check whether the user is logged in via the access token cookie.
        $cookies = $request->getCookieParams();
        $token   = $cookies[$this->settings['oauth']['access_token_cookie']] ?? null;
        if ( !empty( $token ) ) {
            // 如果該終端 browser 早就有登入的 access cookie，則無須在發送這次的 login 回應。直接倒回到 login 頁面，
            // 再由當頁面的判定進行轉指進入首頁。
            return $response->withStatus( StatusCode::HTTP_FOUND )
                ->withHeader(
                    'Location',
                    $this->settings['app']['url']['login']
                );
        }
        $is_remember = $remember_flg === 1; // check remember-me flag
        $register = new RegisterFun( $this->container );
        $check_code = $register->confirmPassword( $email, $password, true );
        if ( $check_code === static::PROC_OK ) {
            // 這邊之後必須要先進去資料庫查詢，是否這個使用者有要求使用 OTP 進行二次驗證。如果沒有，則照舊；反之，則進入OTP的模式
            $otp_data = $register->email4OTP( $email );
            if ( is_int( $otp_data ) ) {
                return $response->withStatus( StatusCode::HTTP_FOUND )
                                ->withHeader(
                                    'Location',
                                    ( $this->settings['app']['url']['login'] . '?code=' . $otp_data )
                                );
            }
            // output is an array, if the array is empty, it means the user doesn't allow the OTP process
            if ( empty( $otp_data ) ) { // OTP flag = 0(false)
                $payload = $register->Login( $email, $password, $is_remember );
                if ( is_int( $payload ) ) {
                    return $response->withStatus( StatusCode::HTTP_FOUND )
                                    ->withHeader(
                                        'Location',
                                        ( $this->settings['app']['url']['login'] . '?code=' . $payload )
                                    );
                }
                // set access token cookie to save in client
                $access_token = JWT::encode(
                    $payload,
                    $this->settings['oauth']['access_secret'],
                    $this->settings['oauth']['algorithm']
                );
                // generate access token in cookie.
                // if the remember-me flag is false, the expired time must
                // be set up in 0 to make the browser remove it when the browser window closed.
                $cookie_content = GnCookie::setWebCookie_v2 (
                    $this->settings['oauth']['access_token_cookie'],
                    $access_token,
                    '',
                    '/',
                    ( $is_remember === true ? $payload['exp'] : 0 ),
                    $this->settings['oauth']['cookie_secur']
                );
                
                // log for success and mark account
                $this->container->logger->notice( $email . ' is logged in' );
                
                return $response->withStatus( StatusCode::HTTP_FOUND )
                                ->withHeader( 'Set-Cookie', $cookie_content )
                                ->withHeader('Location', '/');
            } else {    // OTP flag = 1(true), start the OTP process
                // if OTP-clear is not working, set up a log
                if ( $otp_data['del_code'] !== static::PROC_OK ) {
                    $this->container->logger->emergency(
                        $email . ' OTP cleaning error: ' . static::PROC_TXT[ $otp_data['del_code'] ] .
                        '; data=' . parent::jsonLogStr( $otp_data )
                    );
                }

                // send email with OTP 6-chars code to member
                $mailer = $this->container->get('mailer');
                $is_sent = $mailer->sendMail(
                    [ $email ],
                    static::MAIL_TITLE_ACCESS_OTP,
                    static::MAIL_HTMLBODY_ACCESS_OTP,
                    [ 'xcode' => $otp_data['otp_code'] ]
                );
                if ( !$is_sent ) {
                    // log the error when email doesn't work
                    $this->container->logger->emergency(
                        'OTP mail ' . $email . ' error: data=' . parent::jsonLogStr( $otp_data )
                    );
                    // stop and back to the sign-in page
                    return $response->withStatus( StatusCode::HTTP_FOUND )
                                    ->withHeader(
                                        'Location',
                                        ( $this->settings['app']['url']['login'] . '?code=' . static::PROC_SERV_ERROR )
                                    );
                } else {
                    $this->container->logger->notice(
                        'OTP mail ' . $email . ' success: data=' . parent::jsonLogStr( $otp_data )
                    );
                }

                // return a response to redirect to OTP page
                $payload = JwtPayload::genPayload( 
                    $this->settings['oauth']['issuer'], 
                    'now 5 minutes',
                    [
                        'uuid' => $otp_data['uuid']
                    ]
                );

                $xcode = JWT::encode(
                    $payload,
                    $this->settings['oauth']['pw_reset_secret'],
                    $this->settings['oauth']['algorithm']
                );

                // IMPORTANT: 請一定要將exp設定為0，這樣就是在瀏覽棄關閉之後，自動會全部清除
                $cookie_content = GnCookie::setWebCookie_v2 (
                    $this->settings['oauth']['otp_token_cookie'],
                    $xcode,
                    '',
                    '/',
                    0,
                    $this->settings['oauth']['cookie_secur']
                );

                return $response->withStatus( StatusCode::HTTP_FOUND )
                                ->withHeader( 'Set-Cookie', $cookie_content )
                                ->withHeader( 'Location', ( $this->settings['app']['url']['otp'] ) );
            }
        } else {
            return $response->withStatus( StatusCode::HTTP_FOUND )
                            ->withHeader(
                                'Location',
                                ( $this->settings['app']['url']['login'] . '?code=' . $check_code )
                            );
        }
    }

    /**
     * url: /logout
     * 
     * This is under the JWT authentication, so it will check the access token. if the access token is valid, the process will go on.
     * logout processing, it will remove login cookie and turn off the status in member_login table.
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws ErrorException
     * @author Nick
     * @method GET
     */
    public function GET_Logout( Request $request, Response $response, array $args ): Response
    {
        // check the request is from the website link and login code is existed in cookie.
        // $_SERVER['HTTP_REFERER'] is where are you from
        if( !$this->isReferred() ) {
            return $response->withStatus( StatusCode::HTTP_BAD_REQUEST );
        }
        // no matter what, remove the access cookies at of the member on browser at first!
        $cookie = self::removeAccessTokenCookie( $this->settings['oauth']['access_token_cookie'] );
        
        $register = new RegisterFun( $this->container );
        if ( $register->Logout( $this->container->jwt->jti ) === static::PROC_OK ) {
            // log for error and mark account
            $this->container->logger->notice( 'member (' . $this->container['usr']['id'] . ') logged out' );
        } else { // log for error and mark account
            $this->container->logger->error( 'member (' . $this->container['usr']['id'] . ') log-out error' );
        }
        // else access token is illegal or not existed in database
        return $response->withStatus( StatusCode::HTTP_FOUND )
                        ->withHeader( 'Set-Cookie', $cookie )
                        ->withHeader( 'Location', $this->settings['app']['url']['login'] );
    }

    /**
     * url: /password/forget
     *
     * Member forget password processing start page
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return ResponseInterface
     * @throws Exception
     * @author Nick
     * @method GET
     */
    public function GET_ForgetPassword ( Request $request, Response $response, array $args ): ResponseInterface
    {
        $args[ $this->argsOutputName ] = [
            'token' => self::randRegisterToken( $this->settings['oauth']['pw_reset_secret'] )
        ];
        return $this->renderer->render( $response, 'forget-password.phtml', $args );
    }
    
    /**
     * url: /password/forget
     *
     * Member forget password processing form receiver.
     *
     * @author Nick
     * @method POST
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function POST_ForgetPassword ( Request $request, Response $response, array $args ): Response
    {
        $email      = $request->getParsedBodyParam( 'email' );
        $rand_token = $request->getParsedBodyParam( 'token' );
        if( !$this->isReferred() ) {
            return $response->withStatus( StatusCode::HTTP_BAD_REQUEST )
                            ->write( static::HTTP_RESP_MSG[ StatusCode::HTTP_BAD_REQUEST ] );
        } else if ( !is_string( $email ) || empty( $email ) ) {
            return $response->withStatus( StatusCode::HTTP_BAD_REQUEST )
                            ->write( static::HTTP_RESP_MSG[ StatusCode::HTTP_BAD_REQUEST ] );
        } else if ( !is_string( $rand_token ) || !self::isToken( $rand_token, $this->settings['oauth']['pw_reset_secret'] ) ) {
            return $response->withStatus( StatusCode::HTTP_UNAUTHORIZED )
                            ->write( static::HTTP_RESP_MSG[ StatusCode::HTTP_UNAUTHORIZED ] );
        }
        
        $register = new RegisterFun( $this->container );
        $payload = $register->resetPassword( $email, 1 );
        $errorCode = 0;
        if ( is_array( $payload ) ) {
            // set a log
            $this->container->logger->notice( $email . ' password reset' );
            // generate an url for user confirming.
            $token = JWT::encode( 
                $payload, 
                $this->settings['oauth']['pw_reset_secret'], 
                $this->settings['oauth']['algorithm'] 
            );
            $token = rawurlencode( $token );
            $resetURL = $this->settings['app']['dns']['self'] . $this->settings['app']['url']['reset_pw'] . $token;
            // send mail to member.
            $mailer = $this->container->get('mailer');
            $sent_mail = $mailer->sendMail(
                array( $email ),
                static::MAIL_TITLE_PW_RESET,
                static::MAIL_HTMLBODY_PW_RESET,
                array( 'url' => $resetURL ) );
            
            if ( !$sent_mail ) {
                // NOTE: Just record reset URL into log for testing. If released, PLEASE REMOVE it in log recording.
                $this->container->logger->emergency( $email . ' password-reset mail error: ' . $resetURL );
                $errorCode = static::PROC_SERV_ERROR;
            }
        } else {
            $errorCode = $payload;
        }
        return $response->withStatus( StatusCode::HTTP_FOUND )
                        ->withHeader( 'Location', strtok( $_SERVER['HTTP_REFERER'], '?' ) . '?code=' . $errorCode );
    }
    
    /**
     * url: /password/new
     *
     * Response a form page to help client reset new password.
     *
     * @author Nick
     * @method GET
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response|ResponseInterface
     */
    public function GET_NewPassword ( Request $request, Response $response, array $args )
    {
        $rand_token = $request->getQueryParam( 'v' ); // required
        if ( is_string( $rand_token ) && !empty( $rand_token ) ) {
            $payload = self::isToken( $rand_token, $this->settings['oauth']['pw_reset_secret'] );
            if ( $payload !== false ) {
                $args[ $this->argsOutputName ] = [
                    'token' => $rand_token
                ];
                return $this->renderer->render( $response, 'new-password.phtml', $args );
            }
        }
        return $response->withStatus( StatusCode::HTTP_FOUND )
                        ->withHeader( 'Location', $this->settings['app']['url']['login'] );
    }

    /**
     * url: /password/new
     *
     * Receive a form page to help client set new password.
     *
     * Note : you should ensure the token from register is accessed.
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws ErrorException
     * @author Nick
     * @method POST
     */
    public function POST_NewPassword ( Request $request, Response $response, array $args ): Response
    {
        $pw         = $request->getParsedBodyParam( 'pw' );
        $r_token    = $request->getParsedBodyParam( 'token' );
        $ex_cookies = $request->getCookieParams();
        $ex_token   = $ex_cookies[$this->settings['oauth']['access_token_cookie']] ?? null;
        if( !$this->isReferred() ) {
            return $response->withStatus( StatusCode::HTTP_BAD_REQUEST )
                            ->write( static::HTTP_RESP_MSG[ StatusCode::HTTP_BAD_REQUEST ] );
        } else if ( !is_string( $pw ) || empty( $pw ) || !is_string( $r_token ) || empty( $r_token ) ) {
            return $response->withStatus( StatusCode::HTTP_BAD_REQUEST )
                            ->write( static::HTTP_RESP_MSG[ StatusCode::HTTP_BAD_REQUEST ] );
        }
        
        $reset_payload = self::isToken( $r_token, $this->settings['oauth']['pw_reset_secret'] );
        if ( $reset_payload === false ) {
            return $response->withStatus( StatusCode::HTTP_UNAUTHORIZED )
                            ->write( static::HTTP_RESP_MSG[ StatusCode::HTTP_UNAUTHORIZED ] );
        }
        $register = new RegisterFun( $this->container );
        $out = $register->initPassword( $pw, $reset_payload->jti, $reset_payload->claims->scope );
        if ( is_array( $out ) ) {
            // set a log
            $this->container->logger->notice( $out['email'] . ' password has been changed' );

            // mail user for password changed.
            $mailer = $this->container->get( 'mailer' );
            $sent_mail = $mailer->sendMail(
                array( $out['email'] ),
                static::MAIL_TITLE_PW_CHANGED,
                static::MAIL_HTMLBODY_PW_CHANGED 
            );
            
            if ( !$sent_mail ) { // set a log
                $this->container->logger->emergency( $out['email'] . ' password changing mail fail' );
            }
            
            // NOTE: Clean the ex-user, if the user is login before the password-reset.
            if ( !empty( $ex_token ) ) {
                $ex_payload = self::isToken( $ex_token, $this->settings['oauth']['access_secret'] );
                if ( $ex_payload !== false ) {
                    $register->Logout( $ex_payload->jti ); //don't care success or not.
                }

                // remove the token in illegal/expired.
                $cookie = self::removeAccessTokenCookie( $this->settings['oauth']['access_token_cookie'] );
                $response = $response->withHeader( 'Set-Cookie', $cookie );
            }
            return $response->withStatus( StatusCode::HTTP_FOUND )
                            ->withHeader( 'Location', $this->settings['app']['url']['login'] );
        }
        return $response->withStatus( StatusCode::HTTP_MOVED_PERMANENTLY )
                        ->withHeader(
                            'Location',
                            strtok( $_SERVER['HTTP_REFERER'], '?' ) .
                            '?v=' . $r_token . '&code=' . static::PROC_FAIL
                        );
    }

    /**
     * url: /login/confirm
     *
     * 這是一個讓想要登入帳號的人，透過他取得的 email 之中的 OTP 網頁連結來開啟的網頁
     *
     * Note : you should ensure the token from register is accessed.
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return ResponseInterface|Response
     * @throws ErrorException
     * @author Nick
     * @method POST
     */
    public function GET_OneTimePassword ( Request $request, Response $response, array $args )
    {
        $cookies = $request->getCookieParams();
        $x_code  = $cookies[$this->settings['oauth']['otp_token_cookie']] ?? null;
        if ( !empty( $x_code ) ) {
            $payload = self::isToken( $x_code, $this->settings['oauth']['pw_reset_secret'] );
            if ( $payload !== false ) {
                $cookie = self::removeAccessTokenCookie( $this->settings['oauth']['otp_token_cookie'] );
                $response = $response->withHeader( 'Set-Cookie', $cookie );
                $args[ $this->argsOutputName ] = [
                    'xcode' => $x_code
                ];
                return $this->renderer->render( $response, 'otp-pw.phtml', $args );
            }
            return $response->withStatus( StatusCode::HTTP_FOUND )
                            ->withHeader(
                                'Location',
                                $this->settings['app']['url']['login'] .
                                '?code=' . static::PROC_INVALID
                            );
        }
        return $response->withStatus( StatusCode::HTTP_FOUND )
                        ->withHeader(
                            'Location',
                            $this->settings['app']['url']['login'] .
                            '?code=' . static::PROC_FAIL
                        );
    }

    /**
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws Exception
     * @author Nick
     *  @method POST
     */
    public function POST_OneTimePassword(Request $request, Response $response, array $args ): Response
    {
        $mail_code  = $request->getParsedBodyParam( 'otp-pw-input' );
        $uuid_token = $request->getParsedBodyParam( 'token' );
        $error_code = static::PROC_INVALID;
        if ( !empty( $mail_code ) && !empty( $uuid_token ) ) {
            $ex_payload = self::isToken( $uuid_token, $this->settings['oauth']['pw_reset_secret'] );
            if ( $ex_payload !== false && isset( $ex_payload->data->uuid ) ) {
                $register = new RegisterFun( $this->container );
                $out = $register->verifyLoginOtp( $ex_payload->data->uuid, $mail_code );
                if ( is_int( $out ) ) { // denied
                    // set a log
                    $this->container->logger->notice(
                        'OTP access denied #' . $out .
                        '; payload=' . parent::jsonLogStr( $ex_payload ) .
                        '; code=' . $mail_code . ', uuid=' . $uuid_token);

                    //如果是輸入錯誤，系統會給予三次錯誤機會。因此要回到原來的界面去給予重新輸入的機會
                    // 因此，必須要將uuid的token存回cookie給OTP page重新使用它。才不會造成頁面又被再次轉跳回login page
                    if ( $out === static::PROC_EXCEEDED_ATTEMPT || $out === static::PROC_BLOCKED ) {
                        return $response->withStatus( StatusCode::HTTP_MOVED_PERMANENTLY )
                                        ->withHeader(
                                            'Location',
                                            $this->settings['app']['url']['login'] .
                                            '?code=' . $out
                                        );
                    } else {
                        // return a response to redirect back to OTP page
                        // IMPORTANT: 請一定要將exp設定為0，這樣就是在瀏覽棄關閉之後，自動會全部清除。
                        $cookie_content = GnCookie::setWebCookie_v2(
                            $this->settings['oauth']['otp_token_cookie'],
                            $uuid_token,
                            '',
                            '/',
                            0,
                            $this->settings['oauth']['cookie_secur']
                        );
                        // 會保留在現在的 OTP page
                        return $response->withStatus( StatusCode::HTTP_SEE_OTHER )
                                        ->withHeader( 'Set-Cookie', $cookie_content )
                                        ->withHeader(
                                            'Location',
                                            ( $this->settings['app']['url']['otp'] ) .
                                            '?code=' . $error_code
                                        );
                    }
                } else {    // 驗證全數通過，發予access token相關資料
                    // set access token cookie to save in client
                    $access_token = JWT::encode(
                        $out['payload'],
                        $this->settings['oauth']['access_secret'],
                        $this->settings['oauth']['algorithm']
                    );
                    // generate access token in cookie.
                    // if the remember-me flag is false, the expired time must
                    // be set up in 0 to make the browser remove it when the browser window closed.
                    $cookie_content = GnCookie::setWebCookie_v2 (
                        $this->settings['oauth']['access_token_cookie'],
                        $access_token,
                        '',
                        '/',
                        $out['payload']['exp'],
                        $this->settings['oauth']['cookie_secur']
                    );

                    // log for success and mark account
                    $this->container->logger->notice( $out['email'] . ' login is success via OTP' );

                    return $response->withStatus( StatusCode::HTTP_FOUND )
                                    ->withHeader( 'Set-Cookie', $cookie_content )
                                    ->withHeader('Location', '/');
                }
            } else {
                $error_code = static::PROC_FAIL;
            }
        }
        return $response->withStatus( StatusCode::HTTP_SEE_OTHER )
                        ->withHeader(
                            'Location',
                            $this->settings['app']['url']['login'] .
                            '?code=' . $error_code
                        );
    }

    /**
     * Let user resend a new OTP if it is not expired(over 5 min)
     *
     * @param Request  $request
     * @param Response $response
     * @param array    $args
     * @return Response
     * @throws Exception
     * @author Nick
     */
    public function POST_ResentOneTimePassword ( Request $request, Response $response, array $args ): Response
    {
        $uuid_token = $request->getParsedBodyParam( 'ex-token' );  // 取得已經發出去的token來核對
        $this->respCode = StatusCode::HTTP_BAD_REQUEST;
        if ( !empty( $uuid_token ) ) {
            $ex_payload = self::isToken( $uuid_token, $this->settings['oauth']['pw_reset_secret'] );
            if ( $ex_payload !== false && isset( $ex_payload->data->uuid ) ) {
                $register = new RegisterFun( $this->container );
                $out = $register->updateOTP( $ex_payload->data->uuid );
                if ( is_int( $out ) ) {
                    // log the error when email doesn't work
                    $this->container->logger->emergency( 'OTP token update error(#'.$out.'): uuid=' . $ex_payload->data->uuid );

                    // stop and back to the sign-in page
                    return $response->withStatus( StatusCode::HTTP_FOUND )
                                    ->withHeader(
                                        'Location',
                                        ( $this->settings['app']['url']['login'] . '?code=' . static::PROC_SERV_ERROR )
                                    );
                } else {
                    // send email with OTP 6-chars code to member
                    $mailer = $this->container->get('mailer');
                    $is_sent = $mailer->sendMail(
                        [ $out['email'] ],
                        static::MAIL_TITLE_ACCESS_OTP,
                        static::MAIL_HTMLBODY_ACCESS_OTP,
                        [ 'xcode' => $out['code'] ]
                    );
                    if ( !$is_sent ) {
                        // log the error when email doesn't work
                        $this->container->logger->emergency(
                            'OTP mail ' . $out['email'] . ' error: uuid=' .
                            $ex_payload->data->uuid .
                            '; data=' . parent::jsonLogStr( $out )
                        );
                        // stop and back to the sign-in page
                        return $response->withStatus( StatusCode::HTTP_FOUND )
                                        ->withHeader(
                                            'Location',
                                            ( $this->settings['app']['url']['login'] . '?code=' . static::PROC_SERV_ERROR )
                                        );
                    }
                    
                    $payload = JwtPayload::genPayload( 
                        $this->settings['oauth']['issuer'], 
                        'now 5 minutes',
                        [
                            'uuid' => $ex_payload->data->uuid
                        ]
                    );

                    $xcode = JWT::encode(
                        $payload,
                        $this->settings['oauth']['pw_reset_secret'],
                        $this->settings['oauth']['algorithm']
                    );

                    // IMPORTANT: 請一定要將exp設定為0，這樣就是在瀏覽棄關閉之後，自動會全部清除
                    $cookie_content = GnCookie::setWebCookie_v2 (
                        $this->settings['oauth']['otp_token_cookie'],
                        $xcode,
                        '',
                        '/',
                        0,
                        $this->settings['oauth']['cookie_secur']
                    );

                    $this->container->logger->notice(
                        'OTP resending mail ' . $out['email'] . ' success: uuid= ' .
                        $ex_payload->data->uuid .
                        '; data=' . parent::jsonLogStr( $out )
                    );

                    // keep on the page
                    return $response->withHeader( 'Set-Cookie', $cookie_content )
                                    ->withHeader(
                                        'Location',
                                        ( $this->settings['app']['url']['otp'] . '?code=' . static::PROC_OK )
                                    );
                }
            }
        }
        return $response->withStatus( StatusCode::HTTP_FOUND )
                        ->withHeader(
                            'Location',
                            ( $this->settings['app']['url']['login'] . '?code=' . static::PROC_FAIL )
                        );
    }

    /**
     * url: /api-token.
     *
     * Filter access token by middleware before you call this function API.
     * <p>GET:</p> Use access token to get a new api token.
     *
     * @author Nick
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function GET_GetApiToken ( Request $request, Response $response, array $args ): Response
    {
        $register = new RegisterFun( $this->container );
        $payload = $register->genApiAuth( $this->container->jwt->jti );
        if ( !empty( $payload ) ) {
            $apiToken = JWT::encode(
                $payload,
                $this->settings['oauth']['api_secret'],
                $this->settings['oauth']['algorithm']
            );
            return $response->withHeader( $this->settings['oauth']['header'], $apiToken )
                            ->withJson(
                                parent::jsonResp( 
                                    1, 
                                    static::HTTP_RESP_MSG[ StatusCode::HTTP_OK ] 
                                ),
                                StatusCode::HTTP_OK
                            );
        } else {
            $this->container->logger->warn( 'member(' . $this->container['usr']['id'] . ') get api token fail' );
        }
        return $response->withJson(
            parent::jsonResp( 0, static::HTTP_RESP_MSG[ StatusCode::HTTP_UNAUTHORIZED ] ),
            StatusCode::HTTP_UNAUTHORIZED
        );
    }
}
