<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGiftCardFieldsToGetEvcRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('get_evc_requests', function (Blueprint $table) {
            $table->string('gift_send_option')->nullable();
            $table->string('delivery_mode')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_email')->nullable();
            $table->string('receiver_mobile')->nullable();
            $table->text('receiver_msg')->nullable();
            $table->decimal('vd_discount', 5, 2)->nullable();
            $table->string('vd_brand_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('get_evc_requests', function (Blueprint $table) {
            $table->dropColumn([
                'gift_send_option',
                'delivery_mode',
                'receiver_name',
                'receiver_email',
                'receiver_mobile',
                'receiver_msg',
                'vd_discount',
                'vd_brand_code'
            ]);
        });
    }
}
