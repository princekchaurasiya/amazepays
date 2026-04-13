<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class SeedDemoAuditLogs extends Command
{
    protected $signature = 'audit:seed-demo
                            {--force : Allow running outside local (will prompt for confirmation)}';

    protected $description = 'Insert sample rows into audit_logs for local UI testing (local env by default)';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            if (! $this->option('force')) {
                $this->error('Run only in local, or pass --force (you will be asked to confirm).');

                return self::FAILURE;
            }
            if (! $this->confirm('Environment is not local. Insert demo audit log rows anyway?', false)) {
                $this->info('Aborted.');

                return self::SUCCESS;
            }
        }

        $samples = [
            [
                'action' => 'demo.security_view',
                'new_values' => ['note' => 'Sample entry: security dashboard opened in dev'],
            ],
            [
                'action' => 'mobile.manual_blocked',
                'new_values' => ['mobile' => '9999999999', 'reason' => 'Demo block'],
            ],
            [
                'action' => 'product.updated',
                'old_values' => ['name' => 'Old demo name'],
                'new_values' => ['name' => 'New demo name', 'sku' => 'DEMO-SKU'],
            ],
            [
                'action' => 'offer.created',
                'new_values' => ['code' => 'DEMO10', 'type' => 'flat_discount'],
            ],
        ];

        foreach ($samples as $row) {
            AuditLog::create([
                'tenant_id' => null,
                'user_id' => null,
                'action' => $row['action'],
                'auditable_type' => null,
                'auditable_id' => null,
                'old_values' => $row['old_values'] ?? null,
                'new_values' => $row['new_values'] ?? null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'audit:seed-demo',
                'url' => 'http://127.0.0.1:8000/panel/audit-logs',
            ]);
        }

        $this->info('Inserted '.count($samples).' demo audit log rows. Open /panel/audit-logs to view them.');

        return self::SUCCESS;
    }
}
