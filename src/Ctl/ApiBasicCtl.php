<?php
namespace Gn\Ctl;

use ErrorException;

// from Slim
use Slim\Container;

/**
 * Api basic controller functions for extending.
 * 
 * @author Nick Feng
 * @since 1.0
 */
abstract class ApiBasicCtl extends BasicCtl
{
    /**
     * Constructor.
     *
     * @param Container $container
     * @throws ErrorException
     */
    public function __construct( Container $container )
    {
        if ( empty( $container->jwt ) || !is_object( $container->jwt )  ) {
            throw new ErrorException( 'JWT is missing or invalid in the container' );
        } else if ( empty( $container['usr'] ) ) {
            throw new ErrorException( 'Empty channel: jwt=' . parent::jsonLogStr( $container->jwt ) );
        }
        parent::__construct( $container );
    }
}


