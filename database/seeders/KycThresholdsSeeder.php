<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KycThresholdsSeeder extends Seeder
{
    public function run(): void
    {
        $platformTenantId = Tenant::where('slug', 'platform')->value('id');

        // Policy: orders >= INR 50,000 require PAN (default, tenant-null means platform wide).
        DB::table('kyc_thresholds')->updateOrInsert(
            [
                'tenant_id' => $platformTenantId,
                'name' => 'Order KYC threshold (INR 50,000)',
            ],
            [
                'scope' => 'order',
                'channel' => 'all',
                'threshold_amount_minor' => 5_000_000,
                'currency' => 'INR',
                'required_document_types' => json_encode(['pan']),
                'enforcement' => 'block_until_verified',
                'is_active' => true,
                'effective_from' => now()->toDateString(),
                'effective_until' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}

