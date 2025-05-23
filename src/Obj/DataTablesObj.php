<?php
namespace Gn\Obj;

use Exception;

/**
 * A basic object for datatables I/O
 * 
 * @author Nick Feng
 * @since 2025-03-19
 */
class DataTablesObj
{
    // IMPORTANT: 這些property的設計，是與datatables的應用一致的，Vue.js端也必須一致
    public $draw;
    public $per;
    public $page;
    public $order;
    public $search;
    public $recordsTotal;
    public $data;   // array of database export data rows

    public function __construct(
        ?int $draw = NULL,
        ?int $page = NULL,
        ?int $per = NULL,
        ?array $order = NULL,
        ?string $search = NULL,
        ?int $recordsTotal = NULL,
        ?array $data = NULL
    ) {
        $this->draw = $draw;
        $this->page = $page;
        $this->per = $per;
        $this->order = $order;
        $this->search = $search;
        $this->recordsTotal = $recordsTotal;
        $this->data = $data;
    }

    /**
     * remove all data
     **/
    public function __destruct()
    {
        foreach ( $this as $key => $value ) {
            unset( $this->$key );
        }
    }

    /**
     * Import datatables data from $request->getQueryParams() of Slim
     * if the keys are not equal in array, it will return FALSE
     *
     * @param array $q
     * @return bool
     */
    public function import( array $q ): bool
    {
        if ( isset( $q['draw'] )
            && isset( $q['page'] )
            && isset( $q['per'] )
            && isset( $q['order'] )
            && isset( $q['search'] )
            && ( ctype_digit( $q['draw'] ) || is_int( $q['draw'] ) )
            && ( ctype_digit( $q['page'] ) || is_int( $q['page'] ) )
            && ( ctype_digit( $q['per'] ) || is_int( $q['per'] ) )
            && is_array( $q['order'] ) && is_string( $q['search'] ) )
        {
            $this->draw   = (int)$q['draw'];
            $this->page   = (int)$q['page'];
            $this->per    = (int)$q['per'];
            $this->order  = $q['order'];
            $this->search = $q['search'];
            foreach ( $this as $key => $value ) {
                if ( !self::isValid( $key, $value ) ) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * To detect the object is empty or not
     *
     * @return boolean
     */
    public function isEmpty(): bool
    {
        foreach ( $this as $value ) {
            if ( !is_null( $value ) ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Don't suggest to use the method Individually
     * 如果任何一個欄位的數值是 NULL，則代表這個欄位的數值是可以忽視的。
     * 但是，如果是空字串的話就不可以忽視。因為那有可能是它想要清除字串，才用空字串來消除
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function isValid( string $key, $value = NULL ): bool
    {
        if ( property_exists($this, $key) ) {
            $value = is_null( $value ) ? $this->$key : $value;
            switch ($key) {
                case 'draw': // it cannot be NULL
                case 'per':
                case 'page':
                    if ( is_int( $value )  && $value >= 0 ) {
                        return true;
                    }
                    return false;
                case 'recordsTotal':
                    if ( is_null( $value ) ) {  // skip if no assign
                        return true;
                    }
                    if ( is_int( $value )  && $value >= 0 ) {
                        return true;
                    }
                    return false;
                case 'search':
                    if ( is_null( $value ) ) {
                        return true;
                    }
                    if ( is_string( $value ) ) {
                        return true;
                    }
                    return false;
                case 'data':
                    if ( is_null( $value ) ) {  // skip if no assign
                        return true;
                    }
                    if ( is_array( $value ) ) {
                        return true;
                    }
                    return false;
                case 'order':
                    if ( is_array( $value ) ) {
                        if ( empty( $value ) ) {
                            return true;
                        }
                        foreach ( $value as $v ) {
                            if ( !isset( $v['column'] ) || !is_numeric( $v['column'] ) ) {
                                return false;
                            } else if ( !isset( $v['dir'] ) || !is_string( $v['dir'] ) ) {
                                return false;
                            }
                        }
                        return true;
                    }
                    return false;
            }
        }
        return false;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function __get( string $key )
    {
        return $this->$key;
    }

    /**
     * @param string $key
     * @param $value
     * @return void
     * @throws Exception
     */
    public function __set(string $key, $value )
    {
        if ( !self::isValid( $key, $value )) {
            throw new Exception( $key . ' is assigned an invalid value' );
        }
        $this->$key = $value;
    }

    /**
     *
     * @param string $key
     * @return boolean
     */
    public function __isset( string $key )
    {
        return isset( $this->$key );
    }

    /**
     *
     * @param string $key
     */
    public function __unset( string $key )
    {
        unset( $this->$key );
    }
}