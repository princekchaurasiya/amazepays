<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProviderConnection;
use App\Models\ProviderSyncRun;
use App\Models\SyncedCategory;
use App\Services\Providers\WoohooBearerTokenStore;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WoohooAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('providers.view');

        $tokenStore = app(WoohooBearerTokenStore::class);
        $settingsTokenPresent = $tokenStore->get() !== null;
        $settingsTokenUpdatedAt = $tokenStore->updatedAt();

        $tenantId = 1;
        $host = (string) config('woohoo.host');
        $environment = str_contains($host, 'sandbox') ? 'sandbox' : 'production';
        $connection = ProviderConnection::query()
            ->where('tenant_id', $tenantId)
            ->where('provider', 'woohoo')
            ->where('environment', $environment)
            ->first();

        $syncRuns = [];
        $pendingQueue = 0;
        $failedJobs = 0;
        $lastSyncAt = null;
        if ($connection) {
            $pendingQueue = (int) ProviderSyncRun::query()
                ->where('connection_id', $connection->id)
                ->whereIn('status', ['queued', 'running'])
                ->count();
            $failedJobs = (int) ProviderSyncRun::query()
                ->where('connection_id', $connection->id)
                ->where('status', 'failed')
                ->count();
            $lastSyncAt = ProviderSyncRun::query()
                ->where('connection_id', $connection->id)
                ->whereNotNull('completed_at')
                ->orderByDesc('completed_at')
                ->value('completed_at');

            $syncRuns = ProviderSyncRun::query()
                ->where('connection_id', $connection->id)
                ->orderByDesc('id')
                ->limit(10)
                ->get([
                    'id',
                    'job_type',
                    'status',
                    'records_fetched',
                    'records_created',
                    'records_updated',
                    'records_failed',
                    'last_error_message',
                    'started_at',
                    'completed_at',
                    'created_at',
                ])
                ->map(fn (ProviderSyncRun $r) => [
                    'id' => $r->id,
                    'job_type' => (string) $r->job_type,
                    'status' => (string) $r->status,
                    'records_fetched' => (int) $r->records_fetched,
                    'records_created' => (int) $r->records_created,
                    'records_updated' => (int) $r->records_updated,
                    'records_failed' => (int) $r->records_failed,
                    'last_error_message' => $r->last_error_message ? substr((string) $r->last_error_message, 0, 200) : null,
                    'started_at' => optional($r->started_at)->toIso8601String(),
                    'completed_at' => optional($r->completed_at)->toIso8601String(),
                    'created_at' => optional($r->created_at)->toIso8601String(),
                ])
                ->values()
                ->all();
        }

        $categoriesSynced = (int) SyncedCategory::query()->where('provider', 'woohoo')->count();
        $productsImported = (int) Product::query()->where('source_provider', 'woohoo')->count();

        $woohooCategories = SyncedCategory::query()
            ->where('provider', 'woohoo')
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'external_id']);

        return Inertia::render('Admin/Woohoo/Index', [
            'host' => config('woohoo.host'),
            'configured' => $this->isConfigured(),
            'settingsTokenPresent' => $settingsTokenPresent,
            'settingsTokenUpdatedAt' => $settingsTokenUpdatedAt,
            'syncedCategories' => $woohooCategories,
            'kpis' => [
                'categoriesSynced' => $categoriesSynced,
                'productsImported' => $productsImported,
                'pendingQueue' => $pendingQueue,
                'failedJobs' => $failedJobs,
            ],
            'lastSyncAt' => $lastSyncAt ? optional($lastSyncAt)->toIso8601String() : null,
            'syncRuns' => $syncRuns,
        ]);
    }

    private function isConfigured(): bool
    {
        return (bool) config('woohoo.host')
            && (bool) config('woohoo.client_secret')
            && app(WoohooBearerTokenStore::class)->get() !== null;
    }
}
