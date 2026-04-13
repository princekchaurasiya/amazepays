<?php

namespace App\Console\Commands;

use App\Helpers\ApiSignatureHelper;
use App\Models\Product;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FetchProductData extends Command
{
    protected $signature = 'fetch:productData';

    protected $description = 'Fetch and update product data for each SKU';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $skus = Product::whereNotNull('sku')->where('sku', '!=', '')->pluck('sku');
        try {
            foreach ($skus as $sku) {
                try {
                    $this->info("Fetching product data for SKU: $sku");

                    $requestBody = '';
                    $requestHttpMethod = 'get';
                    $absApiUrl = 'https://'.config('woohoo.host').'/rest/v3/catalog/products/'.$sku;
                    $clientSecret = config('woohoo.client_secret');
                    $bearerToken = config('woohoo.bearer_token');
                    $signature = ApiSignatureHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
                    $dateAtClient = Carbon::now()->toIso8601String();

                    // Use same headers as other Woohoo API calls to avoid CDN blocking
                    $products_resp = Http::acceptJson()
                        ->withToken($bearerToken)
                        ->withHeaders([
                            'dateAtClient' => $dateAtClient,
                            'signature' => $signature,
                            'Accept' => '*/*',
                            'User-Agent' => 'Amazepays/1.0 (+https://amazepays.in)',
                        ])
                        ->get($absApiUrl);

                    Log::info('Product Request:', ['url' => $absApiUrl]);

                    $statusCode = $products_resp->status();
                    $prdtDetails = $products_resp->json();

                    Log::info('Product Response:', [
                        'status_code' => $statusCode,
                        'data' => $prdtDetails,
                    ]);

                    // Check if the response was successful (200) and contains valid data
                    if ($statusCode !== 200) {
                        $errorMessage = "Failed to fetch product data for SKU: $sku. Status Code: $statusCode";
                        $this->warn($errorMessage);
                        Log::warning($errorMessage, [
                            'sku' => $sku,
                            'status_code' => $statusCode,
                            'response_body' => $products_resp->body(),
                        ]);

                        // Auto-delete products that consistently fail to fetch (403, 404, 410, etc.)
                        if (in_array($statusCode, [403, 404, 410, 451])) {
                            $deleted = Product::where('sku', $sku)->delete();
                            if ($deleted) {
                                $this->warn("Auto-deleted product with SKU: $sku (Status: $statusCode - Product unavailable)");
                                Log::warning("Auto-deleted product with SKU: $sku", [
                                    'status_code' => $statusCode,
                                    'reason' => 'Product unavailable or access denied',
                                ]);
                            }
                        }

                        continue; // Skip to the next SKU if request failed
                    }

                    // Check if the response is valid and contains 'name'
                    if (! isset($prdtDetails['name'])) {
                        $this->warn("Missing 'name' in response for SKU: $sku");
                        Log::warning("Missing 'name' in response for SKU: $sku", ['response' => $prdtDetails]);

                        continue; // Skip to the next SKU if 'name' is missing
                    }

                    $productName = $prdtDetails['name'];
                    $this->info("Fetched product data for SKU: $sku (Product Name: $productName)");

                    // Null-safe handling for each field
                    $data = [
                        'product_id' => $prdtDetails['id'] ?? null,
                        'description' => $prdtDetails['description'] ?? '',
                        'price' => isset($prdtDetails['price']) ? json_encode($prdtDetails['price']) : json_encode([]),
                        'kycEnabled' => $prdtDetails['kycEnabled'] ?? false,
                        'additionalForm' => $prdtDetails['additionalForm'] ?? '',
                        'metaInformation' => isset($prdtDetails['price']) ? json_encode($prdtDetails['price']) : json_encode([]),
                        'type' => $prdtDetails['type'] ?? '',
                        'schedulingEnabled' => $prdtDetails['schedulingEnabled'] ?? false,
                        'product_currency_code' => $prdtDetails['currency'] ?? '',
                        'images' => isset($prdtDetails['images']) ? json_encode($prdtDetails['images']) : json_encode([]),
                        'tnc' => isset($prdtDetails['tnc']) ? json_encode($prdtDetails['tnc']) : json_encode([]),
                        'categories' => isset($prdtDetails['categories']) ? json_encode($prdtDetails['categories']) : json_encode([]),
                        'customThemesAvailable' => isset($prdtDetails['customThemesAvailable']) ? json_encode($prdtDetails['customThemesAvailable']) : json_encode([]),
                        'handlingCharges' => isset($prdtDetails['handlingCharges']) ? json_encode($prdtDetails['handlingCharges']) : json_encode([]),
                        'reloadCardNumber' => isset($prdtDetails['reloadCardNumber']) ? json_encode($prdtDetails['reloadCardNumber']) : json_encode([]),
                        'expiry' => $prdtDetails['expiry'] ?? '',
                        'formatExpiry' => $prdtDetails['formatExpiry'] ?? '',
                        'discounts' => isset($prdtDetails['discounts']) ? json_encode($prdtDetails['discounts']) : json_encode([]),
                        'relatedProducts' => isset($prdtDetails['relatedProducts']) ? json_encode($prdtDetails['relatedProducts']) : json_encode([]),
                        'storeLocatorUrl' => $prdtDetails['storeLocatorUrl'] ?? '',
                        'brandName' => $prdtDetails['brandName'] ?? '',
                        'etaMessage' => $prdtDetails['etaMessage'] ?? '',
                        'cpg' => isset($prdtDetails['cpg']) ? serialize($prdtDetails['cpg']) : serialize([]),
                        'payout' => isset($prdtDetails['payout']) ? serialize($prdtDetails['payout']) : serialize([]),
                        'allowedfulfillments' => isset($prdtDetails['allowedfulfillments']) ? json_encode($prdtDetails['allowedfulfillments']) : json_encode([]),
                        'slug' => $prdtDetails['url'] ?? ($prdtDetails['name'] ? Str::slug($prdtDetails['name']) : ''),
                    ];

                    Product::updateOrInsert(['sku' => $sku], $data);
                    $productId = $prdtDetails['id'] ?? 'N/A';
                    $this->info("Updated product data for SKU: $sku (ID: $productId)");
                    Log::info("Updated product data for SKU: $sku (ID: $productId)");

                } catch (Exception $e) {
                    // Log the error for the specific SKU and continue with the next one
                    $this->error("Error fetching data for SKU: $sku - ".$e->getMessage());
                    Log::error("Error fetching data for SKU: $sku - ".$e->getMessage());

                    continue; // Continue with the next SKU
                }
            }

            $updateTime = Carbon::now('Asia/Kolkata')->format('d/m/y H:i:s');
            $this->info('Product data fetch and update completed.');
            Log::info('Product data fetch and update completed in the database at:', ['update_time' => $updateTime]);

        } catch (Exception $e) {
            $this->error($e->getMessage());
            Log::error('Product data fetch and update failed: '.$e->getMessage());
        }
    }
}
