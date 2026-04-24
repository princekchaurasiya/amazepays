<?php

namespace App\Services\Payment;

use App\Contracts\PaymentCallbackResult;
use App\Contracts\PaymentGatewayInterface;
use App\Contracts\PaymentInitiateResult;
use App\Contracts\PaymentStatusResult;
use App\Contracts\RefundResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UnlimitGateway implements PaymentGatewayInterface
{
    private array $config;

    public function __construct(array $credentials = [])
    {
        $this->config = empty($credentials) ? [
            'api_base_url' => rtrim((string) config('unlimit.api_base_url'), '/'),
            'auth_token_endpoint' => (string) config('unlimit.endpoints.auth_token', '/api/auth/token'),
            'payments_endpoint' => (string) config('unlimit.endpoints.payments', '/api/payments'),
            'basic_code' => (string) env('UNLIMIT_CODE', ''),
            'terminal_code' => (string) env('UNLIMIT_PUBLIC_KEY', ''),
            'password' => (string) env('UNLIMIT_SECRET_KEY', ''),
        ] : $credentials;
    }

    public function getName(): string
    {
        return 'unlimit';
    }

    public function supportsRecurring(): bool
    {
        return false;
    }

    public function initiatePayment(array $order): PaymentInitiateResult
    {
        try {
            $token = $this->getAccessToken();

            $now = now()->setTimezone((string) config('app.timezone', 'Asia/Kolkata'));
            // Indian standard: ISO-8601 with milliseconds + explicit offset (e.g. +05:30).
            $time = $now->format("Y-m-d\TH:i:s.").$now->format('v').$now->format('P');

            $apiBase = rtrim((string) $this->config['api_base_url'], '/');
            $paymentsUrl = $apiBase.(string) ($this->config['payments_endpoint'] ?? '/api/payments');

            $paymentMethod = (string) ($order['payment_method'] ?? 'bankcard');

            $payload = [
                'request' => [
                    'id' => (string) Str::uuid(),
                    'time' => $time,
                ],
                'merchant_order' => [
                    'id' => (string) $order['order_id'],
                    'description' => (string) ($order['product_name'] ?? 'Voucher Purchase'),
                ],
                'payment_method' => $paymentMethod,
                'payment_data' => [
                    'amount' => (float) ($order['amount'] ?? 0),
                    'currency' => (string) ($order['currency'] ?? 'INR'),
                ],
                'return_urls' => [
                    // Keep existing URL stable. Both resolve to the same route.
                    'success_url' => route('unlimit.return'),
                    'decline_url' => route('unlimit.return'),
                ],
            ];

            // For server-to-server callbacks (if enabled by Unlimit), keep it on the API v1 webhook route.
            try {
                $payload['return_urls']['callback_url'] = route('api.v1.webhooks.unlimit');
            } catch (\Throwable) {
                // Route may not exist in some environments; ignore.
            }

            $response = Http::withToken($token)->post($paymentsUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();

                return new PaymentInitiateResult(
                    success: true,
                    redirectUrl: $data['redirect_url'] ?? null,
                    paymentToken: $data['payment_data']['id'] ?? null,
                    gatewayOrderId: $data['merchant_order']['id'] ?? ($data['merchant_order_id'] ?? $order['order_id'] ?? null),
                    raw: $data,
                );
            }

            throw new \RuntimeException('Unlimit API error: '.$response->body());
        } catch (\Exception $e) {
            Log::error('Unlimit initiate payment failed', ['error' => $e->getMessage()]);

            return new PaymentInitiateResult(success: false, error: $e->getMessage());
        }
    }

    public function handleCallback(array $payload): PaymentCallbackResult
    {
        // Signature is verified upstream by VerifyUnlimitSignature middleware
        $paymentData = $payload['payment_data'] ?? [];
        $status = strtolower($paymentData['status'] ?? '');

        $mappedStatus = match ($status) {
            'completed' => 'paid',
            'declined' => 'failed',
            'terminated' => 'cancelled',
            default => 'pending',
        };

        return new PaymentCallbackResult(
            success: $mappedStatus === 'paid',
            status: $mappedStatus,
            transactionId: $paymentData['id'] ?? null,
            gatewayOrderId: $payload['merchant_order']['id'] ?? null,
            amount: isset($paymentData['amount']) ? (float) $paymentData['amount'] : null,
            paymentMethod: $paymentData['payment_method'] ?? null,
            raw: $payload,
        );
    }

    public function refund(string $transactionId, float $amount, string $reason): RefundResult
    {
        try {
            return new RefundResult(
                success: false,
                error: 'Refunds are not implemented for Unlimit in this codebase yet.',
            );
        } catch (\Exception $e) {
            Log::error('Unlimit refund failed', ['error' => $e->getMessage()]);

            return new RefundResult(success: false, error: $e->getMessage());
        }
    }

    public function queryStatus(string $transactionId): PaymentStatusResult
    {
        try {
            $token = $this->getAccessToken();

            $apiBase = rtrim((string) $this->config['api_base_url'], '/');
            $paymentsUrl = $apiBase.(string) ($this->config['payments_endpoint'] ?? '/api/payments');

            // Unlimit APIs differ across terminals; try a permissive query.
            // We send both request_id and merchant_order_id to maximize compatibility.
            $params = [
                'request_id' => $transactionId,
                'merchant_order_id' => $transactionId,
            ];

            $response = Http::withToken($token)->get($paymentsUrl, $params);
            if (! $response->successful()) {
                throw new \RuntimeException('Unlimit status API error: '.$response->body());
            }

            $data = $response->json();
            if (is_object($data)) {
                $data = json_decode(json_encode($data), true);
            }
            if (! is_array($data)) {
                $data = [];
            }

            // Some endpoints return { list: [ { payment_data: { status, amount, ... } } ] }
            $paymentData = null;
            if (isset($data['list']) && is_array($data['list']) && $data['list'] !== []) {
                $paymentData = $data['list'][0]['payment_data'] ?? ($data['list'][0] ?? null);
            } else {
                $paymentData = $data['payment_data'] ?? $data;
            }

            if (is_object($paymentData)) {
                $paymentData = json_decode(json_encode($paymentData), true);
            }
            if (! is_array($paymentData)) {
                $paymentData = [];
            }

            $rawStatus = strtolower((string) ($paymentData['status'] ?? $data['status'] ?? ''));
            $mappedStatus = match ($rawStatus) {
                'completed', 'success', 'approved', 'confirmed', 'paid' => 'paid',
                'declined', 'failed' => 'failed',
                'terminated', 'cancelled' => 'cancelled',
                default => 'pending',
            };

            $amount = null;
            if (isset($paymentData['amount']) && is_numeric($paymentData['amount'])) {
                $amount = (float) $paymentData['amount'];
            }

            return new PaymentStatusResult(
                success: true,
                status: $mappedStatus,
                amount: $amount,
                raw: is_array($data) ? $data : [],
            );
        } catch (\Exception $e) {
            return new PaymentStatusResult(success: false, status: 'unknown', error: $e->getMessage());
        }
    }

    private function getAccessToken(): string
    {
        $apiBase = rtrim((string) $this->config['api_base_url'], '/');
        $authUrl = $apiBase.(string) ($this->config['auth_token_endpoint'] ?? '/api/auth/token');

        $basicCode = (string) ($this->config['basic_code'] ?? '');
        $terminalCode = (string) ($this->config['terminal_code'] ?? '');
        $password = (string) ($this->config['password'] ?? '');

        if ($basicCode === '' || $terminalCode === '' || $password === '') {
            throw new \RuntimeException('Unlimit credentials missing.');
        }

        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic '.base64_encode($basicCode),
            ])
            ->post($authUrl, [
                'grant_type' => 'password',
                'password' => $password,
                'terminal_code' => $terminalCode,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to get Unlimit access token.');
        }

        $token = $response->json('access_token');
        if (! is_string($token) || $token === '') {
            throw new \RuntimeException('Unlimit token missing from response.');
        }

        return $token;
    }
}
