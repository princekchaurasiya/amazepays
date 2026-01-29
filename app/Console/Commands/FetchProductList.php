<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\CommonHelper;
use App\Models\QsProduct;
use App\Models\QsCategory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon;
use DB;

class FetchProductList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:productList';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Product List From Woohoo Server';

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
        try {
            $qsCat = QsCategory::pluck('id')->first();
            $requestBody = '';
            $requestHttpMethod = 'get';
            $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/catalog/categories/' . $qsCat . '/products';
            $clientSecret = setting('api.qs_clientSecret');
            $bearerToken = setting('api.bearer_token');
            $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);

            // Get the current time in ISO8601 format
            $dateAtClient = Carbon\Carbon::now()->toIso8601String();

            Log::info('Product List Request:', [
                'url' => $absApiUrl,
                'method' => $requestHttpMethod,
                'requestBody' => $requestBody,
                'headers' => [
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                ],
            ]);

            // Send a GET request to retrieve products from the API
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
            $responseBody = $products_resp->body();

            Log::info('Product List Response:', [
                'status_code' => $products_resp->status(),
                'data' => json_encode($products_resp->json(), JSON_PRETTY_PRINT),
            ]);

            if ($products_resp->status() == 200) {
                $collection = collect($products_resp->json($key = null)['products']);
                $inserted = 0;
                $updated = 0;
                $skipped = 0;
                
                $collection->each(function ($item, $key) use ($qsCat, &$inserted, &$updated, &$skipped) {
                    try {
                        // Skip products with empty or null SKUs
                        if (empty($item['sku']) || trim($item['sku']) === '') {
                            $skipped++;
                            Log::warning('Skipped product with empty SKU', ['product_name' => $item['name'] ?? 'Unknown']);
                            return;
                        }
                        
                        $data = [
                            'sku' => $item['sku'],
                            'name' => $item['name'],
                            'currency' => json_encode($item['currency']),
                            'url' => $item['url'],
                            'minPrice' => $item['minPrice'],
                            'maxPrice' => $item['maxPrice'],
                            'price' => json_encode($item['price']),
                            'images' => json_encode($item['images']),
                            'prdt_created_at' => $item['createdAt'],
                            'prdt_updated_at' => $item['updatedAt'],
                            'qs_category_id' => $qsCat,
                        ];
                        
                        // Check if product exists to track inserts vs updates
                        $exists = DB::table('qs_products')->where('sku', $item['sku'])->exists();
                        
                        DB::table('qs_products')->updateOrInsert(['sku' => $item['sku']], $data);
                        
                        if ($exists) {
                            $updated++;
                        } else {
                            $inserted++;
                        }
                    } catch (\Exception $e) {
                        // Handle duplicate key or other database errors gracefully
                        Log::error('Failed to insert/update product', [
                            'sku' => $item['sku'] ?? 'Unknown',
                            'error' => $e->getMessage()
                        ]);
                        $skipped++;
                    }
                });

                // Log the time when the update is performed
                $updateTime = Carbon\Carbon::now('Asia/Kolkata')->format('d/m/y H:i:s');
                Log::info('Products updated in the database at:', ['update_time' => $updateTime]);
                $this->info("Products stored successfully: {$inserted} inserted, {$updated} updated, {$skipped} skipped");
                return json_encode(['status' => $products_resp->status(), 'data' => 'Stored Successfully']);
            } else {
                $errorMessage = 'Something went wrong while fetching the product list. Status Code: ' . $products_resp->status();
                Log::error('Something went wrong while fetching the product list.', ['error' => $products_resp->body()]);
                $this->error($errorMessage);
                return json_encode(['status' => $products_resp->status(), 'data' => 'Something went wrong']);
            }
        } catch (Exception $e) {
            Log::error('An error occurred: ' . $e->getMessage());
            $this->error($e->getMessage());
            return $e->getMessage();
        }
    }
}
