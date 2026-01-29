<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAmazepayColumnsToQsProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('qs_products')) {
            Schema::table('qs_products', function (Blueprint $table) {
                if (!Schema::hasColumn('qs_products', 'amazepay_how_to_redeem')) {
                    $table->text('amazepay_how_to_redeem')->nullable()->after('show_product');
                }
                if (!Schema::hasColumn('qs_products', 'amazepay_t_and_c')) {
                    $table->text('amazepay_t_and_c')->nullable()->after('amazepay_how_to_redeem');
                }
                if (!Schema::hasColumn('qs_products', 'amazepay_product_description')) {
                    $table->text('amazepay_product_description')->nullable()->after('amazepay_t_and_c');
                }
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
            $table->dropColumn([
                'amazepay_how_to_redeem',
                'amazepay_t_and_c',
                'amazepay_product_description',
            ]);
        });
    }
}
