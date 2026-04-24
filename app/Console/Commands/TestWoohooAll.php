<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class TestWoohooAll extends Command
{
    protected $signature = 'woohoo:test-all 
                            {--skip-catalog : Skip catalog API tests}
                            {--skip-order : Skip order API tests}
                            {--detailed : Show detailed output}';

    protected $description = 'Run all Woohoo API test scenarios';

    public function handle()
    {
        $this->info('╔══════════════════════════════════════════════════════════╗');
        $this->info('║     Woohoo API Comprehensive Test Suite                 ║');
        $this->info('╚══════════════════════════════════════════════════════════╝');
        $this->newLine();

        $results = [];
        $skipCatalog = $this->option('skip-catalog');
        $skipOrder = $this->option('skip-order');
        $detailed = $this->option('detailed');

        // Test Catalog API
        if (! $skipCatalog) {
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('📋 Testing Catalog API');
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            $results['catalog_categories'] = $this->runTest('woohoo:test-catalog', [
                '--type' => 'categories',
            ], $detailed);

            // Test products if we have a category ID (you may need to adjust this)
            // For now, we'll skip products test as it requires a valid category ID
        }

        // Test Order API Scenarios
        if (! $skipOrder) {
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('🛒 Testing Order API - Success Scenarios');
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            // 1.1 Success - Single Card (CNPIN)
            $results['order_success_single'] = $this->runTest('woohoo:test-order', [
                '--sku' => 'CNPIN',
                '--amount' => '1',
                '--qty' => '1',
                '--scenario' => 'success-single',
            ], $detailed);

            // 1.2 Success - Multiple Cards (CNPIN)
            $this->warn('⚠️  Multiple cards test may take 40-60 seconds...');
            $results['order_success_multiple'] = $this->runTest('woohoo:test-order', [
                '--sku' => 'CNPIN',
                '--amount' => '1',
                '--qty' => '2', // Using 2 instead of 5 for faster testing
                '--scenario' => 'success-multiple',
            ], $detailed);

            // 1.4 Success - Voucher Code
            $results['order_voucher_code'] = $this->runTest('woohoo:test-order', [
                '--sku' => 'VOUCHERCODE',
                '--amount' => '100',
                '--scenario' => 'voucher-code',
            ], $detailed);

            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('❌ Testing Order API - Failure Scenarios');
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            // 2.1 Validation Error - Invalid Denomination
            $results['order_validation_error'] = $this->runTest('woohoo:test-order', [
                '--sku' => 'VOUCHERCODE',
                '--amount' => '90', // Below minimum
                '--scenario' => 'validation-error',
            ], $detailed);

            // Note: Other failure scenarios may require specific test SKUs that may not be available
            // You can add them here if needed
        }

        // Display Summary
        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════════════╗');
        $this->info('║                    Test Summary                         ║');
        $this->info('╚══════════════════════════════════════════════════════════╝');
        $this->newLine();

        $passed = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($results as $testName => $result) {
            $status = $result['exit_code'] === 0 ? '✅ PASS' : '❌ FAIL';
            $this->line(sprintf('%-40s %s', $testName, $status));

            if ($result['exit_code'] === 0) {
                $passed++;
            } else {
                $failed++;
            }
        }

        $this->newLine();
        $this->info('Total Tests: '.count($results));
        $this->info("✅ Passed: {$passed}");
        $this->error("❌ Failed: {$failed}");
        $this->newLine();

        if ($failed > 0) {
            $this->warn('⚠️  Some tests failed. Review the output above for details.');

            return 1;
        }

        $this->info('🎉 All tests passed!');

        return 0;
    }

    private function runTest($command, $options = [], $detailed = false)
    {
        $this->newLine();
        $this->info("Running: php artisan {$command} ".implode(' ', array_map(function ($key, $value) {
            return "--{$key}={$value}";
        }, array_keys($options), $options)));

        if ($detailed) {
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        }

        $exitCode = Artisan::call($command, $options);
        $output = Artisan::output();

        if ($detailed || $exitCode !== 0) {
            $this->line($output);
        }

        if ($detailed) {
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        }

        return [
            'exit_code' => $exitCode,
            'output' => $output,
        ];
    }
}
