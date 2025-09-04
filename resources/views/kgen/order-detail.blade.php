@extends('layouts.app')

@section('content')
<div class="container">
    <br/>
    <h2>Order Details</h2>
    <p><strong>Order ID:</strong> {{ $order['orderID'] }}</p>
    <p><strong>External Ref:</strong> {{ $order['externalRef'] }}</p>
    <p><strong>DP ID:</strong> {{ $order['dpID'] }}</p>
    <p><strong>Status:</strong> {{ $order['status'] }}</p>
    <p><strong>Total Amount:</strong> {{ $order['totalAmount'] }}</p>
    <p><strong>Order Date:</strong> {{ $order['orderDate'] }}</p>

    <h4>Line Items</h4>
    <ul>
        @foreach($order['lineItems'] as $item)
            <li>
                Variant: {{ $item['variantID'] }}<br>
                Voucher Code: {{ $item['voucherCode'] }}<br>
                Voucher Pin: {{ $item['voucherPin'] ?? 'N/A' }}<br>
                Expiration: {{ $item['expirationDate'] }}
            </li>
        @endforeach
    </ul>

    <a href="{{ route('orders.get') }}" class="btn btn-secondary">Back</a>
</div>
@endsection
