<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Homepage\Actions\GetHomepageDocumentAction;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class HomepageController extends Controller
{
    public function __construct(
        private GetHomepageDocumentAction $getHomepageDocument,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $platform = (string) ($request->query('platform') ?: 'mobile');
        $surface = (string) ($request->query('surface') ?: 'storefront_home');

        $document = $this->getHomepageDocument->execute($tenantId, $platform, $surface);

        $version = (int) ($document['versioning']['version'] ?? 1);

        return response()
            ->json([
                'success' => true,
                'message' => __('Home data retrieved.'),
                'data' => $document,
            ])
            ->header('X-Homepage-Version', (string) $version);
    }

    private function resolveTenantId(Request $request): int
    {
        $tenant = $request->attributes->get('tenant');
        if ($tenant && method_exists($tenant, 'getKey')) {
            return (int) $tenant->getKey();
        }

        $id = app()->bound('current_tenant_id') ? app('current_tenant_id') : null;
        if (is_int($id) && $id > 0) {
            return $id;
        }

        if (! Schema::hasTable('tenants')) {
            return 1;
        }

        $fallback = Tenant::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->value('id');

        return (int) ($fallback ?? 1);
    }
}

