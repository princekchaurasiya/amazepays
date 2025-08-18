<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBrandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('brand_code')->unique();
            $table->string('brand_name');
            $table->string('brand_type')->nullable();
            $table->decimal('discount', 5, 2)->nullable();
            $table->integer('min_price')->nullable();
            $table->integer('max_price')->nullable();
            $table->text('denomination_list')->nullable();
            $table->integer('stock_available')->nullable();
            $table->string('category')->nullable();
            $table->longText('description')->nullable();
            $table->json('images')->nullable();
            $table->longText('tnc')->nullable();
            $table->json('important_instruction')->nullable();
            $table->json('redeem_steps')->nullable();
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
        Schema::dropIfExists('brands');
    }
}
