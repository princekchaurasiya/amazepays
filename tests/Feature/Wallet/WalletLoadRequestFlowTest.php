<?php

namespace Tests\Feature\Wallet;

use App\Http\Middleware\RequireTwoFactor;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WalletLoadRequest;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WalletLoadRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutMiddleware(RequireTwoFactor::class);
    }

    public function test_b2b_user_can_submit_load_request(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->assignRole('b2b-client');
        $tenant = Tenant::factory()->create(['status' => 'active']);
        $tenant->users()->attach($user->id, ['role' => 'owner', 'is_primary' => true]);

        $file = UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf');

        $this->actingAs($user)
            ->post('/panel/b2b/wallet/load-request', [
                'amount' => 500,
                'payment_mode' => 'neft',
                'reference_no' => 'UTR123456',
                'proof' => $file,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('wallet_load_requests', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'amount' => 500,
            'payment_mode' => 'neft',
            'reference_no' => 'UTR123456',
            'status' => 'pending',
        ]);

        $req = WalletLoadRequest::first();
        $this->assertNotNull($req);
        Storage::disk('local')->assertExists($req->proof_file);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'wallet.load.requested',
        ]);
    }

    public function test_finance_approve_credits_with_metadata(): void
    {
        Storage::fake('local');

        $customer = User::factory()->create();
        $load = WalletLoadRequest::create([
            'user_id' => $customer->id,
            'tenant_id' => null,
            'amount' => 1000,
            'payment_mode' => 'imps',
            'reference_no' => 'REF1',
            'proof_file' => 'wallet_proofs/x.pdf',
            'status' => 'pending',
        ]);
        Storage::disk('local')->put('wallet_proofs/x.pdf', 'x');

        $finance = User::factory()->create();
        $finance->assignRole('finance');

        $this->actingAs($finance)
            ->post("/panel/wallets/load-requests/{$load->id}/approve", ['note' => 'ok'])
            ->assertRedirect();

        $this->assertEquals(1000, (float) $customer->fresh()->wallet->balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'type' => 'credit',
            'amount' => 1000,
            'reference_type' => 'load_request',
            'reference_id' => $load->id,
            'idempotency_key' => 'wallet-load-'.$load->id,
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'wallet.load.approved']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'wallet.credit']);
    }

    public function test_finance_reject_does_not_credit(): void
    {
        $customer = User::factory()->create();
        $load = WalletLoadRequest::create([
            'user_id' => $customer->id,
            'tenant_id' => null,
            'amount' => 500,
            'payment_mode' => 'cash',
            'reference_no' => null,
            'proof_file' => null,
            'status' => 'pending',
        ]);

        $finance = User::factory()->create();
        $finance->assignRole('finance');

        $this->actingAs($finance)
            ->post("/panel/wallets/load-requests/{$load->id}/reject", ['reason' => 'Invalid proof'])
            ->assertRedirect();

        $this->assertEquals(0, (float) $customer->fresh()->wallet->balance);
        $this->assertDatabaseHas('wallet_load_requests', ['id' => $load->id, 'status' => 'rejected']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'wallet.load.rejected']);
    }

    public function test_proof_download_forbidden_without_permission(): void
    {
        Storage::fake('local');

        $customer = User::factory()->create();
        $load = WalletLoadRequest::create([
            'user_id' => $customer->id,
            'amount' => 100,
            'payment_mode' => 'neft',
            'proof_file' => 'wallet_proofs/a.pdf',
            'status' => 'pending',
        ]);
        Storage::disk('local')->put('wallet_proofs/a.pdf', 'k');

        $other = User::factory()->create();
        $other->assignRole('b2b-operator');
        $tenant = Tenant::factory()->create(['status' => 'active']);
        $tenant->users()->attach($other->id, ['role' => 'operator', 'is_primary' => true]);

        $this->actingAs($other)
            ->get("/panel/wallets/load-requests/{$load->id}/proof")
            ->assertForbidden();
    }
}
