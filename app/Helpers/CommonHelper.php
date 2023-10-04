<?php

namespace App\Helpers;

class CommonHelper
{
    public static function generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret)
    {
        $requestBody = $requestBody;
        $requestHttpMethod = $requestHttpMethod;
        $absApiUrl = $absApiUrl;
        $clientSecret = $clientSecret;

        //echo hash_hmac('sha512', getConcatenateBaseString($absApiUrl, $requestHttpMethod, $requestBody), $clientSecret);
        // return hash_hmac('sha512', getConcatenateBaseString($absApiUrl, $requestHttpMethod, $requestBody), $clientSecret);

        return hash_hmac('sha512', self::getConcatenateBaseString($absApiUrl, $requestHttpMethod, $requestBody), $clientSecret);
    }

    private static function getConcatenateBaseString($absApiUrl, $requestHttpMethod, $requestBody)
    {
        $baseStrings = [];
        $baseStrings[] = strtoupper($requestHttpMethod);
        $url = explode('?', $absApiUrl);
        $apiUrl = $url[0];
        if (isset($url[1])) {
            $baseStrings[] = rawurlencode($apiUrl . '?' . self::sortQueryParams($url[1]));
        } else {
            $baseStrings[] = rawurlencode($apiUrl);
        }

        if ($requestBody) {
            $jsonDecodedRequestBody = json_decode($requestBody, true);
            self::sortParams($jsonDecodedRequestBody);
            $baseStrings[] = rawurlencode(json_encode($jsonDecodedRequestBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        return implode('&', $baseStrings);
    }

    private static function sortParams(array &$params)
    {
        ksort($params);
        foreach ($params as $key => &$value) {
            $value = is_object($value) ? (array) $value : $value;
            if (is_array($value)) {
                self::sortParams($value);
            }
        }
    }

    private static function sortQueryParams($queryParam)
    {
        $query = explode('&', $queryParam);
        asort($query, SORT_STRING);
        return implode('&', $query);
    }

    // Define other helper functions as needed
}
