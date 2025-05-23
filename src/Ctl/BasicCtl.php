<?php
namespace Gn\Ctl;

use ErrorException;
use OutOfBoundsException;

use Gn\Interfaces\BaseRespCodesInterface;
use Gn\Interfaces\HttpMessageInterface;

// from Slim
use Slim\Container;
use Slim\Http\StatusCode;

/**
 * Basic controller functions for extending.
 * 
 * @author Nick Feng
 * @since 1.0
 */
abstract class BasicCtl implements HttpMessageInterface, BaseRespCodesInterface
{
    /**
     * Get container object from Slim.
     * @var Container
     */
    protected $container;
    
    /**
     * API response code of header
     * @var int
     */
    protected $respCode = StatusCode::HTTP_BAD_REQUEST;
    
    /**
     * API response JSON content
     * @var array
     */
    protected $respJson = NULL;
    
    /**
     * Constructor.
     *
     * @param Container $container
     */
    public function __construct ( Container $container )
    {
        $this->container = $container;
        /*
         HTTP Response Code 500 => For SQL error, php exception error.
         HTTP Response Code 401 => Only for token verification.
         HTTP Response Code 400 => Only for request variable fields and values are illegal.
         HTTP Response Code 200 => All without above two items, but there are some status code in response JSON structure:
             0 is on failure,
             1 is on success
         */
        $this->respJson = self::jsonResp( 
            0, 
            static::HTTP_RESP_MSG[ StatusCode::HTTP_BAD_REQUEST ]
        );
    }
    
    /**
     * check the request is from the specified host
     *
     * @return bool
     */
    protected function isReferred (): bool
    {
        if( !isset( $_SERVER['HTTP_REFERER'] ) || 
            !isset( $_SERVER['HTTP_HOST'] ) || 
            !isset( $_SERVER['SERVER_NAME'] ) ||
            strpos( $_SERVER['HTTP_REFERER'], $_SERVER['SERVER_NAME'] ) === false ||
            strpos( $_SERVER['HTTP_HOST'], $_SERVER['SERVER_NAME'] ) === false
        ) {
            return false;
        }
        return true;
    }

    /**
     * Get the coming reference
     *
     * @return string
     */
    protected function getExitNodeIpAddress (): string
    {
        $ip = '';
        // check HTTP_CLIENT_IP
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { // check HTTP_X_FORWARDED_FOR
            // Resolving multiple IP addresses
            $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            foreach ($ipList as $forwardedIp) {
                $forwardedIp = trim($forwardedIp);
                if (filter_var($forwardedIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    $ip = $forwardedIp;
                    break;
                }
            }
        } else if (
            !empty($_SERVER['REMOTE_ADDR']) && 
            filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)
        ) { // check REMOTE_ADDR
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // If there is nothing, return default IP address 0.0.0.0
        return $ip ?: '0.0.0.0';
    }
    
    /**
     * Before you save log string with token information in jwt->data,
     * you can use the function to convert all to string.
     *
     * @param mixed $payloadData 
     * @param int   $options JSON default is JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
     * @return string
     * @throws ErrorException
     */
    protected function jsonLogStr ( 
        $payloadData, 
        int $options = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ): string {
        $json = json_encode($payloadData, $options);
        if ($json === false) {
            $error = json_last_error_msg();
            throw new ErrorException("Failed to encode JSON: $error");
        }
        return $json;
    }
    
    /**
     * Message JSON array
     *
     * @param int $status
     * @param mixed $data in json
     * @return array { 'status': [ false = 0| TRUE = 1 ], 'message': [ string | json structure array ] }
     */
    protected function jsonResp ( int $status = 0, $data = [] ): array
    {
        /*
         HTTP Response Code 500 => For SQL error, php exception error.
         HTTP Response Code 401 => Only for token verification.
         HTTP Response Code 400 => Only for request variable fields and values are illegal.
         HTTP Response Code 200 => All without above two items, but there are some status code in response JSON structure:
             0 is on failure,
             1 is on success,
         */
        return [
            'status' => $status,
            'data'   => $data
        ];
    }

    /**
     * 驗證參數的類型
     *
     * @param mixed $value 要驗證的值
     * @param string $type 預期的類型（integer, string, array, boolean, float）
     * @return bool 如果類型匹配則返回 true，否則返回 false
     */
    protected function validateParamType($value, string $type): bool
    {
        switch ($type) {
            case 'integer':
                return ctype_digit((string)$value) || is_int($value);
            case 'string':
                return is_string($value);
            case 'array':
                return is_array($value);
            case 'boolean':
                return is_bool($value);
            case 'float':
                return is_float($value) || is_numeric($value);
            default:
                return false;
        }
    }
    
    /**
     * This works for DataTables.net framework in front-end javascript in AJAX.
     * The function is working for detecting the URL query parameters is in need.
     * What system only needs are page, per, order, search, and draw(a random code)
     * 
     * @param array $q
     * @return bool
     */
    protected function is_dataTablesParams ( array $q ): bool
    {
        $requiredParams = [
            'draw'   => 'integer',
            'page'   => 'integer',
            'per'    => 'integer',
            'order'  => 'array',
            'search' => 'string',
        ];
        foreach ($requiredParams as $param => $type) {
            if (!isset($q[$param])) {
                error_log("Missing required parameter: $param");
                return false;
            }
            if (!$this->validateParamType($q[$param], $type)) {
                error_log("Invalid parameter type for $param. Expected $type.");
                return false;
            }
        }
        return true;
    }
    
    /**
     * This works for DataTables.net framework in front-end javascript in AJAX.
     *
     * @param int $drawCode It prevents XXS for Datatables.
     * @param int $totalElem
     * @param array $jsonArray
     * @return array
     */
    protected function dataTablesResp ( 
        int $drawCode, 
        int $totalElem = 0, 
        array $jsonArray = [],
        ?int $filteredElem = null
    ): array {
        if ($drawCode < 0) {
            throw new ErrorException('Invalid drawCode. It must be a non-negative integer.');
        }
        if ($totalElem < 0) {
            throw new ErrorException('Invalid totalElem. It must be a non-negative integer.');
        }
        if ($filteredElem !== null && $filteredElem < 0) {
            throw new ErrorException('Invalid filteredElem. It must be a non-negative integer.');
        }
        $filteredElem = $filteredElem ?? $totalElem;
        return array (
            'draw'            => $drawCode,     // It prevents XXS for Datatables, so it from front-side to give a start
            'recordsTotal'    => $totalElem,    // total number of elements for this category(a single table contents)
            'recordsFiltered' => $totalElem,    // limit for elements to show
            'data'            => $jsonArray     // json data array.
        );
    }

    /**
     * NOTE: 請跟著 Slim3 的定義去顯示 HTTP TEXT & HTTP CODE
     *
     * @param int    $code
     * @param string $header_txt
     * @param mixed  $data
     * @return bool
     * @throws OutOfBoundsException
     */
    protected function resp_decoder (
        int $code,
        string $header_txt = '',
        $data = NULL
    ): bool {
        // default value is 200
        $this->respCode = StatusCode::HTTP_OK;

        // check the code is valid
        if (!isset(static::PROC_TXT[$code])) {
            throw new OutOfBoundsException(
                sprintf("Invalid process code: %d. Allowed codes: %s", $code, implode(', ', array_keys(static::PROC_TXT)))
            );
        }

        $isSuccess = false;
        switch ($code) {
            case static::PROC_OK:
                $this->respJson['status'] = 1; // success
                $isSuccess = true;
                break;
            case static::PROC_INVALID:
                $this->respCode = StatusCode::HTTP_BAD_REQUEST;
                break;
            case static::PROC_SERIALIZATION_FAELURE:
            case static::PROC_SERV_ERROR:
                $this->respCode = StatusCode::HTTP_INTERNAL_SERVER_ERROR;
                break;
            default:
                // 可擴展其他處理代碼
                $this->respCode = StatusCode::HTTP_BAD_REQUEST;
                break;
        }
        $this->respJson['data'] = $data ?? ($header_txt . static::PROC_TXT[$code]);
        return $isSuccess;
    }

    /**
     * Response for dataTable API
     *
     * @param int $drawCode
     * @param int $totalElem
     * @param array $jsonArray
     * @param int|null $filteredElem 過濾後的記錄數，默認為 $totalElem
     * @return void
     * @throws ErrorException 如果參數無效
     */
    protected function resp_datatable (
        int $drawCode,
        int $totalElem = 0,
        array $jsonArray = [],
        ?int $filteredElem = null
    ) {
        $this->respCode = StatusCode::HTTP_OK;
        $this->respJson = self::dataTablesResp( $drawCode, $totalElem, $jsonArray, $filteredElem );
    }

    /**
     * 將物件或陣列遞迴轉換為陣列，並移除值為 null 的欄位
     *
     * @param object|array $data 物件或陣列
     * @param bool $removeNull 是否移除值為 null 的欄位
     * @return array|mixed 轉換後的陣列或原始值
     */
    function obj2arr( $data, bool $removeNull = true )
    {
        // 如果是物件，先轉換成陣列
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        // 如果是陣列，遞迴處理每一個元素
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                // 進行遞迴轉換
                $converted = $this->obj2arr($value, $removeNull);
                if ( $removeNull ) {
                    // 只將值不為 null 的項目加入結果
                    if ($converted !== null) {
                        $result[$key] = $converted;
                    }
                }
            }
            return $result;
        }
        return $data;   // 若非陣列或物件，就直接回傳原值
    }
}