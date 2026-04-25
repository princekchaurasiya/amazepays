<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();

            // Public URL or storage path; rendering layer decides how to build final URL.
            $table->string('path', 1024);
            $table->string('disk', 64)->default('public');
            $table->string('mime', 128)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('sha256', 64)->nullable();
            $table->string('alt_text')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'disk']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};

