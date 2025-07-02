<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BrandDetail;
use App\Services\VDWebApiService;

class SyncAllStores extends Command
{
 
    protected $signature = 'sync:stores';
    protected $description = 'Sync store data for all brands daily';

    public function handle(VDWebApiService $vdWeb)
    {
        $token = $vdWeb->getToken();

        if (!$token) {
            $this->error('Failed to fetch token');
            return;
        }

        $brands = BrandDetail::pluck('brand_code');

        foreach ($brands as $brandCode) {
            $vdWeb->syncStoresToDatabase($token, $brandCode);
            $this->info("Synced stores for brand: $brandCode");
        }

        $this->info('All brand stores synced successfully');
    }
}
