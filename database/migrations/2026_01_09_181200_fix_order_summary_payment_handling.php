<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixOrderSummaryPaymentHandling extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('order_summary')) {
            // Check if foreign key exists before dropping
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'order_summary' 
                AND COLUMN_NAME = 'payment_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            Schema::table('order_summary', function (Blueprint $table) use ($foreignKeys) {
                // Drop the foreign key constraint if it exists
                if (!empty($foreignKeys)) {
                    $table->dropForeign(['payment_id']);
                }
                
                // Add payment_gateway field to distinguish between payment types
                if (!Schema::hasColumn('order_summary', 'payment_gateway')) {
                    $table->string('payment_gateway')->nullable()->after('payment_id');
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
        Schema::table('order_summary', function (Blueprint $table) {
            $table->dropColumn('payment_gateway');
            // Note: We don't re-add the foreign key as it was incorrect
        });
    }
}
