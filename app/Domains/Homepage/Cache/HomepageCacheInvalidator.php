<?php

namespace App\Domains\Homepage\Cache;

use Illuminate\Support\Facades\Cache;

class HomepageCacheInvalidator
{
    public function invalidate(int $tenantId): void
    {
        // Tags are supported by Redis and the common production cache drivers.
        Cache::tags(['tenant', (string) $tenantId, 'homepage'])->flush();
    }
}

