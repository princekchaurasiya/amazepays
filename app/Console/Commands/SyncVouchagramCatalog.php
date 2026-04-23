<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Catalog\CatalogSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncVouchagramCatalog extends Command
{
    protected $signature = 'vouchagram:sync-catalog {--mode=both : send, pull, or both}';

    protected $description = 'Sync Vouchagram Send (B2C) and/or Pull (B2B) brands into products (vouchagram_send / vouchagram_pull)';

    public function handle(CatalogSyncService $catalog): int
    {
        $mode = strtolower((string) $this->option('mode'));
        if (! in_array($mode, ['send', 'pull', 'both'], true)) {
            $this->error('--mode must be send, pull, or both');

            return self::FAILURE;
        }

        try {
            $modes = $mode === 'both' ? ['send', 'pull'] : [$mode];

            foreach ($modes as $m) {
                $provider = $m === 'pull' ? 'vouchagram_pull' : 'vouchagram_send';
                $options = $m === 'pull' ? ['default_show_product' => false] : [];
                $this->info("Syncing {$provider}...");
                $stats = $catalog->syncProvider($provider, $options);
                $this->line('Created: '.$stats['created'].', Updated: '.$stats['updated'].', Deactivated: '.$stats['deactivated']);
            }

            Product::query()
                ->whereIn('source_provider', ['vouchagram', 'vouchagram_send', 'vouchagram_pull'])
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
