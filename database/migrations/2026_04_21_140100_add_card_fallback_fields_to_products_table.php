<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'card_logo_url')) {
                $table->string('card_logo_url')->nullable()->after('custom_image');
            }
            if (! Schema::hasColumn('products', 'card_bg_color')) {
                $table->string('card_bg_color', 16)->nullable()->after('card_logo_url');
            }
            if (! Schema::hasColumn('products', 'card_text_color')) {
                $table->string('card_text_color', 16)->nullable()->after('card_bg_color');
            }
            if (! Schema::hasColumn('products', 'card_accent_color')) {
                $table->string('card_accent_color', 16)->nullable()->after('card_text_color');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'card_accent_color')) {
                $table->dropColumn('card_accent_color');
            }
            if (Schema::hasColumn('products', 'card_text_color')) {
                $table->dropColumn('card_text_color');
            }
            if (Schema::hasColumn('products', 'card_bg_color')) {
                $table->dropColumn('card_bg_color');
            }
            if (Schema::hasColumn('products', 'card_logo_url')) {
                $table->dropColumn('card_logo_url');
            }
        });
    }
};
