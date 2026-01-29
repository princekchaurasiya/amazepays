<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderSummaryTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('order_summary')) {
            Schema::create('order_summary', function (Blueprint $table) {
                $table->id(); // Auto-incrementing ID for the order_summary table

                // Use integer instead of foreignId for compatibility
                $table->integer('order_id')->unsigned(); // Match with qs_orders.id
                $table->integer('payment_id')->unsigned()->nullable(); // Payment reference (nullable since cc_avenue_payment table doesn't exist)

                $table->string('product_name')->nullable();
                $table->string('sender_name')->nullable();
                $table->string('sender_email')->nullable();
                $table->string('sender_phone')->nullable();
                $table->string('payment_status')->nullable();
                $table->string('order_status')->nullable();
                $table->timestamps();
            });

            // Foreign key constraints - only if referenced table exists and foreign key doesn't exist
            if (Schema::hasTable('qs_orders') && Schema::hasTable('order_summary')) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $foreignKeys = $sm->listTableForeignKeys('order_summary');
                $hasOrderForeignKey = false;
                foreach ($foreignKeys as $foreignKey) {
                    if (in_array('order_id', $foreignKey->getLocalColumns())) {
                        $hasOrderForeignKey = true;
                        break;
                    }
                }
                
                if (!$hasOrderForeignKey && Schema::hasColumn('order_summary', 'order_id')) {
                    Schema::table('order_summary', function (Blueprint $table) {
                        $table->foreign('order_id')->references('id')->on('qs_orders')->onDelete('cascade');
                    });
                }
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('order_summary');
    }
}
