<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\CommonHelper;
use App\QsProduct;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon;

class FetchProductData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:productData';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and update product data for each SKU';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $skus = QsProduct::pluck('sku');

        try {
            foreach ($skus as $sku) {
                $this->info("Fetching product data for SKU: $sku");
                $requestBody = '';
                $requestHttpMethod = 'get';
                $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/catalog/products/' . $sku;
                // dd($absApiUrl);
                $clientSecret = setting('api.qs_clientSecret');
                $bearerToken = setting('api.bearer_token');
                $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
                $dateAtClient = Carbon\Carbon::now()->toIso8601String();
                $products_resp = Http::acceptJson()
                    ->withToken($bearerToken)
                    ->withHeaders([
                        'dateAtClient' => $dateAtClient,
                        'signature' => $signature,
                    ])
                    ->get($absApiUrl);
                $responseContent = $products_resp->getBody()->getContents();
                // Extract product details from the API response
                $prdtDetails = $products_resp->json();

                $data = [
                    'product_id' => $prdtDetails['id'],
                    'description' => $prdtDetails['description'],
                    'price' => json_encode($prdtDetails['price']),
                    'kycEnabled' => $prdtDetails['kycEnabled'],
                    'additionalForm' => $prdtDetails['additionalForm'],
                    'metaInformation' => json_encode($prdtDetails['price']),
                    'type' => $prdtDetails['type'],
                    'schedulingEnabled' => $prdtDetails['schedulingEnabled'],
                    'product_currency_code' => $prdtDetails['currency'],
                    'images' => json_encode($prdtDetails['images']),
                    'tnc' => json_encode($prdtDetails['tnc']),
                    'categories' => json_encode($prdtDetails['categories']),
                    'customThemesAvailable' => json_encode($prdtDetails['customThemesAvailable']),
                    'handlingCharges' => json_encode($prdtDetails['handlingCharges']),
                    'reloadCardNumber' => json_encode($prdtDetails['reloadCardNumber']),
                    'expiry' => $prdtDetails['expiry'],
                    'formatExpiry' => $prdtDetails['formatExpiry'],
                    'discounts' => json_encode($prdtDetails['discounts']),
                    'relatedProducts' => json_encode($prdtDetails['relatedProducts']),
                    'storeLocatorUrl' => $prdtDetails['storeLocatorUrl'],
                    'brandName' => $prdtDetails['brandName'],
                    'etaMessage' => $prdtDetails['etaMessage'],
                    // 'created_at' => $item['createdAt'],
                    // 'updated_at' => $item['updatedAt'],
                    'cpg' => serialize($prdtDetails['cpg']),
                    'payout' => serialize($prdtDetails['payout']),
                    'allowedfulfillments' => json_encode($prdtDetails['allowedfulfillments']),
                ];
                QsProduct::updateOrInsert(['sku' => $sku], $data);
                $this->info("Updated product data for SKU: $sku (ID: {$prdtDetails['id']})");
                Log::info("Updated product data for SKU: $sku (ID: {$prdtDetails['id']})");
            }

            // Update product details in the 'qs_products' table based on SKU
            $updatedprdtDetails = QsProduct::where('sku', '=', $sku)->update($data);

            $this->info('Product data fetch and update completed.');
            Log::info('Product data fetch and update completed.');
        } catch (Exception $e) {
            $this->error($e->getMessage());
            Log::error('Product data fetch and update failed: ' . $e->getMessage());
        }
    }
}
