<?php

namespace App\Console\Commands;

use App\Http\Services\VDWebApiService;
use App\Models\Brand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAllStores extends Command
{
    protected $signature = 'sync:vdStores';

    protected $description = 'Sync store data for all Value Design brands';

    public function handle(VDWebApiService $vdWeb)
    {
        try {
            $this->info('🔄 Fetching Value Design token...');
            $token = $vdWeb->getToken();

            if (! $token) {
                $this->error('❌ Failed to fetch token');
                Log::error('SyncAllStores: Failed to get token');

                return Command::FAILURE;
            }

            $this->info('✅ Token retrieved successfully');

            $brands = Brand::whereNotNull('brand_code')->pluck('brand_code');

            if ($brands->isEmpty()) {
                $this->warn('⚠️  No brands found in database. Please fetch brands first.');

                return Command::FAILURE;
            }

            $this->info("🔄 Syncing stores for {$brands->count()} brands...");
            $successCount = 0;
            $failCount = 0;

            foreach ($brands as $brandCode) {
                try {
                    $result = $vdWeb->syncStoresToDatabase($token, $brandCode);
                    if ($result) {
                        $this->info("✅ Synced stores for brand: {$brandCode}");
                        $successCount++;
                    } else {
                        $this->warn("⚠️  Failed to sync stores for brand: {$brandCode}");
                        $failCount++;
                    }
                } catch (\Exception $e) {
                    $this->error("❌ Error syncing brand {$brandCode}: ".$e->getMessage());
                    Log::error('SyncAllStores: Error syncing brand', [
                        'brand_code' => $brandCode,
                        'error' => $e->getMessage(),
                    ]);
                    $failCount++;
                }
            }

            $this->info("✅ Store sync completed. Success: {$successCount}, Failed: {$failCount}");
            Log::info('SyncAllStores: Completed', [
                'total_brands' => $brands->count(),
                'success_count' => $successCount,
                'fail_count' => $failCount,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Exception occurred: '.$e->getMessage());
            Log::error('SyncAllStores: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
