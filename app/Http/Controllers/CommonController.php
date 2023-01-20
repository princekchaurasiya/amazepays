<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon;

class CommonController extends Controller
{
    public function checkData(Request $request){
        $data=['status'=>true,'msg'=>$request->all()];
        return response()->json($data);
    }

    public function generateAuthcode(Request $request){
        // dd(13132);
        $authorizationCode_resp = Http::post('https://sandbox.woohoo.in/oauth2/verify', [
            "clientId"=>"546300637c4f1caf36daeeb4a10443f6",  //coming from database
            "username"=>"freneticapisandbox@woohoo.in",    //coming from database
            "password"=>"freneticapisandbox@1234"        //coming from database
        ]);
        if($authorizationCode_resp->status() == 200) {
            // save authocode into database
            $this->generateToken($authorizationCode_resp->json());
        } else {
            return json_response(["status"=>$authorizationCode_resp->status(), "msg"=>$authorizationCode_resp->failed()]);
        }
        
    }

    private function generateToken($authorizationCode){
        $token_resp = Http::post('https://sandbox.woohoo.in/oauth2/token', [
            "clientId"=>"546300637c4f1caf36daeeb4a10443f6",                  //coming from database
            "clientSecret"=>"fb3e12b2e1f526b68ff41b79152be092",             //coming from database
            "authorizationCode"=>  $authorizationCode['authorizationCode'] //from signatureGenerate function
        ]);
        //dd($token_resp->json());
        // if($token_resp->status() == 200){
        //     // save token  into database
        //     //$this->generateToken($token_resp->json());
        // } else {
        //     return json_response(["status"=>$token_resp->status(), "msg"=>$token_resp->failed()]);
        // }
        
    }

    // keep this function in helper call 
    function generateSignature($requestBody=null, $requestHttpMethod, $absApiUrl, $clientSecret){
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
            }
            else {
                $baseStrings[] = rawurlencode($apiUrl);
            }

            if ($requestBody) {
                $jsonDecodedRequestBody = json_decode($requestBody, TRUE);
                sortParams($jsonDecodedRequestBody);
                $baseStrings[] = rawurlencode(
                    json_encode($jsonDecodedRequestBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                );
            }

            return implode('&', $baseStrings);
        }

        echo hash_hmac('sha512', getConcatenateBaseString($absApiUrl, $requestHttpMethod, $requestBody), $clientSecret);
        return hash_hmac('sha512', getConcatenateBaseString($absApiUrl, $requestHttpMethod, $requestBody), $clientSecret);

    }


    public function getCategory(){
        $requestBody = '';
        $requestHttpMethod = 'get';
        $absApiUrl = 'https://sandbox.woohoo.in/rest/v3/catalog/categories';
        $clientSecret = 'fb3e12b2e1f526b68ff41b79152be092';
        $bearerToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJjb25zdW1lcklkIjoiNDI5IiwiZXhwIjoxNjc0MzgyMzU2LCJ0b2tlbiI6ImQ5ZThmMzVkMTFlM2YxNDAzNjgyZGEzMjljZGMzODI2In0.BSvQT-nNow2eJxeSo3br0UGgq0UvP1TEhjXcRjc6z3Q';
        $signature = $this->generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
        //dd($signature);
        
        $dateAtClient = Carbon\Carbon::now()->toIso8601String();
       
        $category_resp = Http::acceptJson()->withToken($bearerToken)->withHeaders([
            'dateAtClient' => $dateAtClient,
            'signature' => $signature,
        ])->get('https://sandbox.woohoo.in/rest/v3/catalog/categories');

        // save category into database
        dd($category_resp->json());

    }

    public function getProducts(){
        $requestBody = '';
        $requestHttpMethod = 'get';
        $absApiUrl = 'https://sandbox.woohoo.in/rest/v3/catalog/categories/121/products';
        $clientSecret = 'fb3e12b2e1f526b68ff41b79152be092';
        $bearerToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJjb25zdW1lcklkIjoiNDI5IiwiZXhwIjoxNjc0MzgyMzU2LCJ0b2tlbiI6ImQ5ZThmMzVkMTFlM2YxNDAzNjgyZGEzMjljZGMzODI2In0.BSvQT-nNow2eJxeSo3br0UGgq0UvP1TEhjXcRjc6z3Q';
        $signature = $this->generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
        //dd($signature);
        
        $dateAtClient = Carbon\Carbon::now()->toIso8601String();
       
        $products_resp = Http::acceptJson()->withToken($bearerToken)->withHeaders([
            'dateAtClient' => $dateAtClient,
            'signature' => $signature,
        ])->get('https://sandbox.woohoo.in/rest/v3/catalog/categories/121/products');

        // save Products into database
        dd($products_resp->json());

    }

    public function getProductbySKU(Request $request){
        $requestBody = '';
        $requestHttpMethod = 'get';
        $absApiUrl = 'https://sandbox.woohoo.in/rest/v3/catalog/products/CNPIN';
        $clientSecret = 'fb3e12b2e1f526b68ff41b79152be092';
        $bearerToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJjb25zdW1lcklkIjoiNDI5IiwiZXhwIjoxNjc0MzgyMzU2LCJ0b2tlbiI6ImQ5ZThmMzVkMTFlM2YxNDAzNjgyZGEzMjljZGMzODI2In0.BSvQT-nNow2eJxeSo3br0UGgq0UvP1TEhjXcRjc6z3Q';
        $signature = $this->generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
        //dd($signature);
        
        $dateAtClient = Carbon\Carbon::now()->toIso8601String();
       
        $products_resp = Http::acceptJson()->withToken($bearerToken)->withHeaders([
            'dateAtClient' => $dateAtClient,
            'signature' => $signature,
        ])->get('https://sandbox.woohoo.in/rest/v3/catalog/products/CNPIN');

        // fetch Products with sku
        dd($products_resp->json());

    }
}
