<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeQtyNullableInUnlimitPaymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('unlimit_payment') && Schema::hasColumn('unlimit_payment', 'qty')) {
            Schema::table('unlimit_payment', function (Blueprint $table) {
                $table->integer('qty')->nullable()->change();
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
        Schema::table('unlimit_payment', function (Blueprint $table) {
            $table->integer('qty')->nullable(false)->change();
        });
    }
}
