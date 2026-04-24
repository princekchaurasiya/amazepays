<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 120);
            $table->string('idempotency_key', 120);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('request_hash', 64)->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['scope', 'idempotency_key'], 'idempotency_scope_key_unique');
            $table->index(['user_id', 'created_at'], 'idempotency_user_created_at_index');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
