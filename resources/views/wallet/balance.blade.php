@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Wallet Balance</h2>

        <p><strong>Client Name:</strong> {{ $data['walletdetails']['clientName'] }}</p>
        <p><strong>Currency:</strong> {{ $data['walletdetails']['currency'] }}</p>
        <p><strong>Balance:</strong> ₹{{ $data['walletdetails']['balance'] }}</p>
        <p><strong>Timestamp:</strong> {{ $data['walletdetails']['responseTimestamp'] }}</p>
</div>
@endsection
