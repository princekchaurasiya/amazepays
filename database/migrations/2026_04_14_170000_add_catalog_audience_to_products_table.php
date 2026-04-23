<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('catalog_audience', 10)->default('both')->after('source_provider');
        });

        DB::table('products')->where('source_provider', 'vouchagram_pull')->update(['catalog_audience' => 'b2b']);

        DB::table('products')->whereIn('source_provider', ['vouchagram_send', 'vouchagram'])->update(['catalog_audience' => 'b2c']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('catalog_audience');
        });
    }
};
