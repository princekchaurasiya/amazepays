<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Catalog\CatalogSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncVouchagramCatalog extends Command
{
    protected $signature = 'vouchagram:sync-catalog';

    protected $description = 'Sync Vouchagram brands into products (source_provider = vouchagram)';

    public function handle(CatalogSyncService $catalog): int
    {
        $this->info('Syncing Vouchagram catalog...');

        try {
            $stats = $catalog->syncProvider('vouchagram');
            $this->info('Created: '.$stats['created'].', Updated: '.$stats['updated'].', Deactivated: '.$stats['deactivated']);

            Product::query()
                ->where('source_provider', 'vouchagram')
                ->where(function ($q) {
                    $q->whereNull('url')->orWhere('url', '');
                })
                ->each(function (Product $p) {
                    $base = $p->product_name ?? $p->name ?? $p->sku;
                    $slug = Str::slug((string) $base.'-'.$p->sku);
                    if ($slug === '') {
                        $slug = 'vg-'.$p->id;
                    }
                    $p->update(['url' => $slug, 'slug' => $slug]);
                });

            $this->info('Vouchagram catalog sync complete.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
