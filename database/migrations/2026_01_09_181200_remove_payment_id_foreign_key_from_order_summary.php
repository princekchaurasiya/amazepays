<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RemovePaymentIdForeignKeyFromOrderSummary extends Migration
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
            
            if (!empty($foreignKeys)) {
                Schema::table('order_summary', function (Blueprint $table) {
                    // Drop the foreign key constraint if it exists
                    $table->dropForeign(['payment_id']);
                });
            }
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
            // Re-add the foreign key constraint (if needed to rollback)
            // Note: This will fail if cc_avenue_payment table doesn't exist
            // $table->foreign('payment_id')->references('id')->on('cc_avenue_payment')->onDelete('cascade');
        });
    }
}
