<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Actions\SendToCardPay;
use TCG\Voyager\Facades\Voyager;

class VoyagerServiceProvider extends ServiceProvider
{
    public function boot()
    {
    //parent::boot();

    Voyager::addAction(SendToCardPay::class);
    }
}