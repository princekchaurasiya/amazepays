<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxReferenceSeeder extends Seeder
{
    public function run(): void
    {
        // Minimal reference data for tests/dev. Real catalog can be synced from GST APIs later.
        DB::table('tax_jurisdictions')->updateOrInsert(
            ['code' => 'IN'],
            [
                'scope' => 'country',
                'name' => 'India',
                'country_code' => 'IN',
                'state_code' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $jurisdictionId = DB::table('tax_jurisdictions')->where('code', 'IN')->value('id');

        DB::table('hsn_sac_codes')->updateOrInsert(
            ['code' => '999799'],
            [
                'description' => 'Gift card / voucher services (placeholder)',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $hsnId = DB::table('hsn_sac_codes')->where('code', '999799')->value('id');

        if ($jurisdictionId && $hsnId) {
            // Example 18% GST split as IGST for simplicity here.
            DB::table('tax_rates')->updateOrInsert(
                [
                    'hsn_sac_code_id' => $hsnId,
                    'jurisdiction_id' => $jurisdictionId,
                    'component' => 'igst',
                    'effective_from' => now()->toDateString(),
                ],
                [
                    'rate_percent' => 18.0,
                    'effective_until' => null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}

