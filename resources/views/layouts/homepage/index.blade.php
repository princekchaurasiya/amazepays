@extends('layouts.app')
@section('title')
    Amazepay | Exclusive Gift Cards & Vouchers for Every Occasion
@endsection
@section('content')

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
                <p class="mb-0">Please visit the <a href="{{ url('/panel') }}" class="alert-link">Admin Panel</a> to complete the setup.</p>
            </div>
        </div>
    @endif

    <div class="product-wrapper py-8 md:py-10">
        <div class="mx-auto max-w-7xl px-4">

            {{-- Popular brands (Hubble-style grid) --}}
            @if ($homeSettings && $homeSettings->section_brand_status && $brands && $brands->isNotEmpty())
                <section id="storefront-section-brands" class="mb-12 scroll-mt-28" aria-labelledby="home-brands-heading">
                    <p id="home-brands-heading" class="mb-4 text-center text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">
                        {{ $homeSettings->section_brand_title ?? __('storefront.popular_brands') }}
                    </p>
                    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 row-cols-xl-6 g-3">
                        @foreach ($brands as $brand)
                            <div class="col">
                                <x-brand-card
                                    :brand="$brand"
                                    :discount="isset($brandMaxDiscounts) ? ($brandMaxDiscounts[$brand->id] ?? null) : null" />
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Hot deals --}}
            @if ($homeSettings && $homeSettings->section_hot_deal_status && $hotDealProducts && $hotDealProducts->isNotEmpty())
                <section id="storefront-section-hot" class="mb-12 scroll-mt-28" aria-labelledby="home-hot-heading">
                    <p id="home-hot-heading" class="mb-4 text-center text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">
                        {{ $homeSettings->section_hot_deal_title ?? __('storefront.hot_deals') }}
                    </p>
                    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3">
                        @foreach ($hotDealProducts as $product)
                            <div class="col">
                                <x-product-card :product="$product" />
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Category tiles (optional; header already lists categories — use for “explore more”) --}}
            @if ($homeSettings && $homeSettings->section_category_status && $categories && $categories->isNotEmpty())
                <section id="storefront-section-categories" class="mb-12 scroll-mt-28" aria-labelledby="home-cat-heading">
                    <p id="home-cat-heading" class="mb-4 text-center text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">
                        {{ $homeSettings->section_category_title ?? __('storefront.categories') }}
                    </p>
                    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3">
                        @foreach ($categories as $category)
                            <div class="col">
                                <a href="{{ route('categories.show', ['slug' => $category->slug]) }}"
                                    class="flex flex-col items-center rounded-2xl bg-white p-4 text-center shadow-sm ring-1 ring-gray-100 transition hover:ring-brand-500/25">
                                    <span class="mb-2 flex h-14 w-14 items-center justify-center overflow-hidden rounded-full bg-gray-50 ring-1 ring-gray-100">
                                        @if (!empty($category->thumbnail) && $category->thumbnail !== 'null')
                                            <img src="{{ Storage::url($category->thumbnail) }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            <span class="text-lg font-bold text-brand-600">{{ strtoupper(substr($category->name, 0, 1)) }}</span>
                                        @endif
                                    </span>
                                    <span class="text-sm font-medium text-gray-900 line-clamp-2">{{ $category->name }}</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- More deals --}}
            @if ($homeSettings && $homeSettings->section_other_deal_status && $otherDealProducts && $otherDealProducts->isNotEmpty())
                <section id="storefront-section-deals" class="mb-12 scroll-mt-28" aria-labelledby="home-other-heading">
                    <p id="home-other-heading" class="mb-4 text-center text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">
                        {{ $homeSettings->section_other_deal_title ?? __('storefront.other_deals') }}
                    </p>
                    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3">
                        @foreach ($otherDealProducts as $product)
                            <div class="col">
                                <x-product-card :product="$product" />
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- KGen Products Section (title from Settings → Homepage sections) --}}
            @if (!empty($showKgenSection) && !empty($kgenProducts))
                <div class="row mt-5">
                    <div class="col-12">
                        <h1 class="text-grey-900 fw-700 pb-0 mb-2 d-block text-center hot-deal-text">
                            {{ $kgenSectionTitle ?? 'KGen Technology' }}
                        </h1>
                        <hr class="normalhr">

                        <div class="row g-3 justify-content-center mx-0">
                            @foreach ($kgenProducts as $product)
                                <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-4 px-2">
                                    <div class="card kgen-card h-100 shadow-sm position-relative overflow-hidden">
                                        @if (($product['discount_percentage'] ?? 0) > 0)
                                            <span class="badge bg-danger position-absolute kgen-badge">
                                                {{ (int) ($product['discount_percentage'] ?? 0) }}% Off
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
                                                {{ $product['description_excerpt'] ?? '' }}
                                            </p>

                                            @if (!empty($product['primary_variant']))
                                                <div class="kgen-card-pricing mb-3">
                                                    <small class="text-muted d-block">
                                                        Variant: {{ $product['primary_variant']['variantDisplayName'] ?? '-' }}
                                                    </small>
                                                    <div class="d-flex flex-column">
                                                        @if (($product['variant_mrp'] ?? 0) > 0)
                                                            <span class="text-muted text-decoration-line-through">
                                                                ₹{{ number_format((float) ($product['variant_mrp'] ?? 0), 2) }}
                                                            </span>
                                                        @endif
                                                        <span class="fw-bold text-success fs-5">
                                                            ₹{{ number_format((float) ($product['effective_price'] ?? $product['variant_price'] ?? 0), 2) }}
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
@if($heroSection && $heroSection->status)
    {!! $heroSection->content !!}
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
            if ($('.category-slick-slider').length) {
                $('.category-slick-slider').slick({
                    slidesToShow: 8,
                    slidesToScroll: 1,
                    infinite: true,
                    arrows: false,
                    dots: true,
                    autoplay: true,
                    autoplaySpeed: 2000,
                    centerMode: true,
                    centerPadding: '10px',
                    responsive: [{
                        breakpoint: 768,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 1,
                            infinite: true,
                            centerMode: true,
                            centerPadding: '10px',
                            arrows: false,
                            dots: true,
                            autoplay: true,
                            autoplaySpeed: 1000,
                        }
                    }]
                });
            }
            if ($('.brand-slick-slider').length) {
                $('.brand-slick-slider').slick({
                    slidesToShow: 8,
                    slidesToScroll: 1,
                    infinite: true,
                    arrows: false,
                    dots: true,
                    autoplay: true,
                    autoplaySpeed: 1500,
                    centerMode: true,
                    centerPadding: '10px',
                    responsive: [{
                        breakpoint: 768,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 1,
                            infinite: true,
                            centerMode: true,
                            centerPadding: '10px',
                            arrows: false,
                            dots: true,
                            autoplay: true,
                            autoplaySpeed: 1000,
                        }
                    }]
                });
            }
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
