<?php

namespace App\Console\Commands;

use App\Models\KgenProduct;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchFeaturedKgenProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:featured-kgen-products {--limit=8 : Number of featured products to fetch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and store featured KGen products from the API (for homepage display)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $baseUrl = env('EXLR8_BASE_URL');
            $partnerId = env('dpID');
            $clientId = env('EXLR8_USER_ID');
            $clientSecret = env('EXLR8_USER_SECRET');
            $limit = (int) $this->option('limit');

            if (! $baseUrl || ! $partnerId || ! $clientId || ! $clientSecret) {
                $this->error('KGen API credentials are not configured. Please check your .env file.');
                Log::error('KGen API credentials missing', [
                    'baseUrl' => $baseUrl ? 'set' : 'missing',
                    'partnerId' => $partnerId ? 'set' : 'missing',
                    'clientId' => $clientId ? 'set' : 'missing',
                    'clientSecret' => $clientSecret ? 'set' : 'missing',
                ]);

                return 1;
            }

            $url = rtrim($baseUrl, '/').'/products/delivery-partners/'.$partnerId;

            Log::info('Featured KGen Products Request', [
                'url' => $url,
                'method' => 'get',
                'limit' => $limit,
                'headers' => [
                    'x-client-id' => $clientId,
                    'x-client-secret' => '***',
                ],
            ]);

            $response = Http::timeout(10)
                ->withHeaders([
                    'x-client-id' => $clientId,
                    'x-client-secret' => $clientSecret,
                    'Accept' => '*/*',
                    'User-Agent' => 'Amazepays/1.0 (+https://amazepays.in)',
                ])
                ->get($url);

            $statusCode = $response->status();
            $responseData = $response->json();

            Log::info('Featured KGen Products Response', [
                'status_code' => $statusCode,
                'limit' => $limit,
                'data' => $responseData,
            ]);

            if (! $response->successful()) {
                $errorMessage = 'Failed to fetch featured KGen products. Status Code: '.$statusCode;
                $this->error($errorMessage);
                Log::error('Something went wrong while fetching featured KGen products', [
                    'error' => $response->body(),
                ]);

                return 1;
            }

            $products = collect($response->json('products', []));

            if ($products->isEmpty()) {
                $this->warn('No products found in the API response.');
                Log::info('Featured KGen Products: No products found in response');

                return 0;
            }

            // Get discount map from Product
            $discountMap = Product::whereNotNull('discount_percentage')
                ->where('discount_percentage', '>', 0)
                ->pluck('discount_percentage', 'name')
                ->mapWithKeys(function ($discount, $name) {
                    $normalized = strtolower(trim((string) $name));

                    return $normalized !== '' ? [$normalized => (float) $discount] : [];
                });

            // Process and store products with discount mapping
            $count = 0;
            $products->take($limit)->each(function ($product) use ($discountMap, &$count) {
                $nameCandidates = [
                    strtolower(trim((string) ($product['productDisplayName'] ?? ''))),
                    strtolower(trim((string) ($product['productName'] ?? ''))),
                ];

                $discount = 0;
                foreach ($nameCandidates as $key) {
                    if ($key !== '' && isset($discountMap[$key])) {
                        $discount = $discountMap[$key];
                        break;
                    }
                }

                KgenProduct::updateOrCreate(
                    ['productID' => $product['productID'] ?? null],
                    [
                        'productName' => $product['productName'] ?? null,
                        'productDisplayName' => $product['productDisplayName'] ?? null,
                        'descriptionText' => $product['descriptionText'] ?? null,
                        'redemptionInstructions' => $product['redemptionInstructions'] ?? null,
                        'termsAndConditions' => $product['termsAndConditions'] ?? null,
                        'attachments' => $product['attachments'] ?? null,
                        'categories' => $product['categories'] ?? null,
                        'variants' => $product['variants'] ?? null,
                    ]
                );
                $count++;
            });

            $this->info("Successfully fetched and stored {$count} featured KGen products (limit: {$limit}).");
            Log::info('Featured KGen products updated in the database', [
                'count' => $count,
                'limit' => $limit,
                'update_time' => now()->format('d/m/y H:i:s'),
            ]);

            return 0;
        } catch (\Exception $e) {
            $this->error('An error occurred: '.$e->getMessage());
            Log::error('Featured KGen Products fetch error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
