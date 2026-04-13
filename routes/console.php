<?php

use App\Models\BlockedIp;
use App\Models\SecurityEventLog;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes (Scheduled Tasks)
|--------------------------------------------------------------------------
*/

// Voucher catalog sync — runs daily at 2 AM
Schedule::command('voucher:sync-catalog')
    ->dailyAt('02:00')
    ->onOneServer()
    ->withoutOverlapping(60);

// Horizon metrics snapshot
Schedule::command('horizon:snapshot')->everyFiveMinutes();

// Clean expired blocked IPs (those with non-permanent expires_at in the past)
Schedule::call(function () {
    BlockedIp::where('permanent', false)
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->delete();
})->hourly()->name('cleanup-expired-blocked-ips');

// Prune old security event logs (keep last 90 days)
Schedule::call(function () {
    SecurityEventLog::where('created_at', '<', now()->subDays(90))
        ->where('resolved', true)
        ->where('severity', 'info')
        ->delete();
})->weekly()->name('prune-old-security-logs');

// Catalog sync for individual providers (can be triggered manually)
Schedule::command('voucher:sync-catalog --provider=woohoo')
    ->dailyAt('03:00')
    ->onOneServer()
    ->withoutOverlapping(30);
