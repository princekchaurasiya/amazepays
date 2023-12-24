@extends('layouts.app')
@section('title')
    Amazepay | Gift Card
@endsection
@section('content')
    <div class="gift-card-detail-page pt-lg--7 pb-lg--7 pb-5">
        <div class="container-fluid p-5">
            <div class="row">
                <form action="{{ route('storePayNowData-and-go-to-CheckoutPage', ['sku' => $getprdtDetails['sku']]) }}" method="POST" id="giftCardPageForm">
                    {{ csrf_field() }}
                    <div>
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                    <div class="col-lg-12">
                        <h6 class="text-ornage fw-600 font-xs">E-Gift Card</h6>
                        <hr>
                    </div>
                    <div class="row card-form">
                        <div class="col-lg-5">
                            <div class="card-container">
                                <div class="row">
                                    <div class="col-lg-5">
                                        
                                        <img class="img-fluid" src="{{ $getprdtDetails['images']->small == null ? URL::asset('/images/hamburger.jpg') : $getprdtDetails['images']->small }}"
                                    alt="product-detail-image">
                                    </div>
                                    <div class="col-lg-7">
                                        <h6 class=" fw-600 font-md mt-2" name="product_name"
                                                value="{{ $getprdtDetails['name'] }}">{{ $getprdtDetails['name'] }}</h6>
                                        <p class="mb-3 font-xssss fw-600 mt-2">Validity :
                                            {{ $getprdtDetails['expiry'] }}</p>
                                        <div>
                                            <span class="mb-3 fw-600 font-xs mt-2 text-orange">Category </span> : <span class="mb-3 text-black font-xsss fw-400 mt-2"> CN & PIN</span>
                                        </div>
                                        <div>
                                            <span class="mb-3 fw-600 font-xs mt-2 text-orange">Brand </span> : <span class="mb-3 text-black font-xsss fw-400 mt-2">AmazePays</span>        
                                        </div>
                                        
                                    </div>
                                </div>
                                
                            </div>
                            
                            <div class="container">
                                <div class="row">
                                    {{-- <div class="row">
                                        <div class="col-sm-6">
                                            <h6 class="mb-3 fw-600 font-md mt-2" name="product_name"
                                                value="{{ $getprdtDetails['name'] }}">{{ $getprdtDetails['name'] }}</h6>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="mb-3 font-xssss fw-600 mt-2">Validity :
                                                {{ $getprdtDetails['expiry'] }}</span>
                                        </div>
                                    </div> --}}
                                </div>
                                {{-- <div class="row copuan-quantity">
                                    <div class="col-sm-6">
                                        @if ($getprdtDetails['price']->type === 'SLAB' || $getprdtDetails['price']->type == 'RANGE')
                                            <div class="radio-btn-row">
                                                @foreach ($getprdtDetails['price']->denominations as $denomination)
                                                    <div class="custom-control mr-4 custom-radio custom-control-inline">
                                                        <input type="radio" class="custom-control-input range"
                                                            id="customRadio-{{ $denomination }}" name="denomination"
                                                            value="{{ $denomination }}">
                                                        <label
                                                            class="custom-control-label small-size fw-500 text-grey-900 font-xsss"
                                                            for="customRadio-{{ $denomination }}">{{ $denomination }}</label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <input type="text" class="form-control mb-3"
                                                placeholder="Select Denomination" name="denomination" id="denomination"
                                                value="">
                                        @endif
                                        <div>
                                            <p class="font-xssss fw-400 error-rec-deno text-danger"></p>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control mb-3" placeholder="Quantity"
                                            name="quantity" id="quantity" value="">
                                        <span class="font-xssss fw-400 error-rec-qnty text-danger"></span>
                                    </div>
                                </div> --}}


                                {{-- <h6 class="text-grey-900 fw-400 font-xs mt-2">Offers</h6>
                                <ul class="square-type-unordered">
                                    <li>Only UPI payment is accepted for this gift card. --- On Amazon Pay Special E-Gift
                                        Card (woohoo.in/amazon-pay-special-e-gift-card) Credit/Debit card and Net Banking
                                        options are available.</li>
                                </ul> --}}
                            </div>
                        </div>
                        <div class="col-lg-7">
                            
                               
                                <div class="row copuan-quantity">
                                    <div class="col-sm-6 pl-0">
                                        
                                        @if ($getprdtDetails['price']->type == 'RANGE')
                                            <label class="small-size fw-600 text-grey-900 font-xsss">Select Denomination</label>
                                            <div class="radio-btn-row boxed">
                                                @foreach ($getprdtDetails['price']->denominations as $denomination)
                                                    <div class="boxed">
                                                        {{-- <input type="radio" id="customRadio-{{ $denomination }}" name="denomination"
                                                        value="{{ $denomination }}"> --}}
                                                        <input type="radio" class="custom-control-input range"
                                                            id="customRadio-{{ $denomination }}" name="denomination"
                                                            value="{{ $denomination }}">
                                                        <label class="small-size fw-500 font-xsss" for="customRadio-{{ $denomination }}">{{ $denomination }}</label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @elseif ($getprdtDetails['price']->type === 'SLAB')
                                            <label class="small-size fw-600 text-grey-900 font-xsss">Enter Denomination</label>
                                            <input type="text" class="form-control mb-3 credentails-field"
                                                placeholder="Select Denomination" name="denomination" id="denomination"
                                                value="">
                                        @else

                                        @endif
                                        <div>
                                            <p class="font-xssss fw-400 error-rec-deno text-danger"></p>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 pl-0">
                                        <label class="small-size fw-600 text-grey-900 font-xsss">Quantity</label>
                                        <input type="text" class="form-control mb-3 credentails-field" placeholder="Quantity"
                                            name="quantity" id="quantity" value="">
                                        <span class="font-xssss fw-400 error-rec-qnty text-danger"></span>
                                    </div>
                                </div>
                            
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 mb-4">
                            <h6 class="mb-3 fw-600 font-xss mt-2">Gift Send Option</h6>
                            <div class="custom-control mr-4 custom-radio custom-control-inline">
                                <input type="radio" class="custom-control-input" id="customRadio"
                                    name="gift_send_option" value="send_as_gift" checked>
                                <label class="custom-control-label small-size fw-500 text-grey-900 font-xssss"
                                    for="customRadio">Send as Gift</label>
                            </div>
                            <div class="custom-control mr-0 custom-radio custom-control-inline">
                                <input type="radio" class="custom-control-input" id="customRadio1"
                                    name="gift_send_option" value="buy_for_self">
                                <label class="custom-control-label small-size fw-500 text-grey-900 font-xssss"
                                    for="customRadio1">Buy for Self (This E-gift card will be added to your
                                    account)</label>
                            </div>
                        </div>
                        <div class="row gifting-details">
                            <h6 class="mb-3 fw-600 font-xss mt-2">Gifting Details</h6>
                            <div class="col-sm-3 receiver-name">
                                <input type="text" class="form-control mb-3 credentails-field" placeholder="Receiver Name"
                                    name="receiver_name" id="receiver-name">
                                <span class="font-xssss fw-400 error-rec-name text-danger"></span>
                            </div>
                            <div class="col-sm-3 receiver-email">
                                <input type="text" class="form-control mb-3 credentails-field" placeholder="Receiver Email"
                                    name="receiver_email" id="receiver-email">
                                <span class="font-xssss fw-400 error-rec-email text-danger"></span>
                            </div>
                            <div class="col-sm-3 receiver-mobile d-none">
                                <input type="text" class="form-control mb-3 credentails-field" placeholder="Receiver Mobile Number"
                                    name="receiver_mobile" id="receiver-mobile">
                                <span class="font-xssss fw-400 error-rec-mobile text-danger"></span>
                            </div>
                            <div class="col-sm-3 receiver-message">
                                <input type="text" class="form-control mb-3 credentails-field" placeholder="Message for Receiver"
                                    name="receiver_msg" id="receiver-msg">
                            </div>
                            <div class="col-lg-6 mb-4">
                                <h6 class="mb-3 fw-600 font-xss mt-2">Delivery Mode</h6>
                                <div class="custom-control mr-4 custom-radio custom-control-inline">
                                    <input type="radio" class="custom-control-input" id="customRadio3"
                                        name="delivery_mode" value="email" checked>
                                    <label class="custom-control-label small-size fw-500 text-grey-900 font-xsss"
                                        for="customRadio3">Email</label>
                                </div>
                                <div class="custom-control mr-4 custom-radio custom-control-inline">
                                    <input type="radio" class="custom-control-input" id="customRadio4"
                                        name="delivery_mode" value="mobile">
                                    <label class="custom-control-label small-size fw-500 text-grey-900 font-xsss"
                                        for="customRadio4">Mobile</label>
                                </div>
                                <div class="custom-control mr-4 custom-radio custom-control-inline">
                                    <input type="radio" class="custom-control-input" id="customRadio5"
                                        name="delivery_mode" value="both">
                                    <label class="custom-control-label small-size fw-500 text-grey-900 font-xsss"
                                        for="customRadio5">Both</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            @if (\Auth::user())
                                <input type="submit"
                                    class="form-control h60 float-right bg-current text-white text-center font-xss fw-500 border-0 p-0 mt-4 mb-4 w100 login-button-color"
                                    value="Pay Now" id="pay-now">
                            @else
                                <a href="#"
                                    class="form-control h60 float-right bg-current text-white text-center font-xss fw-500 border-0 p-0 mt-4 mb-4 w100 login-button-color"
                                    data-toggle="modal" data-target="#Modallogin">Pay Now</a>
                            @endif
                        </div>
                        <div class="row card-form add-gift-cards d-none">
                            <h6 class="mb-3 fw-600 font-xss mt-2 text-center display3-size">Add Gift Cards to your Account</h6>
                            <div class="row">
                                <div class="col-lg-4 col-md-4 text-center">
                                    <h2 class="fw-500 text-orange display2-size"><img src="{{ asset('images/addTemplate.png') }}" alt="amazepay_addTemp"></h2>
                                    <p class="font-xssss fw-500 text-black lh-26 mt-2">If you want to print or
                                        forward this Email gift card with an attractive template, please select the
                                        'Send as a Gift' option.
                                    </p>
                                </div>
                                <div class="col-lg-4 col-md-4 text-center">
                                    <img src="{{ asset('images/addWallet.png') }}" alt="amazepay_addTemp">
                                    <p class="font-xssss fw-500 text-black lh-26 mt-2">The e-gift card that you
                                        order from this page, will be added to your Woohoo account automatically.
                                    </p>
                                </div>
                                <div class="col-lg-4 col-md-4 text-center">
                                    <img src="{{ asset('images/secure.png') }}" alt="amazepay_addTemp">
                                    <p class="font-xssss fw-500 text-black lh-26 mt-2">For better security of
                                        your e-gift card, the details of your gift card will not be sent separately.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                   
                    <div class="row personalise-gift-card">
                        <div class="tabs">
                            <input type="radio" name="tabs" id="tabone" checked="checked">
                            <label for="tabone">How to Redeem</label>
                            <div class="tab">
                                <ul class="square-type-unordered">
                                    <li>Gift Cards ("GCs" or "Gift Cards") are issued by Pine Labs Pvt. Ltd ("Pine
                                        Labs") which is a private limited company incorporated under the laws of India, and
                                        is authorized by the Reserve Bank of India ("RBI") to issue such Gift Cards.
                                    </li>
                                    <li>The Gift Cards can be redeemed online against Sellers listed on www.amazepays.in or
                                         Mobile App or Flipkart m-site ("Platform") only.
                                    </li>
                                    <li>Gift Cards can be purchased on www.flipkart.com or AmazePays Mobile App using the
                                        following payment modes only - Credit Card, Debit Card and Net Banking.
                                    </li>
                                    <li>Gift Cards can be redeemed by selecting the payment mode as Gift Card. The Gift Card
                                        payment option is available for single orders with multiple sellers.
                                    </li>
                                    <li>Gift Cards cannot be used to purchase other Flipkart Gift Cards or AmazePays First
                                        subscriptions.
                                    </li>
                                </ul>
                            </div>
                            <input type="radio" name="tabs" id="tabtwo">
                            <label for="tabtwo">Description</label>
                            <div class="tab">
                                <ul class="square-type-unordered">
                                    <li>{{ $getprdtDetails['description'] }}</li>
                                </ul>
                            </div>
                            <input type="radio" name="tabs" id="tabthree">
                            <label for="tabthree">Terms & Condition</label>
                            <div class="tab term-condition">
                                {!! $getprdtDetails['tnc']->content !!}
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            "use strict";

            $(document).ready(function() {
                $('div>.owl-items:first').addClass('border-black');
                $('.child:first').css("display", "block");
                $('.child-active:first').addClass('border-black');
                $('.preview > img').attr("src", $('.active:first').find('img').attr('src'));


                // Preview Image
                $('.active').on('click', function() {
                    $('.active').removeClass('border-black');
                    $(this).addClass('border-black');
                    // console.log($(this).find('img').attr('src'));
                    $('.preview > img').attr("src", $(this).find('img').attr('src'));

                });

                //  on load store value in local storage
                let storageData = {};
                if (storageData) {
                    const rangeInputs = $('.copuan-quantity').find('.range');
                    const denominationValue = storageData.denomination;
                    const quantityValue = storageData.quantity !== null ? storageData.quantity : '';

                    if (rangeInputs.attr('type') === 'radio') {
                        rangeInputs.filter((index, element) => element.value === denominationValue)
                            .prop('checked', true);
                    } else {
                        $('#denomination').val(denominationValue);
                    }

                    $('#quantity').val(quantityValue);
                } else {
                    $('#quantity').val('');
                }

            });

            // Gift Send Option toggle
            $('#customRadio').click(function() {
                $('.gifting-details').removeClass('d-none');
                $('.add-gift-cards').addClass('d-none');
            });
            $('#customRadio1').click(function() {
                $('.add-gift-cards').removeClass('d-none');
                $('.gifting-details').addClass('d-none');
            });

            // Delivery Mode Selecting Options toggle
            $("input[name='delivery_mode']").change(function() {
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


            // initialzing gift send button
            var giftSendOption = $("input[name='gift_send_option']:checked").val();

            // Gift Send Option Change Handler
            $("input[name='gift_send_option']").change(function() {
                giftSendOption = $(this).val();
                console.log(giftSendOption);
            });



            // pay now button click redirecting to checkout on submit validation on pay now button

            $('form').on('submit', function(e) {

                e.preventDefault();



                // Denomination validation
                var denominationBooleanValue = false;
                var denomination = '';

                if ($('.copuan-quantity').find('.range').attr('type') === 'radio') {
                    var checkedRadio = $("input[name='denomination']:checked");
                    if (checkedRadio.length > 0) {
                        denomination = checkedRadio.val();
                        denominationBooleanValue = true;
                    } else {
                        denomination = 0;
                        denominationBooleanValue = false;
                    }
                } else {
                    denomination = 0;
                    denominationBooleanValue = false;
                }

                var denominationInvalid = !denomination || denomination === undefined || denomination === null ||
                    denomination === 0 || !denominationBooleanValue;

                if (denominationInvalid) {
                    $('.error-rec-deno').text('Please Select Denomination');
                } else {
                    $('.error-rec-deno').empty();
                }

                // quantity validation
                var quantity = $('#quantity').val();
                var bool = true;
                var flag = true;
                if (!quantity || quantity.length === 0) {
                    $('.error-rec-qnty').text('Enter Quantity');
                    bool = false;
                } else if (!/^\d+$/.test(quantity)) {
                    $('.error-rec-qnty').text('Spaces and characters are not allowed');
                    bool = false;
                } else if (parseInt(quantity) < 1) {
                    $('.error-rec-qnty').text('Quantity must be greater than 1');
                    bool = false;
                } else if (parseInt(quantity) > 10) {
                    $('.error-rec-qnty').text('Maximum quantity allowed is 10');
                    bool = false;
                } else {
                    $('.error-rec-qnty').empty();
                }

                if (giftSendOption === 'send_as_gift') {
                    status = validateRecipient(delivery_mode);
                }


                var delivery_mode = $("input[name='delivery_mode']:checked").val();


                function validateRecipient(mode) {
                    var recName = $('#receiver-name').val();
                    var recEmail = $('#receiver-email').val();
                    var recMobile = $('#receiver-mobile').val();
                    var recMsg = $('#receiver-msg').val();


                    if (!recName || recName.length === 0) {
                        $('.error-rec-name').text('Name Required');
                        flag = false;
                    } else {
                        $('.error-rec-name').empty();
                    }

                    if (mode === 'email' || mode === 'both') {
                        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!recEmail || recEmail.length === 0) {
                            $('.error-rec-email').text('Email Required');
                            flag = false;
                        } else if (!emailRegex.test(recEmail)) {
                            $('.error-rec-email').text('Invalid Email');
                            flag = false;
                        } else {
                            $('.error-rec-email').empty();
                        }
                    }

                    if (mode === 'mobile' || mode === 'both') {
                        var mobileRegex = /^(?:(?:\+|0{0,2})91(\s*[\-]\s*)?|[0]?)?[789]\d{9}$/;
                        if (!recMobile || recMobile.length === 0) {
                            $('.error-rec-mobile').text('Mobile Number Required');
                            flag = false;
                        } else if (!mobileRegex.test(recMobile)) {
                            $('.error-rec-mobile').text('Invalid Mobile Number');
                            flag = false;
                        } else {
                            $('.error-rec-mobile').empty();
                        }
                    }

                    return flag;
                }


                if (bool && flag) {
                    var data = {
                        denomination: denomination,
                        quantity: quantity
                    };
                    window.localStorage.setItem('data', JSON.stringify(data));
                    if (!denominationInvalid) {
                        this.submit();
                    }

                } else {
                    return false;
                }

            });

            $.each($('.radio-btn'), function(key, value) {
                $(this).click(function(e) {
                    $('.radio-btn-selected')
                        .removeClass('radio-btn-selected')
                        .addClass('radio-btn');

                    $(this)
                        .removeClass('radio-btn')
                        .addClass('radio-btn-selected');
                });
            });

            $('#tabthree').click(function() {
                $('.term-condition').find("ol, ul").addClass('square-type-unordered');
            });


            // pay now
            // function payNow(recName,recEmail,recMsg){
            //     console.log(recName,recEmail,recMsg);
            //     $.ajaxSetup({
            //         headers: {
            //             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            //         }
            //     });
            //     // var formData = new FormData();
            //     // formData.append( 'coupan',$('#coupan-code').val());


            //     var type = "POST";
            //     var ajaxurl = "{{ url('/check-user-validation') }}";

            //     return false;
            //     $.ajax({
            //         type: type,
            //         url: ajaxurl,
            //         contentType: 'application/json',
            //         // data: formData,
            //         processData: false,
            //         contentType: false,
            //         dataType: 'json',
            //         success: function (data) {
            //             debugger;
            //             console.log(data);
            //             $("#Modallogin").modal('show');
            //         },
            //         error: function (data) {
            //             console.log(data);
            //         }
            //     });
            //     return false;
            // }
        </script>
    @endpush
@endsection
