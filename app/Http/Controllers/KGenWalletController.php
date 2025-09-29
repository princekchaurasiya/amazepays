<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class KGenWalletController extends Controller
{
    public function store(Request $request)
    {

    if (!auth()->check()) {
            return kgenError("unauthenticated", "UNAUTHORIZED");
        }

    if(env('dpID') == '')
    {
        return kgenError("forbidden: param: admin user does not have access to DP: INVALID_DP_ID", "FORBIDDEN");
    }
    }

    public function wallet(Request $request)
    {
        $response = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
        ])->get(env('EXLR8_BASE_URL') . '/delivery-partners/' . env('dpID') . '/wallet/balance');

        if ($response->successful()) {
           return response()->json([
                'KGen Balance: ' => $response['balance'],
            ]); 
        }

        return back()->withErrors(['error' => 'Failed to fetch wallet details']);
    }

    public function exportCsv(Request $request)
    {
        $queryParams = array_filter([
            'txnType' => $request->txnType,
        ]);

        $response = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
        ])->get(env('EXLR8_BASE_URL') . '/delivery-partners/' . env('dpID') . '/wallet/transactions', $queryParams);

        if (!$response->successful()) {
            return back()->withErrors(['error' => 'Failed to export CSV']);
        }

        $transactions = $response['transactions'];

        $filename = 'wallet_transactions_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Txn ID','dpID', 'TxnType', 'Currency', 'Amount','Balance Before', 'Balance After','Source System','Activity',
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

        public function index(Request $request)
    {
        $queryParams = array_filter([
            'txnType'    => $request->txnType,
            'limit'      => $request->limit ?? 50,
            'nextCursor' => $request->nextCursor,
        ]);

        try {
            $response = Http::withHeaders([
                'x-client-id'     => env('EXLR8_USER_ID'),
                'x-client-secret' => env('EXLR8_USER_SECRET'),
            ])->get(env('EXLR8_BASE_URL') . '/delivery-partners/' . env('dpID') . '/wallet/transactions', $queryParams);

            if ($response->failed()) {
                $status = $response->status();
                $body = $response->json();

                switch ($status) {
                    case 401:
                        $message = $body['error'] ?? 'Unauthorized access';
                        break;
                    case 403:
                        $message = $body['error'] ?? 'Forbidden access';
                        break;
                    case 400:
                        $message = $body['error'] ?? 'Bad request';
                        break;
                    default:
                        $message = 'Something went wrong while fetching transactions';
                }

                return view('kgen.transactions.index', [
                    'transactions' => [],
                    'errorMessage' => $message,
                ]);
            }

            $data = $response->json();

            return view('kgen.transactions.index', [
                'transactions' => $data['data'] ?? [],
                'nextCursor'   => $data['pagination']['nextCursor'] ?? null,
                'errorMessage' => null,
            ]);

        } catch (\Exception $e) {
            return view('transactions.index', [
                'transactions' => [],
                'errorMessage' => $e->getMessage(),
            ]);
        }
    }

}
