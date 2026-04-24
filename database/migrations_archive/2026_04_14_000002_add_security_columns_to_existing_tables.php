<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add new security, fraud detection, and tenancy columns to existing tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        // users table: add security, tenancy, and registration fields
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'two_factor_enabled')) {
                    $table->boolean('two_factor_enabled')->default(false)->after('password');
                }
                if (! Schema::hasColumn('users', 'registration_country')) {
                    $table->char('registration_country', 2)->nullable()->after('mobile');
                }
                if (! Schema::hasColumn('users', 'registration_ip')) {
                    $table->string('registration_ip', 45)->nullable()->after('registration_country');
                }
                if (! Schema::hasColumn('users', 'last_login_ip')) {
                    $table->string('last_login_ip', 45)->nullable()->after('registration_ip');
                }
                if (! Schema::hasColumn('users', 'last_login_at')) {
                    $table->timestamp('last_login_at')->nullable()->after('last_login_ip');
                }
                if (! Schema::hasColumn('users', 'last_login_country')) {
                    $table->char('last_login_country', 2)->nullable()->after('last_login_at');
                }
                if (! Schema::hasColumn('users', 'account_locked')) {
                    $table->boolean('account_locked')->default(false)->after('last_login_country');
                }
                if (! Schema::hasColumn('users', 'account_locked_reason')) {
                    $table->string('account_locked_reason')->nullable()->after('account_locked');
                }
                if (! Schema::hasColumn('users', 'account_locked_until')) {
                    $table->timestamp('account_locked_until')->nullable()->after('account_locked_reason');
                }
                if (! Schema::hasColumn('users', 'login_attempts')) {
                    $table->unsignedTinyInteger('login_attempts')->default(0)->after('account_locked_until');
                }
                if (! Schema::hasColumn('users', 'transaction_pin_enabled')) {
                    $table->boolean('transaction_pin_enabled')->default(false)->after('login_attempts');
                }
                if (! Schema::hasColumn('users', 'password_changed_at')) {
                    $table->timestamp('password_changed_at')->nullable()->after('transaction_pin_enabled');
                }
                if (! Schema::hasColumn('users', 'email_changed_at')) {
                    $table->timestamp('email_changed_at')->nullable()->after('password_changed_at');
                }
                if (! Schema::hasColumn('users', 'phone_changed_at')) {
                    $table->timestamp('phone_changed_at')->nullable()->after('email_changed_at');
                }
                if (! Schema::hasColumn('users', 'daily_purchase_limit')) {
                    $table->decimal('daily_purchase_limit', 12, 2)->nullable()->after('phone_changed_at');
                }
                if (! Schema::hasColumn('users', 'monthly_purchase_limit')) {
                    $table->decimal('monthly_purchase_limit', 12, 2)->nullable()->after('daily_purchase_limit');
                }
                if (! Schema::hasColumn('users', 'device_fingerprint')) {
                    $table->string('device_fingerprint', 255)->nullable()->after('monthly_purchase_limit');
                }
            });
        }

        // orders table: add security, fraud detection, and tenancy columns
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (! Schema::hasColumn('orders', 'tenant_id')) {
                    $table->unsignedBigInteger('tenant_id')->nullable()->after('user_id');
                    // FK added in `2026_04_14_000003_create_tenants_table` after `tenants` exists
                }
                if (! Schema::hasColumn('orders', 'offer_id')) {
                    $table->unsignedBigInteger('offer_id')->nullable()->after('tenant_id');
                }
                if (! Schema::hasColumn('orders', 'offer_discount')) {
                    $table->decimal('offer_discount', 10, 2)->default(0)->after('offer_id');
                }
                if (! Schema::hasColumn('orders', 'device_fingerprint')) {
                    $table->string('device_fingerprint', 255)->nullable()->after('offer_discount');
                }
                if (! Schema::hasColumn('orders', 'purchase_ip')) {
                    $table->string('purchase_ip', 45)->nullable()->after('device_fingerprint');
                }
                if (! Schema::hasColumn('orders', 'purchase_country')) {
                    $table->char('purchase_country', 2)->nullable()->after('purchase_ip');
                }
                if (! Schema::hasColumn('orders', 'code_view_count')) {
                    $table->unsignedInteger('code_view_count')->default(0)->after('purchase_country');
                }
                if (! Schema::hasColumn('orders', 'last_code_viewed_at')) {
                    $table->timestamp('last_code_viewed_at')->nullable()->after('code_view_count');
                }
                if (! Schema::hasColumn('orders', 'is_vpn_purchase')) {
                    $table->boolean('is_vpn_purchase')->default(false)->after('last_code_viewed_at');
                }
                if (! Schema::hasColumn('orders', 'maker_id')) {
                    $table->unsignedBigInteger('maker_id')->nullable()->after('is_vpn_purchase');
                }
                if (! Schema::hasColumn('orders', 'checker_id')) {
                    $table->unsignedBigInteger('checker_id')->nullable()->after('maker_id');
                }
                if (! Schema::hasColumn('orders', 'checker_action_at')) {
                    $table->timestamp('checker_action_at')->nullable()->after('checker_id');
                }
            });
        }

        // wallets table
        if (Schema::hasTable('wallets')) {
            Schema::table('wallets', function (Blueprint $table) {
                if (! Schema::hasColumn('wallets', 'tenant_id')) {
                    $table->unsignedBigInteger('tenant_id')->nullable()->after('user_id');
                }
                if (! Schema::hasColumn('wallets', 'daily_load_limit')) {
                    $table->decimal('daily_load_limit', 12, 2)->default(50000)->after('balance');
                }
                if (! Schema::hasColumn('wallets', 'monthly_load_limit')) {
                    $table->decimal('monthly_load_limit', 12, 2)->default(200000)->after('daily_load_limit');
                }
                if (! Schema::hasColumn('wallets', 'is_frozen')) {
                    $table->boolean('is_frozen')->default(false)->after('monthly_load_limit');
                }
                if (! Schema::hasColumn('wallets', 'frozen_reason')) {
                    $table->string('frozen_reason')->nullable()->after('is_frozen');
                }
            });
        }

        // products table: add catalog sync and security columns
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'source_provider')) {
                    $table->string('source_provider', 50)->nullable()->after('id');
                }
                if (! Schema::hasColumn('products', 'last_synced_at')) {
                    $table->timestamp('last_synced_at')->nullable()->after('source_provider');
                }
                if (! Schema::hasColumn('products', 'sync_status')) {
                    $table->string('sync_status', 20)->default('pending')->after('last_synced_at');
                }
                if (! Schema::hasColumn('products', 'brand_id')) {
                    $table->unsignedBigInteger('brand_id')->nullable()->after('sync_status');
                }
            });
        }
    }

    public function down(): void
    {
        // Drop added columns
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'two_factor_enabled', 'registration_country', 'registration_ip',
                'last_login_ip', 'last_login_at', 'last_login_country',
                'account_locked', 'account_locked_reason', 'account_locked_until',
                'login_attempts', 'transaction_pin_enabled', 'password_changed_at',
                'email_changed_at', 'phone_changed_at', 'daily_purchase_limit',
                'monthly_purchase_limit', 'device_fingerprint',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
