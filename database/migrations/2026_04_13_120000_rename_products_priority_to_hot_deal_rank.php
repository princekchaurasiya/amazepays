<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('priority', 'hot_deal_rank');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('hot_deal_rank', 'priority');
        });
    }
};
