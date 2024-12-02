<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use App\Helpers\CommonHelper;

class CardBalanceController extends Controller
{
    public function showCheckBalanceForm()
    {
        return view('card.balance');
    }

    public function checkBalance(Request $request)
    {
        // Validate the request input
        try {
            $request->validate([
                'cardNumber' => 'required|digits:16',
                'pin' => 'required|digits:6',
                'sku' => 'nullable|string|max:30',
            ], [
                'cardNumber.digits' => 'Card number must be exactly 16 digits.',
                'pin.digits' => 'PIN must be exactly 6 digits.',
                'sku.max' => 'SKU cannot be longer than 30 characters.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed for balance check request', [
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);
            return redirect()->back()->withErrors($e->errors());
        }

        // Prepare data for API request
        $data = [
            "cardNumber" => $request->input('cardNumber'),
            "pin" => $request->input('pin'),
        ];

        if ($request->filled('sku')) {
            $data["sku"] = $request->input('sku');
        }

        $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/balance';
        $clientSecret = setting("api.qs_clientSecret");
        $bearerToken = setting("api.bearer_token");
        $requestHttpMethod = "post";
        $requestBody = json_encode($data);

        // Generate signature
        $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
        $dateAtClient = Carbon::now()->toIso8601String();

        Log::info("Sending Woohoo Balance Check API Request", [
            "URL" => $absApiUrl,
            "Request Body" => $data,
        ]);

        try {
            // Make API call
            $response = Http::withHeaders([
                "Authorization" => "Bearer " . $bearerToken,
                "signature" => $signature,
                "dateAtClient" => $dateAtClient,
                "Content-Type" => "application/json",
            ])->post($absApiUrl, $data);

            if ($response->successful()) {
                $responseData = $response->json();
                return redirect()->back()->with('response', $responseData);
            }

            // Handle API errors
            $errorCode = $response->json('code') ?? $response->status();
            $errorMessage = $response->json('message') ?? 'An error occurred while fetching the balance.';
            $responseBody = $response->body();

            Log::warning("API Response Error", [
                'status_code' => $response->status(),
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'response_body' => $responseBody,
            ]);

            $userFriendlyMessage = $responseBody === 'Service temporary unavailable'
                ? 'The service is temporarily unavailable. Please try again later.'
                : $errorMessage;

            return redirect()->back()->withErrors(['error' => "Error $errorCode: $userFriendlyMessage"]);

        } catch (ConnectionException $e) {
            Log::error("Connection error during Balance Check API call", [
                'message' => $e->getMessage()
            ]);
            return redirect()->back()->withErrors(['error' => 'Unable to connect to the API. Please try again later.']);
        } catch (Exception $e) {
            Log::error("Unexpected error during Balance Check API call", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->withErrors(['error' => 'An unexpected error occurred. Please try again later.']);
        }
    }

}
