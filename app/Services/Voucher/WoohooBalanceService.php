<?php

namespace App\Services\Voucher;

use App\Helpers\ApiSignatureHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WoohooBalanceService
{
    /**
     * @return array{success: bool, status_code: int, data?: array<string,mixed>, message?: string}
     */
    public function check(string $cardNumber, ?string $pin = null, ?string $sku = null): array
    {
        $payload = array_filter([
            'cardNumber' => $cardNumber,
            'pin' => $pin,
            'sku' => $sku,
        ], static fn ($value) => $value !== null && $value !== '');

        $host = (string) config('woohoo.host');
        $clientSecret = (string) config('woohoo.client_secret');
        $bearerToken = (string) config('woohoo.bearer_token');
        $apiUrl = 'https://'.rtrim($host, '/').'/rest/v3/balance';

        if ($host === '' || $clientSecret === '' || $bearerToken === '') {
            return [
                'success' => false,
                'status_code' => 503,
                'message' => 'Woohoo credentials are not configured.',
            ];
        }

        $requestBody = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = ApiSignatureHelper::generateSignature($requestBody ?: '', 'post', $apiUrl, $clientSecret);
        $dateAtClient = Carbon::now()->toIso8601String();

        try {
            $response = Http::acceptJson()
                ->timeout(20)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$bearerToken,
                    'Accept' => '*/*',
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                ])->post($apiUrl, $payload);

            $body = $response->json();
            if ($response->successful()) {
                return [
                    'success' => true,
                    'status_code' => $response->status(),
                    'data' => is_array($body) ? $body : [],
                ];
            }

            return [
                'success' => false,
                'status_code' => $response->status(),
                'message' => is_array($body) ? ((string) ($body['message'] ?? 'Balance enquiry failed.')) : 'Balance enquiry failed.',
                'data' => is_array($body) ? $body : [],
            ];
        } catch (\Throwable $e) {
            Log::warning('Woohoo balance check failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Unable to fetch balance right now. Please try again later.',
            ];
        }
    }
}
