<?php

namespace App\Services\Payment;

use App\Contracts\PaymentCallbackResult;
use App\Contracts\PaymentGatewayInterface;
use App\Contracts\PaymentInitiateResult;
use App\Contracts\PaymentStatusResult;
use App\Contracts\RefundResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UnlimitGateway implements PaymentGatewayInterface
{
    private array $config;

    public function __construct(array $credentials = [])
    {
        $this->config = empty($credentials) ? [
            'gateway_account_id' => config('unlimit.GATEWAY_ACCOUNT_ID'),
            'password' => config('unlimit.PASSWORD'),
            'terminal_code' => config('unlimit.TERMINAL_CODE'),
            'callback_secret' => config('unlimit.CALLBACK_SECRET'),
            'base_url' => config('unlimit.BASE_URL', 'https://sandbox.cardpay.com'),
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
            $response = Http::withToken($token)
                ->post("{$this->config['base_url']}/api/v3/payments", [
                    'request' => [
                        'id' => uniqid('unlimit_'),
                        'time' => now()->toISOString(),
                    ],
                    'merchant_order' => [
                        'id' => $order['order_id'],
                        'description' => $order['product_name'] ?? 'Voucher Purchase',
                        'amount' => $order['amount'],
                        'currency' => $order['currency'] ?? 'INR',
                    ],
                    'payment_data' => [
                        'amount' => $order['amount'],
                        'currency' => $order['currency'] ?? 'INR',
                    ],
                    'return_urls' => [
                        'success_url' => route('payment.unlimit.success'),
                        'decline_url' => route('payment.unlimit.decline'),
                        'cancel_url' => route('payment.unlimit.cancel'),
                        'callback_url' => route('payment.unlimit.callback'),
                    ],
                    'customer' => [
                        'email' => $order['customer_email'] ?? '',
                        'full_name' => $order['customer_name'] ?? '',
                        'phone' => $order['customer_phone'] ?? '',
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return new PaymentInitiateResult(
                    success: true,
                    redirectUrl: $data['redirect_url'] ?? null,
                    gatewayOrderId: $data['payment_data']['id'] ?? null,
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
            $token = $this->getAccessToken();
            $response = Http::withToken($token)
                ->post("{$this->config['base_url']}/api/v3/refunds", [
                    'request' => ['id' => uniqid(), 'time' => now()->toISOString()],
                    'payment_data' => ['id' => $transactionId],
                    'refund_data' => [
                        'amount' => $amount,
                        'currency' => 'INR',
                        'comment' => $reason,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return new RefundResult(
                    success: true,
                    refundId: $data['refund_data']['id'] ?? null,
                    raw: $data,
                );
            }

            throw new \RuntimeException('Unlimit refund API error: '.$response->body());
        } catch (\Exception $e) {
            Log::error('Unlimit refund failed', ['error' => $e->getMessage()]);

            return new RefundResult(success: false, error: $e->getMessage());
        }
    }

    public function queryStatus(string $transactionId): PaymentStatusResult
    {
        try {
            $token = $this->getAccessToken();
            $response = Http::withToken($token)
                ->get("{$this->config['base_url']}/api/v3/payments/{$transactionId}");

            if ($response->successful()) {
                $data = $response->json();
                $status = strtolower($data['payment_data']['status'] ?? 'unknown');

                return new PaymentStatusResult(
                    success: true,
                    status: match ($status) {
                        'completed' => 'paid',
                        'declined' => 'failed',
                        default => 'pending',
                    },
                    amount: isset($data['payment_data']['amount']) ? (float) $data['payment_data']['amount'] : null,
                    raw: $data,
                );
            }

            throw new \RuntimeException('Status query failed');
        } catch (\Exception $e) {
            return new PaymentStatusResult(success: false, status: 'unknown', error: $e->getMessage());
        }
    }

    private function getAccessToken(): string
    {
        $response = Http::asForm()->post("{$this->config['base_url']}/api/v3/tokens", [
            'grant_type' => 'password',
            'terminal_code' => $this->config['terminal_code'],
            'password' => $this->config['password'],
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to get Unlimit access token');
        }

        return $response->json('token_data.access_token');
    }
}
