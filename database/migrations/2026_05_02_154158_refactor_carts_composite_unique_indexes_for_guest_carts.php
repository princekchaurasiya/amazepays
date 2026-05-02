<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guests are keyed by tenant + session_token; authenticated users by tenant + user_id.
 * Drop the global session_token uniqueness so multiple tenants can coexist.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropUnique(['session_token']);
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->unique(['tenant_id', 'session_token']);
            $table->unique(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'session_token']);
            $table->dropUnique(['tenant_id', 'user_id']);
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->unique('session_token');
        });
    }
};
