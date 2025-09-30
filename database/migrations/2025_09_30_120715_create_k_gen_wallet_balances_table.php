<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKGenWalletBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('k_gen_wallet_balances', function (Blueprint $table) {
            $table->id();
            $table->decimal('balance', 15, 2); // wallet balance amount
            $table->string('currency')->nullable(); // optional if API provides currency
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
        Schema::dropIfExists('k_gen_wallet_balances');
    }
}
