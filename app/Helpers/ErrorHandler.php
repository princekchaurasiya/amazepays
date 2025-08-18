<?php

namespace App\Helpers;

use Exception;

class ErrorHandler
{
    public static function handleOrderError(Exception $exception)
    {
        $message = $exception->getMessage();

        // Check for known errors
        if (str_contains($message, 'Address in mailbox given [] does not comply with RFC 2822, 3.6.2.') || 
            str_contains($message, 'Error code : 5313') || 
            str_contains($message, 'Order failed: Duplicate reference number provided') || 
            str_contains($message, 'Error code: 400')) {
            
            return response()->json([
                'message' => 'Please place a fresh new order',
            ], 400);
        }

        // Otherwise rethrow the exception
        throw $exception;
    }
}
