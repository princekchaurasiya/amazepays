<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Offers / Promotions
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->string('code', 50)->unique()->nullable();
            $table->enum('type', [
                'flat_discount', 'percentage_discount', 'cashback',
                'buy_x_get_y', 'first_purchase', 'category_specific',
                'brand_specific', 'product_specific',
            ]);
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('min_order_value', 10, 2)->default(0);
            $table->decimal('max_discount', 10, 2)->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('per_user_limit')->default(1);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->json('applicable_product_ids')->nullable();
            $table->json('applicable_category_ids')->nullable();
            $table->json('applicable_brand_ids')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'start_date', 'end_date']);
        });

        Schema::create('offer_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('cascade');
            $table->decimal('discount_applied', 10, 2);
            $table->timestamps();

            $table->index(['offer_id', 'user_id']);
        });

        // Wallet load requests — B2B bank/UPI load workflow
        Schema::create('wallet_load_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('set null');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 50);
            $table->string('utr_number', 100)->nullable();
            $table->string('bank_reference', 100)->nullable();
            $table->string('payment_proof_path')->nullable();
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_load_requests');
        Schema::dropIfExists('offer_usages');
        Schema::dropIfExists('offers');
    }
};
