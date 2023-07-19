<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon;
use DB;
use App\QsCategory;
use App\QsProduct;
use View;

class CommonController extends Controller
{
    public function checkData(Request $request)
    {
        $data = ['status' => true, 'msg' => $request->all()];
        return response()->json($data);
    }

    public function generateAuthcode(Request $request)
    {
        $authorizationCode_resp = Http::post('https://sandbox.woohoo.in/oauth2/verify', [
            'clientId' => setting('api.clientId'), //coming from database
            'username' => setting('api.qs_username'), //coming from database
            'password' => setting('api.qs_password'), //coming from database
        ]);
        if ($authorizationCode_resp->status() == 200) {
            // save authocode into database
            $response = $this->generateToken($authorizationCode_resp->json());
            return json_decode($response);
        } else {
            return json_decode(json_encode(['status' => $authorizationCode_resp->status(), 'msg' => $authorizationCode_resp->failed()]));
        }
    }

    public function generateToken($authorizationCode)
    {
        $token_resp = Http::post('https://sandbox.woohoo.in/oauth2/token', [
            'clientId' => setting('api.clientId'), //coming from database
            'clientSecret' => setting('api.qs_clientSecret'), //coming from database
            'authorizationCode' => $authorizationCode['authorizationCode'], //from signatureGenerate function
        ]);
        //dd($token_resp->json()['token']);
        if ($token_resp->status() == 200) {
            // save token  into database
            DB::table('settings')->updateOrInsert(['display_name' => 'Bearer Token'], ['value' => $token_resp->json()['token']]);
            return json_encode(['status' => $token_resp->status(), 'data' => $token_resp->json()['token']]);
        } else {
            return json_encode(['status' => $token_resp->status(), 'data' => $token_resp->failed()]);
        }
    }

    // keep this function in helper call
    function generateSignature($requestBody = null, $requestHttpMethod, $absApiUrl, $clientSecret)
    {
        $requestBody = $requestBody;

        $requestHttpMethod = $requestHttpMethod;

        $absApiUrl = $absApiUrl;

        $clientSecret = $clientSecret;

        function sortParams(array &$params)
        {
            ksort($params);
            foreach ($params as $key => &$value) {
                $value = is_object($value) ? (array) $value : $value;
                if (is_array($value)) {
                    sortParams($value);
                }
            }
        }

        function sortQueryParams($queryParam)
        {
            $query = explode('&', $queryParam);
            asort($query, SORT_STRING);

            return implode('&', $query);
        }

        function getConcatenateBaseString($absApiUrl, $requestHttpMethod, $requestBody)
        {
            $baseStrings = [];

            $baseStrings[] = strtoupper($requestHttpMethod);
            $url = explode('?', $absApiUrl);
            $apiUrl = $url[0];
            if (isset($url[1])) {
                $baseStrings[] = rawurlencode($apiUrl . '?' . sortQueryParams($url[1]));
            } else {
                $baseStrings[] = rawurlencode($apiUrl);
            }

            if ($requestBody) {
                $jsonDecodedRequestBody = json_decode($requestBody, true);
                sortParams($jsonDecodedRequestBody);
                $baseStrings[] = rawurlencode(json_encode($jsonDecodedRequestBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

            return implode('&', $baseStrings);
        }

        //echo hash_hmac('sha512', getConcatenateBaseString($absApiUrl, $requestHttpMethod, $requestBody), $clientSecret);
        return hash_hmac('sha512', getConcatenateBaseString($absApiUrl, $requestHttpMethod, $requestBody), $clientSecret);
    }

    public function getCategory()
    {
        try {
            $requestBody = '';
            $requestHttpMethod = 'get';
            $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/catalog/categories';
            $clientSecret = setting('api.qs_clientSecret');
            $bearerToken = setting('api.bearer_token');
            $signature = $this->generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
            //dd($signature);

            $dateAtClient = Carbon\Carbon::now()->toIso8601String();

            $category_resp = Http::acceptJson()
                ->withToken($bearerToken)
                ->withHeaders([
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                ])
                ->get('https://sandbox.woohoo.in/rest/v3/catalog/categories');

            if ($category_resp->status == 200) {
                //save category into database
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
                DB::table('qs_categories')->updateOrInsert(['id' => $category_resp['id']], $data);
                return json_encode(['status' => $token_resp->status(), 'data' => 'Stored Successfully']);
            } else {
                return json_encode(['status' => $token_resp->status(), 'data' => 'Something went wrong']);
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function getProducts()
    {
        try {
            $qsCat = QsCategory::pluck('id')->first();
            $requestBody = '';
            $requestHttpMethod = 'get';
            $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/catalog/categories/' . $qsCat . '/products';
            $clientSecret = setting('api.qs_clientSecret');
            $bearerToken = setting('api.bearer_token');
            $signature = $this->generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);

            $dateAtClient = Carbon\Carbon::now()->toIso8601String();

            $products_resp = Http::acceptJson()
                ->withToken($bearerToken)
                ->withHeaders([
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                ])
                ->get('https://' . setting('api.woohoo_url') . '/rest/v3/catalog/categories/' . $qsCat . '/products');

            // save Products into database

            if ($products_resp->status() == 200) {
                $collection = collect($products_resp->json($key = null)['products']);
                $collection->map(function ($item, $key) use ($qsCat) {
                    // $createdAt = Carbon\Carbon::parse($item['createdAt'])->format('Y-m-d H:m:s');
                    // $updatedAt = Carbon\Carbon::parse($item['updatedAt'])->format('Y-m-d H:m:s');
                    // dd($createdAt);
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
                    // dd($data);
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

    public function getProductbySKU(Request $request)
    {
        // $checkSKU = QsProduct::where("sku", "=",  $prdtDetails['sku'])->first();
        // if(!empty($checkSKU)){

        // } else {

        // }
        try {
            $requestBody = '';
            $requestHttpMethod = 'get';
            $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/catalog/products/' . $request->slug;
            $clientSecret = setting('api.qs_clientSecret');
            $bearerToken = setting('api.bearer_token');
            $signature = $this->generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);

            $dateAtClient = Carbon\Carbon::now()->toIso8601String();

            $products_resp = Http::acceptJson()
                ->withToken($bearerToken)
                ->withHeaders([
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                ])
                ->get('https://' . setting('api.woohoo_url') . '/rest/v3/catalog/products/' . $request->slug);

            // fetch Products with sku

            $prdtDetails = $products_resp->json();
            // dd($prdtDetails);
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
                    // dd($data);
                    // $x = DB::table('qs_products')->update(['sku' => $prdtDetails['sku']], $data);
                    $updatedprdtDetails = QsProduct::where("sku", "=",  $prdtDetails['sku'])->update($data);
                    if(!empty($updatedprdtDetails)){
                        $getprdtDetails = QsProduct::where("sku", "=",  $prdtDetails['sku'])->first()->toArray();
                        $getprdtDetails['price'] = json_decode($getprdtDetails['price']);
                        $getprdtDetails['images'] = json_decode($getprdtDetails['images']);
                        $getprdtDetails['tnc'] = json_decode($getprdtDetails['tnc']);

                    }
                    // dd($getprdtDetails['images']->small);
            return view('userpanel/gift_card_detail_page', compact('getprdtDetails'));
        } catch (Exception $e) {
            return $e->getMessage();
        }

        // dd($products_resp->json());
    }
// wohoo ordercard api call integration
    public function orderCard()
    {
        $body = '{
            "address":
            {
                "firstname":"kevin",
                "lastname":"",
                "email":"kevin.toutle@gmail.com",
                "telephone":"+918652868765",
                "line1":"86/80",
                "line2":"goregaon",
                "city":"mumbai",
                "region":"maharashtra",
                "country":"IN",
                "postcode":"400104",
                "languages":"Hind",
                "billToThis":true
            },
            "billing": {
                "firstname":"kevin",
                "lastname":"",
                "email":"kevin.toutle@gmail.com",
                "telephone":"+918652868765",
                "line1":"86/80",
                "line2":"goregaon",
                "city":"mumbai",
                "region":"maharashtra",
                "country":"IN",
                "postcode":"400104",
                "languages":"Hind",
                "billToThis":true
            },
            "payments":
            [
                {
                    "code":"svc",
                    "amount":1000 //take from selected front end
                }
            ],
            "refno":"CLA3747408489",
            "products":
            [
                {"sku":"CNPIN","price":1000,"qty":1,"currency":356}
            ],
            "syncOnly":true,
            "delivery_mode":"API"
        }';

        $requestBody = $body;
        $requestHttpMethod = 'post';
        $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/orders';
        $clientSecret = setting('api.qs_clientSecret');
        $bearerToken = setting('api.bearer_token');
        $signature = $this->generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);

        $response = Http::acceptJson()
            ->withToken($bearerToken)
            ->withHeaders([
                'dateAtClient' => $dateAtClient,
                'signature' => $signature,
            ])
            ->send('POST', 'https://sandbox.woohoo.in/rest/v3/orders', [
                'body' => $body,
            ])
            ->get();

        // dd($response);
        // if($token_resp->status() == 200){
        //     // save token  into database
        //     DB::table('settings')->updateOrInsert(['display_name' => 'Bearer Token'],['value' => $token_resp->json()['token']]);
        //     return json_encode(["status"=>$token_resp->status(), "data"=>$token_resp->json()['token']]);
        // } else {

        //     return json_encode(["status"=>$token_resp->status(), "data"=>$token_resp->failed()]);
        // }
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
