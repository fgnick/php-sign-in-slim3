<?php
namespace Gn\Obj;

use Exception;
use Gn\Lib\ValueValidate;

class UserDataObj implements ObjInterface
{
    // IMPORTANT: property name 請與 table 的 column name 一樣，這樣比較好在 SQL 的時候轉換方便性較佳
    public $uid;
    public $email;
    public $nickname;
    public $status;
    public $type;
    public $modify_on;
    public $create_on;
    
    public function __construct(
        ?string $uid = NULL,
        ?string $email = NULL,
        ?string $nickname = NULL,
        ?int $status = NULL,
        ?int $type = NULL,
        ?int $modify_on = NULL,
        ?int $create_on = NULL
    ) {
        $this->uid = $uid;
        $this->email = $email;
        $this->nickname = $nickname;
        $this->status = $status;
        $this->type = $type;
        $this->modify_on = $modify_on;
        $this->create_on = $create_on;
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
     * 方便從資料庫輸出的row，可以快速的將資料放到此物件之中的所有對應 properties。但是，物件的properties必須名稱也要與資料庫表單欄位相同。
     *
     * @param array $row
     * @return void
     */
    public function importSqlRow( array $row ): void
    {
        foreach ( $row as $key => $value ) {
            if ( property_exists( $this, $key ) ) { // 只要存在，就把SQL得到的值，賦予給該 property
                // 只要管理好 insert & update 資料的格式正確，其實這邊就不需要再檢查資料正確性了
                $this->$key = $value;
            }
        }
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
                case 'uid': // it cannot be NULL
                    if (is_string($value) && ValueValidate::is_user_uid($value)) {
                        return true;
                    }
                    return false;
                case 'email':
                    if (is_string($value) && ValueValidate::is_email($value)) {
                        return true;
                    }
                    return false;
                case 'nickname':
                    if (is_string($value) && ValueValidate::is_user_nickname($value)) {
                        return true;
                    }
                    return false;
                case 'status':
                    if (is_int($value) && ValueValidate::is_user_status($value)) {
                        return true;
                    }
                    return false;
                case 'type':
                    if (is_int($value) && ValueValidate::is_user_type($value)) {
                        return true;
                    }
                    return false;
                case 'create_on':
                case 'modify_on':
                    if ( is_int($value) && $value >= 0 ) {
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