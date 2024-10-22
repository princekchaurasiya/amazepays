<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCgstSgstIgstToQsProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qs_products', function (Blueprint $table) {
            $table->decimal('CGST', 8, 2)->nullable()->after('discount_percentage');
            $table->decimal('SGST', 8, 2)->nullable()->after('CGST');
            $table->decimal('IGST', 8, 2)->nullable()->after('SGST');
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
            $table->dropColumn(['CGST', 'SGST', 'IGST']);
        });
    }
}
