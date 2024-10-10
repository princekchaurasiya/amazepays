<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSlidesTableForDynamicLinks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('slides', function (Blueprint $table) {
            // Use unsignedInteger for product_id to match the id in qs_products
            $table->unsignedInteger('product_id')->nullable(); // Foreign key for product
            $table->unsignedBigInteger('category_id')->nullable(); // Foreign key for category
            $table->unsignedBigInteger('brand_id')->nullable();    // Foreign key for brand

            // Setting foreign key constraints
            $table->foreign('product_id')->references('id')->on('qs_products')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('amazepay_categories')->onDelete('set null');
            $table->foreign('brand_id')->references('id')->on('amazepay_available_brands')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('slides', function (Blueprint $table) {
            // Dropping foreign key constraints and columns
            $table->dropForeign(['product_id']);
            $table->dropForeign(['category_id']);
            $table->dropForeign(['brand_id']);
            $table->dropColumn(['product_id', 'category_id', 'brand_id']);
        });
    }
}
