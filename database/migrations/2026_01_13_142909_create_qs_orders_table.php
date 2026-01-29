<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQsOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('qs_orders')) {
            Schema::create('qs_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('woohoo_order_id')->nullable();
                $table->string('order_status')->nullable();
                $table->decimal('denomination', 10, 2)->nullable();
                $table->string('sender_first_name')->nullable();
                $table->string('sender_email')->nullable();
                $table->string('sender_phone_no')->nullable();
                $table->string('sender_post_code')->nullable();
                $table->text('sender_address_1')->nullable();
                $table->text('sender_address_2')->nullable();
                $table->string('sender_city')->nullable();
                $table->string('sender_state')->nullable();
                $table->string('sku')->nullable();
                $table->decimal('amount', 10, 2)->nullable();
                $table->string('receiver_name')->nullable();
                $table->string('receiver_email')->nullable();
                $table->string('receiver_mobile')->nullable();
                $table->text('receiver_msg')->nullable();
                $table->json('cards')->nullable();
                $table->boolean('order_cancel')->default(false);
                $table->string('order_payment')->nullable();
                $table->string('currency')->nullable();
                $table->json('additionalTxnFields')->nullable();
                $table->decimal('grand_payable_amount', 10, 2)->nullable();
                $table->decimal('discounted_amount_value', 10, 2)->nullable();
                $table->decimal('amount_payable_after_discount', 10, 2)->nullable();
                $table->string('gst_number')->nullable();
                $table->string('country')->nullable();
                $table->uuid('merchant_order_id')->nullable()->unique();
                $table->string('refno')->nullable();
                $table->string('product_name')->nullable();
                $table->integer('quantity')->default(1);
                $table->string('gift_send_option')->nullable();
                $table->string('delivery_mode')->nullable();
                $table->string('vd_brand_code')->nullable();
                $table->decimal('vd_discount', 10, 2)->nullable();
                $table->decimal('price', 10, 2)->nullable();
                $table->timestamps();
            });

            // Add foreign key and indexes separately with checks
            if (Schema::hasTable('qs_orders') && Schema::hasTable('users')) {
                Schema::table('qs_orders', function (Blueprint $table) {
                    // Check if foreign key doesn't exist before adding
                    $sm = Schema::getConnection()->getDoctrineSchemaManager();
                    $foreignKeys = $sm->listTableForeignKeys('qs_orders');
                    $hasUserForeignKey = false;
                    foreach ($foreignKeys as $foreignKey) {
                        if (in_array('user_id', $foreignKey->getLocalColumns())) {
                            $hasUserForeignKey = true;
                            break;
                        }
                    }
                    
                    if (!$hasUserForeignKey && Schema::hasColumn('qs_orders', 'user_id')) {
                        $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                    }
                });

                // Add indexes with checks
                Schema::table('qs_orders', function (Blueprint $table) {
                    $sm = Schema::getConnection()->getDoctrineSchemaManager();
                    $indexes = $sm->listTableIndexes('qs_orders');
                    $indexNames = array_keys($indexes);

                    if (Schema::hasColumn('qs_orders', 'user_id') && !in_array('qs_orders_user_id_index', $indexNames)) {
                        $table->index('user_id');
                    }
                    if (Schema::hasColumn('qs_orders', 'sku') && !in_array('qs_orders_sku_index', $indexNames)) {
                        $table->index('sku');
                    }
                    if (Schema::hasColumn('qs_orders', 'order_status') && !in_array('qs_orders_order_status_index', $indexNames)) {
                        $table->index('order_status');
                    }
                    if (Schema::hasColumn('qs_orders', 'merchant_order_id') && !in_array('qs_orders_merchant_order_id_index', $indexNames)) {
                        $table->index('merchant_order_id');
                    }
                    if (Schema::hasColumn('qs_orders', 'created_at') && !in_array('qs_orders_created_at_index', $indexNames)) {
                        $table->index('created_at');
                    }
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
        Schema::dropIfExists('qs_orders');
    }
}
