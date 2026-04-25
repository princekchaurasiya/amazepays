<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add ezpin + gyftrr to gift_cards.provider (MySQL ENUM + SQLite CHECK).
 */
return new class extends Migration
{
    /** @return list<string> */
    private function providers(): array
    {
        return ['woohoo', 'vouchagram_send', 'vouchagram_pull', 'vd', 'kgen', 'lysto', 'internal', 'ezpin', 'gyftrr'];
    }

    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $list = implode("','", $this->providers());
            DB::statement("ALTER TABLE gift_cards MODIFY COLUMN provider ENUM('{$list}') NOT NULL");

            return;
        }

        if ($driver !== 'sqlite') {
            return;
        }

        $row = DB::selectOne("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'gift_cards'");
        if ($row === null || empty($row->sql)) {
            return;
        }

        if (str_contains((string) $row->sql, 'ezpin')) {
            return;
        }

        DB::statement('PRAGMA foreign_keys = OFF');

        Schema::rename('gift_cards', 'gift_cards__bak_enum');

        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('provider_order_id')->nullable()->constrained('provider_orders')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnUpdate()->nullOnDelete();
            $table->enum('provider', ['woohoo', 'vouchagram_send', 'vouchagram_pull', 'vd', 'kgen', 'lysto', 'internal', 'ezpin', 'gyftrr'])->index();
            $table->text('card_number_encrypted')->nullable();
            $table->text('card_pin_encrypted')->nullable();
            $table->string('card_last4', 4)->nullable();
            $table->string('external_card_id', 128)->nullable();
            $table->bigInteger('face_value_minor');
            $table->char('currency', 3)->default('INR');
            $table->enum('status', ['issued', 'active', 'redeemed', 'partially_redeemed', 'expired', 'cancelled'])->default('active');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['provider', 'external_card_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'provider', 'status']);
        });

        DB::statement('
            INSERT INTO gift_cards (
                id, tenant_id, order_id, order_item_id, provider_order_id, product_id, provider,
                card_number_encrypted, card_pin_encrypted, card_last4, external_card_id,
                face_value_minor, currency, status, valid_from, valid_until, issued_at, redeemed_at,
                created_at, updated_at, deleted_at
            )
            SELECT
                id, tenant_id, order_id, order_item_id, provider_order_id, product_id, provider,
                card_number_encrypted, card_pin_encrypted, card_last4, external_card_id,
                face_value_minor, currency, status, valid_from, valid_until, issued_at, redeemed_at,
                created_at, updated_at, deleted_at
            FROM gift_cards__bak_enum
        ');

        Schema::drop('gift_cards__bak_enum');

        $maxId = (int) (DB::table('gift_cards')->max('id') ?? 0);
        if ($maxId > 0) {
            DB::table('sqlite_sequence')->updateOrInsert(
                ['name' => 'gift_cards'],
                ['seq' => $maxId]
            );
        }

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        // Irreversible for SQLite (would drop ezpin/gyftrr rows). MySQL could shrink ENUM but risks data loss.
    }
};
