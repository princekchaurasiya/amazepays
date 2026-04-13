<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope to automatically filter records by the current tenant.
 *
 * Applied to models that have a tenant_id column (Order, Wallet, Offer, etc.)
 * The current tenant is resolved by ResolveTenant middleware.
 *
 * Admins (super-admin, admin) bypass this scope to see all tenants' data.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = app('current_tenant_id');

        if (! $tenantId) {
            return;
        }

        // Admins see all data
        if (auth()->check() && auth()->user()->hasAnyRole(['super-admin', 'admin', 'finance'])) {
            return;
        }

        $builder->where($model->getTable().'.tenant_id', $tenantId);
    }
}
