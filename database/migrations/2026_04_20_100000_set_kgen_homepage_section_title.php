<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Default KGen block title: "KGen Technology" (replaces older "Featured KGen Products" copy).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('homepage_sections')) {
            return;
        }

        DB::table('homepage_sections')
            ->where('section_type', 'kgen')
            ->where(function ($q) {
                $q->whereNull('title')
                    ->orWhere('title', '')
                    ->orWhere('title', 'Featured KGen Products');
            })
            ->update(['title' => 'KGen Technology']);
    }

    public function down(): void
    {
        DB::table('homepage_sections')
            ->where('section_type', 'kgen')
            ->where('title', 'KGen Technology')
            ->update(['title' => 'Featured KGen Products']);
    }
};
