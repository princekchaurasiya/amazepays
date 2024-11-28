<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\CommonHelper;
use App\Models\QsProduct;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;
use Carbon;

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
    $skus = QsProduct::pluck('sku');
    try {
        foreach ($skus as $sku) {
            try {
                $this->info("Fetching product data for SKU: $sku");

                $requestBody = '';
                $requestHttpMethod = 'get';
                $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/catalog/products/' . $sku;
                $clientSecret = setting('api.qs_clientSecret');
                $bearerToken = setting('api.bearer_token');
                $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
                $dateAtClient = Carbon\Carbon::now()->toIso8601String();

                $products_resp = Http::acceptJson()
                    ->withToken($bearerToken)
                    ->withHeaders(['dateAtClient' => $dateAtClient, 'signature' => $signature])
                    ->get($absApiUrl);

                Log::info('Product Request:', ['url' => $absApiUrl]);
                Log::info('Product Response:', [
                    'status_code' => $products_resp->status(),
                    'data' => $products_resp->json(),
                ]);

                $prdtDetails = $products_resp->json();

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

                QsProduct::updateOrInsert(['sku' => $sku], $data);
                $productId = $prdtDetails['id'] ?? 'N/A';
                $this->info("Updated product data for SKU: $sku (ID: $productId)");
                Log::info("Updated product data for SKU: $sku (ID: $productId)");

            } catch (Exception $e) {
                // Log the error for the specific SKU and continue with the next one
                $this->error("Error fetching data for SKU: $sku - " . $e->getMessage());
                Log::error("Error fetching data for SKU: $sku - " . $e->getMessage());
                continue; // Continue with the next SKU
            }
        }

        $updateTime = Carbon\Carbon::now('Asia/Kolkata')->format('d/m/y H:i:s');
        $this->info('Product data fetch and update completed.');
        Log::info('Product data fetch and update completed in the database at:', ['update_time' => $updateTime]);

    } catch (Exception $e) {
        $this->error($e->getMessage());
        Log::error('Product data fetch and update failed: ' . $e->getMessage());
    }
}

}


