<?php

namespace App\Jobs;

use App\Models\KGenOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MonitorOrderStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int|string $orderID;

    protected int $maxAttempts;

    protected int $pollInterval; // in seconds

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int|string $orderID, int $maxAttempts = 30, int $pollInterval = 10)
    {
        $this->orderID = $orderID;
        $this->maxAttempts = $maxAttempts;
        $this->pollInterval = $pollInterval;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $attempts = 0;

        while ($attempts < $this->maxAttempts) {
            $attempts++;

            $order = KGenOrder::find($this->orderID);

            if (! $order) {
                Log::error("Order not found: {$this->orderID}");

                return;
            }

            Log::info("Order {$this->orderID} status: {$order->status}/{$order->fulfillment_status}");

            // ✅ Terminal success state
            if ($order->status === 'COMPLETED' && $order->fulfillment_status === 'FULFILLED') {
                Log::info("Order {$this->orderID} completed successfully!");

                return;
            }

            // ❌ Terminal failure states
            if (in_array($order->status, ['FAILED', 'CANCELLED'])) {
                Log::error("Order {$this->orderID} failed: {$order->fulfillment_status}");

                return;
            }

            sleep($this->pollInterval);
        }

        Log::warning("Order monitoring timeout for Order ID: {$this->orderID}");
    }
}
