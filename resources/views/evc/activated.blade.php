@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Activated EVC Details</h2>

    {{-- Summary Card --}}
    <div class="card mt-3">
        <div class="card-body">
            <p><strong>Order ID:</strong> {{ $evc['order_id'] ?? $order_id }}</p>
            <p><strong>Request Ref No:</strong> {{ $evc['request_ref_no'] ?? $request_ref_no }}</p>
        </div>
    </div>

    {{-- Decrypted Data --}}
    @if(!empty($decryptedData))
        <div class="alert alert-success mt-4">
            <strong>Decrypted EVC Data:</strong> {{ $decryptedData }}
        </div>
    @else
        <div class="alert alert-warning mt-4">
            EVC data could not be decrypted or is empty.
        </div>
    @endif
</div>
@endsection
