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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('gift_send_option')->nullable()->after('denomination');
            $table->string('receiver_name')->nullable()->after('gift_send_option');
            $table->string('receiver_email')->nullable()->after('receiver_name');
            $table->string('receiver_mobile')->nullable()->after('receiver_email');
            $table->text('receiver_msg')->nullable()->after('receiver_mobile');
            $table->string('delivery_mode')->nullable()->after('receiver_msg');
            $table->foreignId('gift_theme_id')->nullable()->after('delivery_mode')->constrained('gift_themes')->nullOnDelete();
            $table->string('gift_message_title')->nullable()->after('gift_theme_id');
            $table->string('sender_first_name')->nullable()->after('gift_message_title');
            $table->string('gift_delivery_option')->nullable()->after('sender_first_name');
            $table->timestamp('gift_delivery_at')->nullable()->after('gift_delivery_option');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('gift_theme_id')->nullable()->after('product_id')->constrained('gift_themes')->nullOnDelete();
            $table->string('gift_send_option')->nullable()->after('gift_theme_id');
            $table->string('gift_message_title')->nullable()->after('gift_send_option');
            $table->string('gift_delivery_option')->nullable()->after('gift_message_title');
            $table->timestamp('gift_delivery_at')->nullable()->after('gift_delivery_option');
            $table->string('sender_first_name')->nullable()->after('gift_delivery_at');
            $table->string('receiver_name')->nullable()->after('sender_first_name');
            $table->string('receiver_email')->nullable()->after('receiver_name');
            $table->string('receiver_mobile')->nullable()->after('receiver_email');
            $table->text('receiver_msg')->nullable()->after('receiver_mobile');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['gift_theme_id']);
            $table->dropColumn([
                'gift_theme_id',
                'gift_send_option',
                'gift_message_title',
                'gift_delivery_option',
                'gift_delivery_at',
                'sender_first_name',
                'receiver_name',
                'receiver_email',
                'receiver_mobile',
                'receiver_msg',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['gift_theme_id']);
            $table->dropColumn([
                'gift_send_option',
                'receiver_name',
                'receiver_email',
                'receiver_mobile',
                'receiver_msg',
                'delivery_mode',
                'gift_theme_id',
                'gift_message_title',
                'sender_first_name',
                'gift_delivery_option',
                'gift_delivery_at',
            ]);
        });
    }
};
