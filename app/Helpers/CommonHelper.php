<?php

namespace App\Helpers;
use App\Models\QsProduct;
use TCG\Voyager\Facades\Voyager;


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
    // If $data is a string, assume it's already content
    if (is_string($data)) {
        return self::formatContent($data);
    }

    // If $data is an array, check if 'content' key exists and is not empty
    if (is_array($data) && isset($data['content']) && !empty($data['content'])) {
        return self::formatContent($data['content']);
    }

    // If no valid content is found, return a default message
    return 'No terms & conditions available.';
}



    // Helper to process "How to Redeem" with proper formatting
    public static function extractHowToRedeem($data)
    {
        // If $data is a string, assume it's already content
        if (is_string($data)) {
            return self::formatContent($data);
        }

        // If $data is an array, check if 'content' key exists and is not empty
        if (is_array($data) && isset($data['content']) && !empty($data['content'])) {
            return self::formatContent($data['content']);
        }

        // If no valid content is found, return default instructions
        return '<ul>
            <li><div style="margin-bottom: 8px; font-size: 14px;">How to redeem instructions are not available.</div></li>
        </ul>';
    }






    // Helper to process "Terms & Conditions" with proper formatting
    public static function extractDescription($data)
{
    // If $data is a string, assume it's already content
    if (is_string($data)) {
        return self::formatContent($data);
    }

    // If $data is an array, check if 'content' key exists and is not empty
    if (is_array($data) && isset($data['content']) && !empty($data['content'])) {
        return self::formatContent($data['content']);
    }

    // If no valid content is found, return a default message
    return '<p>No description available.</p>';
}


    public static function getFormattedInvoiceTermsAndConditions()
    {
        // Fetch the content for terms and conditions
        $content = setting('site.invoice_t&c'); // or another method to fetch content

        // Ensure that content is not empty
        if (empty($content)) {
            return 'No terms & conditions available.';
        }

        // Detect if content already contains <ul> or <li> tags
        if (stripos($content, '<ul>') !== false || stripos($content, '<li>') !== false) {
            // If it already contains valid list HTML, just return it as-is
            return $content;
        }

        // Split the content by lines
        $lines = preg_split('/\r\n|\r|\n/', $content);

        // Filter out empty or whitespace-only lines
        $lines = array_filter($lines, function ($line) {
            return !empty(trim($line));
        });

        // Wrap each line with <li> tags
        $formattedLines = array_map(function ($line) {
            return '<li>' . trim($line) . '</li>';
        }, $lines);

        // Wrap all lines inside a <ul>
        $formattedContent = '<ul class="no-bullets">' . implode('', $formattedLines) . '</ul>';

        return $formattedContent;
    }


    /**
     * Handles different types of content formatting including raw text, HTML, and structured arrays.
     */
    public static function formatContent($data)
    {
        if (is_string($data)) {
            return self::cleanHtmlContent($data);
        } elseif (is_array($data) && isset($data['content'])) {
            return self::cleanHtmlContent($data['content']);
        }

        return 'No terms & conditions available.';
    }

    /**
     * Processes HTML content, converting bullet points to lists if necessary.
     */
    private static function cleanHtmlContent($content)
    {
        // Check if content is plain text with bullet points and convert to list
        if (strpos($content, '•') !== false) {
            $lines = preg_split('/•\s*/', $content, -1, PREG_SPLIT_NO_EMPTY);
            $formattedLines = array_map(fn($line) => '<li>' . trim($line) . '</li>', $lines);
            return '<ul>' . implode('', $formattedLines) . '</ul>';
        }

        // Ensure valid HTML output
        $content = trim($content);
        if (!preg_match('/<[^>]+>/', $content)) {
            return '<p>' . htmlspecialchars($content) . '</p>';
        }

        return $content;
    }

    public static function getProductImage($product)
    {
        // Log the input for debugging
        \Log::info('GetProductImage Input:', [
            'product_type' => is_object($product) ? 'object' : (is_array($product) ? 'array' : 'unknown'),
            'product_data' => $product
        ]);

        // Case 1: If $product is an array
        if (is_array($product)) {
            // Check for custom_image first
            if (!empty($product['custom_image']) && $product['custom_image'] !== 'null' && $product['custom_image'] !== 'undefined') {
                \Log::info('Using custom_image from array:', ['image' => $product['custom_image']]);
                return asset('storage/' . $product['custom_image']);
            }
            
            // If no custom_image, try images
            if (!empty($product['images'])) {
                $images = is_string($product['images']) ? json_decode($product['images'], true) : $product['images'];
                \Log::info('Decoded images from array:', ['images' => $images]);
                
                if (is_array($images) && !empty($images['small']) && $images['small'] !== 'null' && $images['small'] !== 'undefined') {
                    return $images['small'];
                } elseif (is_object($images) && !empty($images->small) && $images->small !== 'null' && $images->small !== 'undefined') {
                    return $images->small;
                }
            }
        }
        // Case 2: If $product is an object
        elseif (is_object($product)) {
            // Check for custom_image first
            if (!empty($product->custom_image) && $product->custom_image !== 'null' && $product->custom_image !== 'undefined') {
                \Log::info('Using custom_image from object:', ['image' => $product->custom_image]);
                return asset('storage/' . $product->custom_image);
            }
            
            // If no custom_image, try images
            if (!empty($product->images)) {
                $images = is_string($product->images) ? json_decode($product->images, true) : $product->images;
                \Log::info('Decoded images from object:', ['images' => $images]);
                
                if (is_array($images) && !empty($images['small']) && $images['small'] !== 'null' && $images['small'] !== 'undefined') {
                    return $images['small'];
                } elseif (is_object($images) && !empty($images->small) && $images->small !== 'null' && $images->small !== 'undefined') {
                    return $images->small;
                }
            }
        }

        \Log::info('No valid image found, returning null');
        return null;
    }



}


