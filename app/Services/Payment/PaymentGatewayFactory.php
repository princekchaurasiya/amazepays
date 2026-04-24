<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Tenant;
use InvalidArgumentException;

class PaymentGatewayFactory
{
    private static array $drivers = [
        'ccavenue' => CCAvenuGateway::class,
        'unlimit' => UnlimitGateway::class,
        'razorpay' => RazorpayGateway::class,
        'mock_razorpay' => MockRazorpayGateway::class,
    ];

    public static function make(string $gateway, array $credentials = []): PaymentGatewayInterface
    {
        if (! isset(static::$drivers[$gateway])) {
            throw new InvalidArgumentException("Unknown payment gateway: {$gateway}");
        }

        $class = static::$drivers[$gateway];

        return new $class($credentials);
    }

    public static function makeFromTenant(Tenant $tenant, string $gateway): PaymentGatewayInterface
    {
        $gatewayRecord = $tenant->tenantPaymentGateways()
            ->where('gateway', $gateway)
            ->where('is_active', true)
            ->first();

        $credentials = $gatewayRecord
            ? json_decode(decrypt($gatewayRecord->credentials), true)
            : [];

        return static::make($gateway, $credentials);
    }

    public static function makeDefault(): PaymentGatewayInterface
    {
        $default = config('payment.default_gateway', 'ccavenue');

        return static::make($default);
    }

    public static function available(): array
    {
        return array_keys(static::$drivers);
    }
}
