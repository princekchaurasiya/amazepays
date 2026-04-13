<?php

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

if (! function_exists('money')) {
    /**
     * Format a numeric amount as Indian Rupees.
     */
    function money(float $amount): string
    {
        return '₹'.number_format($amount, 2, '.', ',');
    }
}

if (! function_exists('tenant_id')) {
    /**
     * Get the current tenant ID (set by ResolveTenant middleware).
     */
    function tenant_id(): ?int
    {
        if (! app()->bound('current_tenant_id')) {
            return null;
        }

        $id = app('current_tenant_id');

        return $id === null ? null : (int) $id;
    }
}

/**
 * Log an admin / user action to the audit trail.
 *
 * @param  string  $action  e.g. 'product.updated', 'wallet.credit'
 * @param  Model|null  $model  The model that was acted upon
 * @param  array  $old  Old values (before change)
 * @param  array  $new  New values (after change)
 */
function audit(string $action, ?Model $model = null, array $old = [], array $new = []): void
{
    try {
        AuditLog::create([
            'tenant_id' => tenant_id(),
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $model ? get_class($model) : null,
            'auditable_id' => $model?->getKey(),
            'old_values' => empty($old) ? null : $old,
            'new_values' => empty($new) ? null : $new,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
        ]);
    } catch (Exception $e) {
        Log::warning('Audit log failed', [
            'action' => $action,
            'error' => $e->getMessage(),
        ]);
    }
}
