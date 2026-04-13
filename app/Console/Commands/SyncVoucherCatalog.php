<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Voucher\VoucherProviderFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncVoucherCatalog extends Command
{
    protected $signature = 'voucher:sync-catalog
                                {--provider= : Sync only a specific provider (woohoo|kgen|ezpin|gyftrr)}
                                {--dry-run   : Show what would be synced without saving}';

    protected $description = 'Sync product catalog from all voucher providers';

    public function handle(): int
    {
        $provider = $this->option('provider');
        $dryRun = $this->option('dry-run');
        $providers = $provider ? [$provider] : ['woohoo', 'kgen'];

        $this->info('Starting catalog sync for: '.implode(', ', $providers));

        $totalNew = 0;
        $totalUpdated = 0;
        $totalFailed = 0;

        foreach ($providers as $providerName) {
            $this->info("Syncing {$providerName}...");

            try {
                $driver = VoucherProviderFactory::make($providerName);
                $products = $driver->fetchCatalog();

                if (empty($products)) {
                    $this->warn("  {$providerName}: No products returned");

                    continue;
                }

                $this->info("  {$providerName}: Found ".count($products).' products');

                foreach ($products as $productData) {
                    try {
                        if ($dryRun) {
                            $this->line("  [DRY-RUN] Would upsert: {$productData['name']} ({$productData['sku']})");

                            continue;
                        }

                        $result = Product::updateOrCreate(
                            ['sku' => $productData['sku']],
                            [
                                'product_name' => $productData['name'],
                                'denomination' => $productData['denomination'] ?? 0,
                                'source_provider' => $providerName,
                                'last_synced_at' => now(),
                                'sync_status' => 'synced',
                                'raw_data' => json_encode($productData['raw'] ?? []),
                            ]
                        );

                        if ($result->wasRecentlyCreated) {
                            $totalNew++;
                        } else {
                            $totalUpdated++;
                        }
                    } catch (\Exception $e) {
                        $totalFailed++;
                        Log::warning('Catalog sync product upsert failed', [
                            'provider' => $providerName,
                            'sku' => $productData['sku'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $this->info("  {$providerName}: Done.");
            } catch (\Exception $e) {
                $this->error("  {$providerName}: FAILED — {$e->getMessage()}");
                Log::error("Catalog sync failed for {$providerName}", ['error' => $e->getMessage()]);
            }
        }

        if (! $dryRun) {
            $this->table(
                ['Metric', 'Count'],
                [
                    ['New products', $totalNew],
                    ['Updated products', $totalUpdated],
                    ['Failed', $totalFailed],
                ]
            );
        }

        $this->info('Catalog sync complete.');

        return self::SUCCESS;
    }
}
