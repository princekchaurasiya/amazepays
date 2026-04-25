<?php

namespace App\Console\Commands;

use App\Helpers\ApiSignatureHelper;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestWoohooFetchCards extends Command
{
    protected $signature = 'woohoo:test-fetch-cards 
                            {--order-id= : Woohoo Order ID to fetch cards for}
                            {--refno= : Reference number to fetch cards for}';

    protected $description = 'Test Woohoo Activated Cards API to fetch card details';

    public function handle()
    {
        $orderId = $this->option('order-id');
        $refno = $this->option('refno');

        if (! $orderId && ! $refno) {
            $this->error('❌ Either --order-id or --refno must be provided');

            return 1;
        }

        $this->info('=== Woohoo Activated Cards API Test ===');
        if ($orderId) {
            $this->info("Order ID: {$orderId}");
        }
        if ($refno) {
            $this->info("Reference Number: {$refno}");
        }
        $this->newLine();

        try {
            $result = $this->fetchActivatedCards($orderId, $refno);

            if ($result['success']) {
                $this->info('✅ Test PASSED: Cards fetched successfully');
                $this->displayResults($result);
            } else {
                $this->error('❌ Test FAILED');
                $this->error('Error: '.($result['error'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            $this->error('❌ Test EXCEPTION: '.$e->getMessage());
            Log::error('Woohoo fetch cards exception', [
                'order_id' => $orderId,
                'refno' => $refno,
                'error' => $e->getMessage(),
            ]);
        }

        return 0;
    }

    private function fetchActivatedCards($orderId, $refno)
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

        // Build URL - Activated Cards API uses orderId
        if ($orderId) {
            $absApiUrl = 'https://'.$woohooUrl.'/rest/v3/orders/'.$orderId.'/activatedCards';
        } else {
            // If only refno is provided, we need to get orderId first
            // For now, return error - in production, you'd look up orderId from refno
            return [
                'success' => false,
                'error' => 'Order ID is required for Activated Cards API. Use --order-id option.',
            ];
        }

        $requestHttpMethod = 'get';
        $requestBody = '';
        $signature = ApiSignatureHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
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
            $this->info('Response Body: [redacted]');

            // Validate response
            if ($statusCode === 200 && isset($responseData['cards']) && is_array($responseData['cards'])) {
                $cardCount = count($responseData['cards']);

                if ($cardCount > 0) {
                    // Validate card structure
                    $firstCard = $responseData['cards'][0];
                    $hasCardNumber = isset($firstCard['cardNumber']);
                    $hasCardPin = isset($firstCard['cardPin']) || isset($firstCard['pin']);
                    $hasAmount = isset($firstCard['amount']);

                    if ($hasCardNumber && ($hasCardPin || isset($firstCard['activation_url']))) {
                        return [
                            'success' => true,
                            'data' => $responseData,
                            'card_count' => $cardCount,
                            'cards' => [],
                        ];
                    } else {
                        return [
                            'success' => false,
                            'error' => 'Card structure incomplete',
                            'data' => $responseData,
                        ];
                    }
                } else {
                    return [
                        'success' => false,
                        'error' => 'No cards found in response',
                        'data' => $responseData,
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'Invalid response or error from API',
                    'status_code' => $statusCode,
                    'data' => $responseData,
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception: '.$e->getMessage(),
            ];
        }
    }

    private function displayResults($result)
    {
        $this->newLine();
        $this->info('=== Test Results ===');

        if (isset($result['card_count'])) {
            $this->info('Cards Fetched: '.$result['card_count']);
        }

        if (isset($result['cards']) && is_array($result['cards'])) {
            $this->newLine();
            $this->info('Card Details:');
            foreach ($result['cards'] as $index => $card) {
                $this->info('  Card '.($index + 1).':');
                if (isset($card['cardNumber'])) {
                    $this->info('    Card Number: '.substr($card['cardNumber'], 0, 4).'****'.substr($card['cardNumber'], -4));
                }
                if (isset($card['cardPin']) || isset($card['pin'])) {
                    $pin = $card['cardPin'] ?? $card['pin'];
                    $this->info('    PIN: '.(strlen($pin) > 4 ? substr($pin, 0, 2).'****' : '****'));
                }
                if (isset($card['amount'])) {
                    $this->info('    Amount: '.$card['amount']);
                }
                if (isset($card['validity'])) {
                    $this->info('    Validity: '.$card['validity']);
                }
                if (isset($card['activation_url'])) {
                    $this->info('    Activation URL: '.$card['activation_url']);
                }
            }
        }
    }
}
