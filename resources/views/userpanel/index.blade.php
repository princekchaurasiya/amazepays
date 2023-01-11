@extends('app')
@section('title')
    Gift & Giggles
@endsection
@section('content')

    <!-- banner wrapper -->
        <div class="banner-wrapper style1 bg-image-contain" >
            <!-- Slider 1 -->
                <div class="slider" id="slider1">
                    <!-- Slides -->
                    <div style="background-image:url(https://img.freepik.com/premium-vector/characters-having-financial-problems-debts-loans-scenes_1325-3083.jpg?w=2000)"></div>
                    <div style="background-image:url(https://img.freepik.com/free-photo/ggift-beautiful-box-with-red-bow-grey-blanket_169016-5349.jpg?w=1480&t=st=1659363644~exp=1659364244~hmac=163fcaf4c4a624f0ce891ad80d60b8d7006e9aa6680ece2758a1fd28e332cfc3)"></div>
                    <div style="background-image:url(https://www.axisbank.com/images/default-source/revamp_new/cards/blogs/pre-paid-cards-blog.jpg?sfvrsn=e5048a55_4)"></div>
                    
                        <!-- The Arrows -->
                    <i class="left" class="arrows" style="z-index:2; position:absolute;"><svg viewBox="0 0 100 100">
                        <path d="M 10,50 L 60,100 L 70,90 L 30,50  L 70,10 L 60,0 Z"></path>
                        </svg></i>
                    <i class="right" class="arrows" style="z-index:2; position:absolute;"><svg viewBox="0 0 100 100">
                        <path d="M 10,50 L 60,100 L 70,90 L 30,50  L 70,10 L 60,0 Z" transform="translate(100, 100) rotate(180) "></path>
                        </svg></i>
                </div>
        </div>
    <!-- banner wrapper --> 

        <div class="product-wrapper pt-5 pb-7">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 col-xs-6">
                        <h2 class="text-grey-900 fw-700 font-xxl pb-0 mb-1 d-block text-left">Best Product</h2>
                    </div>
                    <div class="col-lg-6 col-xs-6">
                        <a href="{{route('view-all-product' , ['slug' => '1'])}}" class="fw-600 font-xsss text-current d-block text-right">View More <i class="ti-angle-right font-xssss"></i></a>
                    </div>
                </div>
                <div class="row">                    
                    <div class="col-lg-12">
                        <div class="cycle-slider-5 owl-carousel owl-theme dot-none owl-nav-link style2">
                            <div class="owl-items">
                                <div class="col-lg-12 p-3 border rounded-0">
                                    <h4 class="fw-600 ls-2 float-right font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">30% off</h4>
                                   
                                    <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                    <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
                                        <div class="container" style="background-color:white">
                                            <p class="text-center font-xsss mt-3 text-current"><b>20% OFF YOUR PURCHASE</b></p> 
                                            <span class="font-xsss fw-600 text-black">Flat 3% off. Applicable on payment via UPI.</span>
                                        </div>
                                        <div class="container">
                                            
                                            <span class="font-xssss mb-3 fw-600">Use Promo Code: <h4 class="fw-600 ls-2 font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">BOH232</h4></span>
                                            <p class="font-xssss mt-2 fw-600 text-red">Expires: Jan 03, 2021</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <div class="owl-items">
                                <div class="col-lg-12 p-3 border rounded-0">
                                    <h4 class="fw-600 ls-2 float-right font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">30% off</h4>
                                   
                                    <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                    <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
                                        <div class="container" style="background-color:white">
                                            <p class="text-center font-xsss mt-3 text-current"><b>20% OFF YOUR PURCHASE</b></p> 
                                            <span class="font-xsss fw-600 text-black">Flat 3% off. Applicable on payment via UPI.</span>
                                        </div>
                                        <div class="container">
                                            
                                            <span class="font-xssss mb-3 fw-600 text-current">Use Promo Code: <h4 class="fw-600 ls-2 font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">BOH232</h4></span>
                                            <p class="font-xssss mt-2 fw-600 text-red">Expires: Jan 03, 2021</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <div class="owl-items">
                                <div class="col-lg-12 p-3 border rounded-0">
                                    <h4 class="fw-600 ls-2 float-right font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">30% off</h4>
                                   
                                    <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                    <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
                                        <div class="container" style="background-color:white">
                                            <p class="text-center font-xsss mt-3 text-current"><b>20% OFF YOUR PURCHASE</b></p> 
                                            <span class="font-xsss fw-600 text-black">Flat 3% off. Applicable on payment via UPI.</span>
                                        </div>
                                        <div class="container">
                                            
                                            <span class="font-xssss mb-3 fw-600 text-current">Use Promo Code: <h4 class="fw-600 ls-2 font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">BOH232</h4></span>
                                            <p class="font-xssss mt-2 fw-600 text-red">Expires: Jan 03, 2021</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <div class="owl-items">
                                <div class="col-lg-12 p-3 border rounded-0">
                                    <h4 class="fw-600 ls-2 float-right font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">30% off</h4>
                                   
                                    <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                    <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
                                        <div class="container" style="background-color:white">
                                            <p class="text-center font-xsss mt-3 text-current"><b>20% OFF YOUR PURCHASE</b></p> 
                                            <span class="font-xsss fw-600 text-black">Flat 3% off. Applicable on payment via UPI.</span>
                                        </div>
                                        <div class="container">
                                            
                                            <span class="font-xssss mb-3 fw-600 text-current">Use Promo Code: <h4 class="fw-600 ls-2 font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">BOH232</h4></span>
                                            <p class="font-xssss mt-2 fw-600 text-red">Expires: Jan 03, 2021</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <div class="owl-items">
                                <div class="col-lg-12 p-3 border rounded-0">
                                    <h4 class="fw-600 ls-2 float-right font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">30% off</h4>
                                   
                                    <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                    <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
                                        <div class="container" style="background-color:white">
                                            <p class="text-center font-xsss mt-3 text-current"><b>20% OFF YOUR PURCHASE</b></p> 
                                            <span class="font-xsss fw-600 text-black">Flat 3% off. Applicable on payment via UPI.</span>
                                        </div>
                                        <div class="container">
                                            
                                            <span class="font-xssss mb-3 fw-600 text-current">Use Promo Code: <h4 class="fw-600 ls-2 font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">BOH232</h4></span>
                                            <p class="font-xssss mt-2 fw-600 text-red">Expires: Jan 03, 2021</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <div class="owl-items">
                                <div class="col-lg-12 p-3 border rounded-0">
                                    <h4 class="fw-600 ls-2 float-right font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">30% off</h4>
                                   
                                    <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                    <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
                                        <div class="container" style="background-color:white">
                                            <p class="text-center font-xsss mt-3 text-current"><b>20% OFF YOUR PURCHASE</b></p> 
                                            <span class="font-xsss fw-600 text-black">Flat 3% off. Applicable on payment via UPI.</span>
                                        </div>
                                        <div class="container">
                                            
                                            <span class="font-xssss mb-3 fw-600 text-current">Use Promo Code: <h4 class="fw-600 ls-2 font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">BOH232</h4></span>
                                            <p class="font-xssss mt-2 fw-600 text-red">Expires: Jan 03, 2021</p>
                                        </div>
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="product-wrapper pt-lg--7 pt-5 pb-lg--7 pb-5">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 col-xs-6">
                        <h2 class="text-grey-900 fw-700 font-xxl pb-0 mb-1 d-block text-left">All</h2>
                    </div>
                    <div class="col-lg-6 col-xs-6">
                        <a href="{{route('view-all-product' , ['slug' => '1'])}}" class="fw-600 font-xsss text-current d-block text-right">View More <i class="ti-angle-right font-xssss"></i></a>
                    </div>
                </div>
                <div class="row">                
                    <div class="col-lg-12">
                        <div class="row">
                            <div class="col-lg-3 bg-white p-3 border">
                                
                                <h4 class="fw-600 ls-2 float-right font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">30% off</h4>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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

        <div class="product-wrapper pt-lg--7 pt-5 pb-lg--7 pb-5">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 col-xs-6">
                        <h2 class="text-grey-900 fw-700 font-xxl pb-0 mb-1 d-block text-left">Trending</h2>
                    </div>
                    <div class="col-lg-6 col-xs-6">
                        <a href="{{route('view-all-product' , ['slug' => '1'])}}" class="fw-600 font-xsss text-current d-block text-right">View More <i class="ti-angle-right font-xssss"></i></a>
                    </div>
                </div>
                <div class="row">                
                    <div class="col-lg-12">
                        <div class="row">
                            <div class="col-lg-3 bg-white p-3 border">
                                
                                <h4 class="fw-600 ls-2 float-right font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">30% off</h4>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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

        <div class="product-wrapper pt-lg--7 pt-5 pb-lg--7 pb-5">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 col-xs-6">
                        <h2 class="text-grey-900 fw-700 font-xxl pb-0 mb-1 d-block text-left">Hot</h2>
                    </div>
                    <div class="col-lg-6 col-xs-6">
                        <a href="{{route('view-all-product' , ['slug' => '1'])}}" class="fw-600 font-xsss text-current d-block text-right">View More <i class="ti-angle-right font-xssss"></i></a>
                    </div>
                </div>
                <div class="row">                
                    <div class="col-lg-12">
                        <div class="row">
                            <div class="col-lg-3 bg-white p-3 border">
                                
                                <h4 class="fw-600 ls-2 float-right font-xsssss text-white text-uppercase bg-current p-2 d-inline-block">30% off</h4>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}" class="d-block text-center"><img src="{{URL::asset('/images/hamburger.jpg')}}" alt="product-image" class="w-100 mt-1 d-inline-block"></a>
                                <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
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

       

    
        <!-- <div class="how-to-work pt-lg--7 pb-lg--7 pb-5 pt-5">
            <div class="container">
                <div class="row mb-4">
                    <div class="col-lg-12 mb-lg-3">
                        <h2 class="text-grey-900 fw-400 display1-size">All</h2>
                        <hr>
                    </div>
                  
                    <div class="col-lg-4 mb-4">
                        <img class="coupan-img" src="{{URL::asset('/images/hamburger.jpg')}}" alt="Avatar" style="width:100%;">
                        <div class="coupon">
                            <div class="container" style="background-color:white">
                                <span class="coupan-title"><b>20% OFF YOUR PURCHASE</b></span> 
                                <span class="expire">Expires: Jan 03, 2021</span>
                            </div>
                            <div class="container">
                                <span class="coupan-detail">Lorem ipsum dolor sit amet.</span>
                                <span class="code-name" >Use Promo Code: <span class="promo">BOH232</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-4">
                        <img class="coupan-img" src="{{URL::asset('/images/hamburger.jpg')}}" alt="Avatar" style="width:100%;">
                        <div class="coupon">
                            <div class="container" style="background-color:white">
                                <span class="coupan-title"><b>20% OFF YOUR PURCHASE</b></span> 
                                <span class="expire">Expires: Jan 03, 2021</span>
                            </div>
                            <div class="container">
                                <span class="coupan-detail">Lorem ipsum dolor sit amet.</span>
                                <span class="code-name" >Use Promo Code: <span class="promo">BOH232</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-4">
                        <img class="coupan-img" src="{{URL::asset('/images/hamburger.jpg')}}" alt="Avatar" style="width:100%;">
                        <div class="coupon">
                            <div class="container" style="background-color:white">
                                <span class="coupan-title"><b>20% OFF YOUR PURCHASE</b></span> 
                                <span class="expire">Expires: Jan 03, 2021</span>
                            </div>
                            <div class="container">
                                <span class="coupan-detail">Lorem ipsum dolor sit amet.</span>
                                <span class="code-name" >Use Promo Code: <span class="promo">BOH232</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-4">
                        <img class="coupan-img" src="{{URL::asset('/images/hamburger.jpg')}}" alt="Avatar" style="width:100%;">
                        <div class="coupon">
                            <div class="container" style="background-color:white">
                                <span class="coupan-title"><b>20% OFF YOUR PURCHASE</b></span> 
                                <span class="expire">Expires: Jan 03, 2021</span>
                            </div>
                            <div class="container">
                                <span class="coupan-detail">Lorem ipsum dolor sit amet.</span>
                                <span class="code-name" >Use Promo Code: <span class="promo">BOH232</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-4">
                        <img class="coupan-img" src="{{URL::asset('/images/hamburger.jpg')}}" alt="Avatar" style="width:100%;">
                        <div class="coupon">
                            <div class="container" style="background-color:white">
                                <span class="coupan-title"><b>20% OFF YOUR PURCHASE</b></span> 
                                <span class="expire">Expires: Jan 03, 2021</span>
                            </div>
                            <div class="container">
                                <span class="coupan-detail">Lorem ipsum dolor sit amet.</span>
                                <span class="code-name" >Use Promo Code: <span class="promo">BOH232</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-4">
                        <img class="coupan-img" src="{{URL::asset('/images/hamburger.jpg')}}" alt="Avatar" style="width:100%;">
                        <div class="coupon">
                            <div class="container" style="background-color:white">
                                <span class="coupan-title"><b>20% OFF YOUR PURCHASE</b></span> 
                                <span class="expire">Expires: Jan 03, 2021</span>
                            </div>
                            <div class="container">
                                <span class="coupan-detail">Lorem ipsum dolor sit amet.</span>
                                <span class="code-name" >Use Promo Code: <span class="promo">BOH232</span></span>
                            </div>
                        </div>
                    </div>

                </div>
               
                <div class="row">
                    <div class="col-lg-12 mb-lg-5 mb-4 pb-3">
                        <h2 class="text-grey-900 fw-400 display1-size">Hot Deals</h2>
                        <hr>
                    </div>
                  
                    <div class="col-lg-4 mb-3">
                        <a href="{{ route('gift_card_detail_page', ['id' => 1]) }}">
                            <img class="coupan-img" src="{{URL::asset('/images/hamburger.jpg')}}" alt="Avatar" style="width:100%;">
                            <div class="coupon">
                                <div class="container" style="background-color:white">
                                    <span class="coupan-title"><b>20% OFF YOUR PURCHASE</b></span> 
                                    <span class="expire">Expires: Jan 03, 2021</span>
                                </div>
                                <div class="container">
                                    <span class="coupan-detail">Lorem ipsum dolor sit amet.</span>
                                    <span class="code-name" >Use Promo Code: <span class="promo">BOH232</span></span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 mb-3">
                        <img class="coupan-img" src="{{URL::asset('/images/hamburger.jpg')}}" alt="Avatar" style="width:100%;">
                        <div class="coupon">
                            <div class="container" style="background-color:white">
                                <span class="coupan-title"><b>20% OFF YOUR PURCHASE</b></span> 
                                <span class="expire">Expires: Jan 03, 2021</span>
                            </div>
                            <div class="container">
                                <span class="coupan-detail">Lorem ipsum dolor sit amet.</span>
                                <span class="code-name" >Use Promo Code: <span class="promo">BOH232</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 mb-3">
                        <img class="coupan-img" src="{{URL::asset('/images/hamburger.jpg')}}" alt="Avatar" style="width:100%;">
                        <div class="coupon">
                            <div class="container" style="background-color:white">
                                <span class="coupan-title"><b>20% OFF YOUR PURCHASE</b></span> 
                                <span class="expire">Expires: Jan 03, 2021</span>
                            </div>
                            <div class="container">
                                <span class="coupan-detail">Lorem ipsum dolor sit amet.</span>
                                <span class="code-name" >Use Promo Code: <span class="promo">BOH232</span></span>
                            </div>
                        </div>
                    </div>
                   
                </div>
            </div>
        </div> -->
       

        <!-- <div class="offer-wrapper pb-4 bg-lightblue"> -->
        <!-- <div class="offer-wrapper pb-4">


            <div class="tab-wrapper">
                <div class="container">
                    <div class="row-eq-height">
                        <div class="col-lg-12 tab-container"> 
                            <nav class="d-lg-block d-none">
                                <div class="nav nav-tabs border-0 nav-fill" id="nav-tab" role="tablist">
                                    <a class="nav-item nav-link rounded-lg border-0 p-4 mr-2 active bg-current" id="nav-home-tab" data-toggle="tab" href="#nav-home" role="tab" aria-controls="nav-home" aria-selected="true"><i class="font-xxl ti-mobile text-current d-block mt-2"></i><h4 class="font-xssss fw-600 text-grey-900 mt-3">Mobile Recharge</h4></a>
                                    
                                    <a class="nav-item nav-link rounded-lg border-0 p-4 mr-2" id="nav-profile-tab" data-toggle="tab" href="#nav-profile" role="tab" aria-controls="nav-profile" aria-selected="false"><i class="font-xxl ti-shine text-current d-block mt-2"></i><h4 class="font-xssss fw-600 text-grey-900 mt-3">Electicity</h4></a>
                                    <a class="nav-item nav-link rounded-lg border-0 p-4 mr-2" id="nav-contact-tab" data-toggle="tab" href="#nav-contact" role="tab" aria-controls="nav-contact" aria-selected="false"><i class="font-xxl  fa-solid fa-satellite-dish d-block mt-2"></i><h4 class="font-xssss fw-600 text-grey-900 mt-3">DTH</h4></a>

                                    <a class="nav-item nav-link rounded-lg border-0 p-4 mr-2" id="nav-gas-tab" data-toggle="tab" href="#nav-gas" role="tab" aria-controls="nav-gas" aria-selected="false"><i class="font-xxl fa-solid fa-fire-flame-simple"></i><h4 class="font-xssss fw-600 text-grey-900 mt-3">GAS</h4></a>

                                    <a class="nav-item nav-link rounded-lg border-0 p-4 mr-2" id="nav-money-tab" data-toggle="tab" href="#nav-money" role="tab" aria-controls="nav-money" aria-selected="false"><i class="font-xxl fa-solid fa-money-bill-transfer"></i><h4 class="font-xssss fw-600 text-grey-900 mt-3">Money Transfer</h4></a>

                                    <a href="#" class="nav-item nav-link rounded-lg border-0 p-4 mr-0" data-toggle="modal" data-target="#Modalmore"><i class="font-xxl fa-solid fa-ellipsis-vertical"></i><h4 class="font-xssss fw-600 text-grey-900 mt-3">More</h4></a>
                                  
                                </div>
                            </nav>
                            <div class="tab-content rounded-lg  bg-blur p-3 mt-2" id="nav-tabContent">
                                <div class="bg-white shadow-xs rounded-lg ml-4 mr-4 d-none d-block-md">
                                    <a href="#" class="dash-menu d-none d-block-md fw-700 p-3 text-current font-xsss ls-3 "> BROWSE CATEGORIES <i class="ti-menu float-right font-xss mt-1"></i></a>
                                    <ul class="tab-ul">
                                        <li><a href="#" class="bg-white p-3 border-bottom-light w-100 fw-700 text-grey-700 font-xsss d-inline-block"><i class="ti-mobile font-md float-left mr-3"></i> Postpaid Rechagre <i class="ti-angle-right float-right text-grey-400 mt-1"></i></a></li>
                                        <li><a href="#" class="bg-white p-3 border-bottom-light w-100 fw-600 text-grey-500 font-xsss d-inline-block"><i class="ti-plug font-md float-left mr-3"></i> Electicity Rechagre <i class="ti-angle-right float-right text-grey-400 mt-1"></i></a></li>
                                        <li><a href="#" class="bg-white p-3 border-bottom-light w-100 fw-600 text-grey-500 font-xsss d-inline-block"><i class="ti-pie-chart font-md float-left mr-3"></i> DTH Rechagre <i class="ti-angle-right float-right text-grey-400 mt-1"></i></a></li>
                                        <li><a href="#" class="bg-white p-3 border-bottom-light w-100 fw-600 text-grey-500 font-xsss d-inline-block"><i class="ti-stats-up font-md float-left mr-3"></i> Share Market <i class="ti-angle-right float-right text-grey-400 mt-1"></i></a></li>
                                        <li><a href="#" class="bg-white p-3 border-bottom-light w-100 fw-600 text-grey-500 font-xsss d-inline-block"><i class="ti-harddrives font-md float-left mr-3"></i> Broadband Rechagre <i class="ti-angle-right float-right text-grey-400 mt-1"></i></a></li>
                                        <li><a href="#" class="bg-white p-3 border-bottom-light w-100 fw-600 text-grey-500 font-xsss d-inline-block"><i class="ti-game font-md float-left mr-3"></i> Game Download <i class="ti-angle-right float-right text-grey-400 mt-1"></i></a></li>
                                        <li><a href="#" class="bg-white p-3 border-bottom-light w-100 fw-600 text-grey-500 font-xsss d-inline-block"><i class="ti-wallet font-md float-left mr-3"></i> Pay loan <i class="ti-angle-right float-right text-grey-400 mt-1"></i></a></li>
                                        <li><a href="#" class="bg-white p-3 border-bottom-light w-100 fw-600 text-grey-500 font-xsss d-inline-block"><i class="ti-credit-card font-md float-left mr-3"></i> Credit Card <i class="ti-angle-right float-right text-grey-400 mt-1"></i></a></li>
                                        <li><a href="#" class="bg-white p-3 border-bottom-light w-100 fw-600 text-grey-500 font-xsss d-inline-block"><i class="ti-video-clapper font-md float-left mr-3"></i> Book Movie <i class="ti-angle-right float-right text-grey-400 mt-1"></i></a></li>
                                        <li><a href="#" class="bg-white p-3 w-100 fw-600 text-grey-500 font-xsss d-inline-block"><i class="ti-package font-md float-left mr-3"></i> More <i class="ti-angle-right float-right text-grey-400 mt-1"></i></a></li>
                                    </ul>
                                </div>
                                <div class="tab-pane p-4 fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
                                    <form action="#">
                                        <div class="row">
                                            <div class="col-sm-12 mb-2"><h4 class="fw-700 font-xl">Mobile Recharge & Bill Payment</h4></div>
                                            <div class="col-sm-12 mb-4">
                                                <div class="custom-control mr-0 custom-radio custom-control-inline">
                                                    <input type="radio" class="custom-control-input" id="customRadio2" name="example" value="customEx" checked>
                                                    <label class="custom-control-label small-size fw-500 text-grey-900 font-xsss" for="customRadio2">Prepaid</label>
                                                </div>
                                                <div class="custom-control mr-4 custom-radio custom-control-inline">
                                                    <input type="radio" class="custom-control-input" id="customRadio" name="example" value="customEx">
                                                    <label class="custom-control-label small-size fw-500 text-grey-900 font-xsss" for="customRadio">Postpaid</label>
                                                </div>
                                            </div>
                                            <div class="col-sm-3"><input type="text" class="form-control mb-2" placeholder="Enter Mobile Number" required></div>
                                            <div class="col-sm-3">
                                                <select class="operator">
                                                    <option></option>
                                                    <option value="airtel">Airtel</option>
                                                    <option value="jio">JIO</option>
                                                    <option value="vi">VI</option>
                                                </select>   
                                            </div>
                                            <div class="col-sm-3">
                                                <select class="select-state">
                                                    <option></option>
                                                    <option value="maharashtra">Maharashtra</option>
                                                    <option value="Gujurat">Gujurat</option>
                                                    <option value="goa">Goa</option>
                                                </select>   
                                            </div>
                                           
                                            <div class="col-sm-2"><input type="text" class="form-control mb-2 prepared-amount" placeholder="Amount">
                                            <span id="view-plan" >view plan</span></div>
                                            <div class="col-sm-1"><button class="bg-current border-0 w-100 form-bttn fw-500 rounded-lg text-white"><i class="ti-arrow-right"></i></button></div>
                                        </div>
                                    </form>
                                    <br/>
                                   
                                    <table hidden class="table table-responsive-sm table-hover plan-details">
                                        <thead>
                                            <tr>
                                            <th scope="col">Circle</th>
                                            <th scope="col">Plan Type</th>
                                            <th scope="col">Validity</th>
                                            <th scope="col">Description</th>
                                            <th scope="col">Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                            <td scope="row">Maharashtra</td>
                                            <td>Recharge</td>
                                            <td>28 Days</td>
                                            <td>Enjoy talktime of Rs 99 valid for 28 days at 1p/sec local & STD calls with 200 MB data.</td>
                                            <td><button class="bg-current border-0 w-100 form-bttn fw-500 rounded-lg text-white amount" value="99">RS. 99</Button></td>
                                            </tr>
                                            <tr>
                                            <td scope="row">Maharashtra</td>
                                            <td>Recharge</td>
                                            <td>28 Days</td>
                                            <td>Enjoy talktime of Rs 99 valid for 28 days at 1p/sec local & STD calls with 200 MB data.</td>
                                            <td><button class="bg-current border-0 w-100 form-bttn fw-500 rounded-lg text-white amount" value="199">RS. 199</Button></td>
                                            </tr>
                                            <tr>
                                            <td scope="row">Maharashtra</td>
                                            <td>Recharge</td>
                                            <td>28 Days</td>
                                            <td>Enjoy talktime of Rs 99 valid for 28 days at 1p/sec local & STD calls with 200 MB data.</td>
                                            <td><button class="bg-current border-0 w-100 form-bttn fw-500 rounded-lg text-white amount" value="299">RS. 299</Button></td>
                                            </tr>
                                            
                                        </tbody>
                                    </table>
                                </div>
                                <div class="tab-pane p-4 fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                                    <form action="#">
                                        <div class="row">
                                            <div class="col-sm-12 mb-2"><h4 class="fw-700 font-xl">Pay For Electricity</h4></div>
                                            <div class="col-sm-12 mb-4">
                                                <div class="custom-control mr-4 custom-radio custom-control-inline">
                                                    <input type="radio" class="custom-control-input" id="customRadio3" name="example" value="customEx" checked>
                                                    <label class="custom-control-label small-size fw-500 text-grey-900 font-xsss" for="customRadio3">Electricity Boards</label>
                                                </div>
                                                <div class="custom-control mr-0 custom-radio custom-control-inline">
                                                    <input type="radio" class="custom-control-input" id="customRadio4" name="example" value="customEx">
                                                    <label class="custom-control-label small-size fw-500 text-grey-900 font-xsss" for="customRadio4">Apartments</label>
                                                </div>
                                            </div>
                                            <div class="col-sm-3">
                                                <select class="select-state">
                                                    <option></option>
                                                    <option value="maharashtra">Maharashtra</option>
                                                    <option value="Gujurat">Gujurat</option>
                                                    <option value="goa">Goa</option>
                                                </select>   
                                            </div>
                                            <div class="col-sm-3">
                                                <select class="select-electric-board">
                                                    <option></option>
                                                    <option value="adani">Adani</option>
                                                    <option value="tata_power">Tata Power</option>
                                                    <option value="best">Best</option>
                                                </select>   
                                            </div>
                                            <div class="col-sm-3"><input type="text" class="form-control mb-2" placeholder="Consumer Number"></div>
                                            <div class="col-sm-2"><input type="text" class="form-control mb-3" placeholder="Amount"></div>
                                            <div class="col-sm-1"><button class="bg-current border-0 w-100 form-bttn fw-500 rounded-lg text-white"><i class="ti-arrow-right"></i></button></div>
                                        </div>
                                    </form>
                                </div>
                                <div class="tab-pane p-4 fade" id="nav-contact" role="tabpanel" aria-labelledby="nav-contact-tab">
                                    <form action="#">
                                        <div class="row">
                                            <div class="col-sm-12 mb-2"><h4 class="fw-700 font-xl">Recharge DTH or TV</h4></div>
                                            <div class="col-sm-3">
                                                <select class="dth-operator">
                                                    <option></option>
                                                    <option value="dth_tv">DTH TV</option>
                                                    <option value="airtel_digital_tv">Airtel Digital TV</option>
                                                    <option value="d2h">d2h</option>
                                                </select>   
                                            </div>
                                            <div class="col-sm-3"><input type="text" class="form-control mb-2" placeholder="Consumer ID"></div>
                                            <div class="col-sm-3"><input type="text" class="form-control mb-2" placeholder="Amount"></div>
                                            <div class="col-sm-3"><button class="bg-current border-0 w-100 form-bttn fw-500 rounded-lg text-white"><i class="ti-arrow-right"></i></button></div>
                                        </div>
                                    </form>
                                </div>
                                <div class="tab-pane p-4 fade" id="nav-gas" role="tabpanel" aria-labelledby="nav-gas-tab">
                                    <form action="#">
                                        <div class="row">
                                            <div class="col-sm-12 mb-2"><h4 class="fw-700 font-xl">Pay Gas Bill</h4></div>
                                            <div class="col-sm-3">
                                                <select class="gas-bill">
                                                    <option></option>
                                                    <option value="hp_gas">HP Gas</option>
                                                    <option value="bharat_gas">Bharat Gas</option>
                                                </select>   
                                            </div>
                                            <div class="col-sm-3"><input type="text" class="form-control mb-2" placeholder="LPG ID/Consumer ID"></div>
                                            <div class="col-sm-3"><input type="text" class="form-control mb-2" placeholder="Gas Agency"></div>
                                            <div class="col-sm-3"><button class="bg-current border-0 w-100 form-bttn fw-500 rounded-lg text-white"><i class="ti-arrow-right"></i></button></div>
                                        </div>
                                    </form>
                                </div>
                                <div class="tab-pane p-4 fade" id="nav-money" role="tabpanel" aria-labelledby="nav-money-tab">
                                    <form action="#">
                                        <div class="row">
                                            <div class="col-sm-12 mb-2"><h4 class="fw-700 font-xl">Money Transfer</h4></div>
                                            <div class="col-sm-3"><input type="password" class="form-control mb-2" placeholder="Account Number"></div>
                                            <div class="col-sm-3"><input type="text" class="form-control mb-2" placeholder="ReEnter Account Number"></div>
                                            <div class="col-sm-3"><input type="text" class="form-control mb-2" placeholder="IFSC Code"></div>
                                            <div class="col-sm-3"><input type="text" class="form-control mb-2" placeholder="Amount"></div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-4"><input type="password" class="form-control mb-2" placeholder="Beneficiary Name"></div>
                                            <div class="col-sm-4"><input type="text" class="form-control mb-2" placeholder="Beneficiary Nick Name"></div>
                                            <div class="col-sm-4"><button class="bg-current border-0 w-100 form-bttn fw-500 rounded-lg text-white"><i class="ti-arrow-right"></i></button></div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>  
        </div> -->

        <!-- <div class="how-to-work pt-lg--7 pb-lg--7 pb-5 pt-5">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 text-center mb-lg-5 mb-4 pb-3">
                        <h2 class="text-grey-900 fw-400 display1-size">How it work</h2>
                    </div>
                    <div class="col-lg-4 mb-3">
                        <div class="card shadow-lg rounded-0 p-5 bg-white text-center border-0">
                            <i class="ti-home ml-auto mr-auto round-lg-btn text-white bg-current font-xxl text-center"></i>
                            <h2 class="fw-700 font-sm mt-4">What we do</h2>
                            <p class="font-xsss fw-500 text-grey-500 lh-26 mt-2"> We focused on connecting retailers to customers, businesses and their employees through various products and services in the prepaid market</p>
                        </div>
                    </div>

                    <div class="col-lg-4 mb-3">
                        <div class="card shadow-lg rounded-0 p-5 bg-white text-center border-0">
                            <i class="ti-harddrives ml-auto mr-auto round-lg-btn text-white bg-current font-xxl text-center"></i>
                            <h2 class="fw-700 font-sm mt-4">How we do</h2>
                            <p class="font-xsss fw-500 text-grey-500 lh-26 mt-2">Our Network is bringing prepaid and digital commerce together for retailers, brands, consumers and corporate incentives.</p>
                        </div>
                    </div>

                    <div class="col-lg-4 mb-3">
                        <div class="card shadow-lg rounded-0 p-5 bg-white text-center border-0">
                            <i class="ti-package ml-auto mr-auto round-lg-btn text-white bg-current font-xxl text-center"></i>
                            <h2 class="fw-700 font-sm mt-4">Explore amazing code</h2>
                            <p class="font-xsss fw-500 text-grey-500 lh-26 mt-2">We are digital agency, a small design agency based in paris as i was groping to remove through language.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->

        <!-- <div class="feedback-wrapper pt-lg--7 pb-lg--7 pb-5 pt-5">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 text-center mb-lg-5 mb-4 pb-3">
                        <h2 class="text-grey-900 fw-400 display1-size">Our Customers love what we do</h2>
                    </div>
                    <div class="col-lg-12">
                        <div class="feedback-slider owl-carousel owl-theme overflow-visible">
                            <div class="owl-items">
                                <div class="card shadow-lg rounded-0 p-5 bg-white text-left border-0">
                                    <p class="font-xsss fw-500 text-grey-500 lh-32 mt-0 mb-4">Human coronaviruses are common and are typically associated with mild illnesses, similar to the common cold. We are digital agency.</p>
                                    <div class="card-body p-0">
                                        <img src="https://via.placeholder.com/80x80.png" alt="user" class="w60 float-left mr-3">
                                        <h4 class="text-current fw-700 font-xsss mt-1">Thomas Smith</h4>    
                                        <h5 class="text-uppercase font-xsssss fw-500 mb-1 text-grey-500">Ceo Zipto</h5>
                                        <div class="star d-block w-100 text-left">
                                            <img src="images/star.png" alt="star" class="w15 float-left">
                                            <img src="images/star.png" alt="star" class="w15 float-left">
                                            <img src="images/star.png" alt="star" class="w15 float-left">
                                            <img src="images/star.png" alt="star" class="w15 float-left">
                                            <img src="images/star-disable.png" alt="star" class="w15 float-left">
                                        </div>
                                    </div>                            
                                </div>
                            </div>

                            <div class="owl-items">
                                <div class="card shadow-lg rounded-0 p-5 bg-white text-left border-0">
                                    <p class="font-xsss fw-500 text-grey-500 lh-32 mt-0 mb-4">Human coronaviruses are common and are typically associated with mild illnesses, similar to the common cold. We are digital agency.</p>
                                    <div class="card-body p-0">
                                        <img src="https://via.placeholder.com/80x80.png" alt="user" class="w60 float-left mr-3">
                                        <h4 class="text-current fw-700 font-xsss mt-1">Thomas Smith</h4>    
                                        <h5 class="text-uppercase font-xsssss fw-500 mb-1 text-grey-500">Ceo Zipto</h5>
                                        <div class="star d-block w-100 text-left">
                                            <img src="images/star.png" alt="star" class="w15 float-left">
                                            <img src="images/star.png" alt="star" class="w15 float-left">
                                            <img src="images/star.png" alt="star" class="w15 float-left">
                                            <img src="images/star.png" alt="star" class="w15 float-left">
                                            <img src="images/star-disable.png" alt="star" class="w15 float-left">
                                        </div>
                                    </div>                            
                                </div>
                            </div>

                            <div class="owl-items">
                                <div class="card shadow-lg rounded-0 p-5 bg-white text-left border-0">
                                    <p class="font-xsss fw-500 text-grey-500 lh-32 mt-0 mb-4">Human coronaviruses are common and are typically associated with mild illnesses, similar to the common cold. We are digital agency.</p>
                                    <div class="card-body p-0">
                                        <img src="https://via.placeholder.com/80x80.png" alt="user" class="w60 float-left mr-3">
                                        <h4 class="text-current fw-700 font-xsss mt-1">Thomas Smith</h4>    
                                        <h5 class="text-uppercase font-xsssss fw-500 mb-1 text-grey-500">Ceo Zipto</h5>
                                        <div class="star d-block w-100 text-left">
                                            <img src="images/star.png" alt="star" class="w15 float-left">
                                            <img src="images/star.png" alt="star" class="w15 float-left">
                                            <img src="images/star.png" alt="star" class="w15 float-left">
                                            <img src="images/star.png" alt="star" class="w15 float-left">
                                            <img src="images/star-disable.png" alt="star" class="w15 float-left">
                                        </div>
                                    </div>                            
                                </div>
                            </div>
                            <div class="owl-items">
                                <div class="card shadow-lg rounded-0 p-5 bg-white text-left border-0">
                                    <p class="font-xsss fw-500 text-grey-500 lh-32 mt-0 mb-4">Human coronaviruses are common and are typically associated with mild illnesses, similar to the common cold. We are digital agency.</p>
                                    <div class="card-body p-0">
                                        <img src="https://via.placeholder.com/80x80.png" alt="user" class="w60 float-left mr-3">
                                        <h4 class="text-current fw-700 font-xsss mt-1">Thomas Smith</h4>    
                                        <h5 class="text-uppercase font-xsssss fw-500 mb-1 text-grey-500">Ceo Zipto</h5>
                                        <div class="star d-block w-100 text-left">
                                            <img src="images/star.png" alt="star" class="w15 float-left">
                                            <img src="images/star.png" alt="star" class="w15 float-left">
                                            <img src="images/star.png" alt="star" class="w15 float-left">
                                            <img src="images/star.png" alt="star" class="w15 float-left">
                                            <img src="images/star-disable.png" alt="star" class="w15 float-left">
                                        </div>
                                    </div>                            
                                </div>
                            </div>
                        </div>   
                    </div>              
                </div>
                
            </div>
        </div> -->

        <!-- <div class="faq-wrapper pt-4 pb-0">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="filters">
                            <ul>
                            <li class="is-checked" data-filter="*">All</li>          
                            <li data-filter=".bank_cards">Bank Cards</li>
                            <li data-filter=".gift_cards">Gift Cards</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="rows grid data-isotope='{ "itemSelector": ".grid-item", "masonry": { "columnWidth": 200 } }'">
                            <div class="col-md-4 grid-item gift_cards" data-category="gift_cards">
                                <img src="{{URL::asset('/images/bigbasket_card.png')}}" alt="gift card" height="300" width="300">
                            </div>
                            <div class="col-md-4 grid-item bank_cards" data-category="bank_cards">
                                <img src="{{URL::asset('/images/ajio_card.png')}}" alt="gift card" height="300" width="300">
                            </div>
                            <div class="col-md-4 grid-item bank_cards" data-category="bank_cards">
                                <img src="{{URL::asset('/images/flipkart_card.png')}}" alt="gift card" height="300" width="300">
                            </div>
                            <div class="col-md-4 grid-item gift_cards" data-category="gift_cards">
                                <img src="{{URL::asset('/images/amazon_card.png')}}" alt="gift card" height="300" width="300">
                            </div>      
                        </div>
                    </div>
                    <div class="col-md-12 text-center">
                        <button type="button" class="btn btn-link">Show More</button>
                    </div>
                </div>   
            </div>
        </div> -->
       
        <!-- <div class="count-wrapper pt-lg--7 pb-lg--7 pb-5 pt-5">
            <div class="container">
                <div class="row">
                    <div class="col-lg-4 text-center">
                        <h2 class="fw-500 text-grey-900 display2-size">4M</h2>
                        <p class="font-xsss fw-500 text-grey-500 lh-26 mt-2">Daily active users</p>
                    </div>
                    <div class="col-lg-4 text-center">
                        <h2 class="fw-500 text-grey-900 display2-size">12k</h2>
                        <p class="font-xsss fw-500 text-grey-500 lh-26 mt-2">Total active Member</p>
                    </div>
                    <div class="col-lg-4 text-center">
                        <h2 class="fw-500 text-grey-900 display2-size">20M</h2>
                        <p class="font-xsss fw-500 text-grey-500 lh-26 mt-2">Total active Community </p>
                    </div>
                </div>
            </div>
        </div> -->

        <div class="brand-wrapper pt-2 pb-7">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="brand-slider owl-carousel owl-theme overflow-visible dot-none">
                            <div class="owl-items text-center"><img src="{{URL::asset('/images/britannia-logo.png')}}" alt="icon" class="w100 ml-auto mr-auto"></div>
                            <div class="owl-items text-center"><img src="{{URL::asset('/images/GIFTS_GIGGLES.png')}}" alt="icon" class="w100 ml-auto mr-auto"></div>
                            <div class="owl-items text-center"><img src="{{URL::asset('/images/tomtom.png')}}" alt="icon" class="w100 ml-auto mr-auto"></div>
                            <div class="owl-items text-center"><img src="{{URL::asset('/images/cashfin.png')}}" alt="icon" class="w100 ml-auto mr-auto"></div>
                            <div class="owl-items text-center"><img src="{{URL::asset('/images/onstage.png')}}" alt="icon" class="w100 ml-auto mr-auto"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

       
@endsection

@push('scripts')
   
        <script>
                $(document).ready(function(){
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

                    $('#view-plan').on('click',function() {
                        var bool=$(".plan-details").is(":hidden")
                        $(".plan-details").toggleClass('hidden')
                        $(".plan-details").attr('hidden',!bool)
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
                $buttonGroup.on( 'click', 'li', function( event ) {
                $buttonGroup.find('.is-checked').removeClass('is-checked');
                var $button = $( event.currentTarget );
                $button.addClass('is-checked');
                var filterValue = $button.attr('data-filter');
                $grid.isotope({ filter: filterValue });
                });
    //-------------------------------------- end isotope filter for cards----------------------------

    //------------------------------ for slider --------------------------------------------
    (function ($) {
        "use strict";
        $.fn.sliderResponsive = function (settings) {
            var set = $.extend(
            {
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
            $slider.find("> div").each(function () {
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
            sliderIntervalID = setInterval(function () {
                nextSlide();
            }, set.slidePause);
            }

            // on mouseover stop the autoplay
            $slider.mouseover(function () {
            if (set.autoPlay === "on") {
                clearInterval(sliderIntervalID);
            }
            });

            // on mouseout starts the autoplay
            $slider.mouseout(function () {
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
            $slider.find(" > ul > li").click(function () {
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
    $('.amount').click(function(){
        $(".prepared-amount").val($(this).val());
    })

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
                                    <h4 class="font-xsss fw-700 mt-3 text-grey-900">All</h4></a>
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