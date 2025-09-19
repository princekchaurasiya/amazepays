@extends('layouts.app')

@section('content')
<div class="container">

<br/>
    <h1>Products</h1>
    
    <form method="GET" action="{{ route('kgen.products') }}" class="mb-4">

        <div class="list-group mb-4">
            @foreach($products as $product)
                <div class="list-group-item">
                    <h5>{{ $product['productDisplayName'] }} ({{ $product['productID'] }})</h5>
                    @foreach($product['variants'] as $variant)
                        <div>
                            <strong>Variant:</strong> {{ $variant['variantDisplayName'] }} ({{ $variant['variantID'] }})<br>
                            <strong>Price:</strong> {{ $variant['price'] }} / <strong>MRP:</strong> {{ $variant['mrp'] }}
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
 </form>
</div>
@endsection
