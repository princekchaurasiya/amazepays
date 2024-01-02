<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\CommonHelper;
use App\QsProduct;
use App\QsCategory;
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
            $products_resp = Http::acceptJson()
                ->withToken($bearerToken)
                ->withHeaders([
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                ])
                ->get($absApiUrl);
            $responseBody = $products_resp->body();

            Log::info('Product List Response:', [
                'status_code' => $products_resp->status(),
                'data' => json_encode($products_resp->json(), JSON_PRETTY_PRINT),
            ]);

            if ($products_resp->status() == 200) {
                $collection = collect($products_resp->json($key = null)['products']);
                $collection->map(function ($item, $key) use ($qsCat) {
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
                    DB::table('qs_products')->updateOrInsert(['sku' => $item['sku']], $data);
                });

                // Log the time when the update is performed
                $updateTime = Carbon\Carbon::now('Asia/Kolkata')->format('d/m/y H:i:s');
                Log::info('Products updated in the database at:', ['update_time' => $updateTime]);
                $this->info('Products stored successfully in the database.');
                return json_encode(['status' => $products_resp->status(), 'data' => 'Stored Successfully']);
            } else {
                Log::error('Something went wrong while fetching the product list.', ['error' => $products_resp->body()]);
                $this->error('Something went wrong while fetching the product list.', ['error' => $products_resp->body()]);
                return json_encode(['status' => $products_resp->status(), 'data' => 'Something went wrong']);
            }
        } catch (Exception $e) {
            Log::error('An error occurred: ' . $e->getMessage());
            $this->error($e->getMessage());
            return $e->getMessage();
        }
    }
}
