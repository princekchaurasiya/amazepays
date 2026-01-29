<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class TestWoohooStatus extends Command
{
    protected $signature = 'woohoo:test-status 
                            {refno : Reference number to check order status}';

    protected $description = 'Test Woohoo Order Status API';

    public function handle()
    {
        $refno = $this->argument('refno');

        $this->info("=== Woohoo Order Status API Test ===");
        $this->info("Reference Number: {$refno}");
        $this->newLine();

        try {
            $result = $this->checkOrderStatus($refno);
            
            if ($result['success']) {
                $this->info("✅ Test PASSED: Status retrieved successfully");
                $this->displayResults($result);
            } else {
                $this->error("❌ Test FAILED");
                $this->error("Error: " . ($result['error'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            $this->error("❌ Test EXCEPTION: " . $e->getMessage());
            Log::error("Woohoo status test exception", [
                'refno' => $refno,
                'error' => $e->getMessage()
            ]);
        }

        return 0;
    }

    private function checkOrderStatus($refno)
    {
        $woohooUrl = setting('api.woohoo_url');
        $clientSecret = setting('api.qs_clientSecret');
        $bearerToken = setting('api.bearer_token');

        if (!$woohooUrl || !$clientSecret || !$bearerToken) {
            return [
                'success' => false,
                'error' => 'Woohoo API credentials not configured'
            ];
        }

        $absApiUrl = 'https://' . $woohooUrl . '/rest/v3/orders/status/' . $refno;
        $requestHttpMethod = 'get';
        $requestBody = '';
        $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
        $dateAtClient = Carbon::now()->toIso8601String();

        $this->info("Sending request to: {$absApiUrl}");

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->withToken($bearerToken)
                ->withHeaders([
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                    'Accept' => '*/*',
                    'User-Agent' => 'Amazepays/1.0 (+https://amazepays.in)',
                ])
                ->get($absApiUrl);

            $statusCode = $response->status();
            $responseData = $response->json();

            $this->newLine();
            $this->info("Response Status: {$statusCode}");
            $this->info("Response Body: " . json_encode($responseData, JSON_PRETTY_PRINT));

            if ($statusCode === 200 && isset($responseData['status'])) {
                return [
                    'success' => true,
                    'data' => $responseData,
                    'order_status' => $responseData['status']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Invalid response or status not found',
                    'status_code' => $statusCode,
                    'data' => $responseData
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    private function displayResults($result)
    {
        $this->newLine();
        $this->info("=== Test Results ===");
        
        if (isset($result['order_status'])) {
            $this->info("Order Status: " . $result['order_status']);
        }

        if (isset($result['data']['orderId'])) {
            $this->info("Order ID: " . $result['data']['orderId']);
        }

        if (isset($result['data']['message'])) {
            $this->info("Message: " . $result['data']['message']);
        }
    }
}
