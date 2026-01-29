<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('qs_products') && !Schema::hasColumn('qs_products', 'is_special_sku')) {
            Schema::table('qs_products', function (Blueprint $table) {
                $table->boolean('is_special_sku')->default(false)->after('sku');
            });

            // Set the flag true for the specific SKU only
            if (Schema::hasTable('qs_products')) {
                DB::table('qs_products')
                    ->where('sku', 'EGCGBRELSS001')
                    ->update(['is_special_sku' => true]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('qs_products', function (Blueprint $table) {
            if (Schema::hasColumn('qs_products', 'is_special_sku')) {
                $table->dropColumn('is_special_sku');
            }
        });
    }
};



