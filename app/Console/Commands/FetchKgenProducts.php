<?php

namespace App\Console\Commands;

use App\Models\KgenProduct;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchKgenProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:kgen-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and store KGen products from the API';

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

            Log::info('KGen Products Request', [
                'url' => $url,
                'method' => 'get',
                'headers' => [
                    'x-client-id' => $clientId,
                    'x-client-secret' => '***',
                ],
            ]);

            $response = Http::withHeaders([
                'x-client-id' => $clientId,
                'x-client-secret' => $clientSecret,
                'Accept' => '*/*',
                'User-Agent' => 'Amazepays/1.0 (+https://amazepays.in)',
            ])->get($url);

            $statusCode = $response->status();
            $responseData = $response->json();

            Log::info('KGen Products Response', [
                'status_code' => $statusCode,
                'data' => $responseData,
            ]);

            if (! $response->successful()) {
                $errorMessage = 'Failed to fetch KGen products. Status Code: '.$statusCode;
                $this->error($errorMessage);
                Log::error('Something went wrong while fetching KGen products', [
                    'error' => $response->body(),
                ]);

                return 1;
            }

            $products = $response->json('products', []);

            if (empty($products)) {
                $this->warn('No products found in the API response.');
                Log::info('KGen Products: No products found in response');

                return 0;
            }

            $count = 0;
            foreach ($products as $product) {
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
            }

            $this->info("Successfully fetched and stored {$count} KGen products.");
            Log::info('KGen products updated in the database', [
                'count' => $count,
                'update_time' => now()->format('d/m/y H:i:s'),
            ]);

            return 0;
        } catch (\Exception $e) {
            $this->error('An error occurred: '.$e->getMessage());
            Log::error('KGen Products fetch error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
