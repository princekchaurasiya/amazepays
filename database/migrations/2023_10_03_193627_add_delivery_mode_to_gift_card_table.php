<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliveryModeToGiftCardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
     public function up()
    {
        if (Schema::hasTable('gift_card') && !Schema::hasColumn('gift_card', 'delivery_mode')) {
            Schema::table('gift_card', function (Blueprint $table) {
                $table->string('delivery_mode')->nullable();
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
        Schema::table('gift_card', function (Blueprint $table) {
            $table->dropColumn('delivery_mode');
        });
    }
}
