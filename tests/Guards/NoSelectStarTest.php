<?php

namespace Tests\Guards;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class NoSelectStarTest extends TestCase
{
    #[Test]
    public function app_does_not_use_select_star_in_query_builder(): void
    {
        $paths = [
            base_path('app'),
        ];

        $violations = [];

        foreach ($paths as $path) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));

            foreach ($files as $file) {
                if (! $file instanceof \SplFileInfo) {
                    continue;
                }
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $real = $file->getRealPath();
                if (! is_string($real)) {
                    continue;
                }

                $contents = file_get_contents($real);
                if (! is_string($contents)) {
                    continue;
                }

                if (preg_match('/->select\\(\\s*[\'"]\\*[\'"]\\s*\\)/', $contents) === 1) {
                    $violations[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $real);
                }
            }
        }

        $this->assertSame([], $violations, "Found forbidden select('*') usage:\n- ".implode("\n- ", $violations));
    }
}

