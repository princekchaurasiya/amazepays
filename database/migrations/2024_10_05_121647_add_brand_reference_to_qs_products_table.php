<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBrandReferenceToQsProductsTable extends Migration
{
    public function up()
    {
        Schema::table('qs_products', function (Blueprint $table) {
            $table->unsignedBigInteger('brand_id')->nullable()->after('id'); // Add brand_id column

            // Define foreign key constraint
            $table->foreign('brand_id')->references('id')->on('amazepay_available_brands')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('qs_products', function (Blueprint $table) {
            $table->dropForeign(['brand_id']); // Drop foreign key constraint
            $table->dropColumn('brand_id'); // Remove the brand_id column
        });
    }
}

