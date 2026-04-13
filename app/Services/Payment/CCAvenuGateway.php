<?php

namespace App\Services\Payment;

use App\Contracts\PaymentCallbackResult;
use App\Contracts\PaymentGatewayInterface;
use App\Contracts\PaymentInitiateResult;
use App\Contracts\PaymentStatusResult;
use App\Contracts\RefundResult;
use Illuminate\Support\Facades\Log;

class CCAvenuGateway implements PaymentGatewayInterface
{
    private array $config;

    public function __construct(array $credentials = [])
    {
        $this->config = empty($credentials) ? config('paymentconfig') : $credentials;
    }

    public function getName(): string
    {
        return 'ccavenue';
    }

    public function supportsRecurring(): bool
    {
        return false;
    }

    public function initiatePayment(array $order): PaymentInitiateResult
    {
        try {
            $merchantId = $this->config['MERCHANT_ID'] ?? config('paymentconfig.MERCHANT_ID');
            $accessCode = $this->config['ACCESS_CODE'] ?? config('paymentconfig.ACCESS_CODE');
            $workingKey = $this->config['WORKING_KEY'] ?? config('paymentconfig.WORKING_KEY');
            $redirectUrl = route('payment.ccavenue.callback');
            $cancelUrl = route('payment.ccavenue.cancel');

            $params = [
                'merchant_id' => $merchantId,
                'order_id' => $order['order_id'],
                'amount' => $order['amount'],
                'currency' => $order['currency'] ?? 'INR',
                'redirect_url' => $redirectUrl,
                'cancel_url' => $cancelUrl,
                'billing_name' => $order['customer_name'] ?? '',
                'billing_email' => $order['customer_email'] ?? '',
                'billing_tel' => $order['customer_phone'] ?? '',
                'merchant_param1' => $order['user_id'] ?? '',
                'integration_type' => 'iframe_normal',
                'language' => 'EN',
            ];

            $encryptedData = $this->encrypt(http_build_query($params), $workingKey);

            return new PaymentInitiateResult(
                success: true,
                redirectUrl: $this->getApiUrl(),
                paymentToken: $encryptedData,
                gatewayOrderId: $order['order_id'],
                raw: ['access_code' => $accessCode],
            );
        } catch (\Exception $e) {
            Log::error('CCAvenue initiate payment failed', ['error' => $e->getMessage()]);

            return new PaymentInitiateResult(success: false, error: $e->getMessage());
        }
    }

    public function handleCallback(array $payload): PaymentCallbackResult
    {
        try {
            $workingKey = $this->config['WORKING_KEY'] ?? config('paymentconfig.WORKING_KEY');
            $encResp = $payload['encResp'] ?? '';

            $decrypted = $this->decrypt($encResp, $workingKey);
            parse_str($decrypted, $data);

            $status = match (strtolower($data['order_status'] ?? '')) {
                'success' => 'paid',
                'aborted' => 'cancelled',
                default => 'failed',
            };

            return new PaymentCallbackResult(
                success: $status === 'paid',
                status: $status,
                transactionId: $data['tracking_id'] ?? null,
                gatewayOrderId: $data['order_id'] ?? null,
                amount: isset($data['amount']) ? (float) $data['amount'] : null,
                paymentMethod: $data['payment_mode'] ?? null,
                raw: $data,
            );
        } catch (\Exception $e) {
            Log::error('CCAvenue callback processing failed', ['error' => $e->getMessage()]);

            return new PaymentCallbackResult(success: false, status: 'failed', error: $e->getMessage());
        }
    }

    public function refund(string $transactionId, float $amount, string $reason): RefundResult
    {
        // CCAvenue does not have a standard API refund endpoint
        // Refunds must be processed through the CCAvenue merchant dashboard
        Log::info('CCAvenue refund requested — manual action needed', [
            'transaction_id' => $transactionId,
            'amount' => $amount,
        ]);

        return new RefundResult(
            success: false,
            error: 'CCAvenue refunds must be processed manually through the merchant dashboard.'
        );
    }

    public function queryStatus(string $transactionId): PaymentStatusResult
    {
        return new PaymentStatusResult(
            success: false,
            status: 'unknown',
            error: 'CCAvenue does not support real-time status query via API.'
        );
    }

    private function getApiUrl(): string
    {
        $mode = config('paymentconfig.PAYMENT_MODE', 'TEST');

        return $mode === 'LIVE'
            ? 'https://secure.ccavenue.com/transaction/transaction.do?command=initiateTransaction'
            : 'https://test.ccavenue.com/transaction/transaction.do?command=initiateTransaction';
    }

    private function encrypt(string $plainText, string $key): string
    {
        $keyBytes = $this->hexToBin(md5($key));
        $ivBytes = $this->hexToBin('000102030405060708090a0b0c0d0e0f');
        $encrypted = openssl_encrypt($plainText, 'AES-128-CBC', $keyBytes, OPENSSL_RAW_DATA, $ivBytes);

        return bin2hex($encrypted);
    }

    private function decrypt(string $cipherText, string $key): string
    {
        $keyBytes = $this->hexToBin(md5($key));
        $ivBytes = $this->hexToBin('000102030405060708090a0b0c0d0e0f');
        $decoded = $this->hexToBin($cipherText);

        return openssl_decrypt($decoded, 'AES-128-CBC', $keyBytes, OPENSSL_RAW_DATA, $ivBytes);
    }

    private function hexToBin(string $hex): string
    {
        $result = '';
        for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
            $result .= chr(hexdec(substr($hex, $i, 2)));
        }

        return $result;
    }
}
