<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnlimitResponseToUnlimitPaymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('unlimit_payment') && !Schema::hasColumn('unlimit_payment', 'unlimit_response')) {
            Schema::table('unlimit_payment', function (Blueprint $table) {
                $table->longText('unlimit_response')->nullable()->after('billing_notes');
            });
        }
    }

public function down()
{
    Schema::table('unlimit_payment', function (Blueprint $table) {
        $table->dropColumn('unlimit_response');
    });
}

}
