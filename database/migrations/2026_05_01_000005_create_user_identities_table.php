<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * OTP-only authentication identity table (mobile-only).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('type', ['mobile'])->default('mobile')->index();
            $table->string('identifier')->comment('10-digit mobile number');
            $table->string('display_identifier')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['identifier']);
            $table->index(['user_id', 'is_primary']);
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('user_identities', function (Blueprint $table) {
                // MySQL-friendly constraint: at most one primary identity per user.
                $table->unsignedBigInteger('primary_user_id')
                    ->nullable()
                    ->storedAs('IF(`is_primary` = 1, `user_id`, NULL)');
            });

            Schema::table('user_identities', function (Blueprint $table) {
                $table->unique('primary_user_id', 'user_identities_one_primary_per_user_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_identities');
    }
};

