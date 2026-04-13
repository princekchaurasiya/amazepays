<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_media')) {
            return;
        }
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id')->nullable();
            if (Schema::hasTable('tenants')) {
                $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            }
            $table->string('collection', 30)->default('gallery');
            $table->string('disk', 30)->default('public');
            $table->string('path', 500);
            $table->string('filename', 255);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('alt_text', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'collection', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_media');
    }
};
