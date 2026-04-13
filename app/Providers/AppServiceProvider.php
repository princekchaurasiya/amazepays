<?php

namespace App\Providers;

use App\Http\Services\WalletService;
use App\Models\Category;
use App\Models\Product;
use App\Models\StorefrontBrand;
use App\Services\Voucher\VouchagramService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
            return new WalletService;
        });

        $this->app->singleton(VouchagramService::class, function () {
            return new VouchagramService;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('layouts.app', function ($view) {
            $view->with([
                'storefrontCategories' => Category::query()->orderBy('order')->get(),
                'storefrontBrandsNav' => StorefrontBrand::query()->orderBy('order')->limit(24)->get(),
            ]);
        });

        Product::saving(function (Product $product) {
            $sku = strtoupper($product->sku ?? '');
            $isSpecial = ($product->is_special_sku ?? false) || $sku === 'EGCGBRELSS001';
            if (! $isSpecial) {
                return;
            }

            $discountPercentage = $product->discount_percentage;
            $hasDiscount = is_numeric($discountPercentage)
                ? floatval($discountPercentage) > 0
                : ! empty($discountPercentage);

            $product->sku_limits = $hasDiscount ? 50000 : 100000;
        });
    }
}
