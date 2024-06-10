@extends('layouts.app')
@section('title')
Amazepay | Products
@endsection
@section('content')
        <div class="product-wrapper pt-lg--7 pt-5 pb-lg--7 pb-5">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 col-xs-6">
                        <h2 class="text-grey-900 fw-700 font-xxl pb-0 mb-1 d-block text-left">All</h2>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="row">
                            <div class="col-lg-3 bg-white p-3 border">

                                <h4 class="fw-600 ls-2 float-right font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">30% off</h4>
                                <a href="{{ route('productPage', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('productPage', ['id' => 1]) }}">
                                    <div class="container" style="background-color:white">
                                        <p class="text-center font-xsss mt-3 text-current"><b>20% OFF YOUR PURCHASE</b></p>
                                        <span class="font-xsss text-black fw-600">Flat 3% off. Applicable on payment via UPI.</span>
                                    </div>
                                    <div class="container">
                                        <span class="font-xssss mb-3 text-current fw-700">Use Promo Code: <h4 class="fw-600 ls-2 font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">BOH232</h4></span>
                                        <p class="font-xssss mt-2 text-red fw-600">Expires: Jan 03, 2021</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 bg-white p-3 border">

                                <h4 class="fw-600 ls-2 float-right font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">30% off</h4>
                                <a href="{{ route('productPage', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('productPage', ['id' => 1]) }}">
                                    <div class="container" style="background-color:white">
                                        <p class="text-center font-xsss mt-3 text-current"><b>20% OFF YOUR PURCHASE</b></p>
                                        <span class="font-xsss text-black fw-600">Flat 3% off. Applicable on payment via UPI.</span>
                                    </div>
                                    <div class="container">
                                        <span class="font-xssss mb-3 text-current fw-700">Use Promo Code: <h4 class="fw-600 ls-2 font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">BOH232</h4></span>
                                        <p class="font-xssss mt-2 text-red fw-600">Expires: Jan 03, 2021</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 bg-white p-3 border">

                                <h4 class="fw-600 ls-2 float-right font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">30% off</h4>
                                <a href="{{ route('productPage', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('productPage', ['id' => 1]) }}">
                                    <div class="container" style="background-color:white">
                                        <p class="text-center font-xsss mt-3 text-current"><b>20% OFF YOUR PURCHASE</b></p>
                                        <span class="font-xsss text-black fw-600">Flat 3% off. Applicable on payment via UPI.</span>
                                    </div>
                                    <div class="container">
                                        <span class="font-xssss mb-3 text-current fw-700">Use Promo Code: <h4 class="fw-600 ls-2 font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">BOH232</h4></span>
                                        <p class="font-xssss mt-2 text-red fw-600">Expires: Jan 03, 2021</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 bg-white p-3 border">

                                <h4 class="fw-600 ls-2 float-right font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">30% off</h4>
                                <a href="{{ route('productPage', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('productPage', ['id' => 1]) }}">
                                    <div class="container" style="background-color:white">
                                        <p class="text-center font-xsss mt-3 text-current"><b>20% OFF YOUR PURCHASE</b></p>
                                        <span class="font-xsss text-black fw-600">Flat 3% off. Applicable on payment via UPI.</span>
                                    </div>
                                    <div class="container">
                                        <span class="font-xssss mb-3 text-current fw-700">Use Promo Code: <h4 class="fw-600 ls-2 font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">BOH232</h4></span>
                                        <p class="font-xssss mt-2 text-red fw-600">Expires: Jan 03, 2021</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 bg-white p-3 border">

                                <h4 class="fw-600 ls-2 float-right font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">30% off</h4>
                                <a href="{{ route('productPage', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('productPage', ['id' => 1]) }}">
                                    <div class="container" style="background-color:white">
                                        <p class="text-center font-xsss mt-3 text-current"><b>20% OFF YOUR PURCHASE</b></p>
                                        <span class="font-xsss text-black fw-600">Flat 3% off. Applicable on payment via UPI.</span>
                                    </div>
                                    <div class="container">
                                        <span class="font-xssss mb-3 text-current fw-700">Use Promo Code: <h4 class="fw-600 ls-2 font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">BOH232</h4></span>
                                        <p class="font-xssss mt-2 text-red fw-600">Expires: Jan 03, 2021</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 bg-white p-3 border">

                                <h4 class="fw-600 ls-2 float-right font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">30% off</h4>
                                <a href="{{ route('productPage', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('productPage', ['id' => 1]) }}">
                                    <div class="container" style="background-color:white">
                                        <p class="text-center font-xsss mt-3 text-current"><b>20% OFF YOUR PURCHASE</b></p>
                                        <span class="font-xsss text-black fw-600">Flat 3% off. Applicable on payment via UPI.</span>
                                    </div>
                                    <div class="container">
                                        <span class="font-xssss mb-3 text-current fw-700">Use Promo Code: <h4 class="fw-600 ls-2 font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">BOH232</h4></span>
                                        <p class="font-xssss mt-2 text-red fw-600">Expires: Jan 03, 2021</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 bg-white p-3 border">

                                <h4 class="fw-600 ls-2 float-right font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">30% off</h4>
                                <a href="{{ route('productPage', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('productPage', ['id' => 1]) }}">
                                    <div class="container" style="background-color:white">
                                        <p class="text-center font-xsss mt-3 text-current"><b>20% OFF YOUR PURCHASE</b></p>
                                        <span class="font-xsss text-black fw-600">Flat 3% off. Applicable on payment via UPI.</span>
                                    </div>
                                    <div class="container">
                                        <span class="font-xssss mb-3 text-current fw-700">Use Promo Code: <h4 class="fw-600 ls-2 font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">BOH232</h4></span>
                                        <p class="font-xssss mt-2 text-red fw-600">Expires: Jan 03, 2021</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 bg-white p-3 border">

                                <h4 class="fw-600 ls-2 float-right font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">30% off</h4>
                                <a href="{{ route('productPage', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('productPage', ['id' => 1]) }}">
                                    <div class="container" style="background-color:white">
                                        <p class="text-center font-xsss mt-3 text-current"><b>20% OFF YOUR PURCHASE</b></p>
                                        <span class="font-xsss text-black fw-600">Flat 3% off. Applicable on payment via UPI.</span>
                                    </div>
                                    <div class="container">
                                        <span class="font-xssss mb-3 text-current fw-700">Use Promo Code: <h4 class="fw-600 ls-2 font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">BOH232</h4></span>
                                        <p class="font-xssss mt-2 text-red fw-600">Expires: Jan 03, 2021</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@push('scripts')
    <script>

    </script>
@endpush
@endsection