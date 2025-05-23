<?php
namespace Gn;

use ErrorException;
use Gn\Fun\LogFun;
use Gn\Ctl\ApiBasicCtl;

// from Slim
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Log controller.
 * All requests in JSON.
 * 
 * @author Nick Feng
 * @since 1.0
 */
class LogCtrl extends ApiBasicCtl
{
    /**
     * system log function.
     * 
     * @var LogFun
     */
    protected $systemLog = NULL;

    /**
     * Constructor.
     *
     * @param Container $container
     * @throws ErrorException
     */
    public function __construct( Container $container )
    {
        parent::__construct( $container );
        $this->systemLog = new LogFun( $container );
    }
    
    /**
     * <p>Get:</p> JSON string data for remote commands selector.
     * 
     * params: ?st=[timestamp]&c=[channel number]&l=[level number]
     * 
     * @author Nick
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function GET_PrintSysTemLog(Request $request, Response $response, array $args): Response
    {
        $start   = $request->getQueryParam( 's', '0' ); // required
        $channel = $request->getQueryParam( 'c', '0' ); // required
        $level   = $request->getQueryParam( 'l', '0' ); // optional
        $search  = $request->getQueryParam( 'q', '' );  // optional
        if ( ctype_digit( $start ) && ctype_digit( $channel ) && ctype_digit( $level ) && is_string( $search ) ) {
            if ( $this->container['usr']['permission']['system'] >= 1 ) {
                $out = $this->systemLog->getSystemLog( $start, $channel, $level, $search );
                if ( is_int( $out ) ) {
                    self::resp_decoder( $out );
                } else {
                    self::resp_decoder( static::PROC_OK, '', $out );
                }
            } else {
                self::resp_decoder( static::PROC_NO_ACCESS );
            }
        }
        return $response->withJson( $this->respJson, $this->respCode );
    }
}
