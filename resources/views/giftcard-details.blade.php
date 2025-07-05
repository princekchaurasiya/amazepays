@extends('layouts.app')

@section('content')
<div class="container">
    <br/>
    <h2>SKUs for Giftcard ID: {{ $giftcardId }}</h2>

    @if (!empty($skus))
        <div class="row">
            @foreach ($skus as $sku)
                <div class="col-md-4 mb-4">
                    <form action="{{ route('giftcard.purchase.view') }}" method="GET">
                        <input type="hidden" name="giftcard_id" value="{{ $giftcardId }}">
                        <input type="hidden" name="sku_id" value="{{ $sku['sku_id'] }}">
                        <button type="submit" class="btn btn-link p-0 w-100 text-start" style="text-decoration: none;">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $sku['name'] }}</h5>
                                    <p><strong>Brand:</strong> {{ $sku['brand'] }}</p>
                                    <p><strong>Denomination:</strong> {{ $sku['denomination'] }}</p>
                                    <p><strong>Discount:</strong> {{ $sku['discount'] }}</p>
                                </div>
                            </div>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @else
        <p>No SKUs found for this gift card.</p>
    @endif
</div>
@endsection

