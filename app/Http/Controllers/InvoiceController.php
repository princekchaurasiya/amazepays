<?php

namespace App\Http\Controllers;

use App\Invoice;
use App\Models\ApiToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function getToken()
    {
        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic '.base64_encode('1887:1zXIo78Jtw5U'),
            ])
            ->post('https://psp.in.unlimit.com/api/auth/token', [
                'grant_type' => 'password',
                'password' => '1zXIo78Jtw5U',
                'terminal_code' => '1887',
            ]);

        $data = $response->json();
        // dd($data);

        if (isset($data['access_token'])) {
            ApiToken::create([
                'access_token' => $data['access_token'],
                'expires_at' => isset($data['expires_in'])
                    ? Carbon::now()->addSeconds($data['expires_in'])
                    : null,
            ]);

            // return response()->json(['message' => 'Token saved.']);
            return $data['access_token'];

        }

        return response()->json(['error' => 'Token not received', 'response' => $data], 400);
    }

    public function storeAndSendInvoice($id)
    {
        $token = $this->getToken();
        if (! $token) {
            return redirect()->back()->with([
                'message' => 'Failed to get access token.',
                'alert-type' => 'error',
            ]);
        }

        $invoice = Invoice::findOrFail($id);

        $payload = [
            'request' => [
                'id' => (string) Str::uuid(),
                'time' => now()->toIso8601String(),
            ],
            'invoice_data' => [
                'amount' => $invoice->amount,
                'currency' => $invoice->currency,
                'expire_at' => $invoice->expire_at,
            ],
            'merchant_order' => [
                'id' => $invoice->merchant_order_id,
                'items' => json_decode($invoice->items, true),
            ],
            'customer' => [
                'email' => $invoice->customer_email,
            ],
            'payment_methods' => [
                $invoice->payment_method,
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('https://psp.in.unlimit.com/api/invoices', $payload);

        $invoice->update(['api_response' => $response->json()]);

        return back()->with('success', 'Invoice created successfully on Unlimit.');
    }
}
