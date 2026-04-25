<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

final class DocsGenerateRouteMap extends Command
{
    protected $signature = 'docs:route-map';

    protected $description = 'Generate docs/ROUTE_MAP.md from current route:list output';

    public function handle(): int
    {
        $lines = [];
        $lines[] = '# ROUTE_MAP (generated)';
        $lines[] = '';
        $lines[] = 'Generated at: '.now()->toDateTimeString();
        $lines[] = '';
        $lines[] = '```';

        $output = $this->callSilent('route:list', ['--compact' => true]);
        // callSilent returns exit code; actual output is on stdout buffer, so we re-run via Artisan.
        // Keep this generator intentionally simple and deterministic.
        $list = \Illuminate\Support\Facades\Artisan::output();
        $lines[] = trim($list);
        $lines[] = '```';
        $lines[] = '';

        $path = base_path('docs/ROUTE_MAP.md');
        file_put_contents($path, implode(PHP_EOL, $lines).PHP_EOL);

        $this->info("Wrote {$path}");

        return $output === 0 ? self::SUCCESS : self::FAILURE;
    }
}

