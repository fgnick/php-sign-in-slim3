<?php
namespace Gn;

use ErrorException;

use Gn\Lib\GnCookie;
use Gn\Ctl\AppBasicCtl;

use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\StatusCode;

/**
 * Work for Vue.js controller
 * You have to put on the front-end Vue.js code to the public(root directory) folder.
 * 
 * @author Nick Feng
 * @since 1.0
 */
class AppVue extends AppBasicCtl
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
     * This class works for Vue.js front-end side only.
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws ErrorException
     * @author Nick Feng
     */
    public function __invoke ( Request $request, Response $response, array $args ): Response
    {
        $resp = $this->appRenderer( $response, $args, $this->container['usr'], 'index' );
        if ( $resp === false ) {
            // when it is false, it means access token is illegal, 
            // you must clean up access token cookie to make it be logged out.
            $cookie = GnCookie::setWebCookie_v2(
                $this->settings['oauth']['access_token_cookie'],
                '',
                '',
                '/',
                -1,
                $this->settings['oauth']['cookie_secur']
            );
            return $response->withStatus( StatusCode::HTTP_FOUND )
                            ->withHeader( 'Set-Cookie', $cookie )
                            ->withHeader( 'Location', $this->settings['app']['url']['login'].'?code='.static::PROC_NO_ACCESS );
        }
        return $resp;
    }
}