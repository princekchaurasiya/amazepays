<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_category_eligibility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('mode', ['include', 'exclude'])->default('include');
            $table->timestamps();

            $table->unique(['offer_id', 'category_id']);
            $table->index(['category_id', 'mode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_category_eligibility');
    }
};
