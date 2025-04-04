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
            // Check if the columns don't exist before adding them
            if (!Schema::hasColumn('slides', 'product_id')) {
                $table->unsignedInteger('product_id')->nullable(); // Foreign key for product
            }

            if (!Schema::hasColumn('slides', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable(); // Foreign key for category
            }

            if (!Schema::hasColumn('slides', 'brand_id')) {
                $table->unsignedBigInteger('brand_id')->nullable();    // Foreign key for brand
            }

            // Check if foreign keys don't exist before adding them
            $foreignKeys = Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys('slides');
            $foreignKeyNames = array_map(function($fk) { return $fk->getName(); }, $foreignKeys);

            if (!in_array('slides_product_id_foreign', $foreignKeyNames)) {
                $table->foreign('product_id')->references('id')->on('qs_products')->onDelete('set null');
            }
            if (!in_array('slides_category_id_foreign', $foreignKeyNames)) {
                $table->foreign('category_id')->references('id')->on('amazepay_categories')->onDelete('set null');
            }
            if (!in_array('slides_brand_id_foreign', $foreignKeyNames)) {
                $table->foreign('brand_id')->references('id')->on('amazepay_available_brands')->onDelete('set null');
            }
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
            // Check if foreign keys exist before dropping them
            $foreignKeys = Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys('slides');
            $foreignKeyNames = array_map(function($fk) { return $fk->getName(); }, $foreignKeys);

            if (in_array('slides_product_id_foreign', $foreignKeyNames)) {
                $table->dropForeign(['product_id']);
            }
            if (in_array('slides_category_id_foreign', $foreignKeyNames)) {
                $table->dropForeign(['category_id']);
            }
            if (in_array('slides_brand_id_foreign', $foreignKeyNames)) {
                $table->dropForeign(['brand_id']);
            }

            // Drop columns if they exist
            if (Schema::hasColumn('slides', 'product_id')) {
                $table->dropColumn('product_id');
            }
            if (Schema::hasColumn('slides', 'category_id')) {
                $table->dropColumn('category_id');
            }
            if (Schema::hasColumn('slides', 'brand_id')) {
                $table->dropColumn('brand_id');
            }
        });
    }
}
