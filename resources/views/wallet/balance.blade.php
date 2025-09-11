@extends('layouts.app')

@section('content')
<div class="container">
    <br/>
    <h2>Wallet Balance</h2>
    @php
        $details = $data['walletdetails']
            ?? $data['wallet_details']
            ?? $data['walletDetails']
            ?? $data['data']['walletdetails']
            ?? $data['data']['wallet_details']
            ?? $data['data']['walletDetails']
            ?? [];
    @endphp

    @if (empty($details))
        <div class="alert alert-warning">Wallet details are unavailable.</div>
        <pre class="bg-light p-3 border rounded">{{ json_encode($data, JSON_PRETTY_PRINT) }}</pre>
    @else
        <p><strong>Client Name:</strong> {{ $details['clientName'] ?? '' }}</p>
        <p><strong>Currency:</strong> {{ $details['currency'] ?? '' }}</p>
        <p><strong>Balance:</strong> ₹{{ $details['balance'] ?? 0 }}</p>
        <p><strong>Timestamp:</strong> {{ $details['responseTimestamp'] ?? '' }}</p>
    @endif
</div>
@endsection
