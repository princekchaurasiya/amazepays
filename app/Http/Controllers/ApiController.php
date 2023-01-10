<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function signature_validation (Request $request){
        $responseBody = '';

        
        $requestHttpMethod = 'get';

        
        //$absApiUrl = 'https://sandbox.woohoo.in/rest/v3/catalog/categories';
        $absApiUrl = 'https://sandbox.woohoo.in/rest/v3/catalog/categories/121/products';

        
        $clientSecret = 'fb3e12b2e1f526b68ff41b79152be092';

        
        function sortParams(array &$params)
        {
            ksort($params);
            foreach ($params as $key => &$value) {
                $value = is_object($value) ? (array) $value : $value;
                if (is_array($value)) {
                    sortParams($value);
                }
            }
        }

        function sortQueryParams($queryParam)
        {
            $query = explode('&', $queryParam);
            asort($query, SORT_STRING);
            return implode('&', $query);
        }

       
        function getConcatenateBaseString($absApiUrl, $requestHttpMethod, $responseBody)
        {
            $baseStrings = [];

            $baseStrings[] = strtoupper($requestHttpMethod);
            $url = explode('?', $absApiUrl);
            $apiUrl = $url[0];

            if (isset($url[1])) {
                $baseStrings[] = rawurlencode($apiUrl . '?' . sortQueryParams($url[1]));
            }
            else {
                $baseStrings[] = rawurlencode($apiUrl);
            }

            if ($responseBody) {
                $jsonDecodedResponseBody = json_decode($responseBody, TRUE);
                sortParams($jsonDecodedResponseBody);
                $baseStrings[] = rawurlencode(
                    json_encode($jsonDecodedResponseBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                );
            }

            return implode('&', $baseStrings);
        }

        echo hash_hmac('sha512', getConcatenateBaseString($absApiUrl, $requestHttpMethod, $responseBody), $clientSecret);
    }
}
