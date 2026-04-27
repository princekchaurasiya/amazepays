<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Product;
use App\Services\Providers\WoohooBearerTokenStore;
use App\Services\Voucher\VouchagramService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
        $this->hydrateWoohooBearerTokenFromSettings();

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

    private function hydrateWoohooBearerTokenFromSettings(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            // Single source of truth: settings table (encrypted with WOOHOO_TOKEN_ENCRYPTION_KEY).
            $token = app(WoohooBearerTokenStore::class)->get();
            if (is_string($token) && $token !== '') {
                config(['woohoo.bearer_token' => $token]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to hydrate woohoo bearer token from settings', ['error' => $e->getMessage()]);
        }
    }
}
