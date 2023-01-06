@extends('app')
@section('title')
    Gift & Giggles
@endsection
@section('content')

    <div class="gift-card-detail-page pt-lg--7 pb-lg--7 pb-5 pt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 mb-lg-4 mb-4 pb-3">
                    <h6 class="text-grey-900 fw-400 font-xl">E-Gift Card</h6>
                    <hr>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card-container">
                            <img class="coupan-img" src="{{URL::asset('/images/hamburger.jpg')}}" alt="Avatar" style="width:100%;">     
                        </div>
                        <div class="container">
                            <h6 class="text-grey-900 fw-400 font-xs mt-2">Offers</h6>
                            <ul class="square-type-unordered">
                                <li>Only UPI payment is accepted for this gift card. --- On Amazon Pay Special E-Gift Card (woohoo.in/amazon-pay-special-e-gift-card) Credit/Debit card and Net Banking options are available.</li>
                            </ul>
                        </div>
                            
                    </div>
                    <div class="col-lg-6">
                        <div class="container">
                            <div class="row">
                                <div class="row copuan-quantity">
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control mb-3" placeholder="Enter Denomination">
                                    </div>
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control mb-3" placeholder="Quantity">
                                    </div>
                                </div>
                                <div class="col-sm-12 mb-4">
                                    <h6 class="mb-3 fw-600 font-xss mt-2">Gift Send Option</h6>
                                    <div class="custom-control mr-4 custom-radio custom-control-inline">
                                        <input type="radio" class="custom-control-input" id="customRadio" name="gift_send_option" value="send_as_gift" checked>
                                        <label class="custom-control-label small-size fw-500 text-grey-900 font-xsss" for="customRadio">Send as Gift</label>
                                    </div>
                                    <div class="custom-control mr-0 custom-radio custom-control-inline">
                                        <input type="radio" class="custom-control-input" id="customRadio1" name="gift_send_option" value="buy_for_self">
                                        <label class="custom-control-label small-size fw-500 text-grey-900 font-xsss" for="customRadio1">Buy for Self (This E-gift card will be added to your account)</label>
                                    </div>
                                </div>
                                <div class="row card-form gifting-details">
                                <h6 class="mb-3 fw-600 font-xss mt-2">Gifting Details</h6>
                                <div class="col-sm-6 receiver-name">
                                    <input type="text" class="form-control mb-3" placeholder="Receiver Name">
                                </div>
                                <div class="col-sm-6 receiver-email">
                                    <input type="text" class="form-control mb-3" placeholder="Receiver Email">
                                </div>
                                <div class="col-sm-6 receiver-mobile d-none">
                                    <input type="text" class="form-control mb-3" placeholder="Receiver Mobile Number">
                                </div>
                                <div class="col-sm-6 receiver-message">
                                    <input type="text" class="form-control mb-3" placeholder="Message for Receiver">
                                </div>
                                <div class="col-sm-12 mb-4">
                                        <h6 class="mb-3 fw-600 font-xss mt-2">Delivery Mode</h6>
                                        <div class="custom-control mr-4 custom-radio custom-control-inline">
                                            <input type="radio" class="custom-control-input" id="customRadio3" name="delivery_mode" value="email" checked>
                                            <label class="custom-control-label small-size fw-500 text-grey-900 font-xsss" for="customRadio3">Email</label>
                                        </div>
                                        <div class="custom-control mr-4 custom-radio custom-control-inline">
                                            <input type="radio" class="custom-control-input" id="customRadio4" name="delivery_mode" value="mobile">
                                            <label class="custom-control-label small-size fw-500 text-grey-900 font-xsss" for="customRadio4">Mobile</label>
                                        </div>
                                        <div class="custom-control mr-4 custom-radio custom-control-inline">
                                            <input type="radio" class="custom-control-input" id="customRadio5" name="delivery_mode" value="both">
                                            <label class="custom-control-label small-size fw-500 text-grey-900 font-xsss" for="customRadio5">Both</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row card-form add-gift-cards d-none">
                                    <h6 class="mb-3 fw-600 font-xss mt-2">Add Gift Cards to your Account</h6>
                                    <div class="row">
                                        <div class="col-lg-4 col-md-4 text-center">
                                            <h2 class="fw-500 text-grey-900 display2-size">4M</h2>
                                            <p class="font-xssss fw-500 text-grey-400 lh-26 mt-2">If you want to print or forward this Email gift card with an attractive template, please select the 'Send as a Gift' option.</p>
                                        </div>
                                        <div class="col-lg-4 col-md-4 text-center">
                                            <h2 class="fw-500 text-grey-900 display2-size">12k</h2>
                                            <p class="font-xssss fw-500 text-grey-400 lh-26 mt-2">The e-gift card that you order from this page, will be added to your Woohoo account automatically.</p>
                                        </div>
                                        <div class="col-lg-4 col-md-4 text-center">
                                            <h2 class="fw-500 text-grey-900 display2-size">20M</h2>
                                            <p class="font-xssss fw-500 text-grey-400 lh-26 mt-2">For better security of your e-gift card, the details of your gift card will not be sent separately.</p>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>   
                        </div>
                    </div>
                </div>
                <div class="row personalise-gift-card">
                    <span class="font-xsssss fw-400">Preview</span>
                    <div class="col-lg-6 preview">
                        
                        <img class="mt-2" src="{{URL::asset('/images/hamburger.jpg')}}" alt="Avatar" style="width:100%;">
                        <div class="">
                            <div class="container"> 
                                <h5 class="text-uppercase font-xssss fw-500 mb-4 my-4">Hi Receiver,</h5>
                                <h5 class="text-uppercase font-xssss fw-500 mb-4">You've got a Amazon Pay E-Gift Card</h5>
                                <h4 class="text-current fw-700 font-sm  mt-1 mb-3">Your message will appear here</h4>
                            </div>
                            <div class="container">
                                <div class="row cart-item-record">
                                    <div class="col-md-6 col-sm-4 col-xs-12">
                                        <img class="my-4" src="{{URL::asset('/images/hamburger.jpg')}}" alt="Avatar" style="width:100%;">
                                        <span class="font-xssss fw-400">* Validity: xx xx xxxx</span>
                                    </div>
                                        <div class="col-md-6 col-sm-4 col-xs-9"><p class="font-xl fw-800">₹50.00</p>
                                        <div class="row">
                                            <div class="col-md-12 col-sm-4 col-xs-6"><span class="font-xssss fw-400">Card Number</span><p>xxxxxxxxxxxxxxxx</p></div>
                                            <div class="col-md-12 col-sm-4 col-xs-6"><span class="font-xssss fw-400">Pin</span><p> xxx</p></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                         
                    </div>
                    <div class="col-lg-6">
                        <div class="row">
                            <div class="col-lg-6 text-left mb-4 pb-0">
                                <span class="font-xs fw-400">Choose Theme - New Year</span>
                            </div>
                        
                            <div class="col-lg-12">
                                <div class="theme-slider owl-carousel owl-theme dot-none right-nav pb-4">
                                    <div class="owl-items text-center">
                                        <div class="card w-100 text-left border-0 shadow-md rounded-lg">
                                           <img class="" src="{{URL::asset('/images/hamburger.jpg')}}" alt="Avatar" style="width:100%;"> 
                                        </div>
                                    </div>
                                    <div class="owl-items text-center">
                                        <div class="card w-100 text-left border-0 shadow-md rounded-lg">
                                            <img class="" src="{{URL::asset('/images/hamburger.jpg')}}" alt="Avatar" style="width:100%;"> 
                                        </div>
                                    </div>
                                    <div class="owl-items text-center">
                                        <div class="card w-100 text-left border-0 shadow-md rounded-lg">
                                            <img class="" src="{{URL::asset('/images/hamburger.jpg')}}" alt="Avatar" style="width:100%;"> 
                                        </div>
                                    </div>
                                    <div class="owl-items text-center">
                                        <div class="card w-100 text-left border-0 shadow-md rounded-lg">
                                            <img class="" src="{{URL::asset('/images/hamburger.jpg')}}" alt="Avatar" style="width:100%;"> 
                                        </div>
                                    </div>
                                    <div class="owl-items text-center">
                                        <div class="card w-100 text-left border-0 shadow-md rounded-lg">
                                            <img class="" src="{{URL::asset('/images/hamburger.jpg')}}" alt="Avatar" style="width:100%;">
                                        </div>
                                    </div> 
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="theme-slider owl-carousel owl-theme dot-none right-nav mt-5">
                                    <div class="owl-items text-center">
                                        <div class="card w-100 text-left border-0 shadow-md rounded-lg">
                                           <img class="" src="{{URL::asset('/images/hamburger.jpg')}}" alt="Avatar" style="width:100%;"> 
                                        </div>
                                    </div>
                                    <div class="owl-items text-center">
                                        <div class="card w-100 text-left border-0 shadow-md rounded-lg">
                                            <img class="" src="{{URL::asset('/images/hamburger.jpg')}}" alt="Avatar" style="width:100%;"> 
                                        </div>
                                    </div>
                                    <div class="owl-items text-center">
                                        <div class="card w-100 text-left border-0 shadow-md rounded-lg">
                                            <img class="" src="{{URL::asset('/images/hamburger.jpg')}}" alt="Avatar" style="width:100%;"> 
                                        </div>
                                    </div>
                                    <div class="owl-items text-center">
                                        <div class="card w-100 text-left border-0 shadow-md rounded-lg">
                                            <img class="" src="{{URL::asset('/images/hamburger.jpg')}}" alt="Avatar" style="width:100%;"> 
                                        </div>
                                    </div>
                                    <div class="owl-items text-center">
                                        <div class="card w-100 text-left border-0 shadow-md rounded-lg">
                                            <img class="" src="{{URL::asset('/images/hamburger.jpg')}}" alt="Avatar" style="width:100%;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('checkout') }}" class="form-control rounded-lg h60 float-right bg-current text-white text-center font-xss fw-500 border-2 border-0 p-0 mt-4 w100">Pay Now</a>
                    </div>
                </div> 
            </div>
            <div class="row mt-4">
                <div class="tabs">
                    <input type="radio" name="tabs" id="tabone" checked="checked">
                    <label for="tabone">Offers</label>
                    <div class="tab">  
                        <ul class="square-type-unordered">
                            <li>Flipkart Gift Cards ("GCs" or "Gift Cards") are issued by Pine Labs Pvt. Ltd ("Pine Labs") which is a private limited company incorporated under the laws of India, and is authorized by the Reserve Bank of India ("RBI") to issue such Gift Cards.</li>
                            <li>The Gift Cards can be redeemed online against Sellers listed on www.flipkart.com or Flipkart Mobile App or Flipkart m-site ("Platform") only.</li>
                            <li>Gift Cards can be purchased on www.flipkart.com or Flipkart Mobile App using the following payment modes only - Credit Card, Debit Card and Net Banking.</li>
                            <li>Gift Cards can be redeemed by selecting the payment mode as Gift Card. The Gift Card payment option is available for single orders with multiple sellers.</li>
                            <li>Gift Cards cannot be used to purchase other Flipkart Gift Cards or Flipkart First subscriptions.</li>
                        </ul>
                    </div>
                
                    <input type="radio" name="tabs" id="tabtwo">
                    <label for="tabtwo">Description</label>
                    <div class="tab">
                        <ul class="square-type-unordered">
                            <li>Flipkart Gift Cards ("GCs" or "Gift Cards") are issued by Pine Labs Pvt. Ltd ("Pine Labs") which is a private limited company incorporated under the laws of India, and is authorized by the Reserve Bank of India ("RBI") to issue such Gift Cards.</li>
                            <li>The Gift Cards can be redeemed online against Sellers listed on www.flipkart.com or Flipkart Mobile App or Flipkart m-site ("Platform") only.</li>
                            <li>Gift Cards can be purchased on www.flipkart.com or Flipkart Mobile App using the following payment modes only - Credit Card, Debit Card and Net Banking.</li>
                            <li>Gift Cards can be redeemed by selecting the payment mode as Gift Card. The Gift Card payment option is available for single orders with multiple sellers.</li>
                            <li>Gift Cards cannot be used to purchase other Flipkart Gift Cards or Flipkart First subscriptions.</li>
                        </ul>
                    </div>
                
                    <input type="radio" name="tabs" id="tabthree">
                    <label for="tabthree">Terms & Condition</label>
                    <div class="tab">
                        <ul class="square-type-unordered">
                            <li>Flipkart Gift Cards ("GCs" or "Gift Cards") are issued by Pine Labs Pvt. Ltd ("Pine Labs") which is a private limited company incorporated under the laws of India, and is authorized by the Reserve Bank of India ("RBI") to issue such Gift Cards.</li>
                            <li>The Gift Cards can be redeemed online against Sellers listed on www.flipkart.com or Flipkart Mobile App or Flipkart m-site ("Platform") only.</li>
                            <li>Gift Cards can be purchased on www.flipkart.com or Flipkart Mobile App using the following payment modes only - Credit Card, Debit Card and Net Banking.</li>
                            <li>Gift Cards can be redeemed by selecting the payment mode as Gift Card. The Gift Card payment option is available for single orders with multiple sellers.</li>
                            <li>Gift Cards cannot be used to purchase other Flipkart Gift Cards or Flipkart First subscriptions.</li>
                            <li>Flipkart Gift Cards ("GCs" or "Gift Cards") are issued by Pine Labs Pvt. Ltd ("Pine Labs") which is a private limited company incorporated under the laws of India, and is authorized by the Reserve Bank of India ("RBI") to issue such Gift Cards.</li>
                            <li>The Gift Cards can be redeemed online against Sellers listed on www.flipkart.com or Flipkart Mobile App or Flipkart m-site ("Platform") only.</li>
                            <li>Gift Cards can be purchased on www.flipkart.com or Flipkart Mobile App using the following payment modes only - Credit Card, Debit Card and Net Banking.</li>
                            <li>Gift Cards can be redeemed by selecting the payment mode as Gift Card. The Gift Card payment option is available for single orders with multiple sellers.</li>
                            <li>Gift Cards cannot be used to purchase other Flipkart Gift Cards or Flipkart First subscriptions.</li>
                            <li>Flipkart Gift Cards ("GCs" or "Gift Cards") are issued by Pine Labs Pvt. Ltd ("Pine Labs") which is a private limited company incorporated under the laws of India, and is authorized by the Reserve Bank of India ("RBI") to issue such Gift Cards.</li>
                            <li>The Gift Cards can be redeemed online against Sellers listed on www.flipkart.com or Flipkart Mobile App or Flipkart m-site ("Platform") only.</li>
                            <li>Gift Cards can be purchased on www.flipkart.com or Flipkart Mobile App using the following payment modes only - Credit Card, Debit Card and Net Banking.</li>
                            <li>Gift Cards can be redeemed by selecting the payment mode as Gift Card. The Gift Card payment option is available for single orders with multiple sellers.</li>
                            <li>Gift Cards cannot be used to purchase other Flipkart Gift Cards or Flipkart First subscriptions.</li>
                            <li>Flipkart Gift Cards ("GCs" or "Gift Cards") are issued by Pine Labs Pvt. Ltd ("Pine Labs") which is a private limited company incorporated under the laws of India, and is authorized by the Reserve Bank of India ("RBI") to issue such Gift Cards.</li>
                            <li>The Gift Cards can be redeemed online against Sellers listed on www.flipkart.com or Flipkart Mobile App or Flipkart m-site ("Platform") only.</li>
                            <li>Gift Cards can be purchased on www.flipkart.com or Flipkart Mobile App using the following payment modes only - Credit Card, Debit Card and Net Banking.</li>
                            <li>Gift Cards can be redeemed by selecting the payment mode as Gift Card. The Gift Card payment option is available for single orders with multiple sellers.</li>
                            <li>Gift Cards cannot be used to purchase other Flipkart Gift Cards or Flipkart First subscriptions.</li>
                        </ul>
                    </div>
                    <input type="radio" name="tabs" id="tabfour">
                    <label for="tabfour">How to Redeem</label>
                    <div class="tab">
                        <ul class="square-type-unordered">
                            <li>Flipkart Gift Cards ("GCs" or "Gift Cards") are issued by Pine Labs Pvt. Ltd ("Pine Labs") which is a private limited company incorporated under the laws of India, and is authorized by the Reserve Bank of India ("RBI") to issue such Gift Cards.</li>
                            <li>The Gift Cards can be redeemed online against Sellers listed on www.flipkart.com or Flipkart Mobile App or Flipkart m-site ("Platform") only.</li>
                            <li>Gift Cards can be purchased on www.flipkart.com or Flipkart Mobile App using the following payment modes only - Credit Card, Debit Card and Net Banking.</li>
                            <li>Gift Cards can be redeemed by selecting the payment mode as Gift Card. The Gift Card payment option is available for single orders with multiple sellers.</li>
                            <li>Gift Cards cannot be used to purchase other Flipkart Gift Cards or Flipkart First subscriptions.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
        // Gift Send Option
            $('#customRadio').click(function(){
                $('.gifting-details').removeClass('d-none');
                $('.add-gift-cards').addClass('d-none');
            });
            $('#customRadio1').click(function(){
                $('.add-gift-cards').removeClass('d-none');
                $('.gifting-details').addClass('d-none');
            });

        // Delivery Mode
            $("input[name='delivery_mode']").change(function(){
                var delivery_mode = $(this).val();
                switch (delivery_mode) { 
                    case 'email': 
                        $('.receiver-email').removeClass('d-none');
                        $('.receiver-mobile').addClass('d-none');
                        break;
                    case 'mobile': 
                        $('.receiver-email').addClass('d-none');
                        $('.receiver-mobile').removeClass('d-none');
                        break;
                    default:
                        $('.receiver-email').removeClass('d-none');
                        $('.receiver-mobile').removeClass('d-none');
                }
            });
        </script>
    @endpush
@endsection