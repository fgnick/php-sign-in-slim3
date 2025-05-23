<?php
namespace Gn\Lib;

use Exception;

/**
 * UUID(GUID) processing functions.
 *
 * @author Nick Feng
 * @since  1.0
 */
class Uuid
{
    /**
     * 完全亂數，沒有任何版本以及變體號碼的參預，128個位元全部亂數(其他的都是122個)
     *
     * @return string
     * @throws Exception
     * @since  2025-02-13
     */
    public static function v0(): string
    {
        if (function_exists('random_bytes')) {
            $hex = bin2hex(random_bytes(16)); // 產生32位元的十六進位字串 for php 8.2 or higher
            return vsprintf('%04s%04s-%04s-%04s-%04s-%04s%04s%04s', str_split($hex, 4));
        } else if (function_exists('openssl_random_pseudo_bytes')) {
            $hex = bin2hex(openssl_random_pseudo_bytes(16)); // 產生32位元的十六進位字串 for php 8.2 or higher
            return vsprintf('%04s%04s-%04s-%04s-%04s-%04s%04s%04s', str_split($hex, 4));
        } else {
            return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                // 32 bits for "time_low"
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                // 16 bits for "time_mid"
                mt_rand(0, 0xffff),
                // 16 bits for "time_hi_and_version",
                // four most significant bits holds version number 4
                mt_rand(0, 0xffff),
                // 16 bits, 8 bits for "clk_seq_hi_res",
                // 8 bits for "clk_seq_low",
                // two most significant bits holds zero and one for variant DCE1.1
                mt_rand(0, 0xffff),
                // 48 bits for "node"
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
        }
    }

    /**
     * Generate v3 UUID
     *
     * Version 3 UUIDs are named based. They require a namespace (another
     * valid UUID) and a value (the name). Given the same namespace and
     * name, the output is always the same.
     *
     * @param string $namespace
     * @param string $name
     * @return false|string
     */
    public static function v3(string $namespace, string $name)
    {
        if(!self::is_valid($namespace)) return false;
        
        // Get hexadecimal components of namespace
        $nhex = str_replace(array('-','{','}'), '', $namespace);
        
        // Binary Value
        $nstr = '';
        
        // Convert Namespace UUID to bits
        for($i = 0; $i < strlen($nhex); $i+=2) {
            $nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
        }
        
        // Calculate hash value
        $hash = md5($nstr . $name);
        
        return sprintf('%08s-%04s-%04x-%04x-%12s',
            
            // 32 bits for "time_low"
            substr($hash, 0, 8),
            
            // 16 bits for "time_mid"
            substr($hash, 8, 4),
            
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 3
            (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x3000,
            
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
            
            // 48 bits for "node"
            substr($hash, 20, 12)
        );
    }
    
    /**
     *
     * Generate v4 UUID
     *
     * Version 4 UUIDs are pseudo-random.
     */
    public static function v4(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            
            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Generate v5 UUID
     *
     * Version 5 UUIDs are named based. They require a namespace (another
     * valid UUID) and a value (the name). Given the same namespace and
     * name, the output is always the same.
     *
     * @param string $namespace
     * @param string $name
     * @return false|string
     */
    public static function v5(string $namespace, string $name)
    {
        if(!self::is_valid($namespace)) return false;
        
        // Get hexadecimal components of namespace
        $nhex = str_replace(array('-','{','}'), '', $namespace);
        
        // Binary Value
        $nstr = '';
        
        // Convert Namespace UUID to bits
        for($i = 0; $i < strlen($nhex); $i+=2) {
            $nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
        }
        
        // Calculate hash value
        $hash = sha1($nstr . $name);
        
        $uuid = sprintf('%08s-%04s-%04x-%04x-%12s',
            
            // 32 bits for "time_low"
            substr($hash, 0, 8),
            
            // 16 bits for "time_mid"
            substr($hash, 8, 4),
            
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 5
            (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,
            
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
            
            // 48 bits for "node"
            substr($hash, 20, 12)
        );
        return self::toBinary($uuid);
    }

    /**
     * @param string $uuid
     * @return bool
     */
    public static function is_valid(string $uuid): bool
    {
        return preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $uuid) === 1;
    }

    /**
     * @param $uuid
     * @return false|string
     */
    public static function toBinary($uuid)
    {
        return pack('H*', str_replace('-', '', $uuid));
    }

    /**
     * @param $uuid
     * @return array|string|string[]|null
     */
    public static function toString($uuid)
    {
        $string = unpack('H*', $uuid)[1];
        return preg_replace('/([0-9a-f]{8})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{12})/', '$1-$2-$3-$4-$5', $string);
    }

    /**
     * @param $str
     * @return bool
     */
    public static function isBinary($str): bool
    {
        return preg_match('~[^\x20-\x7E\t\r\n]~', $str) > 0;
    }

    /**
     * @param $bin
     * @param int $len
     * @return bool
     */
    public static function emptyBin( $bin, int $len = 16 ): bool
    {
        return preg_match('/[\x00]{'.$len.'}/', $bin) > 0;
    }
}