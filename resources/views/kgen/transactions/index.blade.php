{{-- resources/views/kgen/transactions/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container mx-auto py-6">
    <br/>
    <h2 class="text-2xl font-bold mb-6">Transaction History</h2>

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('kgen.transactions.index') }}" class="mb-4">
        <div class="flex space-x-4">
            <select name="txnType" class="border rounded px-3 py-2">
                <option value="">All</option>
                <option value="DEBIT" {{ request('txnType') === 'DEBIT' ? 'selected' : '' }}>Debit</option>
                <option value="CREDIT" {{ request('txnType') === 'CREDIT' ? 'selected' : '' }}>Credit</option>
            </select>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Filter</button>
        </div>
    </form>

    {{-- Transactions Table --}}
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border rounded shadow">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left">Txn ID</th>
                    <th class="px-4 py-2 text-left">Type</th>
                    <th class="px-4 py-2 text-left">Amount</th>
                    <th class="px-4 py-2 text-left">Currency</th>
                    <th class="px-4 py-2 text-left">Balance Before</th>
                    <th class="px-4 py-2 text-left">Balance After</th>
                    <th class="px-4 py-2 text-left">Activity</th>
                    <th class="px-4 py-2 text-left">Reference</th>
                    <th class="px-4 py-2 text-left">Comment</th>
                    <th class="px-4 py-2 text-left">Created At</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $txn)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $txn['txnID'] }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 rounded text-sm 
                                {{ $txn['txnType'] === 'DEBIT' ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600' }}">
                                {{ $txn['txnType'] }}
                            </span>
                        </td>
                        <td class="px-4 py-2 font-semibold">{{ number_format($txn['amount'], 2) }}</td>
                        <td class="px-4 py-2">{{ $txn['currency'] }}</td>
                        <td class="px-4 py-2">{{ number_format($txn['balanceBefore'], 2) }}</td>
                        <td class="px-4 py-2">{{ number_format($txn['balanceAfter'], 2) }}</td>
                        <td class="px-4 py-2">{{ $txn['activity'] }}</td>
                        <td class="px-4 py-2">{{ $txn['referenceID'] }} ({{ $txn['referenceType'] }})</td>
                        <td class="px-4 py-2">{{ $txn['metadata']['comment'] ?? '-' }}</td>
                        <td class="px-4 py-2">{{ \Carbon\Carbon::parse($txn['createdAt'])->format('d M Y, H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-4 text-center text-gray-500">
                            No transactions found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination / Next Cursor --}}
    @if(isset($nextCursor))
        <div class="mt-6 text-center">
            <a href="{{ route('transactions.index', array_merge(request()->all(), ['nextCursor' => $nextCursor])) }}"
               class="bg-gray-800 text-white px-6 py-2 rounded hover:bg-gray-700">
                Load More
            </a>
        </div>
    @endif
</div>
@endsection
