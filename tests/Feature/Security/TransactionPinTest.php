<?php

namespace Tests\Feature\Security;

use App\Models\TransactionPin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TransactionPinTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_pin_can_be_set(): void
    {
        $user = User::factory()->create(['transaction_pin_enabled' => false]);

        TransactionPin::create([
            'user_id' => $user->id,
            'pin_hash' => Hash::make('123456'),
            'failed_attempts' => 0,
        ]);

        $pin = TransactionPin::where('user_id', $user->id)->first();

        $this->assertNotNull($pin);
        $this->assertTrue(Hash::check('123456', $pin->pin_hash));
    }

    public function test_correct_pin_verifies(): void
    {
        $user = User::factory()->create();
        $pin = TransactionPin::create([
            'user_id' => $user->id,
            'pin_hash' => Hash::make('654321'),
        ]);

        $this->assertTrue($pin->verify('654321'));
        $this->assertFalse($pin->verify('000000'));
    }

    public function test_pin_locks_after_max_failed_attempts(): void
    {
        $user = User::factory()->create();
        $maxAttempts = config('security.transaction_pin.max_failed_attempts', 5);

        $pin = TransactionPin::create([
            'user_id' => $user->id,
            'pin_hash' => Hash::make('999999'),
            'failed_attempts' => 0,
        ]);

        for ($i = 0; $i < $maxAttempts; $i++) {
            $pin->verify('000000');
        }

        $pin->refresh();

        $this->assertNotNull($pin->locked_until);
        $this->assertTrue($pin->isLocked());
    }

    public function test_locked_pin_returns_false(): void
    {
        $user = User::factory()->create();
        $pin = TransactionPin::create([
            'user_id' => $user->id,
            'pin_hash' => Hash::make('111111'),
            'locked_until' => now()->addHour(),
        ]);

        $this->assertFalse($pin->verify('111111'));
    }

    public function test_correct_attempt_resets_failed_counter(): void
    {
        $user = User::factory()->create();
        $pin = TransactionPin::create([
            'user_id' => $user->id,
            'pin_hash' => Hash::make('777777'),
            'failed_attempts' => 3,
        ]);

        $pin->verify('777777');
        $pin->refresh();

        $this->assertEquals(0, $pin->failed_attempts);
        $this->assertNull($pin->locked_until);
    }
}
