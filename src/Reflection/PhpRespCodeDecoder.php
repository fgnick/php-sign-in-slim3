<?php
// control HTTP CORS for adding to middleware.php

namespace Gn\Reflection;

use ReflectionClass;

use Gn\Interfaces\BaseRespCodesInterface;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\StatusCode;

class PhpRespCodeDecoder implements BaseRespCodesInterface
{
    protected $constVars;

    public function __construct()
    {
        $this->constVars = (new ReflectionClass(self::class))->getConstants();
        // you only need PROC_TXT array for exporting
        $this->constVars = array_intersect_key(
            $this->constVars,
            array_flip(array_filter(array_keys($this->constVars), function($key) {
                return strpos($key, 'PROC_TXT') === 0;
            }))
        );
    }

    public function __invoke( Request $request, Response $response, $next )
    {
        $jsonConstants = json_encode( $this->constVars, JSON_UNESCAPED_UNICODE );
        if ( $jsonConstants === false ) {
            return $response->withStatus( StatusCode::HTTP_INTERNAL_SERVER_ERROR )
                            ->withJson(['error' => 'Failed to encode constants']);
        }
        $response = $response->withStatus( StatusCode::HTTP_OK );
        $response->getBody()->write($jsonConstants);
        return $response->withHeader('Content-Type', 'application/json')
                        ->withHeader('Access-Control-Allow-Origin', '*')
                        ->withHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')  
                        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

}