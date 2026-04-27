<?php

namespace App\Services\Order;

use App\Helpers\ApiSignatureHelper;
use App\Services\Providers\WoohooBearerTokenStore;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class WoohooApiService
{
    /**
     * @param  array<string, mixed>  $createOrderPayload
     * @return array<string, mixed>
     */
    public function createOrder(array $createOrderPayload, string $userAgent = 'Amazepays/1.0 (+https://amazepays.in)'): array
    {
        $woohooTimeout = (int) env('WOOHOO_ORDER_TIMEOUT', 30);
        $woohooRetryAttempts = (int) env('WOOHOO_ORDER_RETRY_ATTEMPTS', 2);
        $woohooRetryDelay = (int) env('WOOHOO_ORDER_RETRY_DELAY_MS', 1500);

        $requestBody = json_encode($createOrderPayload);
        if (! is_string($requestBody)) {
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Failed to encode request.',
            ];
        }

        $absApiUrl = 'https://'.config('woohoo.host').'/rest/v3/orders';
        $clientSecret = (string) config('woohoo.client_secret');
        $bearerToken = (string) (config('woohoo.bearer_token') ?: app(WoohooBearerTokenStore::class)->get() ?: '');
        $signature = ApiSignatureHelper::generateSignature($requestBody, 'post', $absApiUrl, $clientSecret);
        $dateAtClient = Carbon::now()->toIso8601String();
        $refno = (string) ($createOrderPayload['refno'] ?? '');

        try {
            $createOrderResponse = Http::acceptJson()
                ->timeout($woohooTimeout)
                ->retry($woohooRetryAttempts, $woohooRetryDelay, function ($exception) {
                    return $exception instanceof ConnectionException;
                })
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$bearerToken,
                    'Accept' => '*/*',
                    'User-Agent' => $userAgent,
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                ])
                ->send('POST', $absApiUrl, ['body' => $requestBody]);
        } catch (\Throwable $e) {
            Log::error('Woohoo create order request failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status_code' => 500,
                'message' => $this->userFriendlyMessage(500),
            ];
        }

        $statusCode = $createOrderResponse->status();
        $responseData = null;
        try {
            $responseData = $createOrderResponse->json();
        } catch (\Throwable) {
            $responseData = null;
        }

        if (is_object($responseData)) {
            $responseData = json_decode(json_encode($responseData), true);
        }
        if (! is_array($responseData)) {
            $responseData = [];
        }

        // Woohoo can return "Duplicate reference number provided" when the same refno is retried.
        // In that case, treat it as idempotent: fetch status by refno and return that result.
        if ($statusCode === 400 && (int) ($responseData['code'] ?? 0) === 5313 && $refno !== '') {
            try {
                $status = $this->getStatusByReferenceNumberLightweight($refno);
                $st = strtoupper((string) ($status['status'] ?? ''));

                if ($st === 'COMPLETE') {
                    return [
                        'success' => true,
                        'status' => 'COMPLETE',
                        'data' => $status,
                        'idempotent' => true,
                    ];
                }

                if ($st === 'PROCESSING' || $st === 'PENDING') {
                    return [
                        'success' => true,
                        'status' => 'PROCESSING',
                        'data' => $status,
                        'idempotent' => true,
                    ];
                }

                return [
                    'success' => false,
                    'status_code' => 409,
                    'message' => 'Duplicate reference number. Unable to confirm status.',
                    'data' => $status,
                ];
            } catch (\Throwable $e) {
                Log::warning('Woohoo duplicate refno status check failed', [
                    'refno' => $refno,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($createOrderResponse->successful() && ($responseData['status'] ?? null) === 'COMPLETE') {
            return [
                'success' => true,
                'status' => 'COMPLETE',
                'data' => $responseData,
            ];
        }

        return [
            'success' => false,
            'status_code' => $statusCode,
            'message' => $this->userFriendlyMessage($statusCode),
            'data' => $responseData,
        ];
    }

    /**
     * Full status poll with retries (legacy behavior).
     *
     * @return array<string, mixed>
     */
    public function getStatusByReferenceNumber(string $refno): array
    {
        $requestHttpMethod = 'GET';
        $absApiUrl = 'https://'.config('woohoo.host').'/rest/v3/order/'.$refno.'/status';
        $clientSecret = (string) config('woohoo.client_secret');
        $bearerToken = (string) (config('woohoo.bearer_token') ?: app(WoohooBearerTokenStore::class)->get() ?: '');
        $signature = ApiSignatureHelper::generateSignature('', $requestHttpMethod, $absApiUrl, $clientSecret);
        $dateAtClient = Carbon::now()->toIso8601String();

        $retryCount = (int) env('RETRY_COUNT', 0);
        $maxRetries = (int) env('MAX_RETRIES', 3);
        $retryInterval = (int) env('RETRY_INTERVAL', 40);
        $startTime = Carbon::now();

        while ($retryCount < $maxRetries) {
            try {
                $retryCount++;
                $elapsedSeconds = $startTime->diffInSeconds(Carbon::now());
                Log::info('Woohoo status check attempt', ['attempt' => $retryCount, 'elapsed_seconds' => $elapsedSeconds, 'refno' => $refno]);

                $orderStatusResponse = Http::acceptJson()->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$bearerToken,
                    'Accept' => '*/*',
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                ])->get($absApiUrl);

                if ($orderStatusResponse->successful()) {
                    $data = $orderStatusResponse->json();
                    if (is_object($data)) {
                        $data = json_decode(json_encode($data), true);
                    }
                    if (! is_array($data)) {
                        $data = [];
                    }

                    $status = $data['status'] ?? null;
                    if ($status === 'COMPLETE') {
                        return $this->callCardActivation($data);
                    }
                    if ($status === 'PROCESSING') {
                        sleep($retryInterval);

                        continue;
                    }

                    return [
                        'transactionStatusMessage' => 'Order status: '.(string) $status,
                        'status_code' => 200,
                        'status' => $status,
                        'isSuccessful' => false,
                    ];
                }

                return [
                    'transactionStatusMessage' => __('errors.7003'),
                    'status_code' => $orderStatusResponse->status(),
                    'status' => null,
                    'errorCode' => '7003',
                    'errorMessage' => __('errors.7003'),
                    'defaultErrorMessage' => __('errors.default'),
                    'isSuccessful' => false,
                ];
            } catch (ConnectionException $exception) {
                return [
                    'transactionStatusMessage' => $exception->getMessage(),
                    'status_code' => 500,
                    'status' => null,
                    'errorCode' => '7001',
                    'errorMessage' => __('errors.7001'),
                    'defaultErrorMessage' => __('errors.default'),
                    'isSuccessful' => false,
                ];
            } catch (\Throwable $e) {
                return [
                    'transactionStatusMessage' => $e->getMessage(),
                    'status_code' => 500,
                    'status' => null,
                    'errorCode' => '7002',
                    'errorMessage' => __('errors.7002'),
                    'defaultErrorMessage' => __('errors.default'),
                    'isSuccessful' => false,
                ];
            }
        }

        return [
            'transactionStatusMessage' => __('errors.5321'),
            'status_code' => 400,
            'status' => null,
            'errorCode' => '5321',
            'errorMessage' => __('errors.5321'),
            'defaultErrorMessage' => __('errors.default'),
            'isSuccessful' => false,
        ];
    }

    /**
     * Lightweight status check (frontend polling).
     *
     * @return array<string, mixed>
     */
    public function getStatusByReferenceNumberLightweight(string $refno): array
    {
        try {
            $requestHttpMethod = 'GET';
            $absApiUrl = 'https://'.config('woohoo.host').'/rest/v3/order/'.$refno.'/status';
            $clientSecret = (string) config('woohoo.client_secret');
            $bearerToken = (string) (config('woohoo.bearer_token') ?: app(WoohooBearerTokenStore::class)->get() ?: '');
            $signature = ApiSignatureHelper::generateSignature('', $requestHttpMethod, $absApiUrl, $clientSecret);
            $dateAtClient = Carbon::now()->toIso8601String();

            $orderStatusResponse = Http::acceptJson()
                ->timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$bearerToken,
                    'Accept' => '*/*',
                    'dateAtClient' => $dateAtClient,
                    'signature' => $signature,
                ])
                ->get($absApiUrl);

            if ($orderStatusResponse->successful()) {
                $data = $orderStatusResponse->json();
                if (is_object($data)) {
                    $data = json_decode(json_encode($data), true);
                }
                if (! is_array($data)) {
                    $data = [];
                }

                $status = $data['status'] ?? null;
                if ($status === 'COMPLETE') {
                    return $this->callCardActivation($data);
                }
                if ($status === 'PROCESSING') {
                    return ['status' => 'PROCESSING', 'message' => 'Transaction is still processing'];
                }

                return ['status' => $status ?? 'unknown', 'message' => 'Transaction status: '.($status ?? 'unknown')];
            }

            return ['status' => 'error', 'message' => 'API call failed'];
        } catch (\Throwable $e) {
            Log::error('Lightweight status check error', ['error' => $e->getMessage()]);

            return ['status' => 'error', 'message' => 'Status check failed'];
        }
    }

    /**
     * @param  array<string, mixed>  $cardStatusApiResponseData
     * @return array<string, mixed>
     */
    public function callCardActivation(array $cardStatusApiResponseData): array
    {
        $orderId = (string) ($cardStatusApiResponseData['orderId'] ?? '');
        if ($orderId === '') {
            return [
                'transactionStatusMessage' => __('errors.7002'),
                'status_code' => 500,
                'errorCode' => '7002',
                'errorMessage' => __('errors.7002'),
                'defaultErrorMessage' => __('errors.default'),
                'isSuccessful' => false,
            ];
        }

        $clientSecret = (string) config('woohoo.client_secret');
        $bearerToken = (string) (config('woohoo.bearer_token') ?: app(WoohooBearerTokenStore::class)->get() ?: '');
        $apiUrl = 'https://'.config('woohoo.host');
        $absApiUrl = "$apiUrl/rest/v3/order/{$orderId}/cards";
        $dateAtClient = Carbon::now()->toIso8601String();
        $signature = ApiSignatureHelper::generateSignature('', 'GET', $absApiUrl, $clientSecret);

        try {
            $activatedCardApiResponse = Http::acceptJson()
                ->withToken($bearerToken)
                ->withHeaders(['signature' => $signature, 'dateAtClient' => $dateAtClient])
                ->get($absApiUrl);

            if (! $activatedCardApiResponse->successful()) {
                return [
                    'transactionStatusMessage' => __('errors.7002'),
                    'status_code' => 500,
                    'errorCode' => '7002',
                    'errorMessage' => __('errors.7002'),
                    'defaultErrorMessage' => __('errors.default'),
                    'isSuccessful' => false,
                ];
            }

            $activatedCardApiResponseData = $activatedCardApiResponse->json();
            if (is_object($activatedCardApiResponseData)) {
                $activatedCardApiResponseData = json_decode(json_encode($activatedCardApiResponseData), true);
            }
            if (! is_array($activatedCardApiResponseData)) {
                $activatedCardApiResponseData = [];
            }

            return array_merge($cardStatusApiResponseData, $activatedCardApiResponseData);
        } catch (ConnectionException $exception) {
            return [
                'transactionStatusMessage' => $exception->getMessage(),
                'status_code' => 500,
                'errorCode' => '7001',
                'errorMessage' => __('errors.7001'),
                'defaultErrorMessage' => __('errors.default'),
                'isSuccessful' => false,
            ];
        } catch (\Throwable $e) {
            return [
                'transactionStatusMessage' => $e->getMessage(),
                'status_code' => 500,
                'errorCode' => '7002',
                'errorMessage' => __('errors.7002'),
                'defaultErrorMessage' => __('errors.default'),
                'isSuccessful' => false,
            ];
        }
    }

    private function userFriendlyMessage(int $statusCode): string
    {
        $messages = [
            400 => 'Invalid request. Please contact support if this issue persists.',
            401 => 'Authentication failed. Please contact support.',
            403 => 'Access denied. Please contact support for assistance.',
            404 => 'Service not found. Please contact support.',
            408 => 'Request timeout. Please try again later.',
            429 => 'Too many requests. Please try again in a few moments.',
            500 => 'Server error. Our team has been notified. Please try again later.',
            502 => 'Service temporarily unavailable. Please try again later.',
            503 => 'Service temporarily unavailable. Please try again later.',
            504 => 'Request timeout. Please try again later.',
        ];

        return $messages[$statusCode] ?? 'An unexpected error occurred. Please contact support if this issue persists.';
    }
}
