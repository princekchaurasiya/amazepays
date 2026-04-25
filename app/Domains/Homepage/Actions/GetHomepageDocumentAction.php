<?php

namespace App\Domains\Homepage\Actions;

use App\Domains\Homepage\Services\HomepageQueryService;

class GetHomepageDocumentAction
{
    public function __construct(
        private HomepageQueryService $query,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(int $tenantId, string $platform, string $surface = 'storefront_home'): array
    {
        return $this->query->homepageDocument($tenantId, $platform, $surface);
    }
}

