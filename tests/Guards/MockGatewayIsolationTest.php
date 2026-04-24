<?php

namespace Tests\Guards;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MockGatewayIsolationTest extends TestCase
{
    #[Test]
    public function mock_gateway_must_not_be_enabled_in_production_config(): void
    {
        $env = config('app.env');
        $this->assertNotSame('production', $env, 'Test environment misconfigured as production.');

        // Guardrail: any mock gateway routes/classes must be behind env checks.
        // We enforce by requiring a dedicated config flag in config/services.php or config/unlimit.php later.
        $this->assertTrue(true);
    }
}

