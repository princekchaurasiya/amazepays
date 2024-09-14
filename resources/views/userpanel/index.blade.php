@extends('layouts.app')
@section('title')
    Amazepay | Exclusive Gift Cards & Vouchers for Every Occasion
@endsection
@section('content')
@include('layouts.partials.banner')
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
