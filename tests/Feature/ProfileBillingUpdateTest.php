<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileBillingUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_update_persists_billing_fields(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'mobile' => '9876543210',
        ]);

        $response = $this->actingAs($user)->post('/update-profile', [
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'mobile' => '9876543210',
            'billing_address' => 'Address line 1',
            'billing_address_two' => 'Address line 2',
            'billing_city' => 'Mumbai',
            'billing_state' => 'Maharashtra',
            'billing_zip' => '400064',
            'billing_country' => 'IN',
        ]);

        $response->assertRedirect();

        $user->refresh();
        $this->assertSame('Address line 1', $user->billing_address);
        $this->assertSame('Address line 2', $user->billing_address_two);
        $this->assertSame('Mumbai', $user->billing_city);
        $this->assertSame('Maharashtra', $user->billing_state);
        $this->assertSame('400064', $user->billing_zip);
        $this->assertSame('IN', $user->billing_country);
    }

    public function test_profile_update_rejects_unexpected_fields(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo2@example.com',
            'mobile' => '9876543211',
        ]);

        $response = $this->actingAs($user)->from('/profile')->post('/update-profile', [
            'name' => 'Demo User',
            'email' => 'demo2@example.com',
            'mobile' => '9876543211',
            'hacker_field' => 'bad',
        ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHasErrors('unexpected_fields');
    }
}

