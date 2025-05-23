<?php
namespace Gn\Sql;

use Gn\Lib\ValueValidate;

use Slim\Container;

/**
 * website asset saving in DB
 * .
 * @author Nick Feng
 * @since 1.0
 */
class SqlAsset extends SqlBase 
{
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
     * @param Container $container from Slim container
     */
    public function __construct( Container $container )
    {
        parent::__construct(); 
        // connect to database
        $this->settings = $container->get('settings');
        $this->conn = new SqlServConn( $this->settings['db']['main'] );
    }
    
    /**
     * get zone code JSON of phone call.
     *
     * @return array|int
     */
    public function get_phone_zone_attribute ()
    {
        return $this->conn->selectTransact( 'SELECT * FROM phone_zone_code' );
    }
    
    /**
     * get country attribute JSON of address.
     * 
     * @return array|int
     */
    public function get_address_country_attribute ()
    {
        return $this->conn->selectTransact( 'SELECT * FROM addr_country_code' );
    }
    
    /**
     * Undocumented function
     *
     * @param string $hash_id   each address has an unique hash id
     * @return array|int
     */
    public function get_address ( array $hash_arr )
    {
        if (empty($hash_arr)) {
            return static::PROC_INVALID;
        }
        foreach ( $hash_arr as $value ) {
            if ( !ValueValidate::is_hashMd5( $value ) ) {
                return static::PROC_INVALID;
            }
        }
        return $this->conn->selectTransact( 
            'SELECT * FROM address WHERE hash_id IN (' . 
            parent::pdoPlaceHolders('?', count($hash_arr)) . ')', 
            $hash_arr 
        );
    }
}
