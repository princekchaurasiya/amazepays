<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomImageToQsProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qs_products', function (Blueprint $table) {
            $table->string('custom_image')->nullable()->after('images'); // Adding custom_image column
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qs_products', function (Blueprint $table) {
            $table->dropColumn('custom_image');
        });
    }
}
