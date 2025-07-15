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
                    <p class="text-muted">{{ $brand['Category'] }}</p>
                    <p><strong>Discount:</strong> {{ $brand['Discount'] }}%</p>
                    <p><strong>Price Range:</strong> ₹{{ $brand['minPrice'] }} - ₹{{ $brand['maxPrice'] }}</p>
                    <p><strong>Available Denominations:</strong> {{ $brand['DenominationList'] }}</p>
                    <p><strong>Stock Available:</strong> {{ $brand['StockAvailable'] ? 'Yes' : 'No' }}</p>

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
