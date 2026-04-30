<?php

namespace Tests\Feature\Api;

use App\Models\UserOtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'b2c-user', 'guard_name' => 'web']);
        
        $this->mock(\App\Services\SmsService::class, function ($mock) {
            $mock->shouldReceive('sendOtp')->andReturn(true);
        });
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

    public function test_api_accepts_phone_parameter_alias(): void
    {
        $mobile = '9876543210';
        
        // Test send-otp with 'phone' parameter
        $response = $this->postJson('/api/v1/auth/otp/send', [
            'phone' => $mobile,
        ]);
        
        // We expect success or validation pass (even if SMS fails, it shouldn't be 'mobile required')
        if ($response->status() !== 200) {
            dump($response->json());
        }
        $response->assertStatus(200);
        $this->assertDatabaseHas('user_identities', [
            'identifier' => $mobile,
        ]);
    }

    public function test_api_accepts_destination_parameter_alias(): void
    {
        $mobile = '9876543211';
        
        // Test send-otp with 'destination' parameter
        $response = $this->postJson('/api/v1/auth/otp/send', [
            'destination' => $mobile,
        ]);
        
        if ($response->status() !== 200) {
            dump($response->json());
        }
        $response->assertStatus(200);
        $this->assertDatabaseHas('user_identities', [
            'identifier' => $mobile,
        ]);
    }

    public function test_api_works_with_alias_route_send_otp(): void
    {
        $mobile = '9876543212';
        
        $response = $this->postJson('/api/v1/auth/send-otp', [
            'mobile' => $mobile,
        ]);
        
        $response->assertStatus(200);
    }

    public function test_api_works_with_alias_route_verify_otp(): void
    {
        $mobile = '9876543213';
        $this->seedValidOtp($mobile);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => $mobile,
            'otp' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_api_works_with_alias_route_complete_registration(): void
    {
        $mobile = '9876543214';
        $this->seedValidOtp($mobile);

        $verify = $this->postJson('/api/v1/auth/verify-otp', [
            'mobile' => $mobile,
            'otp' => '123456',
        ]);
        $verify->assertStatus(200);
        $temp = $verify->json('data.temp_token');

        $complete = $this->postJson('/api/v1/auth/complete-registration', [
            'temp_token' => $temp,
            'name' => 'Alias User',
        ]);

        $complete->assertStatus(201)
            ->assertJsonPath('success', true);
    }
}
