<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gift_card_themes')) {
            return;
        }

        Schema::table('gift_card_themes', function (Blueprint $table) {
            if (! Schema::hasColumn('gift_card_themes', 'gallery_images')) {
                $table->json('gallery_images')->nullable()->after('slug');
            }
        });

        // Remove legacy URL columns after moving to upload-only media handling.
        Schema::table('gift_card_themes', function (Blueprint $table) {
            if (Schema::hasColumn('gift_card_themes', 'thumbnail_url')) {
                $table->dropColumn('thumbnail_url');
            }
            if (Schema::hasColumn('gift_card_themes', 'image_url')) {
                $table->dropColumn('image_url');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('gift_card_themes')) {
            return;
        }

        Schema::table('gift_card_themes', function (Blueprint $table) {
            if (! Schema::hasColumn('gift_card_themes', 'thumbnail_url')) {
                $table->string('thumbnail_url')->nullable()->after('slug');
            }
            if (! Schema::hasColumn('gift_card_themes', 'image_url')) {
                $table->string('image_url')->nullable()->after('thumbnail_url');
            }
            if (Schema::hasColumn('gift_card_themes', 'gallery_images')) {
                $table->dropColumn('gallery_images');
            }
        });
    }
};

