<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'gift_option_policy')) {
                $table->string('gift_option_policy', 20)->default('both')->after('allowedfulfillments');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'gift_option_policy')) {
                $table->dropColumn('gift_option_policy');
            }
        });
    }
};
