<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\QsProduct;
use App\Http\Services\WalletService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(WalletService::class, function () {
        return new WalletService();
        });

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        QsProduct::saving(function (QsProduct $product) {
            $sku = strtoupper($product->sku ?? '');
            $isSpecial = ($product->is_special_sku ?? false) || $sku === 'EGCGBRELSS001';
            if (!$isSpecial) {
                return;
            }

            $discountPercentage = $product->discount_percentage;
            $hasDiscount = is_numeric($discountPercentage)
                ? floatval($discountPercentage) > 0
                : !empty($discountPercentage);

            $product->sku_limits = $hasDiscount ? 50000 : 100000;
        });
    }
}
