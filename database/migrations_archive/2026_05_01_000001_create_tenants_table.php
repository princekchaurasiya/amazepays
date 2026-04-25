<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Root table for multi-tenancy. Every business table carries tenant_id FK -> tenants.id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name');
            $table->string('display_name')->nullable();
            $table->enum('type', ['platform', 'b2c_brand', 'b2b_partner', 'corporate'])->default('b2c_brand');
            $table->enum('status', ['active', 'suspended', 'archived'])->default('active');
            $table->string('default_locale', 10)->default('en');
            $table->string('default_currency', 3)->default('INR');
            $table->string('default_timezone', 64)->default('Asia/Kolkata');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
