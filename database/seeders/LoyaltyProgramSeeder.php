<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LoyaltyProgramSeeder extends Seeder
{
    public function run(): void
    {
        $platformTenantId = Tenant::where('slug', 'platform')->value('id');

        DB::table('loyalty_programs')->updateOrInsert(
            ['tenant_id' => $platformTenantId, 'slug' => 'amazepays_rewards'],
            [
                'name' => 'AmazePays Rewards',
                'point_currency_label' => 'Points',
                'point_to_currency_rate' => 1,
                'currency' => 'INR',
                'status' => 'active',
                'points_expiry_days' => 365,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $programId = DB::table('loyalty_programs')->where('tenant_id', $platformTenantId)->where('slug', 'amazepays_rewards')->value('id');

        if (! $programId) {
            return;
        }

        DB::table('loyalty_tiers')->updateOrInsert(
            ['program_id' => $programId, 'slug' => 'silver'],
            [
                'name' => 'Silver',
                'display_order' => 10,
                'qualifying_spend_minor' => 0,
                'qualifying_orders' => 0,
                'tier_duration_days' => 365,
                'badge_image_url' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('loyalty_tiers')->updateOrInsert(
            ['program_id' => $programId, 'slug' => 'gold'],
            [
                'name' => 'Gold',
                'display_order' => 20,
                'qualifying_spend_minor' => 100_000,
                'qualifying_orders' => 2,
                'tier_duration_days' => 365,
                'badge_image_url' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}

