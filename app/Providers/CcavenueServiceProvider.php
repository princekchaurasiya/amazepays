<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CcavenueServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes(
            [
                __DIR__ . '/path/to/ccavenue/config.php' => config_path('payment.php'),
            ],
            'config',
        );
    }
}
