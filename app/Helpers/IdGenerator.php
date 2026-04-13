<?php

namespace App\Helpers;

/**
 * URL-safe correlation IDs for payments and orders.
 */
final class IdGenerator
{
    public static function generateUniqueId(string $prefix = '', int $maxLength = 24): string
    {
        $random = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
        $timePart = dechex((int) (microtime(true) * 1000));
        $id = $prefix.$timePart.'_'.$random;
        if ($maxLength > 0 && strlen($id) > $maxLength) {
            $id = substr($id, 0, $maxLength);
        }

        return $id;
    }
}
