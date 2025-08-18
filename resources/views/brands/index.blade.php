@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Brand List</h2>

    @if(!empty($brands))
        @foreach($brands as $brand)
            <div class="card mb-4">
                <div class="card-header">
                    <h4>{{ $brand['BrandName'] }} ({{ $brand['BrandCode'] }})</h4>
                </div>
                <div class="card-body">
                    <p><strong>Discount:</strong> {{ $brand['Discount'] }}%</p>
                    <p><strong>Price Range:</strong> ₹{{ $brand['minPrice'] }} - ₹{{ $brand['maxPrice'] }}</p>
                    <p><strong>Stock Available:</strong> {{ $brand['StockAvailable'] }}</p>
                    <p><strong>Category:</strong> {{ $brand['Category'] }}</p>
                    <p><strong>Description:</strong> {!! nl2br(e($brand['Description'])) !!}</p>

                    <p><strong>Denominations:</strong> {{ $brand['DenominationList'] }}</p>

                    <div class="mb-2">
                        <strong>Images:</strong><br>
                        <img src="{{ json_decode(str_replace("'", '"', $brand['Images']), true)['thumbnail'] }}" alt="Thumbnail" style="height: 80px;">
                        <img src="{{ json_decode(str_replace("'", '"', $brand['Images']), true)['featured'] }}" alt="Featured" style="height: 80px;">
                    </div>

                    <p><strong>Terms and Conditions:</strong></p>
                    <pre style="white-space: pre-wrap;">{{ $brand['TnC'] }}</pre>

                    <p><strong>Important Instructions:</strong></p>
                    <ul>
                        @foreach($brand['ImportantInstruction'] as $instruction)
                            <li>{{ $instruction }}</li>
                        @endforeach
                    </ul>

                    <p><strong>Redemption Steps:</strong></p>
                    <ol>
                        @foreach($brand['RedeemSteps'] as $step)
                            <li>
                                <strong>{{ $step['title'] }}</strong><br>
                                <img src="{{ $step['image'] }}" alt="Step Image" style="height: 100px;">
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        @endforeach
    @else
        <p>No brand data available.</p>
    @endif
</div>
@endsection
