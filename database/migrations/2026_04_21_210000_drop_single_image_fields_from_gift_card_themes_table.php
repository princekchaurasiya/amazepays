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
            if (Schema::hasColumn('gift_card_themes', 'thumbnail_path')) {
                $table->dropColumn('thumbnail_path');
            }
            if (Schema::hasColumn('gift_card_themes', 'preview_image_path')) {
                $table->dropColumn('preview_image_path');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('gift_card_themes')) {
            return;
        }

        Schema::table('gift_card_themes', function (Blueprint $table) {
            if (! Schema::hasColumn('gift_card_themes', 'thumbnail_path')) {
                $table->string('thumbnail_path')->nullable()->after('slug');
            }
            if (! Schema::hasColumn('gift_card_themes', 'preview_image_path')) {
                $table->string('preview_image_path')->nullable()->after('thumbnail_path');
            }
        });
    }
};

