@extends('app')
@section('title')
    Gift & Giggles
@endsection
@section('css')
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.6.3/css/bootstrap-select.min.css" />
@endsection
@section('content')

        <!-- banner wrapper -->
        <div class="banner-wrapper style1 bg-image-contain" style="background-image: url(https://via.placeholder.com/1900x500.png);">
             
        </div>
        <!-- banner wrapper --> 

        <div class="offer-wrapper pb-4 bg-lightblue">


            <div class="tab-wrapper">
                <div class="container">
                    <div class="row-eq-height">
                        <div class="col-lg-12 tab-container"> 
                            <nav class="d-lg-block d-none">
                                <div class="nav nav-tabs border-0 nav-fill" id="nav-tab" role="tablist">
                                    <a class="nav-item nav-link rounded-lg border-0 p-4 mr-2 active bg-current" id="nav-home-tab" data-toggle="tab" href="#nav-home" role="tab" aria-controls="nav-home" aria-selected="true"><i class="font-xxl ti-mobile text-current d-block mt-2"></i><h4 class="font-xssss fw-600 text-grey-900 mt-3">Mobile Recharge</h4></a>
                                    <a class="nav-item nav-link rounded-lg border-0 p-4 mr-2" id="nav-profile-tab" data-toggle="tab" href="#nav-profile" role="tab" aria-controls="nav-profile" aria-selected="false"><i class="font-xxl ti-shine text-current d-block mt-2"></i><h4 class="font-xssss fw-600 text-grey-900 mt-3">Electicity</h4></a>
                                    <a class="nav-item nav-link rounded-lg border-0 p-4 mr-2" id="nav-contact-tab" data-toggle="tab" href="#nav-contact" role="tab" aria-controls="nav-contact" aria-selected="false"><i class="font-xxl ti-stats-up text-current d-block mt-2"></i><h4 class="font-xssss fw-600 text-grey-900 mt-3">DTH</h4></a>
                                    <a class="nav-item nav-link rounded-lg border-0 p-4 mr-2" id="nav-about-tab" data-toggle="tab" href="#nav-about" role="tab" aria-controls="nav-about" aria-selected="false"><i class="font-xxl ti-car text-current d-block mt-2"></i><h4 class="font-xssss fw-600 text-grey-900 mt-3">GAS</h4></a>
                                    <a class="nav-item nav-link rounded-lg border-0 p-4 mr-2" id="nav-shoping-tab" data-toggle="tab" href="#nav-profile" role="tab" aria-controls="nav-profile" aria-selected="false"><i class="font-xxl ti-package text-current d-block mt-2"></i><h4 class="font-xssss fw-600 text-grey-900 mt-3">Money Transfer</h4></a>
                                    <a href="#" class="nav-item nav-link rounded-lg border-0 p-4 mr-0" data-toggle="modal" data-target="#Modalmore"><i class="font-xxl ti-announcement text-current d-block mt-2"></i><h4 class="font-xssss fw-600 text-grey-900 mt-3">More</h4></a>
                                    <!-- <a class="nav-item nav-link rounded-lg border-0 p-4 mr-0" id="nav-tab-tab" data-toggle="modal" href="#nav-contact" role="tab" data-target="#ModalregisterD" aria-controls="nav-contact" aria-selected="false"><i class="font-xxl ti-announcement text-current d-block mt-2"></i><h4 class="font-xssss fw-600 text-grey-900 mt-3">More</h4></a> -->
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
                                            <div class="col-sm-3"><input type="text" class="form-control mb-2" placeholder="Enter Mobile Number"></div>
                                            <div class="col-sm-2">
                                                <select class="selectpicker" data-live-search="true">
                                                    <option data-tokens="ketchup mustard">Airtel</option>
                                                    <option data-tokens="mustard">Jio</option>
                                                    <option data-tokens="frosting">VI</option>
                                                </select>
                                            </div>
                                            <div class="col-sm-3"><input type="text" class="form-control mb-2" placeholder="Select Location"></div>
                                            <div class="col-sm-3"><input type="text" class="form-control mb-2" placeholder="Amount">
                                            <span id="view-plan" >view plan</span></div>
                                            <div class="col-sm-1"><button class="bg-current border-0 w-100 form-bttn fw-500 rounded-lg text-white"><i class="ti-arrow-right"></i></button></div>
                                        </div>
                                    </form>
                                    <br/>
                                    <table hidden class="table table-condensed table-striped plan-details">
                                    <!-- <table hidden class="table table-responsive-sm table-hover plan-details"> -->
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
                                            <td><button class="bg-current border-0 w-100 form-bttn fw-500 rounded-lg text-white">RS. 99</Button></td>
                                            </tr>
                                            <tr>
                                            <td scope="row">Maharashtra</td>
                                            <td>Recharge</td>
                                            <td>28 Days</td>
                                            <td>Enjoy talktime of Rs 99 valid for 28 days at 1p/sec local & STD calls with 200 MB data.</td>
                                            <td><button class="bg-current border-0 w-100 form-bttn fw-500 rounded-lg text-white">RS. 99</Button></td>
                                            </tr>
                                            <tr>
                                            <td scope="row">Maharashtra</td>
                                            <td>Recharge</td>
                                            <td>28 Days</td>
                                            <td>Enjoy talktime of Rs 99 valid for 28 days at 1p/sec local & STD calls with 200 MB data.</td>
                                            <td><button class="bg-current border-0 w-100 form-bttn fw-500 rounded-lg text-white">RS. 99</Button></td>
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
                                            <div class="col-sm-4"><input type="text" class="form-control mb-2" placeholder="Select Location"></div>
                                            <div class="col-sm-3"><input type="text" class="form-control mb-2" placeholder="Electricity Board"></div>
                                            <div class="col-sm-4"><input type="text" class="form-control mb-2" placeholder="Consumer Number"></div>
                                            <div class="col-sm-1"><button class="bg-current border-0 w-100 form-bttn fw-500 rounded-lg text-white"><i class="ti-arrow-right"></i></button></div>
                                        </div>
                                    </form>
                                </div>
                                 
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>  

             
            <!-- <div class="container">
                <div class="row">
                    <div class="brand-slider owl-carousel owl-theme overflow-visible dot-none pt-3">
                        <div class="owl-items text-center"><img src="https://via.placeholder.com/100x50.png" alt="icon" class="w100 ml-auto mr-auto"></div>
                        <div class="owl-items text-center"><img src="https://via.placeholder.com/100x50.png" alt="icon" class="w100 ml-auto mr-auto"></div>
                        <div class="owl-items text-center"><img src="https://via.placeholder.com/100x50.png" alt="icon" class="w100 ml-auto mr-auto"></div>
                        <div class="owl-items text-center"><img src="https://via.placeholder.com/100x50.png" alt="icon" class="w100 ml-auto mr-auto"></div>
                        <div class="owl-items text-center"><img src="https://via.placeholder.com/100x50.png" alt="icon" class="w100 ml-auto mr-auto"></div>
                        <div class="owl-items text-center"><img src="https://via.placeholder.com/100x50.png" alt="icon" class="w100 ml-auto mr-auto"></div>
                    </div>
                </div>
            </div> -->
        </div>

        <div class="popular-wrapper pt-lg--7 pb-lg--7 pb-5 pt-5 bg-lightblue">
            <div class="container">
                <div class="row">
                    <div class="col-lg-4 text-left mb-5 pb-0">
                        <h2 class="text-grey-900 fw-400 display1-size lh-2">Explore <br> by category</h2>
                    </div>
                
                    <div class="col-lg-12">
                        <div class="categorie-slider owl-carousel owl-theme overflow-visible dot-none right-nav pb-4">
                            <div class="owl-items text-center">
                                <div class="card w-100 p-4 text-left border-0 shadow-md rounded-lg">
                                    <i class="ti-world mt-4 font-xl text-current"></i>
                                    <h4 class="font-xsss fw-700 mt-3 text-grey-900">Vouchers</h4>
                                    <h6 class="fw-500 font-xssss text-grey-500">Total active Member</h6>
                                </div>
                            </div>
                            <div class="owl-items text-center">
                                <div class="card w-100 p-4 text-left border-0 shadow-md rounded-lg">
                                    <i class="ti-briefcase mt-4 font-xl text-current"></i>
                                    <h4 class="font-xsss fw-700 mt-3 text-grey-900">Finance</h4>
                                    <h6 class="fw-500 font-xssss text-grey-500">Total active Member</h6>
                                </div>
                            </div>
                            <div class="owl-items text-center">
                                <div class="card w-100 p-4 text-left border-0 shadow-md rounded-lg">
                                    <i class="ti-location-pin mt-4 font-xl text-current"></i>
                                    <h4 class="font-xsss fw-700 mt-3 text-grey-900">Flights</h4>
                                    <h6 class="fw-500 font-xssss text-grey-500">Total active Member</h6>
                                </div>
                            </div>
                            <div class="owl-items text-center">
                                <div class="card w-100 p-4 text-left border-0 shadow-md rounded-lg">
                                    <i class="ti-video-clapper mt-4 font-xl text-current"></i>
                                    <h4 class="font-xsss fw-700 mt-3 text-grey-900">Movies</h4>
                                    <h6 class="fw-500 font-xssss text-grey-500">Total active Member</h6>
                                </div>
                            </div>
                            <div class="owl-items text-center">
                                <div class="card w-100 p-4 text-left border-0 shadow-md rounded-lg">
                                    <i class="ti-credit-card mt-4 font-xl text-current"></i>
                                    <h4 class="font-xsss fw-700 mt-3 text-grey-900">Coupon</h4>
                                    <h6 class="fw-500 font-xssss text-grey-500">Total active Member</h6>
                                </div>
                            </div>
                            <div class="owl-items text-center">
                                <div class="card w-100 p-4 text-left border-0 shadow-md rounded-lg">
                                    <i class="ti-wallet mt-4 font-xl text-current"></i>
                                    <h4 class="font-xsss fw-700 mt-3 text-grey-900">Shopping</h4>
                                    <h6 class="fw-500 font-xssss text-grey-500">Total active Member</h6>
                                </div>
                            </div>

                            <div class="owl-items text-center">
                                <div class="card w-100 p-4 text-left border-0 shadow-md rounded-lg">
                                    <i class="ti-stats-up mt-4 font-xl text-current"></i>
                                    <h4 class="font-xsss fw-700 mt-3 text-grey-900">Share</h4>
                                    <h6 class="fw-500 font-xssss text-grey-500">Total active Member</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="how-to-work pt-lg--7 pb-lg--7 pb-5 pt-5 bg-greylight">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 text-center mb-lg-5 mb-4 pb-3">
                        <h2 class="text-grey-900 fw-400 display1-size">How it work</h2>
                    </div>
                    <div class="col-lg-4 mb-3">
                        <div class="card shadow-lg rounded-0 p-5 bg-white text-center border-0">
                            <i class="ti-home ml-auto mr-auto round-lg-btn text-white bg-current font-xxl text-center"></i>
                            <h2 class="fw-700 font-sm mt-4">Choose what to do</h2>
                            <p class="font-xsss fw-500 text-grey-500 lh-26 mt-2">We are digital agency, a small design agency based in paris as i was groping to remove through language.</p>
                        </div>
                    </div>

                    <div class="col-lg-4 mb-3">
                        <div class="card shadow-lg rounded-0 p-5 bg-white text-center border-0">
                            <i class="ti-harddrives ml-auto mr-auto round-lg-btn text-white bg-current font-xxl text-center"></i>
                            <h2 class="fw-700 font-sm mt-4">Find what you want</h2>
                            <p class="font-xsss fw-500 text-grey-500 lh-26 mt-2">We are digital agency, a small design agency based in paris as i was groping to remove through language.</p>
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
        </div>

        <div class="feedback-wrapper pt-lg--7 pb-lg--7 pb-5 pt-5">
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
                <div class="row mt-5 pt-5">
                    <div class="col-lg-6"><img src="https://via.placeholder.com/570x600.png" alt="app-image" class="img-fluid"></div>
                    <div class="col-lg-4 offset-lg-1 pt-5 mt-5">
                        <h4 class="text-uppercase text-current font-xsss fw-600 mb-3 mt-5">Download & Enjoy</h4>
                        <h2 class="text-grey-900 fw-700 display1-size lh-3">Get the zpito app <br> for payment</h2>
                        <p class="w-75 font-xsss fw-500 text-grey-500 lh-26 mt-2">We are digital agency, a small design agency based in paris as i was groping to remove through language.</p>
                        <a href="#"><img src="https://via.placeholder.com/210x60.png" alt="icon" class="w175 mb-xs-2"></a>
                        <a href="#"><img src="https://via.placeholder.com/210x60.png" class="w175 p-md--2"></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="faq-wrapper pt-4 pb-0">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 text-center mb-lg-5 mb-4 pb-3">
                        <h2 class="text-grey-900 fw-400 display1-size">Frequently Asked Question</h2>
                    </div>
                </div>
                <div class="row justify-content-center">
                    <div class="col-xl-8">
                        <div id="accordion" class="accordion">
                          <div class="card border-0 mb-4">
                            <div class="card-header" id="headingOne">
                              <h5 class="mb-0">
                                <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                  I have read and agree to the Privacy Policy and Terms &amp; Conditions*
                                </button>
                              </h5>
                            </div>

                            <div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                              <div class="card-body">
                                <p>Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.</p>
                              </div>
                            </div>
                          </div>
                          <div class="card border-0 mb-4">
                            <div class="card-header" id="headingTwo">
                              <h5 class="mb-0">
                                <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                  You can easily build a page without any design or custom coding.
                                </button>
                              </h5>
                            </div>
                            <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordion">
                              <div class="card-body">
                                <p>Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.</p>
                              </div>
                            </div>
                          </div>
                          <div class="card border-0 mb-4">
                            <div class="card-header" id="headingFour">
                              <h5 class="mb-0">
                                <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                  I have read and agree to the Privacy Policy and Terms &amp; Conditions*
                                </button>
                              </h5>
                            </div>

                            <div id="collapseFour" class="collapse" aria-labelledby="headingFour" data-parent="#accordion">
                              <div class="card-body">
                                <p>Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.</p>
                              </div>
                            </div>
                          </div>
                          <div class="card border-0 mb-4">
                            <div class="card-header" id="headingFive">
                              <h5 class="mb-0">
                                <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                  I have read and agree to the Privacy Policy and Terms &amp; Conditions*
                                </button>
                              </h5>
                            </div>

                            <div id="collapseFive" class="collapse" aria-labelledby="headingFive" data-parent="#accordion">
                              <div class="card-body">
                                <p>Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.</p>
                              </div>
                            </div>
                          </div>
                          <div class="card border-0 mb-4">
                            <div class="card-header" id="headingSix">
                              <h5 class="mb-0">
                                <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                                  I have read and agree to the Privacy Policy and Terms &amp; Conditions*
                                </button>
                              </h5>
                            </div>

                            <div id="collapseSix" class="collapse" aria-labelledby="headingSix" data-parent="#accordion">
                              <div class="card-body">
                                <p>Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.</p>
                              </div>
                            </div>
                          </div>
                          <div class="card border-0 mb-4">
                            <div class="card-header" id="headingThree">
                              <h5 class="mb-0">
                                <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                  Stream that converts more visitors than any website.
                                </button>
                              </h5>
                            </div>
                            <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordion">
                              <div class="card-body">
                                <p>Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven't heard of them accusamus labore sustainable VHS.</p>
                              </div>
                            </div>
                          </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="count-wrapper pt-lg--7 pb-lg--7 pb-5 pt-5">
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
        </div>

        <div class="brand-wrapper pt-2 pb-7">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="brand-slider owl-carousel owl-theme overflow-visible dot-none">
                            <div class="owl-items text-center"><img src="https://via.placeholder.com/100x50.png" alt="icon" class="w100 ml-auto mr-auto"></div>
                            <div class="owl-items text-center"><img src="https://via.placeholder.com/100x50.png" alt="icon" class="w100 ml-auto mr-auto"></div>
                            <div class="owl-items text-center"><img src="https://via.placeholder.com/100x50.png" alt="icon" class="w100 ml-auto mr-auto"></div>
                            <div class="owl-items text-center"><img src="https://via.placeholder.com/100x50.png" alt="icon" class="w100 ml-auto mr-auto"></div>
                            <div class="owl-items text-center"><img src="https://via.placeholder.com/100x50.png" alt="icon" class="w100 ml-auto mr-auto"></div>
                            <div class="owl-items text-center"><img src="https://via.placeholder.com/100x50.png" alt="icon" class="w100 ml-auto mr-auto"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        

@endsection

@push('scripts')
   
        <script>
                $(document).ready(function(){
                    $('.selectpicker').selectpicker();
                    $('#view-plan').on('click',function() {
                        var bool=$(".plan-details").is(":hidden")
                        $(".plan-details").toggleClass('hidden')
                        $(".plan-details").attr('hidden',!bool)
                    });
                });
        </script> 
@endpush