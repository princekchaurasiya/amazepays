<?php

namespace App\Jobs\Woohoo;

use App\Models\Product;
use App\Models\ProviderSyncRun;
use App\Services\Catalog\WoohooCatalogService;
use App\Services\Catalog\WoohooNormalizedProductPersister;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SyncWoohooAllProductDetailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $syncRunId,
        public readonly int $tenantId,
        public readonly int $chunkSize = 100,
    ) {}

    public function handle(WoohooCatalogService $woohoo, WoohooNormalizedProductPersister $persister): void
    {
        /** @var ProviderSyncRun|null $run */
        $run = ProviderSyncRun::query()->find($this->syncRunId);
        if (! $run) {
            return;
        }

        $query = Product::query()
            ->where('tenant_id', $this->tenantId)
            ->where('source_provider', 'woohoo')
            ->orderBy('id');

        $total = (int) (clone $query)->count();

        $run->update([
            'status' => 'running',
            'started_at' => now(),
            'last_error_message' => null,
            'records_fetched' => $total,
            'records_created' => 0,
            'records_updated' => 0,
            'records_skipped' => 0,
            'records_failed' => 0,
        ]);

        $updated = 0;
        $failed = 0;
        $skipped = 0;

        try {
            $query->chunkById($this->chunkSize, function ($products) use ($woohoo, $persister, &$updated, &$failed, &$skipped, $run) {
                foreach ($products as $product) {
                    $sku = trim((string) $product->sku);
                    if ($sku === '') {
                        $skipped++;
                        continue;
                    }

                    try {
                        $payload = $woohoo->fetchProductDetailsBySku($sku);
                        $persister->persist(productId: (int) $product->id, sku: $sku, tenantId: $this->tenantId, payload: $payload);
                        $updated++;
                    } catch (\Throwable $e) {
                        $failed++;
                        $run->update([
                            'last_error_message' => substr("SKU {$sku}: ".$e->getMessage(), 0, 1000),
                        ]);
                    }

                    // Keep the dashboard feeling alive without hammering DB.
                    if ((($updated + $failed + $skipped) % 25) === 0) {
                        $run->update([
                            'records_updated' => $updated,
                            'records_failed' => $failed,
                            'records_skipped' => $skipped,
                        ]);
                    }
                }
            });

            $run->update([
                'status' => $failed > 0 ? 'failed' : 'succeeded',
                'records_updated' => $updated,
                'records_failed' => $failed,
                'records_skipped' => $skipped,
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'failed',
                'records_updated' => $updated,
                'records_failed' => $failed + 1,
                'records_skipped' => $skipped,
                'last_error_message' => substr($e->getMessage(), 0, 1000),
                'completed_at' => now(),
            ]);
        }
    }
}

