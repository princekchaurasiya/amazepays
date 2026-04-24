<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_whitelists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('added_by_user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('ip_address', 64);
            $table->string('cidr', 64)->nullable();
            $table->string('label');
            $table->enum('scope', ['webhook_unlimit', 'webhook_ccavenue', 'webhook_razorpay', 'webhook_woohoo', 'admin', 'api', 'all'])->default('all');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'ip_address', 'scope']);
            $table->index('scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_whitelists');
    }
};
