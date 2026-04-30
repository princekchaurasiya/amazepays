<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ResponseCode;
use App\Helpers\ApiSignatureHelper;
use App\Http\Controllers\Controller;
use App\Jobs\Woohoo\SyncWoohooAllProductDetailsJob;
use App\Jobs\Woohoo\SyncWoohooCategoryProductsJob;
use App\Jobs\Woohoo\SyncWoohooSkuJob;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProviderConnection;
use App\Models\ProviderSyncRun;
use App\Models\SyncedCategory;
use App\Models\Tenant;
use App\Services\Providers\WoohooBearerTokenStore;
use App\Support\Http\ResponsePayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class WoohooController extends Controller
{
    public function getToken(Request $request): ResponsePayload
    {
        $this->authorize('providers.view');

        $host = (string) config('woohoo.host');
        $clientId = (string) config('woohoo.client_id');
        $clientSecret = (string) config('woohoo.client_secret');
        $username = (string) config('woohoo.username');
        $password = (string) config('woohoo.password');

        if ($host === '' || $clientId === '' || $clientSecret === '' || $username === '' || $password === '') {
            return ResponsePayload::fail(
                code: ResponseCode::VALIDATION_FAILED,
                messageKey: 'providers.woohoo_missing_credentials',
                details: [
                    'required' => ['WOOHOO_URL', 'WOOHOO_CLIENT_ID', 'WOOHOO_CLIENT_SECRET', 'WOOHOO_USERNAME', 'WOOHOO_PASSWORD'],
                ],
                httpStatus: 422,
            );
        }

        $verifyUrl = "https://{$host}/oauth2/verify";
        $tokenUrl = "https://{$host}/oauth2/token";

        try {
            $verifyResp = Http::acceptJson()
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => '*/*',
                    'User-Agent' => 'Amazepays/1.0 (+https://amazepays.in)',
                ])
                ->send('POST', $verifyUrl, [
                    'body' => json_encode([
                        'clientId' => $clientId,
                        'username' => $username,
                        'password' => $password,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);

            if (! $verifyResp->successful()) {
                Log::error('Woohoo verify failed', ['status' => $verifyResp->status(), 'body' => $verifyResp->body()]);

                return ResponsePayload::fail(ResponseCode::UPSTREAM_FAILED, 'providers.woohoo_verify_failed', [
                    'status' => $verifyResp->status(),
                    'body' => substr($verifyResp->body(), 0, 500),
                ], $verifyResp->status());
            }

            $authorizationCode = (string) ($verifyResp->json()['authorizationCode'] ?? '');
            if ($authorizationCode === '') {
                return ResponsePayload::fail(ResponseCode::UPSTREAM_FAILED, 'providers.woohoo_verify_failed', [
                    'reason' => 'missing_authorizationCode',
                    'response' => $verifyResp->json(),
                ], 502);
            }

            $tokenResp = Http::acceptJson()
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => '*/*',
                    'User-Agent' => 'Amazepays/1.0 (+https://amazepays.in)',
                ])
                ->send('POST', $tokenUrl, [
                    'body' => json_encode([
                        'clientId' => $clientId,
                        'clientSecret' => $clientSecret,
                        'authorizationCode' => $authorizationCode,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);

            if (! $tokenResp->successful()) {
                Log::error('Woohoo token failed', ['status' => $tokenResp->status(), 'body' => $tokenResp->body()]);

                return ResponsePayload::fail(ResponseCode::UPSTREAM_FAILED, 'providers.woohoo_token_failed', [
                    'status' => $tokenResp->status(),
                    'body' => substr($tokenResp->body(), 0, 500),
                ], $tokenResp->status());
            }

            $token = (string) ($tokenResp->json()['token'] ?? '');
            if ($token === '') {
                return ResponsePayload::fail(ResponseCode::UPSTREAM_FAILED, 'providers.woohoo_token_failed', [
                    'reason' => 'missing_token',
                    'response' => $tokenResp->json(),
                ], 502);
            }

            // Persist token (encrypted-at-rest) using a dedicated env key.
            app(WoohooBearerTokenStore::class)->put($token);

            // Never return bearer token to the browser.
            return ResponsePayload::ok('providers.woohoo_token_ok', [
                'stored' => [
                    'settings' => true,
                    'encrypted' => true,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Woohoo getToken exception', ['error' => $e->getMessage()]);

            return ResponsePayload::fail(ResponseCode::INTERNAL_ERROR, 'providers.woohoo_token_failed', [
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function fetchCategories(Request $request): ResponsePayload
    {
        $this->authorize('providers.view');

        $host = (string) config('woohoo.host');
        $clientSecret = (string) config('woohoo.client_secret');
        $bearerToken = (string) (app(WoohooBearerTokenStore::class)->get() ?: '');

        if ($host === '' || $clientSecret === '' || $bearerToken === '') {
            return ResponsePayload::fail(ResponseCode::VALIDATION_FAILED, 'providers.woohoo_missing_credentials', [
                'required' => ['WOOHOO_URL', 'WOOHOO_CLIENT_SECRET', 'Woohoo bearer token stored in settings'],
            ], 422);
        }

        $absApiUrl = "https://{$host}/rest/v3/catalog/categories/";
        $signature = ApiSignatureHelper::generateSignature('', 'get', $absApiUrl, $clientSecret);
        $dateAtClient = now()->toIso8601String();

        try {
            $resp = Http::acceptJson()
                ->withToken($bearerToken)
                ->withHeaders([
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                    'Accept' => '*/*',
                    'User-Agent' => 'Amazepays/1.0 (+https://amazepays.in)',
                ])
                ->get($absApiUrl);

            if (! $resp->successful()) {
                return ResponsePayload::fail(ResponseCode::UPSTREAM_FAILED, 'providers.woohoo_categories_failed', [
                    'status' => $resp->status(),
                    'body' => substr($resp->body(), 0, 500),
                ], $resp->status());
            }

            $payload = $resp->json();
            if (! is_array($payload)) {
                return ResponsePayload::fail(ResponseCode::UPSTREAM_FAILED, 'providers.woohoo_categories_failed', [
                    'reason' => 'invalid_json',
                ], 502);
            }

            $tenantId = (int) (Tenant::query()->min('id') ?? 1);

            // Woohoo seems to return a single category object in this legacy implementation.
            $externalId = (string) ($payload['id'] ?? '');
            $name = (string) ($payload['name'] ?? '');

            if ($externalId === '' || $name === '') {
                return ResponsePayload::fail(ResponseCode::UPSTREAM_FAILED, 'providers.woohoo_categories_failed', [
                    'reason' => 'missing_id_or_name',
                    'response' => $payload,
                ], 502);
            }

            $row = SyncedCategory::query()->updateOrCreate(
                ['provider' => 'woohoo', 'external_id' => $externalId],
                [
                    'tenant_id' => $tenantId,
                    'external_parent_id' => (string) ($payload['parentId'] ?? ($payload['external_parent_id'] ?? '')) ?: null,
                    'name' => $name,
                    'raw_payload' => $payload,
                    'synced_at' => now(),
                ]
            );

            return ResponsePayload::ok('providers.woohoo_categories_ok', [
                'upserted' => 1,
                'category' => [
                    'id' => $row->id,
                    'provider' => $row->provider,
                    'external_id' => $row->external_id,
                    'name' => $row->name,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Woohoo fetchCategories exception', ['error' => $e->getMessage()]);

            return ResponsePayload::fail(ResponseCode::INTERNAL_ERROR, 'providers.woohoo_categories_failed', [
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function fetchProducts(Request $request): ResponsePayload
    {
        $this->authorize('providers.view');
        $validated = $request->validate([
            'synced_category_id' => ['required', 'integer', 'min:1'],
        ]);

        $cat = SyncedCategory::query()->find((int) $validated['synced_category_id']);
        if (! $cat) {
            return ResponsePayload::fail(ResponseCode::NOT_FOUND, 'providers.woohoo_category_not_found', httpStatus: 404);
        }

        $host = (string) config('woohoo.host');
        $clientSecret = (string) config('woohoo.client_secret');
        $bearerToken = (string) (app(WoohooBearerTokenStore::class)->get() ?: '');

        if ($host === '' || $clientSecret === '' || $bearerToken === '') {
            return ResponsePayload::fail(ResponseCode::VALIDATION_FAILED, 'providers.woohoo_missing_credentials', [
                'required' => ['WOOHOO_URL', 'WOOHOO_CLIENT_SECRET', 'Woohoo bearer token stored in settings'],
            ], 422);
        }

        $externalId = (string) $cat->external_id;
        $absApiUrl = "https://{$host}/rest/v3/catalog/categories/{$externalId}/products";
        $signature = ApiSignatureHelper::generateSignature('', 'get', $absApiUrl, $clientSecret);
        $dateAtClient = now()->toIso8601String();

        try {
            $resp = Http::acceptJson()
                ->withToken($bearerToken)
                ->withHeaders([
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                    'Accept' => '*/*',
                    'User-Agent' => 'Amazepays/1.0 (+https://amazepays.in)',
                ])
                ->get($absApiUrl);

            if (! $resp->successful()) {
                return ResponsePayload::fail(ResponseCode::UPSTREAM_FAILED, 'providers.woohoo_products_failed', [
                    'status' => $resp->status(),
                    'body' => substr($resp->body(), 0, 500),
                ], $resp->status());
            }

            $json = $resp->json();
            $products = is_array($json) ? ($json['products'] ?? null) : null;
            if (! is_array($products)) {
                return ResponsePayload::fail(ResponseCode::UPSTREAM_FAILED, 'providers.woohoo_products_failed', [
                    'reason' => 'missing_products_array',
                    'response' => is_array($json) ? array_keys($json) : gettype($json),
                ], 502);
            }

            $tenantId = (int) (Tenant::query()->min('id') ?? 1);
            $inserted = 0;
            $updated = 0;
            $skipped = 0;

            foreach ($products as $item) {
                if (! is_array($item)) {
                    $skipped++;

                    continue;
                }
                $skuRaw = $item['sku'] ?? '';
                $nameRaw = $item['name'] ?? '';
                $sku = trim(is_scalar($skuRaw) ? (string) $skuRaw : '');
                $name = trim(is_scalar($nameRaw) ? (string) $nameRaw : '');
                $sourceIdRaw = $item['id'] ?? ($item['productId'] ?? '');
                $sourceProductId = is_scalar($sourceIdRaw) ? (string) $sourceIdRaw : '';

                if ($sku === '' || $name === '') {
                    $skipped++;

                    continue;
                }

                $brandRaw = $item['brandName'] ?? ($item['brand'] ?? 'Woohoo');
                $brandName = trim(is_scalar($brandRaw) ? (string) $brandRaw : 'Woohoo');
                if ($brandName === '') {
                    $brandName = 'Woohoo';
                }

                $brand = Brand::query()->firstOrCreate(
                    ['tenant_id' => $tenantId, 'slug' => Str::slug($brandName)],
                    [
                        'name' => $brandName,
                        'status' => 'active',
                        'is_featured' => false,
                        'display_order' => 0,
                        'source_provider' => 'woohoo',
                        'source_brand_id' => null,
                    ]
                );

                $slug = Str::slug($name.'-'.$sku);
                if ($slug === '') {
                    $slug = 'woohoo-'.$sku;
                }

                $exists = Product::query()->where('tenant_id', $tenantId)->where('sku', $sku)->exists();

                $currencyRaw = $item['currency'] ?? 'INR';
                if (is_array($currencyRaw)) {
                    $currencyRaw = $currencyRaw['code'] ?? ($currencyRaw['currency'] ?? 'INR');
                }
                $currency = trim(is_scalar($currencyRaw) ? (string) $currencyRaw : 'INR');
                if ($currency === '') {
                    $currency = 'INR';
                }

                Product::query()->updateOrCreate(
                    ['tenant_id' => $tenantId, 'sku' => $sku],
                    [
                        'brand_id' => $brand->id,
                        'name' => $name,
                        'slug' => $slug,
                        'source_provider' => 'woohoo',
                        'source_product_id' => $sourceProductId !== '' ? $sourceProductId : null,
                        'currency' => $currency,
                        'status' => 'active',
                        'type' => 'gift_card',
                        'delivery_mode' => 'digital',
                        'is_featured' => false,
                        'is_b2b_only' => false,
                        'is_b2c_only' => false,
                        'display_order' => 0,
                        'published_at' => now(),
                    ]
                );

                if ($exists) {
                    $updated++;
                } else {
                    $inserted++;
                }
            }

            return ResponsePayload::ok('providers.woohoo_products_ok', [
                'synced_category' => [
                    'id' => $cat->id,
                    'external_id' => $cat->external_id,
                    'name' => $cat->name,
                ],
                'stats' => [
                    'inserted' => $inserted,
                    'updated' => $updated,
                    'skipped' => $skipped,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Woohoo fetchProducts exception', ['error' => $e->getMessage()]);

            return ResponsePayload::fail(ResponseCode::INTERNAL_ERROR, 'providers.woohoo_products_failed', [
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function fetchProductDetails(Request $request): ResponsePayload
    {
        $this->authorize('providers.view');
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:128'],
        ]);

        $sku = trim((string) $validated['sku']);
        if ($sku === '') {
            return ResponsePayload::fail(ResponseCode::VALIDATION_FAILED, 'providers.woohoo_products_failed', [
                'sku' => 'required',
            ], 422);
        }

        $host = (string) config('woohoo.host');
        $clientSecret = (string) config('woohoo.client_secret');
        $bearerToken = (string) (app(WoohooBearerTokenStore::class)->get() ?: '');

        if ($host === '' || $clientSecret === '' || $bearerToken === '') {
            return ResponsePayload::fail(ResponseCode::VALIDATION_FAILED, 'providers.woohoo_missing_credentials', [
                'required' => ['WOOHOO_URL', 'WOOHOO_CLIENT_SECRET', 'Woohoo bearer token stored in settings'],
            ], 422);
        }

        $absApiUrl = "https://{$host}/rest/v3/catalog/products/{$sku}";
        $signature = ApiSignatureHelper::generateSignature('', 'get', $absApiUrl, $clientSecret);
        $dateAtClient = now()->toIso8601String();

        try {
            $resp = Http::acceptJson()
                ->withToken($bearerToken)
                ->withHeaders([
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                    'Accept' => '*/*',
                    'User-Agent' => 'Amazepays/1.0 (+https://amazepays.in)',
                ])
                ->get($absApiUrl);

            if (! $resp->successful()) {
                return ResponsePayload::fail(ResponseCode::UPSTREAM_FAILED, 'providers.woohoo_product_details_failed', [
                    'status' => $resp->status(),
                    'body' => substr($resp->body(), 0, 500),
                ], $resp->status());
            }

            $payload = $resp->json();
            if (! is_array($payload)) {
                return ResponsePayload::fail(ResponseCode::UPSTREAM_FAILED, 'providers.woohoo_product_details_failed', [
                    'reason' => 'invalid_json',
                ], 502);
            }

            // Industry approach: keep provider payload in product_source_sync (not in products table).
            $externalProductId = (string) ($payload['id'] ?? '');
            $product = Product::query()
                ->where('sku', $sku)
                ->where('source_provider', 'woohoo')
                ->first(['id']);

            if ($product && Schema::hasTable('product_source_sync') && $externalProductId !== '') {
                DB::table('product_source_sync')->updateOrInsert(
                    ['product_id' => $product->id],
                    [
                        'provider' => 'woohoo',
                        'external_product_id' => $externalProductId,
                        'external_sku' => $sku,
                        'sync_status' => 'healthy',
                        'last_sync_error' => null,
                        'last_sync_at' => now(),
                        'last_raw_payload' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            return ResponsePayload::ok('providers.woohoo_product_details_ok', [
                'sku' => $sku,
                'product' => $payload,
            ]);
        } catch (\Throwable $e) {
            Log::error('Woohoo fetchProductDetails exception', ['error' => $e->getMessage()]);

            return ResponsePayload::fail(ResponseCode::INTERNAL_ERROR, 'providers.woohoo_product_details_failed', [
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function testOrder(Request $request): ResponsePayload
    {
        $this->authorize('providers.view');
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:128'],
            'amount' => ['required', 'numeric', 'min:1'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:10'],
            'sync_only' => ['nullable', 'boolean'],
        ]);

        // Lightweight: call the existing TestWoohooOrder command logic later.
        // For now return a structured stub so the panel wiring is correct.
        return ResponsePayload::ok('providers.woohoo_test_order_stub', [
            'requested' => [
                'sku' => $validated['sku'],
                'amount' => (float) $validated['amount'],
                'qty' => (int) ($validated['qty'] ?? 1),
                'sync_only' => (bool) ($validated['sync_only'] ?? false),
            ],
            'note' => 'Panel wiring OK. Next iteration will invoke Woohoo order API/service directly.',
        ]);
    }

    public function syncSku(Request $request): ResponsePayload
    {
        $this->authorize('providers.view');
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:128'],
        ]);

        $sku = trim((string) $validated['sku']);
        if ($sku === '') {
            return ResponsePayload::fail(ResponseCode::VALIDATION_FAILED, 'providers.woohoo_products_failed', [
                'sku' => 'required',
            ], 422);
        }

        $tenantId = (int) (Tenant::query()->min('id') ?? 1);
        $connection = $this->ensureWoohooConnection($tenantId);

        $run = ProviderSyncRun::query()->create([
            'connection_id' => $connection->id,
            'job_type' => 'catalog_sync',
            'status' => 'queued',
        ]);

        SyncWoohooSkuJob::dispatch(syncRunId: $run->id, sku: $sku, tenantId: $tenantId);

        return ResponsePayload::ok('providers.woohoo_products_ok', [
            'queued' => true,
            'sync_run' => [
                'id' => $run->id,
                'status' => $run->status,
                'job_type' => $run->job_type,
            ],
            'sku' => $sku,
        ]);
    }

    public function syncCategory(Request $request): ResponsePayload
    {
        $this->authorize('providers.view');
        $validated = $request->validate([
            'synced_category_id' => ['required', 'integer', 'min:1'],
        ]);

        $tenantId = (int) (Tenant::query()->min('id') ?? 1);
        $connection = $this->ensureWoohooConnection($tenantId);

        $run = ProviderSyncRun::query()->create([
            'connection_id' => $connection->id,
            'job_type' => 'catalog_sync',
            'status' => 'queued',
        ]);

        SyncWoohooCategoryProductsJob::dispatch(
            syncRunId: $run->id,
            syncedCategoryId: (int) $validated['synced_category_id'],
            tenantId: $tenantId
        );

        return ResponsePayload::ok('providers.woohoo_products_ok', [
            'queued' => true,
            'sync_run' => [
                'id' => $run->id,
                'status' => $run->status,
                'job_type' => $run->job_type,
            ],
            'synced_category_id' => (int) $validated['synced_category_id'],
        ]);
    }

    public function syncAllProductDetails(Request $request): ResponsePayload
    {
        $this->authorize('providers.view');

        $tenantId = (int) (Tenant::query()->min('id') ?? 1);
        $connection = $this->ensureWoohooConnection($tenantId);

        $run = ProviderSyncRun::query()->create([
            'connection_id' => $connection->id,
            'job_type' => 'catalog_sync',
            'status' => 'queued',
        ]);

        SyncWoohooAllProductDetailsJob::dispatch(syncRunId: $run->id, tenantId: $tenantId);

        return ResponsePayload::ok('providers.woohoo_products_ok', [
            'queued' => true,
            'sync_run' => [
                'id' => $run->id,
                'status' => $run->status,
                'job_type' => $run->job_type,
            ],
            'scope' => [
                'provider' => 'woohoo',
                'source' => 'products_table',
                'selector' => 'source_provider=woohoo',
            ],
        ]);
    }

    private function ensureWoohooConnection(int $tenantId): ProviderConnection
    {
        $host = (string) config('woohoo.host');
        $environment = str_contains($host, 'sandbox') ? 'sandbox' : 'production';

        /** @var ProviderConnection $conn */
        $conn = ProviderConnection::query()->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'provider' => 'woohoo',
                'environment' => $environment,
            ],
            [
                'label' => $environment === 'sandbox' ? 'Woohoo (Sandbox)' : 'Woohoo (Production)',
                'is_active' => true,
                'connected_at' => now(),
                'public_config' => [
                    'host' => $host,
                ],
            ]
        );

        return $conn;
    }
}
