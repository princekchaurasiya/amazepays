<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_mobiles', function (Blueprint $table) {
            $table->id();
            $table->string('mobile', 15)->unique();
            $table->text('reason');
            $table->timestamp('blocked_at');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('auto_blocked')->default(false);
            $table->foreignId('blocked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedInteger('block_count')->default(1);
            $table->boolean('permanent')->default(false);
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_mobiles');
    }
};
