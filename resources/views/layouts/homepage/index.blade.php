@extends('layouts.app')
@section('title')
    Amazepay | Exclusive Gift Cards & Vouchers for Every Occasion
@endsection
@section('content')
@php
    use App\Helpers\CommonHelper;
    use Illuminate\Support\Str;
@endphp

    {{-- Banner Section --}}
    @if ($homeSettings && $homeSettings->section_banner_status)
        @include('layouts.partials.banner')
    @endif

    {{-- Setup Required Message for Admins --}}
    @if ((!$categories || $categories->isEmpty()) && (!$allProducts || $allProducts->isEmpty()))
        <div class="container mt-5 mb-5">
            <div class="alert alert-info" role="alert">
                <h4 class="alert-heading"><i class="fa fa-info-circle"></i> Setup Required</h4>
                <p>Welcome! To get started with your homepage, please configure the following:</p>
                <ul>
                    <li><strong>Categories:</strong> Add product categories via the admin panel</li>
                    <li><strong>Brands:</strong> Add brands to showcase on the homepage</li>
                    <li><strong>Products:</strong> Import or add products to your catalog</li>
                    <li><strong>Home Settings:</strong> Configure homepage sections and banners</li>
                    <li><strong>Slides:</strong> Add banner slides for the carousel</li>
                </ul>
                <hr>
                <p class="mb-0">Please visit the <a href="/admin" class="alert-link">Admin Panel</a> to complete the setup.</p>
            </div>
        </div>
    @endif

    <div class="product-wrapper pt-5 pb-5">
        <div class="container-fluid">

            {{-- Brand Section --}}
            @if ($homeSettings && $homeSettings->section_brand_status && $brands && $brands->isNotEmpty())
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <h1 class="text-grey-900 fw-700 pb-0 mb-2 d-block text-center hot-deal-text">
                            {{ $homeSettings->section_brand_title ?? 'Popular Brands' }}
                        </h1>
                        <hr class="normalhr">

                        <div class="row mt-5 mb-5 justify-content-center mx-0 gx-0">


                            <div class="brand-slick-slider">
                                @foreach ($brands as $brand)
                                    <div class="col-lg-1 mx-auto col-3">
                                        <a href="{{ route('brands.show', ['slug' => $brand->slug]) }}">
                                            <div class="shop-category-circle">
                                                @if (!empty($brand->logo) && $brand->logo !== 'null' && $brand->logo !== 'undefined')
                                                    <img src="{{ Voyager::image($brand->logo) }}"
                                                        alt="{{ $brand->name }}" class="shop-category-circle-image img-fluid">
                                                @else
                                                    <div class="no-image-placeholder">
                                                        <span>{{ substr($brand->name, 0, 1) }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                            <p class="text-center text-black mt-2">{{ $brand->name }}</p>
                                        </a>
                                    </div>
                                @endforeach
                            </div>


                        </div>
                    </div>
                </div>
            @endif

            {{-- Hot Deal Section --}}
            @if ($homeSettings && $homeSettings->section_hot_deal_status && $priorityProducts && $priorityProducts->isNotEmpty())
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="text-grey-900 fw-700 pb-0 mb-2 d-block text-center hot-deal-text">
                            {{ $homeSettings->section_hot_deal_title ?? 'Hot Deals' }}
                        </h1>
                        <hr class="normalhr">

                        <div class="row justify-content-center">
                            <div class="col-12 col-lg-10">
                                <div class="row">

                                    @foreach ($priorityProducts as $product)
                                    @if ($product->slug)
                                    <div class="col-lg-3 col-6">
                                        <div class="product-wrapper-image">
                                            <a href="{{ route('get-product-by-slug', ['slug' => $product->url]) }}" class="d-block text-center position-relative">
                                                @php
                                                    $productImage = CommonHelper::getProductImage($product);
                                                @endphp
                                                @if (!empty($productImage))
                                                    <img src="{{ $productImage }}" alt="product-image" class="w-100 mt-4">
                                                @else
                                                    <div class="no-product-image">
                                                        <span>{{ substr($product->name, 0, 1) }}</span>
                                                    </div>
                                                @endif
                                                @if (!empty($product->out_of_stock) && $product->out_of_stock)
                                                    <span class="stock-badge">Out of stock</span>
                                                @endif
                                            </a>
                                            <hr>
                                            <a href="{{ route('get-product-by-slug', ['slug' => $product->url]) }}">
                                                <p class="text-center fw-600 mt-3 produtName">{{ ucwords($product->name) }}</p>
                                            </a>
                                            @if ($product->discount_percentage > 0)
                                                <div class="ribbon">
                                                    <span>{{ $product->discount_percentage }}% off</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Category Section --}}
            @if ($homeSettings && $homeSettings->section_category_status && $categories && $categories->isNotEmpty())
                <div class="row justify-content-center mt-5">
                    <div class="col-lg-10">
                        <h1 class="text-grey-900 fw-700 pb-0 mb-2 d-block text-center hot-deal-text">
                            {{ $homeSettings->section_category_title ?? 'Categories' }}
                        </h1>
                        <hr class="normalhr">

                        <div class="row mt-5 mb-5 justify-content-center mx-0 gx-0">
                            <div class="category-slick-slider">
                                @foreach ($categories as $category)
                                    <div class="col-lg-1 mx-auto col-3">
                                        <a href="{{ route('categories.show', ['slug' => $category->slug]) }}">
                                            <div class="shop-category-circle">
                                                @if (!empty($category->thumbnail) && $category->thumbnail !== 'null' && $category->thumbnail !== 'undefined')
                                                    <img src="{{ Voyager::image($category->thumbnail) }}"
                                                        alt="{{ $category->name }}" class="shop-category-circle-image img-fluid">
                                                @else
                                                    <div class="no-image-placeholder">
                                                        <span>{{ substr($category->name, 0, 1) }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                            <p class="text-center text-black mt-2">{{ $category->name }}</p>
                                        </a>

                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Other Deal Section --}}
            @if ($homeSettings && $homeSettings->section_other_deal_status && $noPriorityProducts && $noPriorityProducts->isNotEmpty())
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="text-grey-900 fw-700 pb-0 mb-2 d-block text-center hot-deal-text">
                            {{ $homeSettings->section_other_deal_title ?? 'Other Deals' }}
                        </h1>
                        <hr class="normalhr">

                        <div class="row justify-content-center">
                            <div class="col-12 col-lg-10">
                                <div class="row">
                                    @foreach ($noPriorityProducts as $product)
                                    @php
                                        $productImage = CommonHelper::getProductImage($product);
                                    @endphp

                                    @if ($product->slug && !empty($productImage))
                                        <div class="col-lg-3 col-6">
                                            <div class="product-wrapper-image">
                                                <a href="{{ route('get-product-by-slug', ['slug' => $product->url]) }}" class="d-block text-center position-relative">
                                                    <img src="{{ $productImage }}" alt="product-image" class="w-100 mt-4">
                                                    @if (!empty($product->out_of_stock) && $product->out_of_stock)
                                                        <span class="stock-badge">Out of stock</span>
                                                    @endif
                                                </a>

                                                <hr>
                                                <a href="{{ route('get-product-by-slug', ['slug' => $product->url]) }}">
                                                    <p class="text-center fw-600 mt-3 produtName">{{ ucwords($product->name) }}</p>
                                                </a>

                                                @if ($product->discount_percentage > 0)
                                                    <div class="ribbon">
                                                        <span>{{ $product->discount_percentage }}% off</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @elseif ($product->slug)
                                        <div class="col-lg-3 col-6">
                                            <div class="product-wrapper-image">
                                                <a href="{{ route('get-product-by-slug', ['slug' => $product->url]) }}" class="d-block text-center">
                                                    <div class="no-product-image">
                                                        <span>{{ substr($product->name, 0, 1) }}</span>
                                                    </div>
                                                </a>

                                                <hr>
                                                <a href="{{ route('get-product-by-slug', ['slug' => $product->url]) }}">
                                                    <p class="text-center fw-600 mt-3 produtName">{{ ucwords($product->name) }}</p>
                                                </a>

                                                @if ($product->discount_percentage > 0)
                                                    <div class="ribbon">
                                                        <span>{{ $product->discount_percentage }}% off</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                    @endforeach

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- KGen Products Section --}}
            @if (!empty($kgenProducts))
                <div class="row mt-5">
                    <div class="col-12">
                        <h1 class="text-grey-900 fw-700 pb-0 mb-2 d-block text-center hot-deal-text">
                            Featured KGen Products
                        </h1>
                        <hr class="normalhr">

                        <div class="row g-3 justify-content-center mx-0">
                            @foreach ($kgenProducts as $product)
                                @php
                                    $availableVariants = collect($product['variants'] ?? [])->filter(function ($variant) {
                                        $stockAvailable = $variant['stockAvailable'] ?? $variant['inStock'] ?? $variant['available'] ?? $variant['isAvailable'] ?? true;
                                        $stock = $variant['stock'] ?? $variant['quantity'] ?? null;

                                        if ($stock === 0 || $stockAvailable === false || $stockAvailable === 0) {
                                            return false;
                                        }

                                        if ($stockAvailable === true || ($stock !== null && $stock > 0)) {
                                            return true;
                                        }

                                        return true;
                                    })->values();

                                    $primaryVariant = $availableVariants->first();
                                    $discountPercentage = (float)($product['discount_percentage'] ?? 0);
                                    $variantMrp = $primaryVariant ? (float)($primaryVariant['mrp'] ?? 0) : 0;
                                    $variantPrice = $primaryVariant ? (float)($primaryVariant['price'] ?? $variantMrp) : 0;
                                    $priceSource = $variantMrp > 0 ? $variantMrp : $variantPrice;
                                    $effectivePrice = $primaryVariant
                                        ? ($discountPercentage > 0
                                            ? round($priceSource * (1 - ($discountPercentage / 100)), 2)
                                            : $variantPrice)
                                        : null;
                                @endphp
                                <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-4 px-2">
                                    <div class="card kgen-card h-100 shadow-sm position-relative overflow-hidden">
                                        @if ($discountPercentage > 0)
                                            <span class="badge bg-danger position-absolute kgen-badge">
                                                {{ (int) $discountPercentage }}% Off
                                            </span>
                                        @endif

                                        <img src="{{ $product['attachments'][0] ?? 'https://via.placeholder.com/300x200' }}"
                                            class="card-img-top kgen-card-image"
                                            alt="{{ $product['productDisplayName'] ?? 'KGen Product' }}"
                                            style="width: 100%; height: 180px; object-fit: cover;">

                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title">{{ $product['productDisplayName'] ?? 'Unnamed' }}</h5>
                                            <p class="text-muted small mb-2">
                                                Category: {{ $product['categories'][0]['categoryName'] ?? 'Uncategorized' }}
                                            </p>
                                            <p class="kgen-card-desc">
                                                {{ Str::limit($product['descriptionText'] ?? '', 90) }}
                                            </p>

                                            @if ($primaryVariant)
                                                <div class="kgen-card-pricing mb-3">
                                                    <small class="text-muted d-block">
                                                        Variant: {{ $primaryVariant['variantDisplayName'] ?? '-' }}
                                                    </small>
                                                    <div class="d-flex flex-column">
                                                        @if ($variantMrp > 0)
                                                            <span class="text-muted text-decoration-line-through">
                                                                ₹{{ number_format($variantMrp, 2) }}
                                                            </span>
                                                        @endif
                                                        <span class="fw-bold text-success fs-5">
                                                            ₹{{ number_format($effectivePrice ?? $variantPrice, 2) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="alert alert-warning py-2 mb-3">No variants available</div>
                                            @endif

                                            <a href="{{ route('products', ['search' => $product['productDisplayName'] ?? $product['productID'] ?? null]) }}"
                                                class="btn btn-primary w-100 mt-auto">
                                                View on KGen
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="text-center mt-3">
                            <a href="{{ route('products') }}" class="btn btn-outline-primary px-4">
                                Browse all KGen products
                            </a>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

{{-- Lysto Gift Cards Button --}}
<!--<div class="row justify-content-center mt-4 mb-4">
    <div class="col-lg-4 text-center">
        <a href="{{ url('/giftcards2') }}" class="btn btn-primary btn-lg px-4">
            Lysto Gift Cards
        </a>
    </div>
</div>

<div class="row justify-content-center mt-4 mb-4">
    <div class="col-lg-4 text-center">
        <a href="{{ url('/vdbrands') }}" class="btn btn-primary btn-lg px-4">
            Value Design Gift Cards
        </a>
    </div>
</div>-->
@php
use App\Models\HomepageSection;

try {
    $section = HomepageSection::where('section_name', 'hero')->first();
} catch (\Exception $e) {
    // If table doesn't exist or any error, just set section to null
    $section = null;
}
@endphp

@if($section && $section->status)
    {!! $section->content !!}
@endif
{{-- Values Design Gift Cards --}}
<!-- <div class="product-wrapper pt-5 pb-5">
        <div class="container-fluid">
<div class="row justify-content-center">
                    <div class="col-lg-10">
                        <h1 class="text-grey-900 fw-700 pb-0 mb-2 d-block text-center hot-deal-text">
Explore more Gift Cards & Vouchers
</h1>
                        <hr class="normalhr">

                        <div class="row mt-5 mb-5 justify-content-center mx-0 gx-0">


                            <div class="brand-slick-slider">
<div class="col-lg-1 mx-auto col-3">
        <div class="flex items-center gap-2 mb-4">
        <img src="{{ asset('images/glam_logo.png') }}" alt="Glam logo" class="w-10 h-10 object-contain" width="120" height="30">
         
       
            <a href="{{ url('/vdbrands') }}" class="inline-block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
              Explore more Gift Cards
              <img src="{{ asset('images/gc_image.png') }}" alt="Brand logo" class="w-20 h-20 md:w-24 md:h-24 object-contain" width="300" height="150">
            </a>
        </div>
</div>
</div>
                        </div>
                    </div>
                </div>  
</div>-->
@endsection
@push('scripts')
    <script>
        // Deferred carousel initialization function to prevent blocking and forced reflows
        function initializeCarousels() {
            $('.category-slick-slider').slick({
                slidesToShow: 8, // Desktop
                slidesToScroll: 1, // Scroll 3 slides at a time
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

            $('.brand-slick-slider').slick({
                slidesToShow: 8, // Desktop
                slidesToScroll: 1, // Scroll 3 slides at a time
                infinite: true, // Enable infinite scrolling
                arrows: false, // Disable arrows for navigation
                dots: true, // Enable dots for navigation
                autoplay: true, // Enables automatic scrolling
                autoplaySpeed: 1500, // Autoplay speed in milliseconds (1 second)
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
        }

        $(document).ready(function() {
            // Defer carousel initialization to prevent blocking and forced reflows
            requestAnimationFrame(function() {
                initializeCarousels();
            });

            $('.operator').select2({
                placeholder: "Select Operator"
            });

            // for states
            $('.select-state').select2({
                placeholder: "Select State"
            });

            // for electric board
            $('.select-electric-board').select2({
                placeholder: "Select Electric Board"
            });

            // for dth operator
            $('.dth-operator').select2({
                placeholder: "Select Operator"
            });

            // for gas bill
            $('.gas-bill').select2({
                placeholder: "Gas Provider"
            });

            $('#view-plan').on('click', function() {
                var bool = $(".plan-details").is(":hidden")
                $(".plan-details").toggleClass('hidden')
                $(".plan-details").attr('hidden', !bool)
            });
        });
        //----------------------------------- for isotope filter for cards-----------------------------
        var $grid = $('.grid').isotope({
            // options
            itemSelector: '.grid-item',
            layoutMode: 'fitRows',
        });

        // change is-checked class on buttons
        var $buttonGroup = $('.filters');
        $buttonGroup.on('click', 'li', function(event) {
            $buttonGroup.find('.is-checked').removeClass('is-checked');
            var $button = $(event.currentTarget);
            $button.addClass('is-checked');
            var filterValue = $button.attr('data-filter');
            $grid.isotope({
                filter: filterValue
            });
        });
        //-------------------------------------- end isotope filter for cards----------------------------

        //------------------------------ for slider --------------------------------------------
        (function($) {
            "use strict";
            $.fn.sliderResponsive = function(settings) {
                var set = $.extend({
                        slidePause: 5000,
                        fadeSpeed: 800,
                        autoPlay: "on",
                        showArrows: "off",
                        hideDots: "off",
                        hoverZoom: "on",
                        titleBarTop: "off"
                    },
                    settings
                );

                var $slider = $(this);
                var size = $slider.find("> div").length; //number of slides
                var position = 0; // current position of carousal
                var sliderIntervalID; // used to clear autoplay

                // Add a Dot for each slide
                $slider.append("<ul></ul>");
                $slider.find("> div").each(function() {
                    $slider.find("> ul").append("<li></li>");
                });

                // Put .show on the first Slide
                $slider.find("div:first-of-type").addClass("show");

                // Put .showLi on the first dot
                $slider.find("li:first-of-type").addClass("showli");

                //fadeout all items except .show
                $slider.find("> div").not(".show").fadeOut();

                // If Autoplay is set to 'on' than start it
                if (set.autoPlay === "on") {
                    startSlider();
                }

                // If showarrows is set to 'on' then don't hide them
                if (set.showArrows === "on") {
                    $slider.addClass("showArrows");
                }

                // If hideDots is set to 'on' then hide them
                if (set.hideDots === "on") {
                    $slider.addClass("hideDots");
                }

                // If hoverZoom is set to 'off' then stop it
                if (set.hoverZoom === "off") {
                    $slider.addClass("hoverZoomOff");
                }

                // If titleBarTop is set to 'on' then move it up
                if (set.titleBarTop === "on") {
                    $slider.addClass("titleBarTop");
                }

                // function to start auto play
                function startSlider() {
                    sliderIntervalID = setInterval(function() {
                        nextSlide();
                    }, set.slidePause);
                }

                // on mouseover stop the autoplay
                $slider.mouseover(function() {
                    if (set.autoPlay === "on") {
                        clearInterval(sliderIntervalID);
                    }
                });

                // on mouseout starts the autoplay
                $slider.mouseout(function() {
                    if (set.autoPlay === "on") {
                        startSlider();
                    }
                });

                //on right arrow click
                $slider.find("> .right").click(nextSlide);

                //on left arrow click
                $slider.find("> .left").click(prevSlide);

                // Go to next slide
                function nextSlide() {
                    position = $slider.find(".show").index() + 1;
                    if (position > size - 1) position = 0;
                    changeCarousel(position);
                }

                // Go to previous slide
                function prevSlide() {
                    position = $slider.find(".show").index() - 1;
                    if (position < 0) position = size - 1;
                    changeCarousel(position);
                }

                //when user clicks slider button
                $slider.find(" > ul > li").click(function() {
                    position = $(this).index();
                    changeCarousel($(this).index());
                });

                //this changes the image and button selection
                function changeCarousel() {
                    $slider.find(".show").removeClass("show").fadeOut();
                    $slider.find("> div").eq(position).fadeIn(set.fadeSpeed).addClass("show");
                    // The Dots
                    $slider.find("> ul").find(".showli").removeClass("showli");
                    $slider.find("> ul > li").eq(position).addClass("showli");
                }

                return $slider;
            };
        })(jQuery);
        $("#slider1").sliderResponsive({
            // Using default everything
            // slidePause: 5000,
            // fadeSpeed: 800,
            // autoPlay: "on",
            // showArrows: "off",
            // hideDots: "off",
            // hoverZoom: "on",
            // titleBarTop: "off"
        });
        // ------------------------------------ end Slider ------------------------------

        // ------------------------------------ for amount display ----------------------
        $('.amount').click(function() {
            $(".prepared-amount").val($(this).val());
        });
    </script>
    <!-- Other transaction Modal -->
    <div class="modal bottom fade" id="Modalmore" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0">
                <div class="row">
                    <div class="col-md-4 more-card">
                        <div class="owl-items text-center">
                            <div class="card w-100 p-4 border-0 shadow-md rounded-lg">
                                <i class="fa-solid fa-gift mt-4 font-xl"></i>
                                <h4 class="font-xsss fw-700 mt-3 text-grey-900">Gift Cards</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 more-card">
                        <div class="owl-items text-center">
                            <div class="card w-100 p-4 border-0 shadow-md rounded-lg">
                                <i class="fa-solid fa-money-bill-trend-up mt-4 font-xl"></i>
                                <h4 class="font-xsss fw-700 mt-3 text-grey-900">Bank Cards</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 more-card">
                        <div class="owl-items text-center">
                            <div class="card w-100 p-4 border-0 shadow-md rounded-lg">
                                <i class="fa-solid fa-money-check mt-4 font-xl"></i>
                                <h4 class="font-xsss fw-700 mt-3 text-grey-900">Mutual Funds</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 more-card">
                        <div class="owl-items text-center">
                            <div class="card w-100 p-4 border-0 shadow-md rounded-lg">
                                <i class="fa-solid fa-water mt-4 font-xl"></i>
                                <h4 class="font-xsss fw-700 mt-3 text-grey-900">Water</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 more-card">
                        <div class="owl-items text-center">
                            <div class="card w-100 p-4 border-0 shadow-md rounded-lg">
                                <i class="fa-solid fa-scroll mt-4 font-xl"></i>
                                <h4 class="font-xsss fw-700 mt-3 text-grey-900">Insurance</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 more-card">
                        <div class="owl-items text-center">
                            <div class="card w-100 p-4 border-0 shadow-md rounded-lg">
                                <i class="fa-solid fa-landmark mt-4 font-xl"></i>
                                <h4 class="font-xsss fw-700 mt-3 text-grey-900">Loan Payment</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 more-card">
                        <div class="owl-items text-center">
                            <div class="card w-100 p-4 border-0 shadow-md rounded-lg">
                                <i class="fa-solid fa-car mt-4 font-xl"></i>
                                <h4 class="font-xsss fw-700 mt-3 text-grey-900">FASTag</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 more-card">
                        <div class="owl-items text-center">
                            <div class="card w-100 p-4 border-0 shadow-md rounded-lg">
                                <i class="fa-solid fa-tv mt-4 font-xl"></i>
                                <h4 class="font-xsss fw-700 mt-3 text-grey-900">Cable TV</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 more-card">
                        <div class="owl-items text-center">
                            <div class="card w-100 p-4 border-0 shadow-md rounded-lg">
                                <a href="{{ url('all_transaction') }}" class="all-category">
                                    <i class="fa-solid fa-table-cells-large mt-4 font-xl"></i>
                                    <h4 class="font-xsss fw-700 mt-3 text-grey-900">All</h4>
                                </a>
                                <!-- <i class="fa-regular fa-credit-card mt-4 font-xl text-current"></i>
                                                <h4 class="font-xsss fw-700 mt-3 text-grey-900">Credit Card</h4> -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endpush
@push('styles')
<style>
    .no-image-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f0f0f0;
        border-radius: 50%;
        color: #666;
        font-size: 24px;
        font-weight: bold;
    }
    
    .shop-category-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        overflow: hidden;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f0f0f0;
    }
    
    .no-product-image {
        width: 100%;
        height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f0f0f0;
        color: #666;
        font-size: 48px;
        font-weight: bold;
        margin-top: 1rem;
    }

    .kgen-card-image {
        height: 180px;
        object-fit: cover;
    }

    .kgen-card {
        border: none;
        border-radius: 12px;
        max-width: 100%;
        overflow: hidden;
    }
    
    .kgen-card .card-body {
        overflow: hidden;
        word-wrap: break-word;
    }
    
    .kgen-card .card-title {
        word-wrap: break-word;
        overflow-wrap: break-word;
        hyphens: auto;
    }

    .kgen-card-desc {
        min-height: 60px;
        color: #4b4b4b;
    }

    .kgen-card-pricing span {
        line-height: 1.2;
    }

    .kgen-badge {
        top: 12px;
        right: 12px;
        font-size: 0.75rem;
        padding: 6px 10px;
        border-radius: 12px;
    }
</style>
@endpush
