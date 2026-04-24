<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KGenWalletBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

final class KGenDistributorWalletController extends Controller
{
    public function wallet(Request $request)
    {
        $response = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
        ])->get(env('EXLR8_BASE_URL').'/delivery-partners/'.env('dpID').'/wallet/balance');

        if ($response->successful()) {
            return response()->json([
                'KGen Balance: ' => $response['balance'],
            ]);
        }

        return back()->withErrors(['error' => 'Failed to fetch wallet details']);
    }

    public function fetchAndStore()
    {
        $response = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
        ])->get(env('EXLR8_BASE_URL').'/delivery-partners/'.env('dpID').'/wallet/balance');

        if ($response->successful()) {
            $data = $response->json();

            KGenWalletBalance::create([
                'balance' => $data['balance'],
                'currency' => $data['currency'] ?? null,
            ]);

            return response()->json(['message' => 'Wallet balance saved successfully']);
        }

        return response()->json(['message' => 'Failed to fetch wallet balance'], 500);
    }

    public function latest()
    {
        $latest = KGenWalletBalance::latest()->first();

        return response()->json(['wallet_balance' => $latest]);
    }

    public function exportCsv(Request $request)
    {
        $queryParams = array_filter([
            'txnType' => $request->txnType,
        ]);

        $response = Http::withHeaders([
            'x-client-id' => env('EXLR8_USER_ID'),
            'x-client-secret' => env('EXLR8_USER_SECRET'),
        ])->get(env('EXLR8_BASE_URL').'/delivery-partners/'.env('dpID').'/wallet/transactions', $queryParams);

        if (! $response->successful()) {
            return back()->withErrors(['error' => 'Failed to export CSV']);
        }

        $transactions = $response['transactions'];

        $filename = 'wallet_transactions_'.now()->format('Ymd_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Txn ID', 'dpID', 'TxnType', 'Currency', 'Amount', 'Balance Before', 'Balance After', 'Source System', 'Activity',
                'Reference ID', 'Reference Type', 'Comment',  'Created Date',
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
            'txnType' => $request->txnType,
            'limit' => $request->limit ?? 50,
            'nextCursor' => $request->nextCursor,
        ]);

        try {
            $response = Http::withHeaders([
                'x-client-id' => env('EXLR8_USER_ID'),
                'x-client-secret' => env('EXLR8_USER_SECRET'),
            ])->get(env('EXLR8_BASE_URL').'/delivery-partners/'.env('dpID').'/wallet/transactions', $queryParams);

            if ($response->failed()) {
                $status = $response->status();
                $body = $response->json();

                $message = match ($status) {
                    401 => $body['error'] ?? 'Unauthorized access',
                    403 => $body['error'] ?? 'Forbidden access',
                    400 => $body['error'] ?? 'Bad request',
                    default => 'Something went wrong while fetching transactions',
                };

                return Inertia::render('Admin/KGen/WalletTransactions', [
                    'transactions' => [],
                    'nextCursor' => null,
                    'errorMessage' => $message,
                ]);
            }

            $data = $response->json();

            return Inertia::render('Admin/KGen/WalletTransactions', [
                'transactions' => $data['data'] ?? [],
                'nextCursor' => $data['pagination']['nextCursor'] ?? null,
                'errorMessage' => null,
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Admin/KGen/WalletTransactions', [
                'transactions' => [],
                'nextCursor' => null,
                'errorMessage' => $e->getMessage(),
            ]);
        }
    }
}

