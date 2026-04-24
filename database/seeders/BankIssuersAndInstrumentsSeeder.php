<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BankIssuersAndInstrumentsSeeder extends Seeder
{
    public function run(): void
    {
        $issuers = [
            ['name' => 'HDFC Bank', 'short_code' => 'HDFC', 'country' => 'IN', 'is_active' => true, 'display_order' => 10],
            ['name' => 'State Bank of India', 'short_code' => 'SBI', 'country' => 'IN', 'is_active' => true, 'display_order' => 20],
            ['name' => 'ICICI Bank', 'short_code' => 'ICICI', 'country' => 'IN', 'is_active' => true, 'display_order' => 30],
            ['name' => 'Axis Bank', 'short_code' => 'AXIS', 'country' => 'IN', 'is_active' => true, 'display_order' => 40],
        ];

        foreach ($issuers as $issuer) {
            DB::table('bank_issuers')->updateOrInsert(
                ['short_code' => $issuer['short_code']],
                array_merge($issuer, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        $hdfcId = DB::table('bank_issuers')->where('short_code', 'HDFC')->value('id');
        $sbiId = DB::table('bank_issuers')->where('short_code', 'SBI')->value('id');

        $instruments = [
            ['bank_issuer_id' => null, 'name' => 'UPI', 'slug' => 'upi', 'category' => 'upi', 'supports_emi' => false, 'is_active' => true, 'display_order' => 10],
            ['bank_issuer_id' => null, 'name' => 'Netbanking', 'slug' => 'netbanking', 'category' => 'netbanking', 'supports_emi' => false, 'is_active' => true, 'display_order' => 20],
            ['bank_issuer_id' => $hdfcId, 'name' => 'HDFC Credit Card (Visa)', 'slug' => 'hdfc_cc_visa', 'category' => 'card', 'card_type' => 'credit', 'card_network' => 'visa', 'supports_emi' => true, 'is_active' => true, 'display_order' => 30],
            ['bank_issuer_id' => $sbiId, 'name' => 'SBI Debit Card (Rupay)', 'slug' => 'sbi_dc_rupay', 'category' => 'card', 'card_type' => 'debit', 'card_network' => 'rupay', 'supports_emi' => false, 'is_active' => true, 'display_order' => 40],
        ];

        foreach ($instruments as $pi) {
            DB::table('payment_instruments')->updateOrInsert(
                ['slug' => $pi['slug']],
                array_merge($pi, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}

