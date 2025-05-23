<?php
namespace Gn;

use ErrorException;
use Gn\Ctl\ApiBasicCtl;
use Gn\Fun\AssetFun;

// from Slim
use InvalidArgumentException;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\StatusCode;
use Slim\Http\Stream;

/**
 * All assets of website are here protected by token.
 *
 * @author Nick Feng
 * @since 1.0
 */
class AssetCtrl extends ApiBasicCtl
{
    /**
     * @var AssetFun
     */
    protected $assetFun;

    /**
     * Constructor.
     *
     * @param Container $container
     * @throws ErrorException
     */
    public function __construct( Container $container )
    {
        parent::__construct( $container );
        $this->assetFun = new AssetFun( $this->container );
    }
    
    /**
     * get phone zone code JSON of phone call.
     *
     * @param Request  $request
     * @param Response $response
     * @param array    $args
     * @return Response
     */
    public function GET_PhoneZoneCode( Request $request, Response $response, array $args ): Response
    {
        $output = $this->assetFun->getPhoneZoneAttribute();
        if ( is_array( $output ) ) {
            $json_txt = json_encode( $output );
            if ( $json_txt === false ) {
                $response = $response->withStatus( StatusCode::HTTP_INTERNAL_SERVER_ERROR );
                $response->withHeader('Content-Type', 'text/html')->getBody()->write( json_last_error_msg() );
                return $response;
            }
            $response->withStatus( StatusCode::HTTP_OK )->getBody()->write( $json_txt );
            return $response->withHeader('Content-Type', 'application/json')
                            ->withHeader('Access-Control-Allow-Origin', '*')
                            ->withHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')  
                            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, '.$this->container->get('settings')['oauth']['header']);
        } else {
            return $response->withStatus( StatusCode::HTTP_NOT_FOUND );
        }
    }

    /**
     * get country code JSON of address.
     *
     * @param Request  $request
     * @param Response $response
     * @param array    $args
     * @return Response
     */
    public function GET_AddressCountryCode( Request $request, Response $response, array $args ): Response
    {
        $output = $this->assetFun->getAddressCountryAttribute();
        if ( is_array($output) ) {
            $json_txt = json_encode( $output );
            if ( $json_txt === false ) {
                $response = $response->withStatus( StatusCode::HTTP_INTERNAL_SERVER_ERROR );
                $response->withHeader('Content-Type', 'text/html')->getBody()->write( json_last_error_msg() );
                return $response;
            }
            $response->withStatus( StatusCode::HTTP_OK )->getBody()->write( $json_txt );
            return $response->withHeader('Content-Type', 'application/json')
                            ->withHeader('Access-Control-Allow-Origin', '*')
                            ->withHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
                            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, '.$this->container->get('settings')['oauth']['header']);
        } else {
            return $response->withStatus( StatusCode::HTTP_NOT_FOUND );
        }
    }

    /**
     * get address JSON of address.
     *
     * @param Request  $request
     * @param Response $response
     * @param array    $args
     * @return Response
     */
    public function GET_Address( Request $request, Response $response, array $args ): Response
    {
        $hashcode_arr = $request->getQueryParam( 'id', [] );
        $output = $this->assetFun->getAddress( $hashcode_arr );
        if ( is_array($output) ) {
            $json_txt = json_encode( $output );
            if ( $json_txt === false ) {
                $response = $response->withStatus( StatusCode::HTTP_INTERNAL_SERVER_ERROR );
                $response->withHeader('Content-Type', 'text/html')->getBody()->write( json_last_error_msg() );
                return $response;
            }
            $response->withStatus( StatusCode::HTTP_OK )->getBody()->write( $json_txt );
            return $response->withHeader('Content-Type', 'application/json')
                            ->withHeader('Access-Control-Allow-Origin', '*')
                            ->withHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
                            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, '.$this->container->get('settings')['oauth']['header']);
        } else {
            return $response->withStatus( StatusCode::HTTP_NOT_FOUND );
        }
    }
}
