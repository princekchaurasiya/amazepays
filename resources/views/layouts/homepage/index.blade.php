@extends('layouts.app')
@section('title')
    Amazepay | Exclusive Gift Cards & Vouchers for Every Occasion
@endsection
@section('content')
@php
    use App\Helpers\CommonHelper;
@endphp

    {{-- Banner Section --}}
    @if ($homeSettings->section_banner_status)
        @include('layouts.partials.banner')
    @endif

    <div class="product-wrapper pt-5 pb-5">
        <div class="container-fluid">

            {{-- Brand Section --}}
            @if ($homeSettings->section_brand_status)
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <h1 class="text-grey-900 fw-700 pb-0 mb-2 d-block text-center hot-deal-text">
                            {{ $homeSettings->section_brand_title }}
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
            @if ($homeSettings->section_hot_deal_status)
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="text-grey-900 fw-700 pb-0 mb-2 d-block text-center hot-deal-text">
                            {{ $homeSettings->section_hot_deal_title }}
                        </h1>
                        <hr class="normalhr">

                        <div class="row justify-content-center">
                            <div class="col-12 col-lg-10">
                                <div class="row">

                                    @foreach ($priorityProducts as $product)
                                    @if ($product->slug)
                                    <div class="col-lg-3 col-6">
                                        <div class="product-wrapper-image">
                                            <a href="{{ route('get-product-by-slug', ['slug' => $product->url]) }}" class="d-block text-center">
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
            @if ($homeSettings->section_category_status)
                <div class="row justify-content-center mt-5">
                    <div class="col-lg-10">
                        <h1 class="text-grey-900 fw-700 pb-0 mb-2 d-block text-center hot-deal-text">
                            {{ $homeSettings->section_category_title }}
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
            @if ($homeSettings->section_other_deal_status)
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="text-grey-900 fw-700 pb-0 mb-2 d-block text-center hot-deal-text">
                            {{ $homeSettings->section_other_deal_title }}
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
                                                <a href="{{ route('get-product-by-slug', ['slug' => $product->url]) }}" class="d-block text-center">
                                                    <img src="{{ $productImage }}" alt="product-image" class="w-100 mt-4">
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
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {



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
</style>
@endpush
