<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_blocked')) {
                $table->boolean('is_blocked')->default(false)->after('status')->index();
            }
            if (! Schema::hasColumn('users', 'can_transact')) {
                $table->boolean('can_transact')->default(true)->after('is_blocked')->index();
            }
            if (! Schema::hasColumn('users', 'restricted_features')) {
                $table->json('restricted_features')->nullable()->after('can_transact');
            }
            if (! Schema::hasColumn('users', 'restriction_reason')) {
                $table->string('restriction_reason', 255)->nullable()->after('restricted_features');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'restriction_reason')) {
                $table->dropColumn('restriction_reason');
            }
            if (Schema::hasColumn('users', 'restricted_features')) {
                $table->dropColumn('restricted_features');
            }
            if (Schema::hasColumn('users', 'can_transact')) {
                $table->dropColumn('can_transact');
            }
            if (Schema::hasColumn('users', 'is_blocked')) {
                $table->dropColumn('is_blocked');
            }
        });
    }
};

