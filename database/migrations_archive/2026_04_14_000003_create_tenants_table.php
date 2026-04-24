<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['platform', 'b2b', 'b2c'])->default('b2b');
            $table->enum('status', ['active', 'suspended', 'deactivated'])->default('active');
            $table->string('business_name')->nullable();
            $table->string('gst_number', 15)->nullable();
            $table->string('pan_number', 10)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->char('country_code', 2)->default('IN');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->string('webhook_url')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->decimal('margin_percentage', 5, 2)->default(0);
            $table->boolean('order_approval_required')->default(false);
            $table->decimal('maker_checker_threshold', 12, 2)->default(50000);
            $table->boolean('business_hours_only')->default(false);
            $table->json('business_hours')->nullable();
            $table->json('allowed_payment_methods')->nullable();
            $table->json('settings')->nullable();
            $table->string('suspension_reason')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('contract_start')->nullable();
            $table->timestamp('contract_end')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('status');
        });

        Schema::create('tenant_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('role', ['owner', 'manager', 'operator', 'viewer'])->default('operator');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('tenant_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->unsignedInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->decimal('custom_price', 10, 2)->nullable();
            $table->decimal('margin_override', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'product_id']);
        });

        Schema::create('tenant_payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('gateway', 50);
            $table->text('credentials');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_sandbox')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'gateway']);
        });

        Schema::create('tenant_voucher_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('provider', 50);
            $table->text('credentials');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'provider']);
        });

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'tenant_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'tenant_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
            });
        }

        Schema::dropIfExists('tenant_voucher_providers');
        Schema::dropIfExists('tenant_payment_gateways');
        Schema::dropIfExists('tenant_products');
        Schema::dropIfExists('tenant_users');
        Schema::dropIfExists('tenants');
    }
};
