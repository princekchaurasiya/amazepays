<?php

namespace App\Jobs;

use App\Helpers\ApiSignatureHelper;
use App\Models\Order;
use App\Services\Order\WoohooGiftCardPersister;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StatusCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $refno;

    protected $data;

    protected $orderId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($refno, $data, $orderId)
    {
        // dd($refno, $data, $orderId);
        $this->refno = $refno;
        $this->data = $data;
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            Log::info("Attempt $attempt: Starting status check...");

            // Record the start time before hitting the API
            $startTime = now();

            $status = $this->getStatusByReferenceNumber($this->refno);

            // Calculate the time taken for the API call
            $apiTime = now()->diffInMilliseconds($startTime);

            if ($status === 'COMPLETE') {
                $this->callCardActivation($this->orderId);
                Log::info("Attempt $attempt: Payment completed. API Time: {$apiTime}ms");
                break; // Exit the loop since the payment is completed.
            } elseif ($status == 'PROCESSING') {
                if ($attempt < 3) {
                    Log::info("Attempt $attempt: Still in processing. Waiting for 40 seconds...");
                    sleep(40);
                } else {
                    Log::error("Attempt $attempt: Job failed - Maximum number of status check attempts reached. API Time: {$apiTime}ms");
                    // Handle the failure, notify, or throw an exception as needed.
                    break; // Exit the loop as maximum attempts reached.
                }
            } else {
                Log::error("Attempt $attempt: Unexpected status '{$status}'. API Time: {$apiTime}ms");
                // Handle the unexpected status, notify, or throw an exception as needed.
                break; // Exit the loop for an unexpected status.
            }
        }
    }

    public function getStatusByReferenceNumber($refno)
    {
        $requestHttpMethod = 'GET';
        $absApiUrl = 'https://'.config('woohoo.host').'/rest/v3/order/'.$refno.'/status';
        $clientSecret = config('woohoo.client_secret');
        $bearerToken = config('woohoo.bearer_token');
        $requestBody = '';
        $dateAtClient = Carbon::now()->toIso8601String();
        $signature = ApiSignatureHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
        $response = Http::acceptJson()
            ->withToken($bearerToken)
            ->withHeaders([
                'signature' => $signature,
                'dateAtClient' => $dateAtClient,
            ])
            ->get($absApiUrl);
        // dd($response->status());
        if ($response->successful()) {
            $responseData = $response->json();
            $status = $responseData['status'] ?? null;
            if ($status === 'COMPLETE') {
                return 'COMPLETE';
            } elseif ($status === 'PROCESSING') {
                return 'PROCESSING';
            }

            return 'FAILED';
        }

        return 'ERROR';
    }

    public function callCardActivation($orderId)
    {
        $clientSecret = config('woohoo.client_secret'); // Your client secret
        $bearerToken = config('woohoo.bearer_token'); // Your bearer token
        $apiUrl = 'https://'.config('woohoo.host');
        $absApiUrl = "$apiUrl/rest/v3/order/{$orderId}/cards";
        $requestBody = '';
        $requestHttpMethod = 'GET';
        $dateAtClient = Carbon::now()->toIso8601String();
        $signature = ApiSignatureHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
        $response = Http::acceptJson()
            ->withToken($bearerToken)
            ->withHeaders([
                'signature' => $signature,
                'dateAtClient' => $dateAtClient,
            ])
            ->get($absApiUrl);

        if ($response->status() == 200) {
            $responseData = $response->json();
            if (isset($responseData['cards'])) {
                $order = Order::where('woohoo_order_id', $orderId)->first();
                if ($order) {
                    app(WoohooGiftCardPersister::class)->syncWoohooCards($order->fresh(), (array) $responseData['cards']);
                }
            }
        }
    }
}
