<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAmazepayCategoryProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('amazepay_category_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('amazepay_category_id')->constrained('amazepay_categories')->onDelete('cascade');

            // Use unsignedBigInteger if you are referencing an `unsignedBigInteger` id column,
            // otherwise, use unsignedInteger for integer types.
            $table->unsignedInteger('qs_product_id');

            // Set up the foreign key constraint properly
            $table->foreign('qs_product_id')
                ->references('id')
                ->on('qs_products')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('amazepay_category_product');
    }
}
