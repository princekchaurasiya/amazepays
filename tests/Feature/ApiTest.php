<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test authentication API - Send OTP
     */
    public function test_send_user_login_otp()
    {
        $response = $this->postJson('/api/auth/sendUserLoginOtp', [
            'phone' => '1234567890'
        ]);

        // Should return 200 or 422 depending on validation
        $this->assertContains($response->status(), [200, 422]);
    }

    /**
     * Test authentication API - Verify OTP
     */
    public function test_verify_user_login_otp()
    {
        $response = $this->postJson('/api/auth/verifyUserLoginOtp', [
            'phone' => '1234567890',
            'otp' => '123456'
        ]);

        // Should return 200 or 422 depending on validation
        $this->assertContains($response->status(), [200, 422]);
    }

    /**
     * Test products API - Get all products
     */
    public function test_get_all_products()
    {
        $response = $this->getJson('/api/products');

        // Should return 200
        $this->assertEquals(200, $response->status());
    }

    /**
     * Test products API - Get single product
     */
    public function test_get_single_product()
    {
        $response = $this->getJson('/api/products/1');

        // Should return 200 or 404
        $this->assertContains($response->status(), [200, 404]);
    }

    /**
     * Test wallet API - Get balance (requires authentication)
     */
    public function test_get_wallet_balance_requires_auth()
    {
        $response = $this->getJson('/api/wallet/balance');

        // Should return 401 (unauthorized) without authentication
        $this->assertEquals(401, $response->status());
    }

    /**
     * Test wallet API - Get balance with authentication
     */
    public function test_get_wallet_balance_with_auth()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/wallet/balance');

        // Should return 200 with authenticated user
        $this->assertEquals(200, $response->status());
    }

    /**
     * Test wallet API - Deposit (requires authentication)
     */
    public function test_wallet_deposit_requires_auth()
    {
        $response = $this->postJson('/api/wallet/deposit', [
            'amount' => 100
        ]);

        // Should return 401 (unauthorized) without authentication
        $this->assertEquals(401, $response->status());
    }

    /**
     * Test wallet API - Transactions (requires authentication)
     */
    public function test_wallet_transactions_requires_auth()
    {
        $response = $this->getJson('/api/wallet/transactions');

        // Should return 401 (unauthorized) without authentication
        $this->assertEquals(401, $response->status());
    }
}
