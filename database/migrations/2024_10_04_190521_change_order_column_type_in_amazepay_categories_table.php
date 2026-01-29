<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeOrderColumnTypeInAmazepayCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('amazepay_categories') && Schema::hasColumn('amazepay_categories', 'order')) {
            Schema::table('amazepay_categories', function (Blueprint $table) {
                // Change the order column from integer to decimal(5,2)
                $table->decimal('order', 5, 2)->change();
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
        Schema::table('amazepay_categories', function (Blueprint $table) {
            // Revert the order column back to integer
            $table->integer('order')->default(1)->change();
        });
    }
}
