<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon;
use DB;
use App\QsCategory;
use App\Models\QsOrder;
use App\QsProduct;
use Illuminate\Support\Facades\Log;
use View;
use App\Helpers\CommonHelper;

class CommonController extends Controller
{
    public function checkData(Request $request)
    {
        $data = ['status' => true, 'msg' => $request->all()];
        return response()->json($data);
    }

    public function getCategory()
    {
        try {
            // $categoryId = 121;
            $requestBody = '';
            $requestHttpMethod = 'get';
            $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/catalog/categories/';
            // $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/catalog/categories/' . $categoryId;
            // dd($absApiUrl);
            $clientSecret = setting('api.qs_clientSecret');
            $bearerToken = setting('api.bearer_token');
            $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
            $dateAtClient = Carbon\Carbon::now()->toIso8601String();

            Log::info('Category Request:', [
                'url' => $absApiUrl,
                'method' => $requestHttpMethod,
                'data' => [
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                ],
            ]);

            $category_resp = Http::acceptJson()
                ->withToken($bearerToken)
                ->withHeaders([
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                ])
                ->get($absApiUrl);

            Log::info('Category Response:', [
                'status_code' => $category_resp->status(),
                'data' => $category_resp->json(),
            ]);

            // dd($category_resp->body());
            if ($category_resp->status == 200) {
                // If the API response status is 200, save category data into the database
                $category_resp = $category_resp->json($key = null);
                $data = [
                    'id' => $category_resp['id'],
                    'name' => $category_resp['name'],
                    'url' => $category_resp['url'],
                    'description' => $category_resp['description'],
                    'images' => json_encode($category_resp['images']),
                    'subcategoriesCount' => $category_resp['subcategoriesCount'],
                    'subcategories' => json_encode($category_resp['subcategories']),
                ];

                // Update or insert the category data into the 'qs_categories' table based on the ID
                DB::table('qs_categories')->updateOrInsert(['id' => $category_resp['id']], $data);
                Log::info('Category stored successfully in the database.');
                return json_encode(['status' => $token_resp->status(), 'data' => 'Stored Successfully']);

            } else {
                Log::error('Something went wrong while fetching the category.', ['error' => $category_resp->body()]);
                return json_encode(['status' => $token_resp->status(), 'data' => 'Something went wrong']);
            }
        } catch (\Exception $e) {
            Log::error('An error occurred: ' . $e->getMessage());
            return $e->getMessage();
        }
    }

    //product api should be called once and stored in database for further uses
    public function getProductList()
    {
        try {
            // Retrieve the ID of the first category from the 'qs_categories' table
            $qsCat = QsCategory::all();
            $qsCat = QsCategory::pluck('id')->first();
            // $qsCat =122;
            // dd($qsCat);
            // Initialize variables for API request
            $requestBody = '';
            $requestHttpMethod = 'get';

            $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/catalog/categories/' . $qsCat . '/products';
            // dd($absApiUrl);

            $clientSecret = setting('api.qs_clientSecret');
            $bearerToken = setting('api.bearer_token');
            $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);

            // Get the current time in ISO8601 format
            $dateAtClient = Carbon\Carbon::now()->toIso8601String();

            // Send a GET request to retrieve products from the API
            $products_resp = Http::acceptJson()
                ->withToken($bearerToken)
                ->withHeaders([
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                ])
                ->get($absApiUrl);
            $responseBody = $products_resp->body();
            // $responseJson = $products_resp->json();
            // dd($responseJson);

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

                return json_encode(['status' => $products_resp->status(), 'data' => 'Stored Successfully']);
            } else {
                return json_encode(['status' => $products_resp->status(), 'data' => 'Something went wrong']);
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
