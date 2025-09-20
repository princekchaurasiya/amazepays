@extends('layouts.app')

@section('content')
<div class="container">
    <br/>
    <h1>Product Search</h1>
    
    {{-- Search Form --}}
    <form method="GET" action="{{ route('products') }}" class="mb-4">
       <div class="row g-3">
            <div class="col-md-6">
                <input type="text" name="search" class="form-control"
                       placeholder="Search by name, ID, or category"
                       value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary">Search</button>
            </div>
        </div>
    </form>

    {{-- Show list view only if NOT searching --}}
    @if(!request()->filled('search'))
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
    @endif

    {{-- Error message --}}
    @if(!empty($error))
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    {{-- Card view always shown --}}
    <div class="row">
        @forelse($products ?? [] as $product)
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    {{-- Product Image --}}
                    <img src="{{ $product['attachments'][0] ?? 'https://via.placeholder.com/300x200' }}"
                         class="card-img-top"
                         alt="{{ $product['productDisplayName'] ?? 'Product' }}">

                    <div class="card-body">
                        <h5 class="card-title">{{ $product['productDisplayName'] ?? 'Unnamed' }}</h5>
                        <p class="text-muted">
                            Category: {{ $product['categories'][0]['categoryName'] ?? 'Uncategorized' }}
                        </p>
                        <p class="card-text">
                            {{ Str::limit($product['descriptionText'] ?? '', 100) }}
                        </p>

                        @foreach($product['variants'] ?? [] as $variant)
                            <div class="mb-2">
                                <strong>{{ $variant['variantDisplayName'] ?? '-' }}</strong><br>
                                MRP: <s>₹{{ $variant['mrp'] ?? '-' }}</s><br>
                                <span class="text-success fw-bold">Price: ₹{{ $variant['price'] ?? '-' }}</span>
                            </div>
                        @endforeach

                        <form action="{{ route('place-order.form') }}" method="GET">
                            <input type="hidden" name="variantId" value="{{ $product['variants'][0]['variantID'] ?? '' }}">
                            <input type="hidden" name="mrp" value="{{ $product['variants'][0]['mrp'] ?? '' }}">
                            <button type="submit" class="btn btn-success w-100">Place Order</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">No products found.</div>
            </div>
        @endforelse
    </div>
</div>
@endsection
