@extends('layouts.app')

@section('content')
    <div class="alert alert-success">
        <br/>
        <h4>🎁 Gift Card Purchased!</h4>
        <p><strong>Gift Code:</strong> {{ $gift_code }}</p>
        <p><strong>Order ID:</strong> {{ $order_id }}</p>
    </div>
@endsection