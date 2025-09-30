<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKgenProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::create('kgen_products', function (Blueprint $table) {
        $table->id();
        $table->string('productID')->unique();
        $table->string('productName');
        $table->string('productDisplayName');
        $table->text('descriptionText')->nullable();
        $table->text('redemptionInstructions')->nullable();
        $table->text('termsAndConditions')->nullable();
        $table->json('attachments')->nullable();
        $table->json('categories')->nullable();
        $table->json('variants')->nullable(); // store all variants JSON
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
        Schema::dropIfExists('kgen_products');
    }
}
