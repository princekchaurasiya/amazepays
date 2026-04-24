<?php

namespace Tests\Guards;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SignatureFunnelTest extends TestCase
{
    #[Test]
    public function webhook_middleware_exists_for_each_provider_signature(): void
    {
        $mustExist = [
            base_path('app/Http/Middleware/VerifyUnlimitSignature.php'),
            base_path('app/Http/Middleware/VerifyCCAvenueSignature.php'),
            base_path('app/Http/Middleware/VerifyRazorpaySignature.php'),
            base_path('app/Http/Middleware/VerifyWoohooSignature.php'),
        ];

        $missing = array_values(array_filter($mustExist, fn ($p) => ! file_exists($p)));

        $this->assertSame([], $missing, "Missing signature middleware:\n- ".implode("\n- ", array_map(fn ($p) => str_replace(base_path().DIRECTORY_SEPARATOR, '', $p), $missing)));
    }

    #[Test]
    public function webhooks_do_not_use_hash_hmac_directly_outside_signature_verifiers(): void
    {
        $paths = [
            base_path('app/Http/Controllers'),
            base_path('app/Services'),
        ];

        $allowedFiles = [
            str_replace('/', DIRECTORY_SEPARATOR, 'app/Http/Middleware/VerifyUnlimitSignature.php'),
            str_replace('/', DIRECTORY_SEPARATOR, 'app/Http/Middleware/VerifyCCAvenueSignature.php'),
            str_replace('/', DIRECTORY_SEPARATOR, 'app/Http/Middleware/VerifyRazorpaySignature.php'),
            str_replace('/', DIRECTORY_SEPARATOR, 'app/Http/Middleware/VerifyWoohooSignature.php'),
            // Outbound request signing is allowed inside gateway/provider boundaries.
            str_replace('/', DIRECTORY_SEPARATOR, 'app/Services/Payment/RazorpayGateway.php'),
            str_replace('/', DIRECTORY_SEPARATOR, 'app/Services/Voucher/WoohooProvider.php'),
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
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $real = $file->getRealPath();
                if (! is_string($real)) {
                    continue;
                }

                $normalized = str_replace(base_path().DIRECTORY_SEPARATOR, '', $real);
                if (in_array($normalized, $allowedFiles, true)) {
                    continue;
                }

                $contents = file_get_contents($real);
                if (! is_string($contents)) {
                    continue;
                }

                if (str_contains($contents, 'hash_hmac(')) {
                    $violations[] = $normalized;
                }
            }
        }

        $this->assertSame([], $violations, "Found hash_hmac() outside signature verifiers:\n- ".implode("\n- ", $violations));
    }
}

