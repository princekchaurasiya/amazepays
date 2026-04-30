<?php

namespace Tests\Feature\Api;

use App\Models\UserOtpCode;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'b2c-user', 'guard_name' => 'web']);
    }

    private function seedValidOtp(string $phone, string $plain = '123456'): void
    {
        UserOtpCode::create([
            'user_id' => null,
            'identity_id' => null,
            'channel' => 'sms',
            'purpose' => 'login',
            'identifier' => $phone,
            'code_hash' => Hash::make($plain),
            'attempts' => 0,
            'max_attempts' => 5,
            'expires_at' => now()->addMinutes(5),
            'consumed_at' => null,
            'request_ip' => '127.0.0.1',
        ]);
    }

    public function test_existing_user_can_verify_otp_and_receive_token(): void
    {
        $mobile = '9876543210';
        $this->seedValidOtp($mobile);

        $user = User::factory()->create([
            'two_factor_enabled' => false,
        ]);
        $user->authIdentities()->create([
            'type' => 'mobile',
            'identifier' => $mobile,
            'is_primary' => true,
            'verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'mobile' => $mobile,
            'otp' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.action', 'logged_in')
            ->assertJsonStructure(['success', 'message', 'data' => ['token', 'user']])
            ->assertJsonPath('data.user.id', $user->id);
    }

    public function test_new_mobile_after_otp_verify_returns_needs_profile(): void
    {
        $mobile = '9123456789';
        $this->seedValidOtp($mobile);

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'mobile' => $mobile,
            'otp' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.action', 'needs_profile')
            ->assertJsonStructure(['success', 'message', 'data' => ['temp_token', 'phone']]);
    }

    public function test_complete_profile_creates_user_and_returns_token(): void
    {
        $mobile = '9988776655';
        $this->seedValidOtp($mobile);

        $verify = $this->postJson('/api/v1/auth/otp/verify', [
            'mobile' => $mobile,
            'otp' => '123456',
        ]);
        $verify->assertStatus(200);
        $temp = $verify->json('data.temp_token');

        $complete = $this->postJson('/api/v1/auth/complete-profile', [
            'temp_token' => $temp,
            'name' => 'Test User',
            'email' => 'newuser@example.com',
        ]);

        $complete->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.action', 'registered')
            ->assertJsonStructure(['success', 'message', 'data' => ['token', 'user']]);

        $this->assertDatabaseHas('user_identities', [
            'identifier' => $mobile,
            'user_id' => User::whereMobile($mobile)->first()->id,
        ]);
    }

    public function test_locked_account_returns_423_on_otp_verify(): void
    {
        $mobile = '9876501234';
        $this->seedValidOtp($mobile);

        $user = User::factory()->create([
            'account_locked' => true,
            'account_locked_reason' => 'Too many failed attempts',
        ]);
        $user->authIdentities()->create([
            'type' => 'mobile',
            'identifier' => $mobile,
            'is_primary' => true,
            'verified_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'mobile' => $mobile,
            'otp' => '123456',
        ]);

        $response->assertStatus(423)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_logout_deletes_sanctum_token(): void
    {
        $user = User::factory()->create();
        $plain = $user->createToken('test')->plainTextToken;

        $this->withToken($plain)->postJson('/api/v1/auth/logout')
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $tokenId = (int) explode('|', $plain, 2)[0];
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
        $this->assertSame(0, $user->fresh()->tokens()->count());
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_otp_send_rate_limited(): void
    {
        $this->app->bind(SmsService::class, fn () => Mockery::mock(SmsService::class, fn ($m) => $m->shouldReceive('sendOtp')->andReturn(true)));

        $mobile = '9876543210';

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/auth/otp/send', ['mobile' => $mobile])->assertStatus(200);
        }

        // Route middleware: throttle 3/min → 4th request is HTTP 429 before app-level OTP rate limit.
        $this->postJson('/api/v1/auth/otp/send', ['mobile' => $mobile])
            ->assertStatus(429);
    }
}
