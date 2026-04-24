<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        // Do not use after('voucher_pin'): this migration may run before
        // 2026_04_16_100000_add_api_order_columns_to_orders_table adds that column.
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'vouchagram_reference_num')) {
                $table->string('vouchagram_reference_num', 100)->nullable();
            }
        });
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'vouchagram_external_order_id')) {
                $table->string('vouchagram_external_order_id', 100)->nullable();
            }
        });
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'vouchagram_voucher_data')) {
                $table->json('vouchagram_voucher_data')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

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
