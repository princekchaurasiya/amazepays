<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\ApiToken;
use Carbon\Carbon;
use App\Models\Payment;

class UnlimitExportController extends Controller
{
    public function getToken()
    {
        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic ' . base64_encode(env('UNLIMIT_CODE')),
            ])
            ->post('https://sandbox.in.unlimit.com/api/auth/token', [
                'grant_type' => 'password',
                'password' => env('UNLIMIT_SECRET_KEY'),
                'terminal_code' => env('UNLIMIT_PUBLIC_KEY'),
            ]);

        $data = $response->json();
        //dd($data);

    if (isset($data['access_token'])) {
        ApiToken::create([
            'access_token' => $data['access_token'],
            'expires_at' => isset($data['expires_in']) 
                ? Carbon::now()->addSeconds($data['expires_in']) 
                : null,
        ]);

        //return response()->json(['message' => 'Token saved.']);
        return $data['access_token'];

    }

    return response()->json(['error' => 'Token not received', 'response' => $data], 400);
}

    public function exportPayments(Request $request)
    {
        $token = $this->getToken();

        if (!$token) {
            return response()->json(['error' => 'Token not available'], 401);
        }
        $start = $request->query('start_time');
        $end = $request->query('end_time');
        $requestId = $request->query('request_id', Str::uuid()->toString());

        if (!$start || !$end) {
            return response()->json([
                'error' => 'start_time and end_time are required'
            ], 400);
        }

        // Send GET request to Unlimit API
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ])->get('https://sandbox.in.unlimit.com/api/payments', [
            'start_time' => $start,
            'end_time' => $end,
            'request_id' => $requestId,
        ]);

        // Handle API error response
        if (!$response->ok()) {
            return response()->json([
                'error' => 'Unlimit API call failed',
                'status' => $response->status(),
                'body' => $response->body()
            ], 500);
        }

        $payments = $response->json();
        //dd($response->json());

    foreach ($payments['data'] as $entry) {
        Payment::create([
            'payment_method' => $entry['payment_method'] ?? null,
            'merchant_order_id' => $entry['merchant_order']['id'] ?? null,
            'merchant_order_description' => $entry['merchant_order']['description'] ?? null,
            'payment_id' => $entry['payment_data']['id'] ?? null,
            'type' => $entry['payment_data']['type'] ?? null,
            'status' => $entry['payment_data']['status'] ?? null,
            'amount' => $entry['payment_data']['amount'] ?? null,
            'currency' => $entry['payment_data']['currency'] ?? null,
            'created_at_api' => isset($entry['payment_data']['created']) 
                ? Carbon::parse($entry['payment_data']['created']) 
                : null,
            'decline_reason' => $entry['payment_data']['decline_reason'] ?? null,
            'decline_code' => $entry['payment_data']['decline_code'] ?? null,
            'is_3d' => $entry['payment_data']['is_3d'] ?? null,
            'arn' => $entry['payment_data']['arn'] ?? null,
            'rrn' => $entry['payment_data']['rrn'] ?? null,
            'original_amount' => $entry['payment_data']['original_amount'] ?? null,
            'masked_pan' => $entry['card_account']['masked_pan'] ?? null,
            'holder' => $entry['card_account']['holder'] ?? null,
            'issuing_country_code' => $entry['card_account']['issuing_country_code'] ?? null,
            'customer_email' => $entry['customer']['email'] ?? null,
            'customer_ip' => $entry['customer']['ip'] ?? null,
            'customer_locale' => $entry['customer']['locale'] ?? null,
        ]);
    }

    \Log::info('Payment entry saved:', ['id' => $entry['payment_data']['id'] ?? 'no-id']);
    return response()->json(['message' => 'Payments stored successfully.']);

        // Try decoding JSON
       /* $data = $response->json();
        if (!isset($data['data']) || !is_array($data['data'])) {
            return response()->json([
                'error' => '"data" field missing or invalid',
                'raw' => $data
            ], 500);
            }

        $data = $data['data']; 
        if (!is_array($data)) {
    return response()->json([
        'error' => '"data" should be an array',
        'raw_data' => $data
    ], 500);
}
        //dd($data);

        // Handle different possible formats of 'data'
        
        // Generate Excel file
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if (empty($data)) {
            $sheet->setCellValue('A1', 'No payment data found');
        } else {
            $headers = array_keys($data[0]);
            $sheet->fromArray([$headers], null, 'A1');

            /*$rowIndex = 2;
            foreach ($data as $row) {
                $sheet->fromArray(array_values($row), null, 'A' . $rowIndex++);
            }
            $headers = array_keys($data[0]); // Header row
            $rows = array_map('array_values', $data); // Strip keys for Excel

            // Insert headers
            $sheet->fromArray($headers, null, 'A1');

            // Insert all rows at once
            $sheet->fromArray($rows, null, 'A2');
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'unlimit_payments.xlsx');
        */
    }
}
