<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'response_ccavenue',
        'payment-cancel',
        '/payment/notify',
        '/payment/return',
        '/unlimit/webhook',
        '/upi/return',
        '/upi/webhook',
    ];
}
