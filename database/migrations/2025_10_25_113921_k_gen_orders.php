<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class KGenOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('k_gen_orders', function (Blueprint $table) {
            $table->id();
            $table->string('variant_id');
            $table->string('external_ref')->nullable();
            $table->decimal('mrp', 10, 2)->nullable();
            $table->json('api_response')->nullable();
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
        Schema::dropIfExists('k_gen_orders');
    }
}
