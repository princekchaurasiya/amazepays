@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Wallet Balance</h1>

    @if($data)
        <div class="alert alert-info">
            <strong>Balance:</strong> {{ number_format($data['balance'], 2) }} {{ $data['currency'] }}
        </div>
    @endif

    {{-- Filters --}}
    <form method="GET" action="{{ route('wallet') }}" class="row g-3 mb-4">
        <div class="col-md-3">
            <label for="txnType" class="form-label">Transaction Type</label>
            <select name="txnType" id="txnType" class="form-select">
                <option value="">All</option>
                <option value="CREDIT" {{ $filters['txnType'] === 'CREDIT' ? 'selected' : '' }}>CREDIT</option>
                <option value="DEBIT" {{ $filters['txnType'] === 'DEBIT' ? 'selected' : '' }}>DEBIT</option>
            </select>
        </div>

        <div class="col-md-3">
            <label for="activityType" class="form-label">Activity Type</label>
            <select name="activityType" id="activityType" class="form-select">
                <option value="">All</option>
                <option value="TOPUP" {{ $filters['activityType'] === 'TOPUP' ? 'selected' : '' }}>TOPUP</option>
                <option value="PURCHASE" {{ $filters['activityType'] === 'PURCHASE' ? 'selected' : '' }}>PURCHASE</option>
                <option value="REFUND" {{ $filters['activityType'] === 'REFUND' ? 'selected' : '' }}>REFUND</option>
                <option value="ADJUST" {{ $filters['activityType'] === 'ADJUST' ? 'selected' : '' }}>ADJUST</option>
            </select>
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>

        <div class="col-md-3">
        <label for="startDate" class="form-label">Start Date</label>
        <input type="datetime-local" name="startDate" id="startDate" class="form-control"
               value="{{ $filters['startDate'] }}">
    </div>

    <div class="col-md-3">
        <label for="endDate" class="form-label">End Date</label>
        <input type="datetime-local" name="endDate" id="endDate" class="form-control"
               value="{{ $filters['endDate'] }}">
    </div>

    <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100">Filter</button>
    </div>

    <div class="col-md-12 mt-2">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-success">
            Export CSV
        </a>
    </div>
    </form>

    {{-- Transactions Table --}}
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Txn ID</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Activity</th>
                <th>Reference ID</th>
                <th>Comment</th>
                <th>Before</th>
                <th>After</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $txn)
                <tr>
                    <td>{{ $txn['txnID'] }}</td>
                    <td>{{ $txn['txnType'] }}</td>
                    <td>{{ number_format($txn['amount'], 2) }} {{ $txn['currency'] }}</td>
                    <td>{{ $txn['activity'] }}</td>
                    <td>{{ $txn['referenceID'] }}</td>
                    <td>{{ $txn['metadata']['comment'] ?? '' }}</td>
                    <td>{{ number_format($txn['balanceBefore'], 2) }}</td>
                    <td>{{ number_format($txn['balanceAfter'], 2) }}</td>
                    <td>{{ \Carbon\Carbon::parse($txn['createdAt'])->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No transactions found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Pagination --}}
    @if($meta['totalPages'] > 1)
        <nav>
            <ul class="pagination">
                @for($i = 1; $i <= $meta['totalPages']; $i++)
                    <li class="page-item {{ $meta['page'] == $i ? 'active' : '' }}">
                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $i]) }}">{{ $i }}</a>
                    </li>
                @endfor
            </ul>
        </nav>
    @endif
</div>
@endsection
