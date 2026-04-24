<?php

namespace Tests\Feature\Webhooks;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WebhookSignatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_unlimit_webhook_requires_signature_header(): void
    {
        config()->set('services.unlimit.callback_secret', 'test-secret');

        $this->postJson('/api/v1/webhooks/unlimit', ['payment_data' => [], 'merchant_order' => []])
            ->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_unlimit_webhook_accepts_valid_signature(): void
    {
        config()->set('services.unlimit.callback_secret', 'test-secret');
        $body = json_encode(['payment_data' => [], 'merchant_order' => []], JSON_THROW_ON_ERROR);

        $signature = hash('sha512', $body.'test-secret');

        $this->call('POST', '/api/v1/webhooks/unlimit', [], [], [], [
            'HTTP_Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => strlen($body),
        ], $body)
            ->assertStatus(200);
    }

    public function test_ccavenue_webhook_requires_encresp(): void
    {
        $this->post('/api/v1/webhooks/ccavenue', [])
            ->assertStatus(400);
    }

    public function test_razorpay_webhook_rejects_invalid_signature_when_secret_configured(): void
    {
        config()->set('services.razorpay.key_secret', 'rzp-secret');
        $body = json_encode(['event' => 'payment.captured', 'payload' => ['payment' => ['entity' => ['id' => 'pay_1']]]], JSON_THROW_ON_ERROR);

        $this->call('POST', '/api/v1/webhooks/razorpay', [], [], [], [
            'HTTP_X-Razorpay-Signature' => 'bad',
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => strlen($body),
        ], $body)
            ->assertStatus(403);
    }

    public function test_razorpay_webhook_accepts_valid_signature_when_secret_configured(): void
    {
        config()->set('services.razorpay.key_secret', 'rzp-secret');
        $body = json_encode(['event' => 'payment.captured', 'payload' => ['payment' => ['entity' => ['id' => 'pay_1']]]], JSON_THROW_ON_ERROR);
        $sig = hash_hmac('sha256', $body, 'rzp-secret');

        $this->call('POST', '/api/v1/webhooks/razorpay', [], [], [], [
            'HTTP_X-Razorpay-Signature' => $sig,
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => strlen($body),
        ], $body)
            ->assertStatus(200);
    }
}
