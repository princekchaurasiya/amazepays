<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // cc_avenue_payment.order_id: created as unsignedBigInteger without FK due to migration order.
        Schema::table('cc_avenue_payment', function (Blueprint $table) {
            if (Schema::hasColumn('cc_avenue_payment', 'order_id')) {
                $table->foreign('order_id', 'cc_avenue_payment_order_id_foreign')
                    ->references('id')
                    ->on('orders')
                    ->nullOnDelete();
            }
        });

        // unlimit_payment user/order references were defined without FKs.
        Schema::table('unlimit_payment', function (Blueprint $table) {
            if (Schema::hasColumn('unlimit_payment', 'user_id')) {
                $table->foreign('user_id', 'unlimit_payment_user_id_foreign')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
            if (Schema::hasColumn('unlimit_payment', 'order_id')) {
                $table->foreign('order_id', 'unlimit_payment_order_id_foreign')
                    ->references('id')
                    ->on('orders')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unlimit_payment', function (Blueprint $table) {
            if (Schema::hasColumn('unlimit_payment', 'order_id')) {
                $table->dropForeign('unlimit_payment_order_id_foreign');
            }
            if (Schema::hasColumn('unlimit_payment', 'user_id')) {
                $table->dropForeign('unlimit_payment_user_id_foreign');
            }
        });

        Schema::table('cc_avenue_payment', function (Blueprint $table) {
            if (Schema::hasColumn('cc_avenue_payment', 'order_id')) {
                $table->dropForeign('cc_avenue_payment_order_id_foreign');
            }
        });
    }
};
