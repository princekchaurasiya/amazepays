@extends('layouts.app')

@section('content')
<br/>
<div class="container">
    <div class="row">
        @foreach ($giftcards as $giftcard)
            <div class="col-md-4 mb-4">
                <a href="{{ route('giftcards.show', $giftcard['id']) }}" style="text-decoration: none; color: inherit;"> 
                <div class="card h-100">
                    <img src="{{ $giftcard['image'] }}" class="card-img-top" alt="{{ $giftcard['name'] }}">
                    <div class="card-body">
                        <h5 class="card-title">{{ $giftcard['name'] }}</h5>
                        <p class="card-text">{{ $giftcard['description'] }}</p>
                        <p class="card-text">
                            <strong>Discount:</strong> {{ $giftcard['overallDiscount'] }}%
                        </p>
                    </div>
                </div>
                </a>
            </div>
        @endforeach
    </div>
</div>
@endsection
