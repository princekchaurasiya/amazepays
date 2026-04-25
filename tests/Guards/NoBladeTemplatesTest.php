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

    #[Test]
    public function repo_contains_no_legacy_php_templates_in_views(): void
    {
        $paths = glob(base_path('resources/views/**/*.php'), GLOB_BRACE) ?: [];

        $allowed = [
            base_path('resources/views/app.php'),
        ];

        $unexpected = array_values(array_diff($paths, $allowed));

        $this->assertSame(
            [],
            $unexpected,
            'Only the Inertia shell (resources/views/app.php) is allowed under resources/views/. Move legacy helpers into app/ (or delete).'
        );
    }
}

