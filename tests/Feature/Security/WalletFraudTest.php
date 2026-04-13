<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletFraudDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletFraudTest extends TestCase
{
    use RefreshDatabase;

    private WalletFraudDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = app(WalletFraudDetector::class);
    }

    public function test_small_normal_transaction_passes(): void
    {
        $wallet = $this->createWallet(10000);

        $result = $this->detector->check($wallet, 500, 'debit');

        $this->assertEquals('allow', $result->action);
        $this->assertFalse($result->isBlocked());
    }

    public function test_drain_attempt_is_flagged(): void
    {
        $wallet = $this->createWallet(10000);

        $result = $this->detector->check($wallet, 9800, 'debit');

        $this->assertContains('wallet_drain_attempt', $result->flags);
        $this->assertGreaterThan(0, $result->score);
    }

    public function test_vpn_transaction_increases_score(): void
    {
        $wallet = $this->createWallet(50000);

        $result = $this->detector->check($wallet, 1000, 'debit', ['is_vpn' => true]);

        $this->assertContains('vpn_transaction', $result->flags);
        $this->assertGreaterThanOrEqual(25, $result->score);
    }

    public function test_high_score_blocks_transaction(): void
    {
        $wallet = $this->createWallet(50000);

        // VPN + drain + new device = very high score
        $result = $this->detector->check($wallet, 48000, 'debit', [
            'is_vpn' => true,
            'is_new_device' => true,
        ]);

        $this->assertTrue($result->isBlocked());
        $this->assertEquals('block', $result->action);
    }

    private function createWallet(float $balance): Wallet
    {
        $user = User::factory()->create();

        return Wallet::create([
            'user_id' => $user->id,
            'balance' => $balance,
        ]);
    }
}
