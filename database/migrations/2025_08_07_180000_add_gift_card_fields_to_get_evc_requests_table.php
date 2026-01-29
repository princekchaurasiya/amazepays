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
        if (Schema::hasTable('get_evc_requests')) {
            Schema::table('get_evc_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('get_evc_requests', 'gift_send_option')) {
                    $table->string('gift_send_option')->nullable();
                }
                if (!Schema::hasColumn('get_evc_requests', 'delivery_mode')) {
                    $table->string('delivery_mode')->nullable();
                }
                if (!Schema::hasColumn('get_evc_requests', 'receiver_name')) {
                    $table->string('receiver_name')->nullable();
                }
                if (!Schema::hasColumn('get_evc_requests', 'receiver_email')) {
                    $table->string('receiver_email')->nullable();
                }
                if (!Schema::hasColumn('get_evc_requests', 'receiver_mobile')) {
                    $table->string('receiver_mobile')->nullable();
                }
                if (!Schema::hasColumn('get_evc_requests', 'receiver_msg')) {
                    $table->text('receiver_msg')->nullable();
                }
                if (!Schema::hasColumn('get_evc_requests', 'vd_discount')) {
                    $table->decimal('vd_discount', 5, 2)->nullable();
                }
                if (!Schema::hasColumn('get_evc_requests', 'vd_brand_code')) {
                    $table->string('vd_brand_code')->nullable();
                }
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
