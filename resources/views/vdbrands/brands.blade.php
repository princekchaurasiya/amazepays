@extends('layouts.app')

@section('content')
<div class="container py-5">
    @php
        $brand = json_decode($brands['original']['decrypted_data'], true)[0];
        $images = json_decode(str_replace("'", '"', $brand['Images']), true);
        $redeemSteps = $brand['RedeemSteps'];
    @endphp

    <div class="card mb-4 shadow">
        <div class="row g-0">
            <div class="col-md-4">
                <img src="{{ $images['featured'] }}" class="img-fluid rounded-start" alt="{{ $brand['BrandName'] }}">
            </div>
            <div class="col-md-8">
                <div class="card-body">
                    <h2 class="card-title">{{ $brand['BrandName'] }}</h2>
                    <p><strong>Discount:</strong> {{ $brand['Discount'] }}%</p>
                    <p class="text-muted">{{ $brand['Category'] }}</p>
                    <p><strong>Price Range:</strong> ₹{{ $brand['minPrice'] }} - ₹{{ $brand['maxPrice'] }}</p>
                    <label>Enter Denomination</label>
                    <input type="number" name="denomination" min="100" max="10000">
                    <p><strong>Available Denominations:</strong> {{ $brand['DenominationList'] }}</p>
                    <p><strong>Stock Available:</strong> {{ $brand['StockAvailable'] ? 'Yes' : 'No' }}</p>

                    <label>Quantity</label>
                    <input type="number" name="quantity" min="1" max="10">

                    <select name="gift_send_option">
                    <option>Send as Gift</option>
                    <option>Buy for Self</option>
                    </select>

                    <input type="text" class="form-control mb-3 credentails-field" placeholder="Receiver Name" name="receiver_name" id="receiver-name" value="">
                    <input type="text" class="form-control mb-3 credentails-field" placeholder="Receiver Email" name="receiver_email" id="receiver-email" value="">
                    <input type="text" class="form-control mb-3 credentails-field" placeholder="Receiver Mobile Number" name="receiver_mobile" id="receiver-mobile" value="">
                    <input type="text" class="form-control mb-3 credentails-field" placeholder="Message for Receiver" name="receiver_msg" id="receiver-msg" value="">

                    <a href="#" class="form-control h60 bg-current float-right text-white text-center font-xss fw-500 border-0 p-0 mt-4 mb-4 w250 login-button-color" data-toggle="modal" data-target="#Modallogin">
                                                    Go to Checkout Page
                                                </a>

                    <h5 class="mt-4">Description</h5>
                    <p>{!! nl2br(strip_tags($brand['Description'])) !!}</p>
                </div>
            </div>
        </div>
    </div>
<p> <a href="{{ url('/stores/filter') }}" class="btn btn-info btn-lg text-white">See Stores</a>
    <h4>Terms & Conditions</h4>
    <div class="mb-4">
        <p>{!! nl2br(strip_tags($brand['TnC'])) !!}</p>
    </div>

    <h4>Important Instructions</h4>
    <ul class="list-group mb-4">
        @foreach($brand['ImportantInstruction'] as $instruction)
            <li class="list-group-item">{{ $instruction }}</li>
        @endforeach
    </ul>
    <h4>How to Redeem</h4>
    <div class="row">
        @foreach($redeemSteps as $step)
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <img src="{{ $step['image'] }}" class="card-img-top" alt="Redeem Step">
                    <div class="card-body">
                        <p class="card-text">{!! nl2br($step['title']) !!}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
