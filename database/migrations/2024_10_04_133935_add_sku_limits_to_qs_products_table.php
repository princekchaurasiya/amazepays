<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSkuLimitsToQsProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('qs_products') && !Schema::hasColumn('qs_products', 'sku_limits')) {
            Schema::table('qs_products', function (Blueprint $table) {
                $table->integer('sku_limits')->nullable()->after('sku'); // Adding sku_limits column
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qs_products', function (Blueprint $table) {
            $table->dropColumn('sku_limits'); // Dropping sku_limits column
        });
    }
}
