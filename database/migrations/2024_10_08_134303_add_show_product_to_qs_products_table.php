<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShowProductToQsProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('qs_products') && !Schema::hasColumn('qs_products', 'show_product')) {
            Schema::table('qs_products', function (Blueprint $table) {
                // Add a boolean column to show or hide the product
                $table->boolean('show_product')->default(true)->after('priority');
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
            // Drop the column if the migration is rolled back
            $table->dropColumn('show_product');
        });
    }
}
