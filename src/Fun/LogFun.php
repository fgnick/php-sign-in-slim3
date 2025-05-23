<?php
/**
 * All system log processing controller functions are here.
 * 
 * @author Nick Feng
 * @since 1.0
 */
namespace Gn\Fun;

use Gn\Sql\SqlLog;

use Slim\Container;

/**
 * Handler of system log.
 *
 * @author Nick Feng
 */
class LogFun
{
    protected $sqlLog = NULL;
    
    /**
     * Constructor, and look up jwt id automatically when it called.
     * 
     * @param Container $container
     */
    public function __construct( Container $container ) 
    {
        $this->sqlLog = new SqlLog( $container );
    }
    
    /**
     * Get all numbers of kinds of notifications. It always works for badge number on navigation.
     * 
     * @param int $start_time UNIX timestamp.
     * @param int $channel
     * @param int $level
     * @param string $search
     * @return int|array
     */
    public function getSystemLog( int $start_time, int $channel = 0, int $level = 0, string $search = '' )
    {
        return $this->sqlLog->printSystemLog( $start_time, $channel, $level, $search );
    }
}
