<?php

namespace Tests\Feature\Security;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ResponseEnvelopeTest extends TestCase
{
    #[Test]
    public function api_v1_and_webhook_paths_do_not_use_response_json_directly(): void
    {
        $paths = [
            base_path('app/Http/Controllers/Api/V1'),
            base_path('app/Http/Controllers/Payment'),
            base_path('app/Http/Controllers/Voucher'),
            base_path('app/Http/Middleware/VerifyUnlimitSignature.php'),
            base_path('app/Http/Middleware/VerifyCCAvenueSignature.php'),
            base_path('app/Http/Middleware/VerifyRazorpaySignature.php'),
            base_path('app/Http/Middleware/VerifyWoohooSignature.php'),
            base_path('app/Exceptions/Handler.php'),
        ];

        $violations = [];

        foreach ($paths as $path) {
            if (! file_exists($path)) {
                continue;
            }

            $files = is_dir($path)
                ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path))
                : [new \SplFileInfo($path)];

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
                if (str_contains($real, DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Support'.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'ResponseFormatter.php')) {
                    continue;
                }
                $contents = file_get_contents($real);
                if (! is_string($contents)) {
                    continue;
                }

                if (str_contains($contents, 'response()->json(')) {
                    $violations[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $real);
                }
            }
        }

        $this->assertSame([], $violations, "Found forbidden response()->json() usage:\n- ".implode("\n- ", $violations));
    }
}
