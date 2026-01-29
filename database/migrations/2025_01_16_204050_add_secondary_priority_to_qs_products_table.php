<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSecondaryPriorityToQsProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('qs_products') && !Schema::hasColumn('qs_products', 'secondary_priority')) {
            Schema::table('qs_products', function (Blueprint $table) {
                $table->decimal('secondary_priority', 5, 2)->nullable()->after('priority');
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
    Schema::table('qs_products', function (Blueprint $table) {
        $table->dropColumn('secondary_priority');
    });
}
}
