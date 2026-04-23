<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'gift_delivery_option')) {
                $table->string('gift_delivery_option', 20)->nullable()->after('gift_message_title');
            }
            if (! Schema::hasColumn('orders', 'gift_delivery_at')) {
                $table->dateTime('gift_delivery_at')->nullable()->after('gift_delivery_option');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'gift_delivery_at')) {
                $table->dropColumn('gift_delivery_at');
            }
            if (Schema::hasColumn('orders', 'gift_delivery_option')) {
                $table->dropColumn('gift_delivery_option');
            }
        });
    }
};
