<?php

namespace Tests\Guards;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ForeignKeyDisciplineTest extends TestCase
{
    use RefreshDatabase;

    public function test_foreign_keys_exist_for_key_relationships(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'sqlite') {
            $this->markTestSkipped('This guard currently validates SQLite FK metadata.');
        }

        $foreignKeys = collect(DB::select("PRAGMA foreign_key_list('payments')"));
        $this->assertTrue($foreignKeys->contains(fn ($fk) => $fk->table === 'orders'), 'payments.order_id must reference orders');
        $this->assertTrue($foreignKeys->contains(fn ($fk) => $fk->table === 'tenants'), 'payments.tenant_id must reference tenants');

        $foreignKeys = collect(DB::select("PRAGMA foreign_key_list('orders')"));
        $this->assertTrue($foreignKeys->contains(fn ($fk) => $fk->table === 'tenants'), 'orders.tenant_id must reference tenants');
    }
}

