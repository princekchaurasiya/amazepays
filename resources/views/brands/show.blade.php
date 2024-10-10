@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="product-wrapper pt-5 pb-5">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <h1 class="text-grey-900 fw-700 pb-0 mb-2 d-block text-center hot-deal-text">{{ $brand->name }}</h1>
                        <hr class="normalhr">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="row justify-content-center">
                        <!-- loop product here -->
                        <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">
                            <div class="row">
                                @foreach ($products as $product)
                                    @if ($product->slug && isset($product->images) && isset($product->images['small']))
                                        <div class="col-lg-3 col-6">
                                            <div class="product-wrapper-image">
                                                <a href="{{ route('get-product-by-slug', ['slug' => $product->slug]) }}" class="d-block text-center">
                                                    <p class="single-image-wrapper">
                                                        <img src="{{ $product->images['small'] ?? URL::asset('/images/no-image.png') }}" alt="product-image" class="w-100 mt-4 d-inline-block">
                                                    </p>
                                                </a>

                                                <hr>
                                                <a href="{{ route('get-product-by-slug', ['slug' => $product->slug]) }}">
                                                    <div class="product-image-text-wrapper m-lg-1">
                                                        <p class="text-center fw-600 text-product-name-color text-product-name-font-size mt-lg-2 mt-3">
                                                            {{ ucwords($product->name) }}
                                                        </p>
                                                    </div>
                                                </a>

                                                @if ($product->discount_percentage && $product->discount_percentage > 0)
                                                    <div class="ribbon">
                                                        <span>{{ $product->discount_percentage }}% off</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div> <!-- Closing row for products -->
                        </div> <!-- Closing col for main product wrapper -->
                    </div> <!-- Closing row for product display -->
                </div> <!-- Closing col for main column -->
            </div> <!-- Closing row for outer wrapper -->
        </div> <!-- Closing product wrapper -->
    </div> <!-- Closing main container -->
@endsection
