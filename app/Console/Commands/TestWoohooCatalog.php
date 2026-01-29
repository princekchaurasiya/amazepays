<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class TestWoohooCatalog extends Command
{
    protected $signature = 'woohoo:test-catalog 
                            {--type=categories : Type of catalog test (categories, products, product)}
                            {--category-id= : Category ID for products test}
                            {--sku= : Product SKU for single product test}
                            {--offset=0 : Offset for pagination}
                            {--limit=100 : Limit for pagination}';

    protected $description = 'Test Woohoo Catalog API (Categories, Products)';

    public function handle()
    {
        $type = $this->option('type');
        $categoryId = $this->option('category-id');
        $sku = $this->option('sku');
        $offset = (int) $this->option('offset');
        $limit = (int) $this->option('limit');

        $this->info("=== Woohoo Catalog API Test ===");
        $this->info("Type: {$type}");
        if ($categoryId) {
            $this->info("Category ID: {$categoryId}");
        }
        if ($sku) {
            $this->info("SKU: {$sku}");
        }
        $this->newLine();

        try {
            switch ($type) {
                case 'categories':
                    $result = $this->testCategories();
                    break;
                case 'products':
                    if (!$categoryId) {
                        $this->error("❌ --category-id is required for products test");
                        return 1;
                    }
                    $result = $this->testProducts($categoryId, $offset, $limit);
                    break;
                case 'product':
                    if (!$sku) {
                        $this->error("❌ --sku is required for single product test");
                        return 1;
                    }
                    $result = $this->testProduct($sku);
                    break;
                default:
                    $this->error("❌ Invalid type. Use: categories, products, or product");
                    return 1;
            }

            if ($result['success']) {
                $this->info("✅ Test PASSED: {$type}");
                $this->displayResults($result, $type);
            } else {
                $this->error("❌ Test FAILED");
                $this->error("Error: " . ($result['error'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            $this->error("❌ Test EXCEPTION: " . $e->getMessage());
            Log::error("Woohoo catalog test exception", [
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }

        return 0;
    }

    private function testCategories()
    {
        $woohooUrl = setting('api.woohoo_url');
        $clientSecret = setting('api.qs_clientSecret');
        $bearerToken = setting('api.bearer_token');

        // Debug output
        $this->info("Checking credentials...");
        $this->info("Woohoo URL: " . ($woohooUrl ?: 'NOT SET'));
        $this->info("Client Secret: " . ($clientSecret ? substr($clientSecret, 0, 10) . '...' : 'NOT SET'));
        $this->info("Bearer Token: " . ($bearerToken ? substr($bearerToken, 0, 20) . '...' : 'NOT SET'));

        if (!$woohooUrl || !$clientSecret || !$bearerToken) {
            $this->warn("Missing credentials. Checking database settings...");
            $settings = \DB::table('settings')->where('key', 'like', 'api.%')->get(['key', 'value']);
            if ($settings->isEmpty()) {
                $this->error("No API settings found in database.");
            } else {
                $this->info("Found " . $settings->count() . " API settings in database:");
                foreach ($settings as $s) {
                    $val = $s->value;
                    if (strlen($val) > 30) $val = substr($val, 0, 30) . '...';
                    $this->line("  - {$s->key}: {$val}");
                }
            }
            
            return [
                'success' => false,
                'error' => 'Woohoo API credentials not configured'
            ];
        }

        $absApiUrl = 'https://' . $woohooUrl . '/rest/v3/catalog/categories';
        $requestHttpMethod = 'get';
        $requestBody = '';
        $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
        $dateAtClient = Carbon::now()->toIso8601String();

        $this->info("Sending request to: {$absApiUrl}");

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->withToken($bearerToken)
                ->withHeaders([
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                    'Accept' => '*/*',
                    'User-Agent' => 'Amazepays/1.0 (+https://amazepays.in)',
                ])
                ->get($absApiUrl);

            $statusCode = $response->status();
            $responseData = $response->json();

            $this->newLine();
            $this->info("Response Status: {$statusCode}");
            $this->info("Response Body: " . json_encode($responseData, JSON_PRETTY_PRINT));

            if ($statusCode === 200 && is_array($responseData)) {
                $categoryCount = count($responseData);
                $this->info("Categories Found: {$categoryCount}");
                
                // Display first few categories
                foreach (array_slice($responseData, 0, 5) as $category) {
                    $this->info("  - ID: " . ($category['id'] ?? 'N/A') . ", Name: " . ($category['name'] ?? 'N/A'));
                }

                return [
                    'success' => true,
                    'data' => $responseData,
                    'count' => $categoryCount
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Invalid response',
                    'status_code' => $statusCode,
                    'data' => $responseData
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    private function testProducts($categoryId, $offset, $limit)
    {
        $woohooUrl = setting('api.woohoo_url');
        $clientSecret = setting('api.qs_clientSecret');
        $bearerToken = setting('api.bearer_token');

        if (!$woohooUrl || !$clientSecret || !$bearerToken) {
            return [
                'success' => false,
                'error' => 'Woohoo API credentials not configured'
            ];
        }

        $absApiUrl = 'https://' . $woohooUrl . '/rest/v3/catalog/categories/' . $categoryId . '/products?offset=' . $offset . '&limit=' . $limit;
        $requestHttpMethod = 'get';
        $requestBody = '';
        $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
        $dateAtClient = Carbon::now()->toIso8601String();

        $this->info("Sending request to: {$absApiUrl}");

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->withToken($bearerToken)
                ->withHeaders([
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                    'Accept' => '*/*',
                    'User-Agent' => 'Amazepays/1.0 (+https://amazepays.in)',
                ])
                ->get($absApiUrl);

            $statusCode = $response->status();
            $responseData = $response->json();

            $this->newLine();
            $this->info("Response Status: {$statusCode}");

            if ($statusCode === 200 && isset($responseData['products']) && is_array($responseData['products'])) {
                $productCount = count($responseData['products']);
                $this->info("Products Found: {$productCount}");
                
                // Display first few products
                foreach (array_slice($responseData['products'], 0, 5) as $product) {
                    $this->info("  - SKU: " . ($product['sku'] ?? 'N/A') . ", Name: " . ($product['name'] ?? 'N/A'));
                }

                return [
                    'success' => true,
                    'data' => $responseData,
                    'count' => $productCount
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Invalid response or products not found',
                    'status_code' => $statusCode,
                    'data' => $responseData
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    private function testProduct($sku)
    {
        $woohooUrl = setting('api.woohoo_url');
        $clientSecret = setting('api.qs_clientSecret');
        $bearerToken = setting('api.bearer_token');

        if (!$woohooUrl || !$clientSecret || !$bearerToken) {
            return [
                'success' => false,
                'error' => 'Woohoo API credentials not configured'
            ];
        }

        $absApiUrl = 'https://' . $woohooUrl . '/rest/v3/catalog/products/' . $sku;
        $requestHttpMethod = 'get';
        $requestBody = '';
        $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
        $dateAtClient = Carbon::now()->toIso8601String();

        $this->info("Sending request to: {$absApiUrl}");

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->withToken($bearerToken)
                ->withHeaders([
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                    'Accept' => '*/*',
                    'User-Agent' => 'Amazepays/1.0 (+https://amazepays.in)',
                ])
                ->get($absApiUrl);

            $statusCode = $response->status();
            $responseData = $response->json();

            $this->newLine();
            $this->info("Response Status: {$statusCode}");
            $this->info("Response Body: " . json_encode($responseData, JSON_PRETTY_PRINT));

            if ($statusCode === 200 && isset($responseData['sku'])) {
                $this->info("Product SKU: " . $responseData['sku']);
                $this->info("Product Name: " . ($responseData['name'] ?? 'N/A'));
                
                if (isset($responseData['metaInformation']) || isset($responseData['price'])) {
                    $priceInfo = $responseData['metaInformation'] ?? $responseData['price'];
                    $this->info("Price Info: " . json_encode($priceInfo));
                }

                return [
                    'success' => true,
                    'data' => $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Product not found or invalid response',
                    'status_code' => $statusCode,
                    'data' => $responseData
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    private function displayResults($result, $type)
    {
        $this->newLine();
        $this->info("=== Test Results ===");
        
        if (isset($result['count'])) {
            $this->info("Items Found: " . $result['count']);
        }

        if ($type === 'product' && isset($result['data']['sku'])) {
            $this->info("SKU: " . $result['data']['sku']);
            $this->info("Name: " . ($result['data']['name'] ?? 'N/A'));
        }
    }
}
