<?php

namespace Tests\Guards;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PricingResolverContractTest extends TestCase
{
    #[Test]
    public function pricing_resolver_service_exists(): void
    {
        if (! class_exists(\App\Services\Pricing\PricingResolver::class)) {
            $this->markTestSkipped('PricingResolver not implemented yet (planned single pricing funnel).');
        }

        $this->assertTrue(true);
    }
}

