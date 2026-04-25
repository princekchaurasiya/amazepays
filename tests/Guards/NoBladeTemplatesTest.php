<?php

declare(strict_types=1);

namespace Tests\Guards;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class NoBladeTemplatesTest extends TestCase
{
    #[Test]
    public function repo_contains_no_blade_templates(): void
    {
        $paths = glob(base_path('resources/views/**/*.blade.php'), GLOB_BRACE) ?: [];
        $this->assertSame([], $paths, 'Blade templates must not exist in Phase 3 baseline.');
    }
}

