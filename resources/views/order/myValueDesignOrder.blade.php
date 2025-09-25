@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <br/>
    <h2 class="mb-4">My Value Design Orders</h2>

    @if($order->isEmpty())
        <div class="alert alert-info">
            You have no Value Design orders yet.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Image</th>
                        <th>Order ID</th>
                        <th>No. of Cards</th>
                        <th>Amount</th>
                        <th>Receipt No</th>
                        <th>Request ID</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Address</th>
                        <th>Gift Option</th>
                        <th>Delivery Mode</th>
                        <th>Receiver</th>
                        <th>Receiver Email</th>
                        <th>Receiver Mobile</th>
                        <th>Receiver Message</th>
                        <th>Discount</th>
                        <th>Currency</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order as $o)
                        <tr>
                            <td>
                                <img src="{{ $o->display_image }}" alt="Logo" width="60" class="img-fluid">
                            </td>
                            <td>{{ $o->order_id }}</td>
                            <td>{{ $o->no_of_card }}</td>
                            <td>{{ number_format($o->amount, 2) }}</td>
                            <td>{{ $o->receipt_no }}</td>
                            <td>{{ $o->req_id }}</td>
                            <td>{{ $o->firstname }} {{ $o->lastname }}</td>
                            <td>{{ $o->email }}</td>
                            <td>{{ $o->mobile_no }}</td>
                            <td>
                                {{ $o->address }}, {{ $o->city }}, {{ $o->state }}, 
                                {{ $o->country }} - {{ $o->pincode }}
                            </td>
                            <td>{{ $o->gift_send_option }}</td>
                            <td>{{ $o->delivery_mode }}</td>
                            <td>{{ $o->receiver_name }}</td>
                            <td>{{ $o->receiver_email }}</td>
                            <td>{{ $o->receiver_mobile }}</td>
                            <td>{{ $o->receiver_msg }}</td>
                            <td>{{ $o->vd_discount }}</td>
                            <td>{{ $o->curr }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
