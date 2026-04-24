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
            if (! Schema::hasColumn('orders', 'gift_theme_id')) {
                $table->unsignedBigInteger('gift_theme_id')->nullable()->after('receiver_msg');
                $table->index('gift_theme_id');
            }

            if (! Schema::hasColumn('orders', 'gift_message_title')) {
                $table->string('gift_message_title', 120)->nullable()->after('gift_theme_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'gift_message_title')) {
                $table->dropColumn('gift_message_title');
            }

            if (Schema::hasColumn('orders', 'gift_theme_id')) {
                $table->dropIndex(['gift_theme_id']);
                $table->dropColumn('gift_theme_id');
            }
        });
    }
};
