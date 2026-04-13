<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AuditLogDemoSeeder extends Seeder
{
    public function run(): void
    {
        AuditLog::where('user_agent', 'AuditLogDemoSeeder')->delete();

        $admin = User::where('email', 'admin@amazepays.in')->first();

        $rows = [
            [
                'action' => 'mobile.manual_blocked',
                'new_values' => ['mobile' => '9998887776', 'reason' => 'Demo: suspicious pattern'],
                'hours_ago' => 1,
            ],
            [
                'action' => 'ip.manual_blocked',
                'new_values' => ['ip' => '203.0.113.50', 'reason' => 'Demo manual block'],
                'hours_ago' => 2,
            ],
            [
                'action' => 'product.updated',
                'old_values' => ['name' => 'Gift card A'],
                'new_values' => ['name' => 'Gift card A (updated)', 'sku' => 'GC-DEMO-01'],
                'hours_ago' => 5,
            ],
            [
                'action' => 'offer.created',
                'new_values' => ['code' => 'WELCOME10', 'type' => 'flat_discount'],
                'hours_ago' => 8,
            ],
            [
                'action' => 'order.status_changed',
                'old_values' => ['order_status' => 'pending'],
                'new_values' => ['order_status' => 'processing'],
                'hours_ago' => 12,
            ],
            [
                'action' => 'settings.updated',
                'new_values' => ['keys' => ['app.name', 'mail.from.address']],
                'hours_ago' => 18,
            ],
            [
                'action' => 'user.role_synced',
                'new_values' => ['roles' => ['admin']],
                'hours_ago' => 24,
            ],
            [
                'action' => 'demo.audit_sample',
                'new_values' => ['note' => 'Sample row from AuditLogDemoSeeder'],
                'hours_ago' => 36,
            ],
        ];

        foreach ($rows as $row) {
            AuditLog::create([
                'tenant_id' => null,
                'user_id' => $admin?->id,
                'action' => $row['action'],
                'auditable_type' => null,
                'auditable_id' => null,
                'old_values' => $row['old_values'] ?? null,
                'new_values' => $row['new_values'] ?? null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'AuditLogDemoSeeder',
                'url' => 'http://127.0.0.1:8000/panel/audit-logs',
                'created_at' => Carbon::now()->subHours($row['hours_ago']),
            ]);
        }

        $this->command->info('Seeded '.count($rows).' demo audit log rows (see /panel/audit-logs).');
    }
}
