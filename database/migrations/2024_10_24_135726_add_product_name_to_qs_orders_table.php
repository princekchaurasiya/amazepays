<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductNameToQsOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('qs_orders') && !Schema::hasColumn('qs_orders', 'product_name')) {
            Schema::table('qs_orders', function (Blueprint $table) {
                $table->string('product_name')->nullable()->after('refno');
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
        Schema::table('qs_orders', function (Blueprint $table) {
            $table->dropColumn('product_name');
        });
    }
}
