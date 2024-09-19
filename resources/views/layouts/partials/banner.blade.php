 <!--  desktop banner wrapper starts here -->

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
            <!-- Desktop Slide 1 -->
            <div class="carousel-item active">
                <img src="{{ asset('images/amazepay-desk-1.png') }}" alt="Amazepay Banner 1" class="d-block w-100">
            </div>
            <!-- Desktop Slide 2 -->
            <div class="carousel-item">
                <img src="{{ asset('images/amazepay-desk-2.png') }}" alt="Amazepay Banner 2" class="d-block w-100">
            </div>
            <!-- Desktop Slide 3 -->
            <div class="carousel-item">
                <img src="{{ asset('images/amazepay-desk-3.png') }}" alt="Amazepay Banner 3" class="d-block w-100">
            </div>
        </div>

        <!-- Controls -->
        <a class="carousel-control-prev" href="#desktopCarousel" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next" href="#desktopCarousel" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
        </a>
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
            <!-- Mobile Slide 1 -->
            <div class="carousel-item active">
                <img src="{{ asset('images/amazepay-mob-1.png') }}" alt="Amazepay Mobile Banner 1" class="d-block w-100">
            </div>
            <!-- Mobile Slide 2 -->
            <div class="carousel-item">
                <img src="{{ asset('images/amazepay-mob-2.png') }}" alt="Amazepay Mobile Banner 2" class="d-block w-100">
            </div>
            <!-- Mobile Slide 3 -->
            <div class="carousel-item">
                <img src="{{ asset('images/amazepay-mob-3.png') }}" alt="Amazepay Mobile Banner 3" class="d-block w-100">
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

<!--  desktop banner wrapper ends here -->

<!-- mobile banner wrapper starts here -->


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
                            @if ($product->slug && $product->images && $product->images->small)
                                <div class="col-lg-3 col-6">
                                    <div class="product-wrapper-image">
                                        <a href="{{ route('get-product-by-slug', ['slug' => $product->slug]) }}" class="d-block text-center">
                                            <p class="single-image-wrapper">
                                                <img src="{{ $product->images->small ?? URL::asset('/images/no-image.png') }}"
                                                     alt="product-image" class="w-100 mt-4 d-inline-block">
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

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- mobile banner wrapper ends here -->
