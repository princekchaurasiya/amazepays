@extends('layouts.app')
@section('title')
    Amazepay | Exclusive Gift Cards & Vouchers for Every Occasion
@endsection
@section('content')
    <!-- banner wrapper -->

    <div class="banner-wrapper style1 bg-image-contain">
        <!-- Desktop Carousel (hidden on mobile) -->
        <div id="desktopCarousel" class="carousel slide d-none d-md-block" data-ride="carousel">
            <!-- Indicators -->
            <ol class="carousel-indicators">
                <li data-target="#desktopCarousel" data-slide-to="0" class="active"></li>
                <li data-target="#desktopCarousel" data-slide-to="1"></li>
                <li data-target="#desktopCarousel" data-slide-to="2"></li>
            </ol>

            <!-- Slides for Desktop -->
            <div class="carousel-inner">
                <!-- Slide 1 -->
                <div class="carousel-item active">
                    <img src="{{ asset('images/amazepay-desk-1.png') }}" alt="Amazepay Banner 1" class="d-block w-100">
                </div>

                <!-- Slide 2 -->
                <div class="carousel-item">
                    <img src="{{ asset('images/amazepay-desk-2.png') }}" alt="Amazepay Banner 2" class="d-block w-100">
                </div>

                <!-- Slide 3 -->
                <div class="carousel-item">
                    <img src="{{ asset('images/amazepay-desk-3.png') }}" alt="Amazepay Banner 3" class="d-block w-100">
                </div>
            </div>

            <!-- Controls -->
            {{-- <a class="carousel-control-prev" href="#desktopCarousel" role="button" data-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="sr-only">Previous</span>
            </a>
            <a class="carousel-control-next" href="#desktopCarousel" role="button" data-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="sr-only">Next</span>
            </a> --}}
        </div>

        <!-- Mobile Carousel (hidden on desktop) -->
        <div id="mobileCarousel" class="carousel slide d-block d-md-none" data-ride="carousel">
            <!-- Indicators -->
            <ol class="carousel-indicators">
                <li data-target="#mobileCarousel" data-slide-to="0" class="active"></li>
                <li data-target="#mobileCarousel" data-slide-to="1"></li>
                <li data-target="#mobileCarousel" data-slide-to="2"></li>
            </ol>

            <!-- Slides for Mobile -->
            <div class="carousel-inner">
                <!-- Slide 1 -->
                <div class="carousel-item active">
                    <img src="{{ asset('images/amazepay-mob-1.png') }}" alt="Amazepay Banner 1" class="d-block w-100">
                </div>

                <!-- Slide 2 -->
                <div class="carousel-item">
                    <img src="{{ asset('images/amazepay-mob-2.png') }}" alt="Amazepay Banner 2" class="d-block w-100">
                </div>

                <!-- Slide 3 -->
                <div class="carousel-item">
                    <img src="{{ asset('images/amazepay-mob-3.png') }}" alt="Amazepay Banner 3" class="d-block w-100">
                </div>
            </div>

            <!-- Controls -->
            <a class="carousel-control-prev" href="#mobileCarousel" role="button" data-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="sr-only">Previous</span>
            </a>
            <a class="carousel-control-next" href="#mobileCarousel" role="button" data-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="sr-only">Next</span>
            </a>
        </div>
    </div>


    <!-- banner wrapper -->


    <div class="product-wrapper  pt-5 pb-5">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-12">
                    <h1 class="text-grey-900 fw-700 pb-0 mb-2 d-block text-center hot-deal-text">Hot Deals, Exclusive
                        Offers, and Special Picks!</h1>
                    <hr class="normalhr">
                </div>
                {{-- <div class="col-4">
                    <a href="{{ route('view-all-product') }}" class="fw-600 font-xsss text-current d-block text-right">View
                        More <i class="ti-angle-right font-xssss"></i></a>
                </div> --}}
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="row justify-content-center ">
                        <!-- loop product here -->
                        <div class="col-12 col-sm-12 col-md-12 col-lg-10 col-xl-10">
                            <div class="row">
                                @foreach ($allProducts as $product)
                                    <div class="col-lg-3 col-6">

                                        <div class="product-wrapper-image">
                                            {{-- <h4 class="fw-600 ls-2 float-right font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">30% off</h4> --}}
                                            <a href="{{ route('get-product-by-slug', ['slug' => $product->slug]) }}"
                                                class="d-block text-center">
                                                <p class="single-image-wrapper">
                                                    <img src="{{ $product->images->small == null ? URL::asset('/images/no-image.png') : $product->images->small }}"
                                                        alt="product-image" class="w-100 mt-4 d-inline-block">
                                                </p>
                                            </a>

                                            <hr>
                                            <a href="{{ route('get-product-by-slug', ['slug' => $product->slug]) }}">
                                                <div class="product-image-text-wrapper m-lg-1">
                                                    <p
                                                        class="text-center fw-600 text-product-name-color text-product-name-font-size mt-lg-2 mt-3">
                                                        {{ ucwords($product->name) }}
                                                    </p>
                                                </div>
                                            </a>


                                            @if ($product->discount_percentage && $product->discount_percentage > 0)
                                                <div class="ribbon"><span>{{ $product->discount_percentage }}% off</span>
                                                </div>
                                            @endif

                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
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
