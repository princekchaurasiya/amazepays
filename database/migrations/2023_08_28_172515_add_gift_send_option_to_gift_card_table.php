<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGiftSendOptionToGiftCardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('gift_card') && !Schema::hasColumn('gift_card', 'gift_send_option')) {
            Schema::table('gift_card', function (Blueprint $table) {
                $table->string('gift_send_option')->nullable();
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
        if (Schema::hasTable('gift_card') && Schema::hasColumn('gift_card', 'gift_send_option')) {
            Schema::table('gift_card', function (Blueprint $table) {
                $table->dropColumn('gift_send_option');
            });
        }
    }
}
