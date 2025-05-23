<?php
namespace Gn\Interfaces;

/**
 * All http response default message string are collected in this interface for implementing
 * 
 * @author nickfeng 
 * @since 2019-08-30
 */
interface BaseRespCodesInterface {
    // status code for returning.
    const PROC_FAIL           = 0x00;
    const PROC_OK             = 0x01;   // 0x01 ~ 0x0F 可子針對 success 回應做自由發揮
    const PROC_INVALID        = 0x10;
    const PROC_NO_ACCESS      = 0x11;
    const PROC_DATA_FULL      = 0x12;
    const PROC_INVALID_USER   = 0x13;
    const PROC_INVALID_PW     = 0x14;
    const PROC_NOT_EXISTED    = 0x15;
    const PROC_BLOCKED        = 0x16;
    const PROC_UNINITIALIZED  = 0x17;   //uninitialized
    const PROC_TOKEN_ERROR    = 0x18;
    const PROC_MEM_VIEW_ERROR = 0x19;
    const PROC_FILE_INVALID   = 0x1A;
    const PROC_WAITING        = 0x1B;
    const PROC_EXCEEDED_ATTEMPT = 0x1C;
    // for SQL error
    const PROC_FOREIGN_KEY_CONSTRAINT = 0xFC;   // foreign key constraint fails
    const PROC_SERIALIZATION_FAELURE  = 0xFD;   // deadlock table
    const PROC_DUPLICATE              = 0xFE;
    const PROC_SERV_ERROR             = 0xFF;
    
    /**
     * Convert processing code to text.
     * 
     * @var array
     */
    const PROC_TXT = [
        self::PROC_FAIL          => 'fail',
        self::PROC_OK            => 'ok',   // 0x01 ~ 0x0F 可子針對 success 回應做自由發揮
        self::PROC_INVALID       => 'invalid input',
        self::PROC_NO_ACCESS     => 'access denied',
        self::PROC_DATA_FULL     => 'data full',
        self::PROC_INVALID_USER  => 'invalid ID',
        self::PROC_INVALID_PW    => 'invalid password',
        self::PROC_NOT_EXISTED   => 'not existed',
        self::PROC_BLOCKED       => 'blocked',
        self::PROC_UNINITIALIZED => 'uninitialized',
        self::PROC_TOKEN_ERROR   => 'token error',
        self::PROC_MEM_VIEW_ERROR=> 'user view error',
        self::PROC_FILE_INVALID  => 'file invalid',
        self::PROC_WAITING       => 'process waiting',
        self::PROC_EXCEEDED_ATTEMPT => 'You\'ve exceeded the maximum number of attempts',
        // for SQL error
        self::PROC_FOREIGN_KEY_CONSTRAINT => 'data with key constraint',   // foreign key constraint fails
        self::PROC_SERIALIZATION_FAELURE  => 'data serialization failure',   // deadlock table or timeout
        self::PROC_DUPLICATE              => 'data duplicate',
        self::PROC_SERV_ERROR             => 'server internal error'
    ];
}
