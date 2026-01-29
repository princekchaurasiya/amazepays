<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateGetEvcRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
public function up(): void
{
    if (Schema::hasTable('get_evc_requests') && !Schema::hasColumn('get_evc_requests', 'receipt_no')) {
        Schema::table('get_evc_requests', function (Blueprint $table) {
            // Use 'receipt_no' with underscore to match column name
            $table->string('receipt_no')->nullable(); // or remove nullable() if required
        });
    }
}

public function down(): void
{
    Schema::table('get_evc_requests', function (Blueprint $table) {
        $table->dropColumn('receipt_no');
    });
}        //
}
