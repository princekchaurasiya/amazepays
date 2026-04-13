<?php

namespace App\Services\Voucher;

use App\Contracts\VoucherProviderInterface;
use App\Models\Product;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;

class VoucherProviderFactory
{
    private static array $drivers = [
        'woohoo' => WoohooProvider::class,
        'kgen' => KGenProvider::class,
        'value_design' => ValueDesignProvider::class,
        'lysto' => LystoProvider::class,
        'ezpin' => EZPinProvider::class,
        'gyftrr' => GyftrProvider::class,
        'vouchagram' => VouchagramSendProvider::class,
        'vouchagram_send' => VouchagramSendProvider::class,
        'vouchagram_pull' => VouchagramPullProvider::class,
    ];

    public static function make(string $provider, array $credentials = []): VoucherProviderInterface
    {
        if (! isset(static::$drivers[$provider])) {
            throw new InvalidArgumentException("Unknown voucher provider: {$provider}");
        }

        $class = static::$drivers[$provider];

        if ($provider === 'vouchagram' || $provider === 'vouchagram_send') {
            return new VouchagramSendProvider(App::make(VouchagramService::class));
        }
        if ($provider === 'vouchagram_pull') {
            return new VouchagramPullProvider(App::make(VouchagramService::class));
        }

        return new $class($credentials);
    }

    public static function available(): array
    {
        return array_keys(static::$drivers);
    }

    public static function forProduct(Product $product): VoucherProviderInterface
    {
        $provider = $product->source_provider ?? 'woohoo';

        return static::make($provider);
    }
}
