@extends('layouts.app')
@section('title')
Amazepay | Transaction
@endsection
@section('content')
        <div class="online bg-lightgrey-after pb-7 pt-3 position-relative">
            <div class="container">
                <div class="row">
                    <div class="col-lg-3 pr-md--2">
                        <div class="bg-white shadow-xs rounded-lg h-100">
                            <a href="#" class="dash-menu d-none d-block-md fw-700 p-3 text-current font-xsss ls-3 "> BROWSE CATEGORIES <i class="ti-menu float-right font-xss mt-1"></i></a>
                            <ul class="nav nav-pills mb-3 border-0 nav-fill all-transaction-list tab-ul" id="pills-tab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link active bg-white p-3 border-bottom-light w-100 fw-600 text-grey-500 font-xsss d-inline-block" id="nav-recharge-tab" data-bs-toggle="pill" data-bs-target="#nav-recharge" role="tab" aria-controls="nav-recharge" aria-selected="false"><i class="ti-mobile font-md float-left mr-3"></i> Postpaid Rechagre <i class="ti-angle-right float-right text-grey-400 mt-1"></i></a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link bg-white p-3 border-bottom-light w-100 fw-600 text-grey-500 font-xsss d-inline-block" id="nav-electric-tab" data-bs-toggle="pill" data-bs-target="#nav-electric" role="tab" aria-controls="nav-electric" aria-selected="false"><i class="ti-shine font-md float-left mr-3"></i> Electric Rechagre <i class="ti-angle-right float-right text-grey-400 mt-1"></i></a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link bg-white p-3 border-bottom-light w-100 fw-600 text-grey-500 font-xsss d-inline-block" id="nav-dth-tab" data-bs-toggle="pill" data-bs-target="#nav-dth" role="tab" aria-controls="nav-dth" aria-selected="false"><i class="fa-solid fa-satellite-dish font-md float-left mr-3"></i> DTH Rechagre <i class="ti-angle-right float-right text-grey-400 mt-1"></i></a>
                                </li>

                                <li class="nav-item" role="presentation">
                                    <a class="nav-link bg-white p-3 border-bottom-light w-100 fw-600 text-grey-500 font-xsss d-inline-block" id="nav-gas-tab" data-bs-toggle="pill" data-bs-target="#nav-gas" role="tab" aria-controls="nav-gas" aria-selected="false"><i class="fa-solid fa-fire-flame-simple font-md float-left mr-3"></i> Gas <i class="ti-angle-right float-right text-grey-400 mt-1"></i></a>
                                </li>

                                <li class="nav-item" role="presentation">
                                    <a class="nav-link bg-white p-3 border-bottom-light w-100 fw-600 text-grey-500 font-xsss d-inline-block" id="nav-money-transfer-tab" data-bs-toggle="pill" data-bs-target="#nav-money-transfer" role="tab" aria-controls="nav-money-transfer" aria-selected="false"><i class="ti-harddrives font-md float-left mr-3"></i> Money Transfer <i class="ti-angle-right float-right text-grey-400 mt-1"></i></a>
                                </li>

                                <li class="nav-item" role="presentation">
                                    <a class="nav-link bg-white p-3 border-bottom-light w-100 fw-600 text-grey-500 font-xsss d-inline-block" id="nav-loan-tab" data-bs-toggle="pill" data-bs-target="#nav-loan" role="tab" aria-controls="nav-loan" aria-selected="false"><i class="ti-wallet font-md float-left mr-3"></i> Pay loan <i class="ti-angle-right float-right text-grey-400 mt-1"></i></a>
                                </li>

                                <li class="nav-item" role="presentation">
                                    <a class="nav-link bg-white p-3 border-bottom-light w-100 fw-600 text-grey-500 font-xsss d-inline-block" id="nav-credit-card-tab" data-bs-toggle="pill" data-bs-target="#nav-credit-card" role="tab" aria-controls="nav-credit-card" aria-selected="false"><i class="ti-credit-card font-md float-left mr-3"></i> Credit Card <i class="ti-angle-right float-right text-grey-400 mt-1"></i></a>
                                </li>
                            </ul> 
                        </div>
                    </div>
                    <div class="col-lg-6 pl-md--2 pr-md--2 mt-sm--3 tab-content" id="pills-tabContent">
                            <div class="tab-pane fade show active" id="nav-recharge" role="tabpanel" aria-labelledby="pills-home-tab">
                                <div class="bg-white shadow-xs rounded-lg h-100 p-4 member-1">
                                    <form action="#">
                                        <div class="row">
                                            <div class="col-sm-12 mb-2"><h4 class="fw-700 font-xxl mb-3">Mobile Recharge &amp; Bill Payment</h4></div>
                                            <div class="col-sm-12 mb-4">
                                                <div class="custom-control mr-4 custom-radio custom-control-inline">
                                                    <input type="radio" class="custom-control-input" id="customRadio" name="example" value="customEx" checked="">
                                                    <label class="custom-control-label small-size fw-500 text-grey-900 font-xsss" for="customRadio">Postpaid</label>
                                                </div>
                                                <div class="custom-control mr-0 custom-radio custom-control-inline">
                                                    <input type="radio" class="custom-control-input" id="customRadio2" name="example" value="customEx">
                                                    <label class="custom-control-label small-size fw-500 text-grey-900 font-xsss" for="customRadio2">Prepaid</label>
                                                </div>
                                            </div>
                                            <div class="col-sm-6"><input type="text" class="form-control mb-3" placeholder="Enter 10 digit Mobile Number"></div>
                                            <div class="col-sm-6"><input type="text" class="form-control mb-3" placeholder="Operator"></div>
                                            <div class="col-sm-6"><input type="text" class="form-control mb-3" placeholder="Amount"></div>
                                            <div class="col-sm-6"><a href="#" class="d-block text-center bg-current border-0 w-100 form-bttn fw-500 rounded-lg text-white member-bttn2">Proceed to Recharge</a></div>
                                            <div class="col-sm-12">
                                                <h4 class="mb-3 fw-600 font-xss mt-2">Promo Code</h4>
                                                <div class="card w-100 pr-4 border shadow-none mb-3 pl-5">
                                                    <img src="https://via.placeholder.com/40x40.png" alt="icon" class="w50 mr-3 float-left position-absolute left-15">
                                                    <h5 class="pl-4 ml-2 fw-900 text-grey-900 font-xsss mb-1 d-inline-block mt-2 ls-3">LUCKY200</h5>
                                                    <p class="pl-4 ml-2 fw-500 text-grey-500 mb-0 font-xssss d-block">200 lucky winners will get 100% cashback every day <a href="#" class="fw-900 font-xssss text-current ls-3 float-right">APPLY</a></p>
                                                </div>

                                                <div class="card w-100 pr-4 border shadow-none mb-3 pl-5">
                                                    <img src="https://via.placeholder.com/40x40.png" alt="icon" class="w50 mr-3 float-left position-absolute left-15">
                                                    <h5 class="pl-4 ml-2 fw-900 text-grey-900 font-xsss mb-1 d-inline-block mt-2 ls-3">GET30</h5>
                                                    <p class="pl-4 ml-2 fw-500 text-grey-500 mb-0 font-xssss d-block">200 lucky winners will get 100% cashback every day <a href="#" class="fw-900 font-xssss text-current ls-3 float-right">APPLY</a></p>
                                                </div>

                                                <div class="card w-100 pr-4 border shadow-none mb-0 pl-5">
                                                    <img src="https://via.placeholder.com/40x40.png" alt="icon" class="w50 mr-3 float-left position-absolute left-15">
                                                    <h5 class="pl-4 ml-2 fw-900 text-grey-900 font-xsss mb-1 d-inline-block mt-2 ls-3">FIRST10</h5>
                                                    <p class="pl-4 ml-2 fw-500 text-grey-500 mb-0 font-xssss d-block">200 lucky winners will get 100% cashback every day <a href="#" class="fw-900 font-xssss text-current ls-3 float-right">APPLY</a></p>
                                                </div>

                                                

                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <div class="member-2" style="display: none;">
                                    <div class="card w-100 shadow-xs rounded-lg border-0">
                                        <!-- <div class="card-body w-100 bg-greylight p-3 border-bottom"><a href="#" class="mb-0 text-grey-800 fw-500 font-xsss"><i class="ti-angle-left text-grey-800 dark-text-white font-xssss mr-1"></i> Go Back</a></div> -->
                                        <div class="card-body w-100 p-4 border-0"> 
                                            <h4 class="float-left fw-600 mb-0 font-sm text-grey-900">Recharge or Bill payment Order <br>
                                                <span class="font-xssss text-grey-500 fw-300">Transaction ID: 12679624220</span></h4>
                                            <!-- <h4 class="mb-0 float-right font-xl text-grey-900 mt-3 fw-600"><span class="font-xs">$</span> 129</b></h4>    -->
                                        </div>
                                    </div>
                                    <div class="card mt-3 w-100 shadow-xs rounded-lg border-0">
                                        <div class="card-body w-100 p-4 border-bottom"><h4 class="mb-0 text-grey-500 fw-500 font-xsss">SELECT AN OPTION TO PAY</h4></div>
                                    </div>

                                    <div class="card bg-white border-0 shadow-xs">
                                        <div class="card-body d-flex justify-content-between align-items-end p-4">
                                            <div>
                                                <h4 class="text-grey-700 mb-0 d-flex align-items-center justify-content-between mt-0 fw-600 lato-font font-xsss">
                                                    <img src="https://via.placeholder.com/50x20.png" alt="image" class="float-left mr-3">
                                                    4321 4432 6565 **** 
                                                </h4>
                                            </div>
                                            <div class="round float-right mb-2">
                                                <input id="radio-1" class="radio-custom" name="radio-group" type="radio" checked="">
                                                <label for="radio-1" class="radio-custom-label m-0"></label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card bg-white shadow-xs border-0">
                                        <div class="card-body d-flex justify-content-between align-items-end p-4">
                                            <div>
                                                <h4 class="text-grey-700 mb-0 d-flex align-items-center justify-content-between mt-0 fw-600 lato-font font-xsss">
                                                    <img src="https://via.placeholder.com/50x20.png" alt="image" class="float-left mr-3">
                                                    ***port@gmail.com
                                                </h4>
                                            </div>
                                            <div class="round float-right mb-2">
                                                <input id="radio-2" class="radio-custom" name="radio-group" type="radio">
                                                <label for="radio-2" class="radio-custom-label m-0"></label>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="card bg-white shadow-xs border-0">
                                        <div class="card-body d-flex justify-content-between align-items-end p-4">
                                            <div>
                                                <h4 class="text-grey-700 mb-0 d-flex align-items-center justify-content-between mt-0 fw-600 lato-font font-xsss">
                                                    <img src="https://via.placeholder.com/50x20.png" alt="image" class="float-left mr-3">
                                                    6565 4321 4432  **** 
                                                </h4>
                                            </div>
                                            <div class="round float-right mb-2">
                                                <input id="radio-3" class="radio-custom" name="radio-group" type="radio">
                                                <label for="radio-3" class="radio-custom-label m-0"></label>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="card w-100 bg-greylight shadow-none rounded-lg border-0 mt-3">
                                        <div class="card-body w-100 p-4 border-0"> 
                                            <h4 class="mb-0 float-left font-xxl text-grey-700 mt-0 fw-900"><span class="font-xs">$</span> 129<br><span class="font-xssss text-grey-500 fw-300 d-block">inclusive tax*</span></h4>   
                                            <a href="#" class="mt-0 btn lh-32 member-bttn3 rounded-lg ls-3 bg-current border-0 font-xssss text-white fw-600 ls-md text-uppercase float-right w175">Next</a>
                                        </div>
                                    </div>
                                </div>

                                <div class="member-3" style="display: none;">
                                    <div class="card dark-card bg-white shadow-xs p-4 text-center border-0">
                                        <div class="card-body">
                                            <i class="ti-check btn-round-xxxl text-white bg-success display3-size mt-5 shadow-lg"></i>
                                            <h2 class="fw-900 display1-size mb-2 lh-3 mt-4">Done </h2>
                                            <p class="font-xsss text-grey-500 pl-3 pr-3">We con't seem to find the page you are looking for...</p>
                                            <a href="home-1.html" class="mt-3 mb-5 border-0 w200 bg-current pt-2 pb-2 lh-32 text-white fw-600 rounded-lg d-inline-block btn-light font-xsss ls-3 text-uppercase">Continue</a>
                                        </div>
                                    </div>
                                    <div class="card w-100 bg-greylight shadow-none rounded-lg border-0 mt-3">
                                        <div class="card-body w-100 p-4 border-0"> 
                                            <h4 class="mb-0 float-left font-xssss text-grey-700 mt-1 fw-300 mb-0"><i class="ti-reload text-success mr-2"></i> 100% Secure Payments Powered by Paytm</h4>   
                                            <img src="https://via.placeholder.com/300x30.png" alt="icon" class="float-right w250">
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="tab-pane fade" id="nav-electric" role="tabpanel" aria-labelledby="pills-home-tab">
                            <div class="bg-white shadow-xs rounded-lg h-100 p-4 member-1">
                                    <form action="#">
                                        <div class="row">
                                            <div class="col-sm-12 mb-2"><h4 class="fw-700 font-xxl mb-3">Pay For Electricity</h4></div>
                                           
                                            <div class="col-sm-6">
                                                <select class="select-state">
                                                    <option></option>
                                                    <option value="maharashtra">Maharashtra</option>
                                                    <option value="Gujurat">Gujurat</option>
                                                    <option value="goa">Goa</option>
                                                </select>   
                                            </div>
                                            <div class="col-sm-6">
                                                <select class="select-electric-board">
                                                    <option></option>
                                                    <option value="adani">Adani</option>
                                                    <option value="tata_power">Tata Power</option>
                                                    <option value="best">Best</option>
                                                </select>   
                                            </div>
                                            <div class="col-sm-5"><input type="text" class="form-control mb-2" placeholder="Consumer Number"></div>
                                            
                                            <div class="col-sm-5"><input type="text" class="form-control mb-3" placeholder="Amount"></div>
                                            <div class="col-sm-2"><a href="#" class="d-block text-center bg-current border-0 w-100 form-bttn fw-500 rounded-lg text-white member-bttn2">Proceed to Recharge</a></div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="nav-dth" role="tabpanel" aria-labelledby="pills-home-tab">DTH</div>
                            <div class="tab-pane fade" id="nav-gas" role="tabpanel" aria-labelledby="pills-home-tab">Gas</div>
                            <div class="tab-pane fade" id="nav-money-transfer" role="tabpanel" aria-labelledby="pills-home-tab">Money Transfer</div>
                            <div class="tab-pane fade" id="nav-loan" role="tabpanel" aria-labelledby="pills-home-tab">Pay Loan</div>
                            <div class="tab-pane fade" id="nav-credit-card" role="tabpanel" aria-labelledby="pills-home-tab">Credit Card</div>
                    </div>
                    <div class="col-lg-3 pl-md--2">
                        <a href="#" class="d-none d-lg-block"><img src="{{URL::asset('/images/limited_offer.png')}}" alt="ad-banner" class="rounded-lg img-fluid"></a>
                    </div>
                </div>
            </div>            
        </div>
        

        <div class="popular-wrapper pb-4">
            <div class="container">
                <div class="row">
                    <div class="page-title style1 col-xl-6 offset-xl-3 col-lg-8 offset-lg-2 col-md-10 offset-md-1 text-center mb-5"><h2 class="text-grey-900 fw-300 display2-size pb-3 mb-0 d-block">Popular Coupon</h2> <p class="fw-300 font-xss lh-28 text-grey-500"></p></div>
                    <div class="col-lg-12">
                        
                        <div class="offer-slider owl-carousel owl-theme overflow-visible dot-none">
                            <div class="owl-items text-center"><img src="{{URL::asset('/images/amz_card.png')}}" alt="icon" class="img-fluid rounded-lg"></div>
                            <div class="owl-items text-center"><img src="{{URL::asset('/images/bank_card_new.png')}}" alt="icon" class="img-fluid rounded-lg"></div>
                            <div class="owl-items text-center"><img src="{{URL::asset('/images/ajo_card.png')}}" alt="icon" class="img-fluid rounded-lg"></div>
                            <div class="owl-items text-center"><img src="{{URL::asset('/images/flip_card.png')}}" alt="icon" class="img-fluid rounded-lg"></div>
                            <div class="owl-items text-center"><img src="{{URL::asset('/images/bb_card.png')}}" alt="icon" class="img-fluid rounded-lg"></div>
                            <div class="owl-items text-center"><img src="{{URL::asset('/images/gift_card_new.png')}}" alt="icon" class="img-fluid rounded-lg"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="how-to-work pt-7 pb-7">
            <div class="container">
                <div class="row">
                     <div class="col-lg-5 mb-4"><img src="{{URL::asset('/images/online_pay.png')}}" alt="image" class="rounded-lg img-fluid shadow-xs"></div>
                     <div class="col-lg-6 offset-lg-1 page-title style1">
                         <h2 class="fw-300 text-grey-900 display2-size lh-3">Online recharge and pay monthly bill easy way.</h2>
                         <p class="fw-300 font-xss lh-28 text-grey-500 mt-3 w-75 w-xs-90">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dol ad minim veniam, quis nostrud exercitation</p>

                         <h4 class="fw-600 font-sm mt-5 mb-2"><i class="ti-check btn-round-xs text-success border-success mr-2 border"></i> Choose what to do</h4>
                         <p class="fw-300 font-xsss lh-28 text-grey-500 mt-0 ml-4 pl-3 w-75 w-xs-90">Looking for a cozy hotel to stay, a restaurant to eat, a museum to visit or a mall to do some.</p>

                         <h4 class="fw-600 font-sm mt-4 mb-2"><i class="ti-check btn-round-xs text-success border-success mr-2 border"></i> Find what you want</h4>
                         <p class="fw-300 font-xsss lh-28 text-grey-500 mt-0 ml-4 pl-3 w-75 w-xs-90">Search and filter hundreds of listings, read reviews, explore photos and find the perfect spot.</p>

                         <h4 class="fw-600 font-sm mt-4 mb-2"><i class="ti-check btn-round-xs text-success border-success mr-2 border"></i> Explore amazing code</h4>
                         <p class="fw-300 font-xsss lh-28 text-grey-500 mt-0 ml-4 pl-3 w-75 w-xs-90">Go and have a good time or even make a booking directly from the listing page.</p>
                     </div>
                </div>
            </div>
        </div>

        <div class="feedback-wrapper bg-greyblue pt-7 pb-7">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 text-center mb-5 pb-3 page-title style1">
                        <h2 class="text-grey-900 fw-300 display2-size">Our Customers love what we do</h2>
                    </div>
                    <div class="col-lg-12">
                        <div class="feedback-slider-2 owl-carousel owl-theme overflow-visible">
                            <div class="owl-items">
                                <div class="card shadow-lg rounded-lg p-5 bg-white text-left border-0">
                                    <img src="images/icon-12.png" alt="icon" class="position-absolute right-0 mr-5 top-0 mt-4 w30 opacity-2">
                                    <h4 class="text-current font-xs fw-700 mb-3">Good Quality</h4>
                                    <p class="font-xsss fw-500 text-grey-500 lh-32 mt-0 mb-4">Human coronaviruses are common and are typically associated with mild illnesses, similar to the common cold. We are digital agency.</p>
                                    <div class="card-body p-0">
                                        <img src="https://via.placeholder.com/80x80.png" alt="user" class="w60 float-left mr-3">
                                        <h4 class="text-grey-900 fw-700 font-xsss mt-2 pt-1">Thomas Smith</h4>    
                                        <h5 class="text-uppercase font-xsssss fw-700 mb-1 ls-3 text-grey-500">Ceo Zipto</h5>
                                    </div>                            
                                </div>
                            </div>

                            <div class="owl-items">
                                <div class="card shadow-lg rounded-lg p-5 bg-white text-left border-0">
                                    <img src="images/icon-12.png" alt="icon" class="position-absolute right-0 mr-5 top-0 mt-4 w30 opacity-2">
                                    <h4 class="text-current font-xs fw-700 mb-3">Clean Code</h4>
                                    <p class="font-xsss fw-500 text-grey-500 lh-32 mt-0 mb-4">Human coronaviruses are common and are typically associated with mild illnesses, similar to the common cold. We are digital agency.</p>
                                    <div class="card-body p-0">
                                        <img src="https://via.placeholder.com/80x80.png" alt="user" class="w60 float-left mr-3">
                                        <h4 class="text-grey-900 fw-700 font-xsss mt-2 pt-1">Thomas Smith</h4>    
                                        <h5 class="text-uppercase font-xsssss fw-700 mb-1 ls-3 text-grey-500">Ceo Zipto</h5>
                                    </div>                            
                                </div>
                            </div>

                            <div class="owl-items">
                                <div class="card shadow-lg rounded-lg p-5 bg-white text-left border-0">
                                    <img src="images/icon-12.png" alt="icon" class="position-absolute right-0 mr-5 top-0 mt-4 w30 opacity-2">
                                    <h4 class="text-current font-xs fw-700 mb-3">Awesome Design</h4>
                                    <p class="font-xsss fw-500 text-grey-500 lh-32 mt-0 mb-4">Human coronaviruses are common and are typically associated with mild illnesses, similar to the common cold. We are digital agency.</p>
                                    <div class="card-body p-0">
                                        <img src="https://via.placeholder.com/80x80.png" alt="user" class="w60 float-left mr-3">
                                        <h4 class="text-grey-900 fw-700 font-xsss mt-2 pt-1">Thomas Smith</h4>    
                                        <h5 class="text-uppercase font-xsssss fw-700 mb-1 ls-3 text-grey-500">Ceo Zipto</h5>
                                    </div>                            
                                </div>
                            </div>
                            <div class="owl-items">
                                <div class="card shadow-lg rounded-lg p-5 bg-white text-left border-0">
                                    <img src="images/icon-12.png" alt="icon" class="position-absolute right-0 mr-5 top-0 mt-4 w30 opacity-2">
                                    <h4 class="text-current font-xs fw-700 mb-3">Great Support</h4>
                                    <p class="font-xsss fw-500 text-grey-500 lh-32 mt-0 mb-4">Human coronaviruses are common and are typically associated with mild illnesses, similar to the common cold. We are digital agency.</p>
                                    <div class="card-body p-0">
                                        <img src="https://via.placeholder.com/80x80.png" alt="user" class="w60 float-left mr-3">
                                        <h4 class="text-grey-900 fw-700 font-xsss mt-2 pt-1">Thomas Smith</h4>    
                                        <h5 class="text-uppercase font-xsssss fw-700 mb-1 ls-3 text-grey-500">Ceo Zipto</h5>
                                    </div>                            
                                </div>
                            </div>
                        </div>   
                    </div>              
                </div>
            </div>
        </div>
        <div class="feedback-wrapper pt-7 pb-7">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6"><img src="{{URL::asset('/images/coming_soon.png')}}" alt="app-image" class="img-fluid"></div>
                    <div class="col-lg-4 offset-lg-1 pt-7 page-title style1">
                        <h4 class="text-uppercase text-current font-xsss fw-600 mb-3 mt-lg-5">Download & Enjoy</h4>
                        <h2 class="text-grey-900 fw-300 display2-size lh-3">Get the Amazepay app <br> for payment</h2>
                        <p class="w-75 font-xssss fw-500 text-grey-500 lh-26 mt-2">We are digital agency, a small design agency based in paris as i was groping to remove through language.</p>
                        <a href="#"><img src="{{URL::asset('/images/playstore.png')}}" alt="icon" class="w175 mb-xs-2"></a>
                        <a href="#"><img src="{{URL::asset('/images/appstore.png')}}" alt="icon" class="w175 mb-xs-2"></a>
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
                });
            </script>

@endpush