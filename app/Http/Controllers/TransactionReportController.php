<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\TransactionReport;
use App\Models\ApiToken;
use Carbon\Carbon;

class TransactionReportController extends Controller
{
    public function getToken()
    {
        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic ' . base64_encode('1887:1zXIo78Jtw5U'),
            ])
            ->post('https://psp.in.unlimit.com/api/auth/token', [
                'grant_type' => 'password',
                'password' => '1zXIo78Jtw5U',
                'terminal_code' => '1887',
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
    public function sendToCardPay($id)
    {
         $token = $this->getToken();
         if (!$token) 
         {
            return redirect()->back()->with([
                'message' => 'Failed to get access token.',
                'alert-type' => 'error',
            ]);
        }

        
        $report = TransactionReport::findOrFail($id);

        $reportTypes = is_array($report->report_type)
    ? $report->report_type
    : json_decode($report->report_type, true);


       /* $payload = [
            "callback_url" => "https://www.example.com/report-url",
            "reports_data" => [
                "end_date" => $report->end_date,
                "report_type" => $reportTypes,
                "start_date" => $report->start_date,
            ],
            "request" => [
                "id" => $report->request_id,
                "time" => now()->toIso8601String(),
            ]
        ];*/

        $payload = [
            "request" => [
                "id" => $report->request_id,
                "time" => now()->toIso8601String(),
            ],
            "reports_data" => [
                "start_date" => $report->start_date,
                "end_date" => $report->end_date,
                "report_type" => $reportTypes,
            ]
            ];
        

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token, // replace with real token
            'Accept' => 'application/json',
        ])->post('https://psp.in.unlimit.com/api/reports', $payload);

        if ($response->successful()) {
            return redirect()->back()->with([
                'message'    => 'Report preparation initiated!',
                'alert-type' => 'success',
            ]);
        } else {
            Log::error('Report API failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $payload,
            ]);

            return redirect()->back()->with([
                'message'    => 'Failed to initiate report: ' . $response->body(),
                'alert-type' => 'error',
            ]);
        }
    }
}
