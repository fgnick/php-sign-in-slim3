<?php
namespace Gn\Sql;

use BadFunctionCallException;
use Exception;
use PDOException;
use DateTime;
use DateTimeZone;

use Gn\Lib\WazaariMySqlLog;
use Monolog\Logger;
use Slim\Container;

/**
 * System log database process.
 *
 * @author Nick Feng
 * @since 1.0
 */
class SqlLog extends SqlBase
{
    /**
     * All things copy from Monolog\Logger, and MySQL table is following up too.
     */
    const MONOLOG_LOGGER_ARR = [
        Logger::DEBUG,
        Logger::INFO,
        Logger::NOTICE,
        Logger::WARNING,
        Logger::ERROR,
        Logger::CRITICAL,
        Logger::ALERT,
        Logger::EMERGENCY
    ];

    private $log_table_name;

    /**
     * @var SqlServConn
     */
    private $conn;

    /**
     * @var array it is Slim global settings' array.
     */
    private $settings;
    
    /**
     * Constructor and get pdo connection.
     *
     * @param Container $container Slim container
     */
    public function __construct( Container $container )
    {
        parent::__construct(); 
        // connect to database
        $this->settings = $container->get('settings');
        $this->conn = new SqlServConn( $this->settings['db']['main'] );
        if ( isset( $this->settings['db']['logger'][ WazaariMySqlLog::LOG_TABLE_INDEX ] ) ) {
            $this->log_table_name = $this->settings['db']['logger'][ WazaariMySqlLog::LOG_TABLE_INDEX ];
            if ( empty( $this->log_table_name ) || strlen( $this->log_table_name ) > 32 ) {
                throw new BadFunctionCallException( 'Log name is invalid' );
            }
        } else {
            throw new BadFunctionCallException( 'Log name is not defined' );
        }
    }
    
    /**
     * Output system log.
     * 
     * @param int $time UNIX timestamp
     * @param int $channels
     * @param int $level
     * @param string $search
     * @return array|int
     */
    public function printSystemLog ( int $time, int $channels = 0, int $level = 0, string $search = '' )
    {
        if ( $time < 0 || $channels < 0 || $level < 0 ) {
            return self::PROC_INVALID;
        } else if ( $level > 0 && !in_array( $level, static::MONOLOG_LOGGER_ARR, TRUE ) ) {
            return self::PROC_INVALID;
        }

        $sql_message = '';
        if ( !empty( $search ) ) {
            $search = trim( $search );
            $q_arr = explode( ' ', preg_replace( '/\s+/', ' ', $search ) );
            foreach ( $q_arr as $txt ) {
                $sql_message .= ' OR message LIKE \'%' . $txt . '%\'';
            }
            if ( !empty( $sql_message ) ) {
                $sql_message = trim( $sql_message, ' OR' );
                $sql_message = ' AND (' . $sql_message . ') ';
            }
        }
        
        $sql_channel_str = '';
        switch ( $channels ) {
            case 0: // all
                $sql_channel_str = '\'web\',\'api\'';
                break;
            case 1: // app
                $sql_channel_str = '\'web\'';
                break;
            case 2: // api
                $sql_channel_str = '\'api\'';
                break;
            default:
                return self::PROC_INVALID;
        }
        
        $result = self::PROC_FAIL;
        $stat   = NULL;
        try {
            $sql_cols = implode( ',', WazaariMySqlLog::LOG_EXPORT_COLUMNS );
            $stat = $this->conn->pdo->query(
                'SELECT ' . $sql_cols .
                ' FROM ' . $this->log_table_name .
                ' WHERE time <= FROM_UNIXTIME(' . $time . ') AND channel IN (' . $sql_channel_str . ') '.
                ( $level > 0 ? 'AND level = ' . $level : '' ) . 
                $sql_message .
                ' ORDER BY time DESC LIMIT 50');
            if ( $stat !== false ) {
                $result = $stat->fetchAll();
                $tz = new DateTimeZone('UTC');    // it's better to set the time zone to be UTC. to prevent the system is not UTC
                foreach ( $result as &$row ) {
                    foreach ( $row as $k => $v ) {
                        if ( is_null( $v ) ) {
                            $row[$k] = '';
                        }
                        if ( $k == 'time' ) {
                            $row['time'] = ( new DateTime( $row['time'], $tz ) )->getTimeStamp();
                        }
                    }
                }
                unset($row);
            }
        } catch ( PDOException $e ) {
            // NOTE: when the log is at the beginning, there is no table, log, in database.
            // Mysql error message: Base table or view not found
            if ( $e->errorInfo[1] == 1146 ) {
                $result = array();
            } else {
                $result = static::PROC_SERV_ERROR;
            }
        } catch ( Exception $e ) {
            $result = static::PROC_SERV_ERROR;
        }
        if ( $stat !== NULL ) {
            $stat->closeCursor();
            $stat = NULL;
        }
        return $result;
    }
}