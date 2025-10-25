<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Helpers\CommonHelper;
use Carbon;
use Illuminate\Support\Sleep;
use Illuminate\Support\Facades\Log;
use QsOrder;
use Exception;

class StatusCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $refno;
    protected $data;
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
                    return view('order.order-failed');
                    Log::error("Attempt $attempt: Job failed - Maximum number of status check attempts reached. API Time: {$apiTime}ms");
                    // Handle the failure, notify, or throw an exception as needed.
                    break; // Exit the loop as maximum attempts reached.
                }
            } else {
                return view('order.order-failed');
                Log::error("Attempt $attempt: Unexpected status. API Time: {$apiTime}ms");
                // Handle the unexpected status, notify, or throw an exception as needed.
                break; // Exit the loop for an unexpected status.
            }
        }
    }
    public function getStatusByReferenceNumber($refno)
    {
        $requestHttpMethod = 'GET';
        $absApiUrl = 'https://' . setting('api.woohoo_url') . '/rest/v3/order/' . $refno . '/status';
        $clientSecret = setting('api.qs_clientSecret');
        $bearerToken = setting('api.bearer_token');
        $requestBody = '';
        $dateAtClient = Carbon\Carbon::now()->toIso8601String();
        $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
        $response = Http::acceptJson()
            ->withToken($bearerToken)
            ->withHeaders([
                'signature' => $signature,
                'dateAtClient' => $dateAtClient,
            ])
            ->get($absApiUrl);
        // dd($response->status());
        if ($response->status() == 200) {
            $responseData = json_decode($response->getBody());
            // dd($responseData);
            if ($responseData->status === 'COMPLETE') {
                return 'COMPLETE';
            } elseif ($responseData->status === 'PROCESSING') {
                return 'PROCESSING';
            } else {
                return view('order.order-failed');
            }
        } else {
            return view('order.order-failed');
        }
    }

    public function callCardActivation($orderId)
    {
        $clientSecret = setting('api.qs_clientSecret'); // Your client secret
        $bearerToken = setting('api.bearer_token'); // Your bearer token
        $apiUrl = 'https://' . setting('api.woohoo_url');
        $absApiUrl = "$apiUrl/rest/v3/order/{$orderId}/cards";
        $requestBody = '';
        $requestHttpMethod = 'GET';
        $dateAtClient = Carbon\Carbon::now()->toIso8601String();
        $signature = CommonHelper::generateSignature($requestBody, $requestHttpMethod, $absApiUrl, $clientSecret);
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
                $encryptedCards = encrypt(json_encode($responseData['cards']));
                // Update by Woohoo order id as that maps to remote order
                QsOrder::where('woohoo_order_id', $orderId)->update(['cards' => $encryptedCards]);
            }
        }
    }
}
