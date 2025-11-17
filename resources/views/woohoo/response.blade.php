@extends('layouts.app')

@section('content')

<div class="container mt-4">

    <h2 class="mb-3">Woohoo Order Response</h2>

    <div class="card p-3">

        <h4>Order Details</h4>
        <p><strong>Order ID:</strong> {{ $order->id }}</p>
        <p><strong>Woohoo Order ID:</strong> {{ $woohoo['order_id'] ?? 'N/A' }}</p>

        <h4 class="mt-4">Woohoo API Response</h4>

        <pre style="background: #f7f7f7; padding: 15px; border-radius: 6px;">
{{ json_encode($woohoo, JSON_PRETTY_PRINT) }}
        </pre>

        @if (!empty($vouchers))
            <h4 class="mt-4">Vouchers</h4>

            @foreach ($vouchers as $v)
                <div class="border p-2 mb-2">
                    <p><strong>Code:</strong> {{ $v['voucher_code'] }}</p>
                    <p><strong>Pin:</strong> {{ $v['voucher_pin'] }}</p>
                    <p><strong>Expiry:</strong> {{ $v['expiry_date'] ?? 'N/A' }}</p>
                </div>
            @endforeach
        @endif

    </div>
</div>

@endsection
