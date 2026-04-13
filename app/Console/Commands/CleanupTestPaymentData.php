<?php

namespace App\Console\Commands;

use App\Models\ApiToken;
use App\Models\Billing;
use App\Models\CcAvenuePayment;
use App\Models\Order;
use App\Models\OrderSummary;
use App\Models\UnlimitPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupTestPaymentData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:cleanup-test-data 
                            {--force : Force deletion without confirmation}
                            {--user-id= : Delete data for specific user ID only}
                            {--order-id= : Delete data for specific order ID only}
                            {--include-tokens : Also delete expired API tokens (default: skip tokens)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up test order and payment data for analysis';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Safety check: Only allow in local/testing environments
        if (app()->environment('production')) {
            $this->error('❌ This command cannot be run in production environment!');

            return 1;
        }

        $userId = $this->option('user-id');
        $orderId = $this->option('order-id');
        $force = $this->option('force');
        $includeTokens = $this->option('include-tokens');

        // Show what will be deleted
        $this->info('📊 Analyzing data to be deleted...');

        $stats = $this->getDeletionStats($userId, $orderId, $includeTokens);

        $tableData = [
            ['Order Summary', $stats['order_summary']],
            ['Unlimit Payments', $stats['unlimit_payment']],
            ['CC Avenue Payments', $stats['cc_avenue_payment']],
            ['Billings', $stats['billings']],
            ['Orders', $stats['orders']],
        ];

        if ($includeTokens) {
            $tableData[] = ['API Tokens (expired only)', $stats['api_tokens']];
        } else {
            $tableData[] = ['API Tokens', 'Skipped (use --include-tokens to delete expired)'];
        }

        $this->table(
            ['Table', 'Records to Delete'],
            $tableData
        );

        if ($stats['total'] === 0) {
            $this->info('✅ No data to delete.');

            return 0;
        }

        // Confirmation
        if (! $force) {
            if (! $this->confirm('⚠️  Are you sure you want to delete this data? This action cannot be undone!', false)) {
                $this->info('❌ Operation cancelled.');

                return 0;
            }
        }

        $this->info('🗑️  Starting deletion...');

        try {
            DB::beginTransaction();

            $deleted = $this->deletePaymentData($userId, $orderId, $includeTokens);

            DB::commit();

            $this->info('✅ Deletion completed successfully!');

            $deletedTable = [
                ['Order Summary', $deleted['order_summary']],
                ['Unlimit Payments', $deleted['unlimit_payment']],
                ['CC Avenue Payments', $deleted['cc_avenue_payment']],
                ['Billings', $deleted['billings']],
                ['QS Orders', $deleted['orders']],
            ];

            if ($includeTokens) {
                $deletedTable[] = ['API Tokens (expired)', $deleted['api_tokens']];
            }

            $this->table(
                ['Table', 'Records Deleted'],
                $deletedTable
            );

            Log::info('🧹 Test payment data cleaned up', [
                'deleted_counts' => $deleted,
                'user_id' => $userId,
                'order_id' => $orderId,
            ]);

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error during deletion: '.$e->getMessage());
            Log::error('❌ Error cleaning up test payment data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }

    /**
     * Get statistics about data to be deleted
     */
    private function getDeletionStats($userId = null, $orderId = null, $includeTokens = false)
    {
        $orderIds = [];

        if ($orderId) {
            $orderIds = [(int) $orderId];
        } elseif ($userId) {
            $orderIds = Order::where('user_id', $userId)->pluck('id')->toArray();
        }

        $stats = [
            'order_summary' => 0,
            'unlimit_payment' => 0,
            'cc_avenue_payment' => 0,
            'billings' => 0,
            'orders' => 0,
            'api_tokens' => 0,
            'total' => 0,
        ];

        if ($orderIds || ! $userId) {
            $orderSummaryQuery = OrderSummary::query();
            $unlimitQuery = UnlimitPayment::query();
            $ccAvenueQuery = CcAvenuePayment::query();
            $billingQuery = Billing::query();
            $orderQuery = Order::query();

            if ($orderIds) {
                $orderSummaryQuery->whereIn('order_id', $orderIds);
                $unlimitQuery->whereIn('order_id', $orderIds);
                $ccAvenueQuery->whereIn('order_id', $orderIds);
                $billingQuery->whereIn('order_id', $orderIds);
                $orderQuery->whereIn('id', $orderIds);
            } elseif ($userId) {
                $unlimitQuery->where('user_id', $userId);
                $ccAvenueQuery->where('user_id', $userId);
                $orderQuery->where('user_id', $userId);
                $orderIds = $orderQuery->pluck('id')->toArray();
                $orderSummaryQuery->whereIn('order_id', $orderIds);
                $billingQuery->whereIn('order_id', $orderIds);
            }

            $stats['order_summary'] = $orderSummaryQuery->count();
            $stats['unlimit_payment'] = $unlimitQuery->count();
            $stats['cc_avenue_payment'] = $ccAvenueQuery->count();
            $stats['billings'] = $billingQuery->count();
            $stats['orders'] = $orderQuery->count();
        }

        // API tokens - only count expired ones if includeTokens is true
        if ($includeTokens) {
            $stats['api_tokens'] = ApiToken::where(function ($query) {
                $query->where('expires_at', '<', now())
                    ->orWhereNull('expires_at');
            })->count();
        } else {
            $stats['api_tokens'] = 0;
        }

        $stats['total'] = array_sum($stats) - $stats['total']; // Recalculate total

        return $stats;
    }

    /**
     * Delete payment data in correct order (respecting foreign keys)
     */
    private function deletePaymentData($userId = null, $orderId = null, $includeTokens = false)
    {
        $deleted = [
            'order_summary' => 0,
            'unlimit_payment' => 0,
            'cc_avenue_payment' => 0,
            'billings' => 0,
            'orders' => 0,
            'api_tokens' => 0,
        ];

        // Get order IDs to delete
        $orderIds = [];

        if ($orderId) {
            $orderIds = [(int) $orderId];
        } elseif ($userId) {
            $orderIds = Order::where('user_id', $userId)->pluck('id')->toArray();
        }

        // 1. Delete Order Summary (references payments)
        if ($orderIds) {
            $deleted['order_summary'] = OrderSummary::whereIn('order_id', $orderIds)->delete();
        } else {
            $deleted['order_summary'] = OrderSummary::query()->delete();
        }

        // 2. Delete Unlimit Payments
        if ($orderIds) {
            $deleted['unlimit_payment'] = UnlimitPayment::whereIn('order_id', $orderIds)->delete();
        } elseif ($userId) {
            $deleted['unlimit_payment'] = UnlimitPayment::where('user_id', $userId)->delete();
        } else {
            $deleted['unlimit_payment'] = UnlimitPayment::query()->delete();
        }

        // 3. Delete CC Avenue Payments
        if ($orderIds) {
            $deleted['cc_avenue_payment'] = CcAvenuePayment::whereIn('order_id', $orderIds)->delete();
        } elseif ($userId) {
            $deleted['cc_avenue_payment'] = CcAvenuePayment::where('user_id', $userId)->delete();
        } else {
            $deleted['cc_avenue_payment'] = CcAvenuePayment::query()->delete();
        }

        // 4. Delete Billings
        if ($orderIds) {
            $deleted['billings'] = Billing::whereIn('order_id', $orderIds)->delete();
        } else {
            $deleted['billings'] = Billing::query()->delete();
        }

        // 5. Delete orders (parent table)
        if ($orderIds) {
            $deleted['orders'] = Order::whereIn('id', $orderIds)->delete();
        } elseif ($userId) {
            $deleted['orders'] = Order::where('user_id', $userId)->delete();
        } else {
            $deleted['orders'] = Order::query()->delete();
        }

        // 6. Delete API Tokens (only expired ones, and only if requested)
        if ($includeTokens) {
            $deleted['api_tokens'] = ApiToken::where(function ($query) {
                $query->where('expires_at', '<', now())
                    ->orWhereNull('expires_at');
            })->delete();
        } else {
            $deleted['api_tokens'] = 0;
            $this->line('ℹ️  API Tokens skipped (use --include-tokens to delete expired tokens)');
        }

        return $deleted;
    }
}
