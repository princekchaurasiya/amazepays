<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_sections', function (Blueprint $table) {
            $table->string('section_type', 64)->default('custom_html')->after('section_name');
            $table->string('title')->nullable()->after('section_type');
            $table->json('config')->nullable()->after('content');
            $table->unsignedInteger('sort_order')->default(0)->after('status');
        });

        // Legacy hero row: treat as custom HTML block
        DB::table('homepage_sections')->where('section_name', 'hero')->update([
            'section_type' => 'custom_html',
            'sort_order' => 90,
        ]);

        $defaults = [
            [
                'section_name' => 'banner',
                'section_type' => 'banner',
                'title' => null,
                'content' => null,
                'status' => true,
                'sort_order' => 1,
                'config' => null,
            ],
            [
                'section_name' => 'brands',
                'section_type' => 'brands',
                'title' => 'Popular Brands',
                'content' => null,
                'status' => true,
                'sort_order' => 2,
                'config' => null,
            ],
            [
                'section_name' => 'hot_deals',
                'section_type' => 'hot_deals',
                'title' => 'Hot Deals',
                'content' => null,
                'status' => true,
                'sort_order' => 3,
                'config' => json_encode(['priority_product_count' => 10]),
            ],
            [
                'section_name' => 'categories',
                'section_type' => 'categories',
                'title' => 'Categories',
                'content' => null,
                'status' => true,
                'sort_order' => 4,
                'config' => null,
            ],
            [
                'section_name' => 'other_deals',
                'section_type' => 'other_deals',
                'title' => 'Other Deals',
                'content' => null,
                'status' => true,
                'sort_order' => 5,
                'config' => null,
            ],
            [
                'section_name' => 'kgen',
                'section_type' => 'kgen',
                'title' => 'KGen Technology',
                'content' => null,
                'status' => true,
                'sort_order' => 6,
                'config' => null,
            ],
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
        Schema::table('homepage_sections', function (Blueprint $table) {
            $table->dropColumn(['section_type', 'title', 'config', 'sort_order']);
        });
    }
};
