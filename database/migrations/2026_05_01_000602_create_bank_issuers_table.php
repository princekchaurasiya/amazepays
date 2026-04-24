<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_issuers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_code', 16)->unique();
            $table->string('logo_url', 512)->nullable();
            $table->string('country', 2)->default('IN');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_issuers');
    }
};
