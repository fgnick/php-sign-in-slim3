<?php
namespace Gn\Fun;

use Gn\Sql\SqlAsset;

use Slim\Container;

/**
 * All asset processing functions are here.
 *
 * @author Nick Feng
 * @since 1.0
 */
class AssetFun
{
    /**
     * asset database.
     * 
     * @var SqlAsset
     */
    protected $sqlAsset = null;
    
    /**
     * Constructor, and look up jwt id automatically when it called.
     *
     * @param array $db_settings
     */
    public function __construct( Container $container )
    {
        $this->sqlAsset = new SqlAsset( $container );
    }
    
    /**
     * 
     * @return array|int
     */
    public function getAddressCountryAttribute ()
    {
        return $this->sqlAsset->get_address_country_attribute();
    }
    
    /**
     * 
     * @return array|int
     */
    public function getPhoneZoneAttribute ()
    {
        return $this->sqlAsset->get_phone_zone_attribute();
    }
    
    /**
     * 
     * @param array $hash_arr
     * @return array|int
     */
    public function getAddress( array $hash_arr )
    {
        return $this->sqlAsset->get_address( $hash_arr );
    }
}
