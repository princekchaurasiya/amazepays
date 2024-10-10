<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAmazepayCategoryIdToQsProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qs_products', function (Blueprint $table) {
            // Adding the new column for amazepay_categories
            $table->unsignedBigInteger('amazepay_category_id')->nullable()->after('qs_category_id');

            // Adding the foreign key constraint
            $table->foreign('amazepay_category_id')->references('id')->on('amazepay_categories')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qs_products', function (Blueprint $table) {
            // Dropping the foreign key constraint first
            $table->dropForeign(['amazepay_category_id']);

            // Dropping the column
            $table->dropColumn('amazepay_category_id');
        });
    }
}
