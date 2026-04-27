<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Order;
use App\Services\Order\OrderFulfillmentOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class FulfillPaidOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $orderId,
    ) {}

    public function handle(OrderFulfillmentOrchestrator $orchestrator): void
    {
        $order = Order::query()->find($this->orderId);
        if (! $order) {
            return;
        }

        try {
            $orchestrator->fulfillPaidOrder($order);
        } catch (\Throwable $e) {
            Log::error('FulfillPaidOrderJob failed', [
                'order_id' => $this->orderId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

