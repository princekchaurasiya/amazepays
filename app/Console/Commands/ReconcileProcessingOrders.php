<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Order\OrderFulfillmentOrchestrator;
use Illuminate\Console\Command;

class ReconcileProcessingOrders extends Command
{
    protected $signature = 'orders:reconcile-processing {--limit=100 : Max orders to scan per run}';

    protected $description = 'Reconcile paid/processing orders against provider status APIs';

    public function handle(OrderFulfillmentOrchestrator $orchestrator): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $orders = Order::query()
            ->where(function ($q) {
                $q->whereIn('status', ['paid', 'processing'])
                    ->orWhereIn('order_status', ['PAID', 'PROCESSING', 'PENDING']);
            })
            ->orderBy('updated_at')
            ->limit($limit)
            ->get();

        $reconciled = 0;
        foreach ($orders as $order) {
            if ($orchestrator->reconcileOrderStatus($order)) {
                $reconciled++;
            }
        }

        $this->info("Scanned {$orders->count()} orders, reconciled {$reconciled}.");

        return self::SUCCESS;
    }
}

