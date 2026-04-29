<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('parent_rule_id')->nullable()->constrained('offer_rules')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('node_type', ['group', 'leaf'])->default('leaf');
            $table->enum('join_operator', ['and', 'or'])->nullable();
            $table->string('field', 64)->nullable();
            $table->string('operator', 32)->nullable();
            $table->json('value')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['offer_id', 'parent_rule_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_rules');
    }
};
