@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="product-wrapper pt-5 pb-5">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <h1 class="text-grey-900 fw-700 pb-0 mb-2 d-block text-center hot-deal-text">{{ $category->name }}</h1>
                        <hr class="normalhr">
                    </div>
                </div>
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="row mt-5 mb-5 justify-content-center mx-0 gx-0 ">

                            <div class="all-category-slick-slider">
                                @if (!empty($allCategories) && $allCategories->isNotEmpty())
                                    @foreach ($allCategories as $singleCategory)
                                        <div class="col-lg-1 mx-auto col-3">
                                            <a href="{{ route('categories.show', ['slug' => $singleCategory->slug]) }}">
                                                {{-- <div class="shop-category-circle">
                                                    <img src="{{ Voyager::image($singleCategory->logo) }}"
                                                        alt="{{ $singleCategory->name ?? 'No Name' }}"
                                                        class="shop-category-circle-image img-fluid">
                                                </div> --}}
                                                <div class="shop-category-circle">
                                                    <img src="{{ Voyager::image($singleCategory->thumbnail) }}"
                                                        alt="{{ $singleCategory->name ?? 'No Name' }}"
                                                        class="shop-category-circle-image img-fluid">
                                                </div>
                                                <p class="text-center text-black mt-2">{{ $singleCategory->name ?? 'No Name' }}</p>
                                            </a>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-center text-muted">No categories available at the moment.</p>
                                @endif
                            </div>
                        </div>
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
                                                <a href="{{ route('get-product-by-slug', ['slug' => $product->url]) }}" class="d-block text-center">
                                                    <p class="single-image-wrapper">
                                                        <img src="{{ $product->images['small'] ?? URL::asset('/images/no-image.png') }}" alt="product-image" class="w-100 mt-4 d-inline-block">
                                                    </p>
                                                </a>

                                                <hr>
                                                <a href="{{ route('get-product-by-slug', ['slug' => $product->url]) }}">
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
@push('scripts')
    <script>
        $(document).ready(function() {
            $('.all-category-slick-slider').slick({
                slidesToShow: 8, // Desktop
                slidesToScroll: 1, // Scroll 1 slide at a time
                infinite: true, // Enable infinite scrolling
                arrows: false, // Disable arrows for navigation
                dots: true, // Enable dots for navigation
                autoplay: true, // Enables automatic scrolling
                autoplaySpeed: 2000, // Autoplay speed in milliseconds (1 second)
                centerMode: true, // Center active slide
                centerPadding: '10px', // Adjust this for how much of the next item you want visible
                responsive: [{
                    breakpoint: 768, // Mobile
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 1, // Scroll 1 slide at a time on mobile for better user experience
                        infinite: true, // Ensure infinite scrolling is enabled on mobile
                        centerMode: true, // Center active slide on mobile
                        centerPadding: '10px', // Adjust for mobile visibility
                        arrows: false, // Disable arrows for mobile
                        dots: true, // Enable dots for mobile
                        autoplay: true, // Enable autoplay on mobile
                        autoplaySpeed: 1000, // Autoplay speed for mobile (1 second)
                    }
                }]
            });
        });
    </script>
@endpush
