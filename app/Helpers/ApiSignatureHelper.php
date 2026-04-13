<?php

namespace App\Helpers;

/**
 * HMAC signature construction for Woohoo / provider API requests.
 */
final class ApiSignatureHelper
{
    public static function generateSignature(string $requestBody, string $requestHttpMethod, string $absApiUrl, string $clientSecret): string
    {
        return hash_hmac('sha512', self::getConcatenateBaseString($absApiUrl, $requestHttpMethod, $requestBody), $clientSecret);
    }

    private static function getConcatenateBaseString(string $absApiUrl, string $requestHttpMethod, string $requestBody): string
    {
        $baseStrings = [];
        $baseStrings[] = strtoupper($requestHttpMethod);
        $url = explode('?', $absApiUrl);
        $apiUrl = $url[0];
        if (isset($url[1])) {
            $baseStrings[] = rawurlencode($apiUrl.'?'.self::sortQueryParams($url[1]));
        } else {
            $baseStrings[] = rawurlencode($apiUrl);
        }

        if ($requestBody !== '' && $requestBody !== '0') {
            $jsonDecodedRequestBody = json_decode($requestBody, true);
            if (! is_array($jsonDecodedRequestBody)) {
                $jsonDecodedRequestBody = [];
            }
            self::sortParams($jsonDecodedRequestBody);
            $baseStrings[] = rawurlencode(json_encode($jsonDecodedRequestBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return implode('&', $baseStrings);
    }

    private static function sortParams(array &$params): void
    {
        ksort($params);
        foreach ($params as &$value) {
            $value = is_object($value) ? (array) $value : $value;
            if (is_array($value)) {
                self::sortParams($value);
            }
        }
    }

    private static function sortQueryParams(string $queryParam): string
    {
        $query = explode('&', $queryParam);
        asort($query, SORT_STRING);

        return implode('&', $query);
    }
}
