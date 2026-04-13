<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'vouchagram_reference_num')) {
                $table->string('vouchagram_reference_num', 100)->nullable()->after('voucher_pin');
            }
            if (! Schema::hasColumn('orders', 'vouchagram_external_order_id')) {
                $table->string('vouchagram_external_order_id', 100)->nullable()->after('vouchagram_reference_num');
            }
            if (! Schema::hasColumn('orders', 'vouchagram_voucher_data')) {
                $table->json('vouchagram_voucher_data')->nullable()->after('vouchagram_external_order_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'vouchagram_voucher_data')) {
                $table->dropColumn('vouchagram_voucher_data');
            }
            if (Schema::hasColumn('orders', 'vouchagram_external_order_id')) {
                $table->dropColumn('vouchagram_external_order_id');
            }
            if (Schema::hasColumn('orders', 'vouchagram_reference_num')) {
                $table->dropColumn('vouchagram_reference_num');
            }
        });
    }
};
