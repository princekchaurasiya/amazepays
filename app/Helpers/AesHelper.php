<?php

namespace App\Helpers;

use Exception;

class AesHelper
{
    private static string $cipher = 'AES-256-CBC';
    private static string $key = 'f7909baf63ca4e9e8c4b0d5b45313e97';
    private static string $iv = 'c14e3ff4b6eb4e76';

    public static function encryptPayload(array $payload, string $key, string $iv): string
    {
        $jsonData = json_encode($payload);
        $encrypted = openssl_encrypt($jsonData, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($encrypted);
    }
    
    public static function decryptPayload($encryptedPayload, $key, $iv)
    {
        try {
            $cipherText = base64_decode($encryptedPayload);
            $decrypted = openssl_decrypt($cipherText, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);

            return $decrypted ?: null;
        } catch (Exception $e) {
            \Log::error('AES Decryption failed: ' . $e->getMessage());
            return null;
        }
    }

    public static function encrypt(string $data): string
    {
        $iv = 'c14e3ff4b6eb4e76';  // 16 bytes IV
        $encrypted = openssl_encrypt($data, self::$cipher, self::$key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($encrypted); // Prepend IV for use in decryption
    }

        public static function decrypt(string $encryptedData): string
    {
        $data = base64_decode($encryptedData);
        //$iv = substr($data, 0, 16); // Extract IV
        $iv = 'c14e3ff4b6eb4e76'; 
        $cipherText = substr($data, 16);
        return openssl_decrypt($cipherText, self::$cipher, self::$key, OPENSSL_RAW_DATA, $iv);
    }
}
