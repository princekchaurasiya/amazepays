@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Product Search</h1>
    
    <form method="GET" action="{{ route('products') }}" class="mb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <input type="text" name="productID" class="form-control" placeholder="Product ID" value="{{ request('productID') }}">
            </div>
            <div class="col-md-4">
                <input type="text" name="variantID" class="form-control" placeholder="Variant ID" value="{{ request('variantID') }}">
            </div>
            <div class="col-md-4">
                <input type="text" name="category" class="form-control" placeholder="Category" value="{{ request('category') }}">
            </div>
        </div>
        <div class="mt-3">
            <button class="btn btn-primary">Search</button>
        </div>
    </form>

    @if(isset($products))
        <h4>Products ({{ $meta['totalCount'] }})</h4>
        <div class="list-group mb-4">
            @foreach($products as $product)
                <div class="list-group-item">
                    <h5>{{ $product['displayName'] }} ({{ $product['id'] }})</h5>
                    <p>Type: {{ implode(', ', $product['type']) }}</p>
                    @foreach($product['variants'] as $variant)
                        <div>
                            <strong>Variant:</strong> {{ $variant['displayName'] }} ({{ $variant['variantID'] }})<br>
                            <strong>Price:</strong> {{ $variant['price'] }} / <strong>MRP:</strong> {{ $variant['mrp'] }}
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <nav>
            <ul class="pagination">
                @for($i = 1; $i <= $meta['totalPages']; $i++)
                    <li class="page-item {{ $meta['page'] == $i ? 'active' : '' }}">
                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $i]) }}">{{ $i }}</a>
                    </li>
                @endfor
            </ul>
        </nav>
    @endif

    @foreach($product['variants'] as $variant)
    <div>
        <strong>Variant:</strong> {{ $variant['displayName'] }} ({{ $variant['variantID'] }})<br>
        <strong>Price:</strong> {{ $variant['price'] }} / <strong>MRP:</strong> {{ $variant['mrp'] }}
        <form action="{{ route('place-order.form') }}" method="GET" style="display:inline;">
            <input type="hidden" name="variantId" value="{{ $variant['variantID'] }}">
            <input type="hidden" name="mrp" value="{{ $variant['mrp'] }}">
            <button type="submit" class="btn btn-sm btn-success">Place Order</button>
        </form>
    </div>
@endforeach

</div>
@endsection
