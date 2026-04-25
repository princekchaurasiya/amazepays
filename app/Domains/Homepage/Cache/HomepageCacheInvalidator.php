<?php

namespace App\Domains\Homepage\Cache;

use Illuminate\Support\Facades\Cache;

class HomepageCacheInvalidator
{
    public function invalidate(int $tenantId): void
    {
        // Cache tags are not supported by all stores (e.g. file).
        try {
            Cache::tags(['tenant', (string) $tenantId, 'homepage'])->flush();
        } catch (\BadMethodCallException|\InvalidArgumentException $e) {
            // Fallback: best-effort no-op; TTL is short and cache keys are tenant-scoped.
        }
    }
}

