<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class KGenWalletController extends Controller
{
    public function wallet(Request $request)
    {
        $response = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
        ])->get(env('EXLR8_BASE_URL') . '/delivery-partners/{dpID}/wallet/balance');

        if ($response->successful()) {
            return response()->json([
                'data' => $response['data'],
            ]);
        }

        return back()->withErrors(['error' => 'Failed to fetch wallet details']);
    }

    private function exportCsv(Request $request, $token)
    {
        $queryParams = array_filter([
            'txnType' => $request->txnType,
        ]);

        $response = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
        ])->get(env('EXLR8_BASE_URL') . '/delivery-partners/{dpID}/wallet/transactions', $queryParams);

        if (!$response->successful()) {
            return back()->withErrors(['error' => 'Failed to export CSV']);
        }

        $transactions = $response['data']['transactions'];

        $filename = 'wallet_transactions_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Txn ID', 'Type', 'Currency', 'Amount','Balance Before', 'Balance After','Source System','Activity',
                'Reference ID', 'Reference Type', 'Comment',  'Created Date'
            ]);
            foreach ($transactions as $txn) {
                fputcsv($file, [
                    $txn['txnID'],
                    $txn['dpID'],
                    $txn['txnType'],
                    $txn['currency'],
                    $txn['amount'],
                    $txn['balanceBefore'],
                    $txn['balanceAfter'],
                    $txn['sourceSystem'],
                    $txn['activity'],
                    $txn['referenceID'],
                    $txn['referenceType'],
                    $txn['metadata']['comment'],
                    $txn['createdAt'],
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
