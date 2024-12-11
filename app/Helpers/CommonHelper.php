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


    public static function extractTnc($data)
    {
        if (isset($data['content'])) {
            $content = $data['content'];

            // Convert only the first level bullet points into valid <li> tags wrapped in a single <ul>
            $content = preg_replace('/^.*?(\n?•\s.*?)(?=\n|$)/', '<li>$1</li>', $content);
            $content = preg_replace('/•\s*(.*?)\n/', '<li>$1</li>', $content);

            // Wrap only the first-level items with `<ul>` without creating multiple nesting layers.
            $content = "<ul>" . $content . "</ul>";

            return $content;
        }

        return 'No terms & conditions available.';
    }



    // Helper to process "How to Redeem" with proper formatting
    public static function extractHowToRedeem($data)
{
    if (!isset($data) || empty(trim($data))) {
        // Return default static instructions
        return '<div class="col-sm-12">
            <ul>
                <li><div class="">Visit the outlet near you.</div></li>
                <li><div class="">Before making the purchase confirm about the acceptance of Gift Card at the store.</div></li>
                <li><div class="">Choose the products you would like to buy.</div></li>
                <li><div class="">Show your Gift Card details to the cashier at the time of billing &amp; pay any balance amount by cash or card.</div></li>
            </ul>
        </div>';
    }

    // Split the data into individual instructions by newlines
    $lines = preg_split('/\r\n|\r|\n/', $data);

    // Wrap each line in <li><div></div></li>, ensuring proper structure
    $formattedLines = array_map(function ($line) {
        $trimmedLine = trim($line);
        if (!empty($trimmedLine)) {
            return '<li><div class="">' . e($trimmedLine) . '</div></li>';
        }
        return '';
    }, $lines);

    // Combine all lines into a single <ul> and wrap in <div>
    $formattedContent = '<div class="col-sm-12"><ul>' . implode('', $formattedLines) . '</ul></div>';

    return $formattedContent;
}




    // Helper to process "Terms & Conditions" with proper formatting
    public static function extractDescription($data)
    {

        return isset($data)
            ? nl2br(e($data))
            : 'No description available.';
    }




}


