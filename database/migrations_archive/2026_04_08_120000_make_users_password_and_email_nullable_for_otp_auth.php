<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY password VARCHAR(255) NULL');
            DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL');
            DB::statement('ALTER TABLE users MODIFY name VARCHAR(255) NULL');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->string('password')->nullable()->change();
                $table->string('email')->nullable()->change();
                $table->string('name')->nullable()->change();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'referral_code')) {
                $table->string('referral_code', 64)->nullable()->after('mobile');
            }
        });

        // Re-apply unique on email only where non-null is app responsibility; MySQL allows multiple NULLs in UNIQUE
        // If email unique index was dropped by altering nullable in some DBs, re-add — usually it stays.
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'referral_code')) {
                $table->dropColumn('referral_code');
            }
        });
    }
};
