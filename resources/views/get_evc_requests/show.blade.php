@extends('layouts.app')

@section('content')
    <div style="max-width: 800px; margin: 40px auto;">
        <h2>EVC Request Details</h2>

        @if(!$evcRequest)
            <div style="padding: 16px; background: #fdecea; color: #611a15; border: 1px solid #f5c6cb; border-radius: 6px;">
                No request was found for the provided identifiers.
                @isset($orderId)
                    <div><strong>Order ID:</strong> {{ $orderId }}</div>
                @endisset
                @isset($requestRefNo)
                    <div><strong>Request Ref No:</strong> {{ $requestRefNo }}</div>
                @endisset
            </div>
        @else
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div><strong>Order ID:</strong> {{ $evcRequest->order_id }}</div>
                <div><strong>Request Ref No:</strong> {{ $evcRequest->req_id }}</div>
                <div><strong>Distributor:</strong> {{ $evcRequest->distributor_id }}</div>
                <div><strong>SKU:</strong> {{ $evcRequest->sku_code }}</div>
                <div><strong>No. of Cards:</strong> {{ $evcRequest->no_of_card }}</div>
                <div><strong>Amount:</strong> {{ $evcRequest->amount }}</div>
                <div><strong>First name:</strong> {{ $evcRequest->firstname }}</div>
                <div><strong>Last name:</strong> {{ $evcRequest->lastname }}</div>
                <div><strong>Email:</strong> {{ $evcRequest->email }}</div>
                <div><strong>Mobile:</strong> {{ $evcRequest->mobile_no }}</div>
                <div><strong>Address:</strong> {{ $evcRequest->address }}</div>
                <div><strong>City:</strong> {{ $evcRequest->city }}</div>
                <div><strong>State:</strong> {{ $evcRequest->state }}</div>
                <div><strong>Country:</strong> {{ $evcRequest->country }}</div>
                <div><strong>Pincode:</strong> {{ $evcRequest->pincode }}</div>
                <div><strong>Currency:</strong> {{ $evcRequest->curr }}</div>
                <div><strong>Gift send option:</strong> {{ $evcRequest->gift_send_option }}</div>
                <div><strong>Delivery mode:</strong> {{ $evcRequest->delivery_mode }}</div>
                <div><strong>Receiver name:</strong> {{ $evcRequest->receiver_name }}</div>
                <div><strong>Receiver email:</strong> {{ $evcRequest->receiver_email }}</div>
                <div><strong>Receiver mobile:</strong> {{ $evcRequest->receiver_mobile }}</div>
                <div style="grid-column: 1 / -1;"><strong>Message:</strong> {{ $evcRequest->receiver_msg }}</div>
            </div>
        @endif
    </div>
@endsection


