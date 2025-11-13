<?php

namespace App\Jobs;
use App\Models\QsOrder;
use App\Models\Billing;
use App\Http\Controllers\WoohooOrderController;
use App\Helpers\CommonHelper;
use App\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ProcessWoohooOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $orderId;

    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle()
    {
        $qsOrderDetails = QsOrder::find($this->orderId);
        if (!$qsOrderDetails) return;

        Log::info("******* you are in create Woohoo Order job ***********");

        // --- build request body exactly as in your function ---
        $billinginfo = Billing::latest()->first();
        $billingName = $billinginfo->billing_name ?? '';
        $parts = preg_split('/\s+/', trim($billingName), 2);
        $firstName = $parts[0] ?? '';
        $lastName = $parts[1] ?? '';
        $refno = $qsOrderDetails->refno;

        $requestBodyData = [
            "address" => [
                "firstname" => $firstName,
                "lastname"  => $lastName,
                "email"     => $billinginfo->billing_email,
                "telephone" => "+91".$billinginfo->billing_tel,
                "line1"     => $billinginfo->billing_address,
                "line2"     => $billinginfo->billing_address_two,
                "city"      => $billinginfo->billing_city,
                "region"    => $billinginfo->billing_state,
                "country"   => "IN",
                "postcode"  => $billinginfo->billing_zip,
                "languages" => "Hindi",
                "billToThis"=> true
            ],
            "billing" => [
                "firstname" => $firstName,
                "lastname"  => $lastName,
                "email"     => $billinginfo->billing_email,
                "telephone" => "+91".$billinginfo->billing_tel,
                "line1"     => $billinginfo->billing_address,
                "line2"     => $billinginfo->billing_address_two,
                "city"      => $billinginfo->billing_city,
                "region"    => $billinginfo->billing_state,
                "country"   => "IN",
                "postcode"  => $billinginfo->billing_zip,
                "languages" => "Hindi",
                "billToThis"=> true
            ],
            "payments" => [
                ["code" => "svc", "amount" => (float) $qsOrderDetails->grand_payable_amount]
            ],
            "refno" => $refno,
            "products" => [
                [
                    "sku" => $qsOrderDetails->sku,
                    "price" => (float) $qsOrderDetails->denomination,
                    "qty" => (int) $qsOrderDetails->quantity,
                    "currency" => 356
                ]
            ],
            "syncOnly" => $qsOrderDetails->quantity > (int) env("SYNC_ONLY_THRESHOLD") ? false : true,
            "delivery_mode" => "API",
        ];

        $requestBody = json_encode($requestBodyData);
        $absApiUrl = "https://" . setting("api.woohoo_url") . "/rest/v3/orders";
        $clientSecret = setting("api.qs_clientSecret");
        $bearerToken = setting("api.bearer_token");
        $signature = CommonHelper::generateSignature($requestBody, "post", $absApiUrl, $clientSecret);
        $dateAtClient = Carbon::now()->toIso8601String();

        try {
            // Debug log for bearer token expiry (masked)
            try {
                if (is_string($bearerToken) && substr_count($bearerToken, '.') === 2) {
                    [$h, $p, $s] = explode('.', $bearerToken);
                    $payloadJson = json_decode(base64_decode(strtr($p, '-_', '+/')), true);
                    $expTs = $payloadJson['exp'] ?? null;
                    $iatTs = $payloadJson['iat'] ?? null;
                    $nowTs = time();
                    Log::info('Bearer token timing (job)', [
                        'now' => $nowTs,
                        'iat' => $iatTs,
                        'exp' => $expTs,
                        'expires_in_sec' => $expTs ? ($expTs - $nowTs) : null,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('JWT timing decode failed (job)');
            }

            // Log masked request details
            Log::info("Woohoo Create Order - Request", [
                'url' => $absApiUrl,
                'method' => 'POST',
                'headers' => [
                    'Authorization' => 'Bearer ' . (is_string($bearerToken) && strlen($bearerToken) > 8 ? substr($bearerToken, 0, 4) . str_repeat('*', strlen($bearerToken) - 8) . substr($bearerToken, -4) : '****'),
                    'signature' => is_string($signature) && strlen($signature) > 10 ? substr($signature, 0, 6) . str_repeat('*', strlen($signature) - 10) . substr($signature, -4) : '****',
                    'dateAtClient' => $dateAtClient,
                ],
                'body' => $requestBodyData,
            ]);

            // Send exact JSON body that was signed
            $response = Http::acceptJson()->timeout(10)
                ->withHeaders([
                    "Content-Type" => "application/json",
                    "Authorization" => "Bearer ".$bearerToken,
                    "Accept" => "*/*",
                    "User-Agent" => "Amazepays/1.0 (+https://amazepays.in)",
                    "dateAtClient" => $dateAtClient,
                    "signature" => $signature,
                ])
                ->send('POST', $absApiUrl, ['body' => $requestBody]);

            $responseData = $response->json();
            $rawBody = $response->body();
            $parsedBody = json_decode($rawBody, true);
            Log::info('Woohoo Create Order - Response', [
                'status_code' => $response->status(),
                'body' => $parsedBody !== null ? $parsedBody : ['raw' => (is_string($rawBody) ? mb_substr($rawBody, 0, 2000) : $rawBody)],
            ]);

            if ($response->successful() && isset($responseData["status"])) {
                if ($responseData["status"] === "COMPLETE") {
                    $qsOrderDetails->update([
                        'woohoo_order_id' => $responseData['orderId'] ?? null,
                        'status' => 'completed'
                    ]);
                } elseif ($responseData["status"] === "PROCESSING") {
                    $statusResponse = (new WoohooOrderController())->getStatusByReferenceNumber($refno);
                    if ($statusResponse && $statusResponse['status'] === 'COMPLETE') {
                        $qsOrderDetails->update([
                            'woohoo_order_id' => $statusResponse['orderId'] ?? null,
                            'status' => 'completed'
                        ]);
                    } else {
                        $qsOrderDetails->update(['status'=>'failed']);
                    }
                }
            } else {
                $qsOrderDetails->update(['status'=>'failed', 'payload'=>$responseData]);
            }
        } catch (\Exception $e) {
            $qsOrderDetails->update(['status'=>'failed', 'payload'=>['error'=>$e->getMessage()]]);
            Log::error("Woohoo order job failed", ['order_id'=>$qsOrderDetails->id, 'error'=>$e->getMessage()]);
        }
    }
}
