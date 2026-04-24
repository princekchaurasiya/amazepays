<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Landing-page contact form submissions. Converted to support_tickets by a queued job.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_us', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('converted_ticket_id')->nullable()->constrained('support_tickets')->cascadeOnUpdate()->nullOnDelete();
            $table->string('full_name');
            $table->string('email');
            $table->string('mobile', 32)->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('source_ip', 64)->nullable();
            $table->enum('status', ['new', 'contacted', 'resolved', 'spam'])->default('new')->index();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_us');
    }
};
