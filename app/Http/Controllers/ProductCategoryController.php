<?php

namespace App\Http\Controllers;

use App\Helpers\ApiSignatureHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductCategoryController extends Controller
{
    public function getProductCategory(Request $request, $themecategoryId = null)
    {

        $themecategoryId = 107;
        // If themecategoryId is provided, use it in the URL, else use the base URL
        $baseApiUrl = 'https://'.config('woohoo.host').'/rest/v3/themes/category';
        $absApiUrl = $themecategoryId ? "{$baseApiUrl}/{$themecategoryId}" : $baseApiUrl;

        $requestBody = '';
        $requestHttpMethod = 'get';

        $clientSecret = config('woohoo.client_secret');
        $bearerToken = config('woohoo.bearer_token');
        $signature = ApiSignatureHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
        $dateAtClient = Carbon::now()->toIso8601String();

        // Logging variables
        Log::info('Request Variables:', [
            'absApiUrl' => $absApiUrl,
            'clientSecret' => $clientSecret,
            'bearerToken' => $bearerToken,
            'signature' => $signature,
            'dateAtClient' => $dateAtClient,
        ]);

        try {
            $products_resp = Http::acceptJson()
                ->withToken($bearerToken)
                ->withHeaders([
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                ])
                ->get($absApiUrl);

            $statusCode = $products_resp->status();
            $responseData = $products_resp->json();

            Log::info('Product Request:', [
                'url' => $absApiUrl,
            ]);

            Log::info('Product Response:', [
                'status_code' => $statusCode,
                'data' => json_encode($responseData, JSON_PRETTY_PRINT),
            ]);

            // Inspect variables using dd()
            dd($absApiUrl, $clientSecret, $bearerToken, $signature, $dateAtClient, $statusCode, $responseData);

            return response()->json($responseData, $statusCode);
        } catch (\Exception $e) {
            Log::error('Product Request Error:', [
                'message' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Unable to fetch product categories.'], 500);
        }
    }
}
