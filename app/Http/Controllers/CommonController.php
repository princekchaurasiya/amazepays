<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon;
use DB;
use App\QsCategory;
use App\Models\QsOrder;
use App\QsProduct;
use App\Jobs\StatusCheckJob;
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

    //user verification to generate authorization Code
    // public function generateAuthcode(Request $request)
    // {
    //     $authorizationCode_resp = Http::post('https://sandbox.woohoo.in/oauth2/verify', [
    //         'clientId' => setting('api.clientId'), //coming from database
    //         'username' => setting('api.qs_username'), //coming from database
    //         'password' => setting('api.qs_password'), //coming from database
    //     ]);

    //     if ($authorizationCode_resp->status() == 200) {
    //         // save authocode into database
    //         $response = $this->generateBearerToken($authorizationCode_resp->json());
    //         return json_decode($response);
    //     } else {
    //         return json_decode(json_encode(['status' => $authorizationCode_resp->status(), 'msg' => $authorizationCode_resp->failed()]));
    //     }
    // }

    //token (Bearer Token) generation
    // public function generateBearerToken($authorizationCode)
    // {
    //     $token_resp = Http::post('https://sandbox.woohoo.in/oauth2/token', [
    //         'clientId' => setting('api.clientId'), //coming from database
    //         'clientSecret' => setting('api.qs_clientSecret'), //coming from database
    //         'authorizationCode' => $authorizationCode['authorizationCode'], //from signatureGenerate function
    //     ]);
    //     //dd($token_resp->json()['token']);
    //     if ($token_resp->status() == 200) {
    //         // save token  into database
    //         DB::table('settings')->updateOrInsert(['display_name' => 'Bearer Token'], ['value' => $token_resp->json()['token']]);
    //         return json_encode(['status' => $token_resp->status(), 'data' => $token_resp->json()['token']]);
    //     } else {
    //         return json_encode(['status' => $token_resp->status(), 'data' => $token_resp->failed()]);
    //     }
    // }

    // keep this function in helper call
    // function generateSignature($requestBody = null, $requestHttpMethod, $absApiUrl, $clientSecret)
    // {
    //     $requestBody = $requestBody;
    //     $requestHttpMethod = $requestHttpMethod;
    //     $absApiUrl = $absApiUrl;
    //     $clientSecret = $clientSecret;

    //     function sortParams(array &$params)
    //     {
    //         ksort($params);
    //         foreach ($params as $key => &$value) {
    //             $value = is_object($value) ? (array) $value : $value;
    //             if (is_array($value)) {
    //                 sortParams($value);
    //             }
    //         }
    //     }

    //     function sortQueryParams($queryParam)
    //     {
    //         $query = explode('&', $queryParam);
    //         asort($query, SORT_STRING);
    //         return implode('&', $query);
    //     }

    //     function getConcatenateBaseString($absApiUrl, $requestHttpMethod, $requestBody)
    //     {
    //         $baseStrings = [];
    //         $baseStrings[] = strtoupper($requestHttpMethod);
    //         $url = explode('?', $absApiUrl);
    //         $apiUrl = $url[0];
    //         if (isset($url[1])) {
    //             $baseStrings[] = rawurlencode($apiUrl . '?' . sortQueryParams($url[1]));
    //         } else {
    //             $baseStrings[] = rawurlencode($apiUrl);
    //         }

    //         if ($requestBody) {
    //             $jsonDecodedRequestBody = json_decode($requestBody, true);
    //             sortParams($jsonDecodedRequestBody);
    //             $baseStrings[] = rawurlencode(json_encode($jsonDecodedRequestBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    //         }
    //         return implode('&', $baseStrings);
    //     }

    //     //echo hash_hmac('sha512', getConcatenateBaseString($absApiUrl, $requestHttpMethod, $requestBody), $clientSecret);
    //     return hash_hmac('sha512', getConcatenateBaseString($absApiUrl, $requestHttpMethod, $requestBody), $clientSecret);
    // }

    //category api should be called once and stored in database for further uses
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
            // dd($signature);
            $dateAtClient = Carbon\Carbon::now()->toIso8601String();

            // Sending a GET request to retrieve categories from an API
            $category_resp = Http::acceptJson()
                ->withToken($bearerToken)
                ->withHeaders([
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                ])
                ->get($absApiUrl);
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
                return json_encode(['status' => $token_resp->status(), 'data' => 'Stored Successfully']);
            } else {
                return json_encode(['status' => $token_resp->status(), 'data' => 'Something went wrong']);
            }
        } catch (\Exception $e) {
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

            // Create the API URL to retrieve products of a specific category
            $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/catalog/categories/' . $qsCat . '/products';
            // dd($absApiUrl);

            // Get client secret and bearer token from settings
            $clientSecret = setting('api.qs_clientSecret');
            $bearerToken = setting('api.bearer_token');

            // Generate a signature for the API request
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
            // dd($responseBody);

            // save Products into database

            // Check if the API response status is 200 (success)
            if ($products_resp->status() == 200) {
                // Extract the list of products from the API response
                $collection = collect($products_resp->json($key = null)['products']);
                // dd( $collection);
                // Process each product item in the collection
                $collection->map(function ($item, $key) use ($qsCat) {
                    // Define the data to be inserted or updated in the 'qs_products' table
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

                    // Update or insert the product data into the 'qs_products' table based on SKU
                    DB::table('qs_products')->updateOrInsert(['sku' => $item['sku']], $data);
                });

                // Return a success response
                return json_encode(['status' => $products_resp->status(), 'data' => 'Stored Successfully']);
            } else {
                // Return an error response if the API request was not successful
                return json_encode(['status' => $products_resp->status(), 'data' => 'Something went wrong']);
            }
        } catch (Exception $e) {
            // Return an error message if an exception occurs during the process
            return $e->getMessage();
        }
    }

    //prodcut list api for woohoo should be called once only
    public function getProductbySKU(Request $request)
    {
        try {
            // Initialize variables for API request

            // $request->slug ="my-random-slug";
            $requestBody = '';
            $requestHttpMethod = 'get';

            // Create the API URL to retrieve product details by SKU
            $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/catalog/products/' . $request->slug;
            // dd($absApiUrl);

            // Get client secret and bearer token from settings
            $clientSecret = setting('api.qs_clientSecret');
            $bearerToken = setting('api.bearer_token');

            // Generate a signature for the API request
            $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);

            // Get the current time in ISO8601 format
            $dateAtClient = Carbon\Carbon::now()->toIso8601String();

            // Send a GET request to retrieve product details from the API
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
            // dd($prdtDetails);

            // Define data to be inserted or updated in the database
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

            // Update product details in the 'qs_products' table based on SKU
            $updatedprdtDetails = QsProduct::where('sku', '=', $prdtDetails['sku'])->update($data);

            // Check if product details were updated successfully
            if (!empty($updatedprdtDetails)) {
                // Retrieve updated product details from the database
                $getprdtDetails = QsProduct::where('sku', '=', $prdtDetails['sku'])
                    ->first()
                    ->toArray();

                // Convert certain JSON fields back to objects
                $getprdtDetails['price'] = json_decode($getprdtDetails['price']);
                $getprdtDetails['images'] = json_decode($getprdtDetails['images']);
                $getprdtDetails['tnc'] = json_decode($getprdtDetails['tnc']);

                // Pass the product details to the view and render it
                return view('userpanel/gift_card_detail_page', compact('getprdtDetails'));
            } else {
                // Return an error response if updating product details failed
                return json_encode(['status' => 'error', 'message' => 'Failed to update product details']);
            }
        } catch (Exception $e) {
            // Return an error message if an exception occurs during the process
            return $e->getMessage();
        }
    }

    // wohoo ordercard api call integration
    // public function orderCard()
    // {
    //     // Create the request body with required information for placing an order
    //     $body = '{
    //         "address":
    //         {
    //             "firstname":"kevin",
    //             "lastname":"",
    //             "email":"kevin.toutle@gmail.com",
    //             "telephone":"+918652868765",
    //             "line1":"86/80",
    //             "line2":"goregaon",
    //             "city":"mumbai",
    //             "region":"maharashtra",
    //             "country":"IN",
    //             "postcode":"400104",
    //             "languages":"Hind",
    //             "billToThis":true
    //         },
    //         "billing": {
    //             "firstname":"kevin",
    //             "lastname":"",
    //             "email":"kevin.toutle@gmail.com",
    //             "telephone":"+918652868765",
    //             "line1":"86/80",
    //             "line2":"goregaon",
    //             "city":"mumbai",
    //             "region":"maharashtra",
    //             "country":"IN",
    //             "postcode":"400104",
    //             "languages":"Hind",
    //             "billToThis":true
    //         },
    //         "payments":
    //         [
    //             {
    //                 "code":"svc",
    //                 "amount":1000 //take from selected front end
    //             }
    //         ],
    //         "refno":"CLA3747408489",
    //         "products":
    //         [
    //             {"sku":"CNPIN","price":1000,"qty":1,"currency":356}
    //         ],
    //         "syncOnly":true,
    //         "delivery_mode":"API"
    //     }';

    //     $requestBody = $body;
    //     $requestHttpMethod = 'post';
    //     $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/orders';
    //     $clientSecret = setting('api.qs_clientSecret');
    //     $bearerToken = setting('api.bearer_token');
    //     $signature = $this->generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);

    //     $response = Http::acceptJson()
    //         ->withToken($bearerToken)
    //         ->withHeaders([
    //             'dateAtClient' => $dateAtClient,
    //             'signature' => $signature,
    //         ])
    //         ->send('POST', 'https://sandbox.woohoo.in/rest/v3/orders', [
    //             'body' => $body,
    //         ])
    //         ->get();

    //     // dd($response);
    //     // if($token_resp->status() == 200){
    //     //     // save token  into database
    //     //     DB::table('settings')->updateOrInsert(['display_name' => 'Bearer Token'],['value' => $token_resp->json()['token']]);
    //     //     return json_encode(["status"=>$token_resp->status(), "data"=>$token_resp->json()['token']]);
    //     // } else {

    //     //     return json_encode(["status"=>$token_resp->status(), "data"=>$token_resp->failed()]);
    //     // }
    // }

    public function handleRetryAttempt($refno, $data, $orderId)
    {   
        
        StatusCheckJob::dispatch($refno, $data, $orderId)->delay(now()->addSeconds(10));
    }

    

    // {
    //     "address":
    //     {
    //         "firstname":"kevin","lastname":"","email":"kevin.toutle@gmail.com","telephone":"+918652868765","line1":"86/80","line2":"goregaon","city":"mumbai","region":"maharashtra","country":"IN","postcode":"400104","languages":"Hind","billToThis":true
    //     },
    //     "payments":
    //     [
    //         {
    //             "code":"svc",
    //             "amount":1000 //take from selected front end
    //         }
    //     ],
    //     "refno":"CLA3747408489",
    //     "products":
    //     [
    //         {"sku":"CNPIN","price":1000,"qty":1,"currency":356}
    //     ],
    //     "syncOnly":true,
    //     "delivery_mode":"API"
    // }
}
