@extends('layouts.app')

@section('content')
@auth
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
                    @if(empty($product['variant_display_rows'] ?? []))
                        <div class="text-muted">No variants available</div>
                    @else
                        @foreach($product['variant_display_rows'] as $row)
                            <div>
                                <strong>Variant:</strong> {{ $row['variant']['variantDisplayName'] ?? '-' }} ({{ $row['variant']['variantID'] ?? '' }})<br>
                                <strong>MRP:</strong> ₹{{ number_format($row['mrpValue'] ?: $row['basePrice'], 2) }} |
                                <strong>Discounted:</strong> <span class="text-success fw-bold">₹{{ number_format($row['effectivePrice'], 2) }}</span>
                            </div>
                        @endforeach
                    @endif
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
                <div class="card h-100 shadow-sm position-relative">
                    @if(($product['discount_percentage'] ?? 0) > 0)
                        <div class="position-absolute" style="top:8px; right:8px; background:#e53935; color:#fff; padding:4px 8px; border-radius:4px; font-weight:600; font-size:12px;">
                            {{ (int)($product['discount_percentage'] ?? 0) }}% off
                        </div>
                    @endif
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
                            {{ $product['description_excerpt'] ?? '' }}
                        </p>

                        @if(empty($product['variant_display_rows'] ?? []))
                            <div class="alert alert-warning mb-2">No variants available</div>
                        @else
                            <div class="mb-3">
                                <strong>Select Variant:</strong>
                                <div class="btn-group-vertical w-100 mt-2" role="group" id="variant-buttons-{{ $product['productID'] }}">
                                    @foreach($product['variant_display_rows'] as $index => $row)
                                        <button type="button"
                                                class="btn btn-outline-primary variant-btn mb-2 {{ $index === 0 ? 'active' : '' }}"
                                                data-variant-id="{{ $row['variant']['variantID'] ?? '' }}"
                                                data-mrp="{{ $row['variant']['mrp'] ?? '' }}"
                                                data-product-id="{{ $product['productID'] }}">
                                            <strong>{{ $row['variant']['variantDisplayName'] ?? '-' }}</strong><br>
                                            <small>MRP: <s>₹{{ number_format($row['mrpValue'] ?: $row['basePrice'], 2) }}</s> |
                                            Discounted: <span class="text-success fw-bold">₹{{ number_format($row['effectivePrice'], 2) }}</span></small>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <form action="{{ route('place-order.form') }}" method="GET" id="order-form-{{ $product['productID'] }}">
                                <input type="hidden" name="variantId" id="variantId-{{ $product['productID'] }}" value="{{ ($product['variant_display_rows'][0]['variant']['variantID'] ?? '') }}">
                                <input type="hidden" name="mrp" id="mrp-{{ $product['productID'] }}" value="{{ ($product['variant_display_rows'][0]['variant']['mrp'] ?? '') }}">
                                <button type="submit" class="btn btn-success w-100">Place Order</button>
                            </form>
                        @endif
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
@else
<div class="container py-5 text-center">
    <h2 class="mb-3">Please log in to view products</h2>
    <p class="text-muted mb-4">You need an active account to browse products and place orders.</p>
    <a href="{{ route('login') }}" class="btn btn-primary">Log in</a>
</div>
@endauth

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    @guest
    setTimeout(function () {
        if (typeof window.openAuthModal === 'function') {
            window.openAuthModal();
        }
    }, 300);
    @endguest

    document.querySelectorAll('.variant-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const variantId = this.getAttribute('data-variant-id');
            const mrp = this.getAttribute('data-mrp');

            document.querySelectorAll('#variant-buttons-' + productId + ' .variant-btn').forEach(function(btn) {
                btn.classList.remove('active');
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline-primary');
            });

            this.classList.add('active');
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary');

            const vId = document.getElementById('variantId-' + productId);
            const mrpEl = document.getElementById('mrp-' + productId);
            if (vId) {
                vId.value = variantId || '';
            }
            if (mrpEl) {
                mrpEl.value = mrp || '';
            }
        });
    });
});
</script>
@endpush
@endsection
