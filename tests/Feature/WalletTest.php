<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientBalanceException;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Tests\TestCase;

class WalletTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_example()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_wallet_is_created_for_user()
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->wallet);
        $this->assertEquals(0, $user->wallet->balance);
    }

    public function test_wallet_can_be_credited()
    {
        $user = User::factory()->create();
        $walletService = app(WalletService::class);

        $walletService->credit($user, 500, 'Test credit');

        $this->assertEquals(500, $user->fresh()->wallet->balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'type' => 'credit',
            'amount' => 500,
        ]);
    }

    public function test_wallet_can_be_debited()
    {
        $user = User::factory()->create();
        $walletService = app(WalletService::class);

        $walletService->credit($user, 500, 'Initial funding');
        $walletService->debit($user, 200, 'Purchase');

        $this->assertEquals(300, $user->fresh()->wallet->balance);
    }

    public function test_wallet_cannot_debit_more_than_balance()
    {
        $this->expectException(InsufficientBalanceException::class);

        $user = User::factory()->create();
        $walletService = app(WalletService::class);

        $walletService->debit($user, 100);
    }
}
