<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameStatusColumnInUnlimitPaymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('unlimit_payment') && Schema::hasColumn('unlimit_payment', 'status') && !Schema::hasColumn('unlimit_payment', 'payment_status')) {
            Schema::table('unlimit_payment', function (Blueprint $table) {
                $table->renameColumn('status', 'payment_status');
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
            if (Schema::hasColumn('unlimit_payment', 'payment_status')) {
                $table->renameColumn('payment_status', 'status');
            }
        });
    }
}
