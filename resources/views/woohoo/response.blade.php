@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2 class="mb-3">Woohoo Order Response</h2>

    <div class="card p-3">
        <h4>Order Details</h4>
        <p><strong>Order ID:</strong> {{ $order->id }}</p>
        <p><strong>Woohoo Order ID:</strong> {{ $order->woohoo_order_id ?? 'N/A' }}</p>

        @if(isset($isSuccess) && $isSuccess)
            <div class="alert alert-success mt-4" role="alert">
                <h4 class="alert-heading">✅ Order Processed Successfully!</h4>
                <p>Your order has been processed successfully. You will receive confirmation details via email shortly.</p>
            </div>
        @else
            <div class="alert alert-danger mt-4" role="alert">
                <h4 class="alert-heading">❌ Order Processing Failed</h4>
                @if(isset($woohoo['message']))
                    <p class="mb-0">{{ $woohoo['message'] }}</p>
                @else
                    <p class="mb-0">We encountered an issue while processing your order. Our team has been notified and will investigate. Please contact support if you need immediate assistance.</p>
                @endif
            </div>
        @endif

        @if(!empty($vouchers))
            <h4 class="mt-4">Vouchers</h4>
            @foreach($vouchers as $v)
                <div class="border p-2 mb-2">
                    <p><strong>Code:</strong> {{ $v['voucher_code'] }}</p>
                    <p><strong>Pin:</strong> {{ $v['voucher_pin'] }}</p>
                    <p><strong>Expiry:</strong> {{ $v['expiry_date'] ?? 'N/A' }}</p>
                </div>
            @endforeach
        @endif

        <div class="mt-4">
            <a href="{{ route('my-order') }}" class="btn btn-primary">View My Orders</a>
        </div>
    </div>
</div>
@endsection
