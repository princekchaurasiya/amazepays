<?php

namespace Tests\Guards;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProviderSecretBoundaryTest extends TestCase
{
    #[Test]
    public function client_bundles_do_not_contain_provider_secrets_like_keys_or_tokens(): void
    {
        $paths = [
            base_path('public/build'),
        ];

        $patterns = [
            // Real credential/token shapes or obvious assignments (avoid provider-name false positives).
            '/rzp_(live|test)_[0-9a-zA-Z]{10,}/',
            '/key[_-]?secret\\s*[:=]/i',
            '/client[_-]?secret\\s*[:=]/i',
            '/webhook[_-]?secret\\s*[:=]/i',
            '/access[_-]?token\\s*[:=]/i',
            "/authorization\\s*[:=]\\s*[\\\"\\']Bearer\\s+[0-9a-zA-Z\\-\\._]{16,}[\\\"\\']/i",
            '/BEGIN\\s+(RSA|EC|DSA)?\\s*PRIVATE\\s+KEY/i',
        ];

        $violations = [];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
            foreach ($files as $file) {
                if (! $file instanceof \SplFileInfo) {
                    continue;
                }
                if (! $file->isFile()) {
                    continue;
                }

                $ext = strtolower($file->getExtension());
                if (! in_array($ext, ['js', 'css', 'map', 'json', 'txt', 'html'], true)) {
                    continue;
                }

                $real = $file->getRealPath();
                if (! is_string($real)) {
                    continue;
                }

                $contents = @file_get_contents($real);
                if (! is_string($contents) || $contents === '') {
                    continue;
                }

                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $contents) === 1) {
                        $violations[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $real).' matched '.$pattern;
                        break;
                    }
                }
            }
        }

        $this->assertSame([], $violations, "Potential secret leakage found in client bundles:\n- ".implode("\n- ", $violations));
    }
}

