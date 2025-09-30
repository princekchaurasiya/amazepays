@extends('layouts.app')

@section('content')
<div class="container">
    <br/>
    <h2 class="mb-4">Products</h2>

    <div class="row">
        @forelse ($products as $product)
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">{{ $product['name'] ?? 'Unnamed Product' }}</h5>
                        <p class="card-text">
                            {{ $product['description'] ?? 'No description available' }}
                        </p>
                        <p><strong>Price:</strong> {{ $product['price'] ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        @empty
            <p>No products found.</p>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="d-flex justify-content-between mt-4">
        @if ($prevCursor)
            <a href="{{ route('products.getByPage', ['page' => $prevCursor, 'limit' => $queryParams['limit']]) }}" class="btn btn-secondary">
                Previous
            </a>
        @else
            <span></span>
        @endif

        @if ($nextCursor)
            <a href="{{ route('products.getByPage', ['page' => $nextCursor, 'limit' => $queryParams['limit']]) }}" class="btn btn-primary">
                Next
            </a>
        @endif
    </div>
</div>
@endsection
