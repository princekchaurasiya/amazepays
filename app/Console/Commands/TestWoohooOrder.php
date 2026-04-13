<?php

namespace App\Console\Commands;

use App\Helpers\ApiSignatureHelper;
use App\Models\Order;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestWoohooOrder extends Command
{
    protected $signature = 'woohoo:test-order 
                            {--sku=CNPIN : Product SKU to test}
                            {--amount=1 : Order amount/denomination}
                            {--qty=1 : Quantity of cards}
                            {--sync-only=true : Use sync_only mode}
                            {--scenario=success-single : Test scenario name}
                            {--refno= : Custom reference number}';

    protected $description = 'Test Woohoo Order API with various scenarios';

    public function handle()
    {
        $sku = $this->option('sku');
        $amount = (float) $this->option('amount');
        $qty = (int) $this->option('qty');
        $syncOnly = $this->option('sync-only') === 'true';
        $scenario = $this->option('scenario');
        $refno = $this->option('refno') ?? 'Amz'.date('YmdHis').rand(1000, 9999);

        $this->info('=== Woohoo Order API Test ===');
        $this->info("SKU: {$sku}");
        $this->info("Amount: {$amount}");
        $this->info("Quantity: {$qty}");
        $this->info('Sync Only: '.($syncOnly ? 'Yes' : 'No'));
        $this->info("Scenario: {$scenario}");
        $this->info("Reference Number: {$refno}");
        $this->newLine();

        try {
            $result = $this->testOrderCreation($sku, $amount, $qty, $syncOnly, $refno, $scenario);

            if ($result['success']) {
                $this->info("✅ Test PASSED: {$scenario}");
                $this->displayResults($result);
            } else {
                $this->error("❌ Test FAILED: {$scenario}");
                $this->error('Error: '.($result['error'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            $this->error('❌ Test EXCEPTION: '.$e->getMessage());
            Log::error('Woohoo test order exception', [
                'scenario' => $scenario,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return 0;
    }

    private function testOrderCreation($sku, $amount, $qty, $syncOnly, $refno, $scenario)
    {
        $woohooUrl = config('woohoo.host');
        $clientSecret = config('woohoo.client_secret');
        $bearerToken = config('woohoo.bearer_token');

        if (! $woohooUrl || ! $clientSecret || ! $bearerToken) {
            return [
                'success' => false,
                'error' => 'Woohoo API credentials not configured',
            ];
        }

        // Get product ID from database
        $product = DB::table('products')->where('sku', $sku)->first();
        if (! $product) {
            return [
                'success' => false,
                'error' => "Product with SKU {$sku} not found in database",
            ];
        }

        $productId = $product->product_id ?? $product->id ?? null;
        if (! $productId) {
            return [
                'success' => false,
                'error' => "Product ID not found for SKU {$sku}",
            ];
        }

        // Prepare request body
        $requestBody = [
            'productId' => $productId,
            'amount' => $amount,
            'qty' => $qty,
            'delivery_mode' => 'API',
            'sync_only' => $syncOnly,
            'refno' => $refno,
            'payment_method' => 'svc',
            'billing' => [
                'firstName' => 'Test',
                'lastName' => 'User',
                'email' => 'test@example.com',
                'phone' => '1234567890',
                'address' => '123 Test Street',
                'city' => 'Test City',
                'state' => 'Test State',
                'zip' => '12345',
                'country' => 'IN',
            ],
            'shipping' => [
                'firstName' => 'Test',
                'lastName' => 'User',
                'email' => 'test@example.com',
                'phone' => '1234567890',
                'address' => '123 Test Street',
                'city' => 'Test City',
                'state' => 'Test State',
                'zip' => '12345',
                'country' => 'IN',
            ],
        ];

        $absApiUrl = 'https://'.$woohooUrl.'/rest/v3/orders';
        $requestHttpMethod = 'post';
        $jsonBody = json_encode($requestBody);
        $signature = ApiSignatureHelper::generateSignature($jsonBody, $requestHttpMethod, $absApiUrl, $clientSecret);
        $dateAtClient = Carbon::now()->toIso8601String();

        $this->info("Sending request to: {$absApiUrl}");
        $this->info('Request body: '.json_encode($requestBody, JSON_PRETTY_PRINT));

        $startTime = microtime(true);

        try {
            $response = Http::timeout(env('WOOHOO_TIMEOUT', 10))
                ->acceptJson()
                ->withToken($bearerToken)
                ->withHeaders([
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                    'Accept' => '*/*',
                    'User-Agent' => 'Amazepays/1.0 (+https://amazepays.in)',
                    'Content-Type' => 'application/json',
                ])
                ->post($absApiUrl, $requestBody);

            $elapsedTime = round((microtime(true) - $startTime) * 1000, 2);
            $statusCode = $response->status();
            $responseData = $response->json();

            $this->newLine();
            $this->info("Response Status: {$statusCode}");
            $this->info("Response Time: {$elapsedTime}ms");
            $this->info('Response Body: '.json_encode($responseData, JSON_PRETTY_PRINT));

            // Validate response based on scenario
            return $this->validateResponse($responseData, $statusCode, $scenario, $qty);

        } catch (ConnectionException $e) {
            $this->warn('⚠️  Connection timeout/error occurred');
            $this->info('This might be expected for timeout scenarios');

            // For timeout scenarios, check order status after delay
            if (strpos($scenario, 'timeout') !== false) {
                $this->info('Waiting 40 seconds before checking order status...');
                sleep(40);

                return $this->checkOrderStatus($refno, $scenario);
            }

            return [
                'success' => false,
                'error' => 'Connection timeout: '.$e->getMessage(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception: '.$e->getMessage(),
            ];
        }
    }

    private function validateResponse($responseData, $statusCode, $scenario, $qty)
    {
        $result = [
            'success' => false,
            'data' => $responseData,
            'status_code' => $statusCode,
        ];

        // Check for error responses
        if (isset($responseData['code']) && $responseData['code'] != 200) {
            $result['error'] = $responseData['message'] ?? 'Unknown error';

            // Some scenarios expect errors
            if (in_array($scenario, ['validation-error', 'disabled-product', 'duplicate-refno', 'multiple-skus'])) {
                $result['success'] = true;
                $result['expected_error'] = true;
            }

            return $result;
        }

        // Success scenarios
        if (isset($responseData['status'])) {
            $status = strtoupper($responseData['status']);

            if ($status === 'COMPLETE') {
                // Validate complete response
                if (isset($responseData['cards']) && is_array($responseData['cards'])) {
                    $cardCount = count($responseData['cards']);

                    if ($qty === 1 && $cardCount === 1) {
                        $card = $responseData['cards'][0];
                        if (isset($card['cardNumber']) && isset($card['cardPin'])) {
                            $result['success'] = true;
                            $result['cards_received'] = $cardCount;
                        }
                    } elseif ($qty > 1) {
                        // For multiple cards, they might be empty initially
                        $result['success'] = true;
                        $result['note'] = 'Cards may need to be fetched via Activated Cards API';
                    }
                }
            } elseif ($status === 'PROCESSING') {
                // Processing status is valid for some scenarios
                if (in_array($scenario, ['success-multiple', 'processing-status'])) {
                    $result['success'] = true;
                    $result['note'] = 'Order is processing, cards will be available later';
                }
            }
        }

        // Check for success flag
        if (isset($responseData['success']) && $responseData['success'] === true) {
            $result['success'] = true;
        }

        return $result;
    }

    private function checkOrderStatus($refno, $scenario)
    {
        $this->info("Checking order status for refno: {$refno}");

        $woohooUrl = config('woohoo.host');
        $clientSecret = config('woohoo.client_secret');
        $bearerToken = config('woohoo.bearer_token');

        $absApiUrl = 'https://'.$woohooUrl.'/rest/v3/orders/status/'.$refno;
        $requestHttpMethod = 'get';
        $requestBody = '';
        $signature = ApiSignatureHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
        $dateAtClient = Carbon::now()->toIso8601String();

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

            $this->info('Status API Response: '.json_encode($responseData, JSON_PRETTY_PRINT));

            if (isset($responseData['status'])) {
                $status = strtoupper($responseData['status']);
                if ($status === 'COMPLETE' || $status === 'SUCCESS') {
                    return [
                        'success' => true,
                        'data' => $responseData,
                        'note' => 'Order completed after timeout, cards need to be fetched',
                    ];
                } else {
                    return [
                        'success' => $scenario === 'timeout-failure',
                        'data' => $responseData,
                        'note' => 'Order status: '.$status,
                    ];
                }
            }

            return [
                'success' => false,
                'error' => 'Invalid status response',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Status check failed: '.$e->getMessage(),
            ];
        }
    }

    private function displayResults($result)
    {
        $this->newLine();
        $this->info('=== Test Results ===');

        if (isset($result['cards_received'])) {
            $this->info('Cards Received: '.$result['cards_received']);
        }

        if (isset($result['note'])) {
            $this->info('Note: '.$result['note']);
        }

        if (isset($result['expected_error'])) {
            $this->info('Expected Error: '.($result['error'] ?? 'N/A'));
        }

        if (isset($result['data']['orderId'])) {
            $this->info('Order ID: '.$result['data']['orderId']);
        }

        if (isset($result['data']['refno'])) {
            $this->info('Reference Number: '.$result['data']['refno']);
        }
    }
}
