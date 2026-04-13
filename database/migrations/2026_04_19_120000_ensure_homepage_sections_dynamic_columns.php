<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent fix: some environments failed the first migration (e.g. `after()` on older MySQL)
 * or never ran it, causing "Unknown column 'sort_order'".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('homepage_sections')) {
            return;
        }

        if (! Schema::hasColumn('homepage_sections', 'section_type')) {
            Schema::table('homepage_sections', function (Blueprint $table) {
                $table->string('section_type', 64)->default('custom_html');
            });
        }

        if (! Schema::hasColumn('homepage_sections', 'title')) {
            Schema::table('homepage_sections', function (Blueprint $table) {
                $table->string('title')->nullable();
            });
        }

        if (! Schema::hasColumn('homepage_sections', 'config')) {
            Schema::table('homepage_sections', function (Blueprint $table) {
                $table->json('config')->nullable();
            });
        }

        if (! Schema::hasColumn('homepage_sections', 'sort_order')) {
            Schema::table('homepage_sections', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0);
            });
        }

        // Backfill legacy hero row
        if (Schema::hasColumn('homepage_sections', 'section_type') && Schema::hasColumn('homepage_sections', 'sort_order')) {
            DB::table('homepage_sections')->where('section_name', 'hero')->update([
                'section_type' => 'custom_html',
                'sort_order' => 90,
            ]);
        }

        $defaults = [
            ['section_name' => 'banner', 'section_type' => 'banner', 'title' => null, 'content' => null, 'status' => true, 'sort_order' => 1, 'config' => null],
            ['section_name' => 'brands', 'section_type' => 'brands', 'title' => 'Popular Brands', 'content' => null, 'status' => true, 'sort_order' => 2, 'config' => null],
            ['section_name' => 'hot_deals', 'section_type' => 'hot_deals', 'title' => 'Hot Deals', 'content' => null, 'status' => true, 'sort_order' => 3, 'config' => json_encode(['priority_product_count' => 10])],
            ['section_name' => 'categories', 'section_type' => 'categories', 'title' => 'Categories', 'content' => null, 'status' => true, 'sort_order' => 4, 'config' => null],
            ['section_name' => 'other_deals', 'section_type' => 'other_deals', 'title' => 'Other Deals', 'content' => null, 'status' => true, 'sort_order' => 5, 'config' => null],
            ['section_name' => 'kgen', 'section_type' => 'kgen', 'title' => 'KGen Technology', 'content' => null, 'status' => true, 'sort_order' => 6, 'config' => null],
        ];

        foreach ($defaults as $row) {
            if (DB::table('homepage_sections')->where('section_name', $row['section_name'])->exists()) {
                continue;
            }
            DB::table('homepage_sections')->insert([
                'section_name' => $row['section_name'],
                'section_type' => $row['section_type'],
                'title' => $row['title'],
                'content' => $row['content'],
                'status' => $row['status'] ? 1 : 0,
                'sort_order' => $row['sort_order'],
                'config' => $row['config'],
            ]);
        }
    }

    public function down(): void
    {
        // Non-destructive: do not drop columns (may be required by app).
    }
};
