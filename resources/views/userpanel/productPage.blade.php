@extends('layouts.app')
@section('title')
    Amazepay | {{ $productDetails['name'] }}
@endsection
@section('content')
    <div class="gift-card-detail-page pt-lg--7 pb-lg--7 pb-5">
        <div class="container-fluid">
            <div class="row">
                <form action="{{ route('checkoutPage', ['slug' => $productDetails['slug']]) }}"
                    method="POST" id="giftCardPageForm">
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
                    <div class="justify-content-center row">
                        <div class="col-12 col-xl-10">
                            <h6 class="text-ornage fw-600 font-xs mt-4">E-Gift Card</h6>
                        </div>
                    </div>
                    <div class="row justify-content-center">
                        <div class="col-lg-10">
                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                    <div class="cardImage">
                                        @if ($productDetails['discount_percentage'] && $productDetails['discount_percentage'] > 0)
                                            <div class="ribbon ribbon-product-page">
                                                <span>{{ $productDetails['discount_percentage'] }}% off</span>
                                            </div>
                                        @endif
                                        <img class="img-fluid single-gift-image"
                                            src="{{ $productDetails['images']->small == null ? URL::asset('/images/hamburger.jpg') : $productDetails['images']->small }}"
                                            alt="product-detail-image">
                                    </div>
                                    <div class="cardText">
                                        <h6 class=" fw-600 font-md mt-2" name="product_name"
                                            value="{{ $productDetails['name'] }}">{{ $productDetails['name'] }}</h6>
                                        <p class="mb-3 font-xssss fw-600 mt-2">Validity :
                                            {{ $productDetails['expiry'] }}
                                        </p>
                                        <p>
                                            <span class="mb-3 fw-600 font-xs mt-2 text-orange">Brand </span> : <span
                                                class="mb-3 text-black font-xsss fw-400 mt-2">{{ $productDetails['brandName'] }}</span>
                                        </p>
                                        <p>
                                            <span class="mb-3 fw-600 font-xs mt-2 text-orange">Category </span> : <span
                                                class="mb-3 text-black font-xsss fw-400 mt-2"> Fashion </span>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-lg-9">
                                    <div class="row">
                                        <div class="col-lg-4">
                                            <div class="order-2 mb-3 mb-lg-0 coupon-quantity">
                                                @dd($productDetails);
                                                <!-- slab means checkbox -->
                                                @if ($productDetails['price']->type == 'SLAB')
                                                    <label class="small-size fw-600 text-grey-900 font-xsss">Select
                                                        Denomination</label>
                                                    <div class="radio-btn-row boxed">
                                                        @foreach ($productDetails['price']->denominations as $denomination)
                                                            <div class="boxed">
                                                                <input type="radio"
                                                                    class="custom-control-input denomination-slab"
                                                                    id="customRadio-{{ $denomination }}" name="denomination"
                                                                    value="{{ $denomination }}"
                                                                    @if ($loop->first) checked @endif>
                                                                <label class="small-size fw-500 font-xsss"
                                                                    for="customRadio-{{ $denomination }}">{{ $denomination }}</label>
                                                            </div>
                                                        @endforeach
                                                        <span class="font-xssss fw-400 error-rec-deno text-danger"></span>
                                                    </div>
                                                    <!-- range means plain input value -->
                                                @elseif ($productDetails['price']->type === 'RANGE')
                                                    <label class="small-size fw-600 text-grey-900 font-xsss">Enter
                                                        Denomination</label>
                                                    <input type="text"
                                                        class="form-control credentails-field denomination-range"
                                                        placeholder="Enter Denomination" name="denomination"
                                                        id="denomination-range" value="{{ $productDetails['minPrice'] }}">
                                                    <small class="float-right form-text text-current font-xsss">Min:
                                                        ₹{{ $productDetails['minPrice'] }} Max:
                                                        ₹{{ $productDetails['maxPrice'] }}</small>
                                                    <div class="font-xssss fw-400 error-rec-deno-range text-danger mt-3">
                                                    </div>
                                                @else
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="row">
                                                <div class="order-3 mb-3 mb-lg-0 coupon-quantity">
                                                    <label
                                                        class="small-size fw-600 text-grey-900 font-xsss">Quantity</label>
                                                    <input type="text" class="form-control credentails-field"
                                                        placeholder="Quantity" name="quantity" id="quantity"
                                                        value="">
                                                    <small class="float-right form-text text-current font-xsss">Min:1 Max:
                                                        10</small>
                                                    <div class="font-xssss fw-400 error-rec-qnty text-danger mt-3"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 mb-4 pl-lg-5 ">
                                            <h6 class="mb-3 fw-600 font-xss mt-2">Gift Send Option</h6>
                                            <div class="custom-control mr-4 custom-radio ">
                                                <input type="radio" class="custom-control-input" id="customRadio"
                                                    name="gift_send_option" value="send_as_gift" checked>
                                                <label
                                                    class="custom-control-label small-size fw-500 text-grey-900 font-xssss"
                                                    for="customRadio">Send as Gift</label>
                                            </div>
                                            <div class="custom-control mr-0 custom-radio ">
                                                <input type="radio" class="custom-control-input" id="customRadio1"
                                                    name="gift_send_option" value="buy_for_self">
                                                <label
                                                    class="custom-control-label small-size fw-500 text-grey-900 font-xssss"
                                                    for="customRadio1">Buy for Self</label>
                                                <div class="row">
                                                    <div class="col-12 mb-4">
                                                        <input type="hidden" name="delivery_mode" value="both">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row ">
                                        </div>
                                        <div class="row  justify-content-center mt-4 gifting-details">
                                            <h6 class="mb-3 fw-600 font-xss mt-2">Gifting Details</h6>
                                            <div class="col-12 col-lg-3 receiver-name">
                                                <input type="text" class="form-control mb-3 credentails-field"
                                                    placeholder="Receiver Name" name="receiver_name" id="receiver-name">
                                                <span class="font-xssss fw-400 error-rec-name text-danger"></span>
                                            </div>
                                            <div class="col-lg-3 receiver-email">
                                                <input type="text" class="form-control mb-3 credentails-field"
                                                    placeholder="Receiver Email" name="receiver_email"
                                                    id="receiver-email">
                                                <span class="font-xssss fw-400 error-rec-email text-danger"></span>
                                            </div>
                                            <div class="col-12 col-lg-3 receiver-mobile">
                                                <input type="text" class="form-control mb-3 credentails-field"
                                                    placeholder="Receiver Mobile Number" name="receiver_mobile"
                                                    id="receiver-mobile">
                                                <span class="font-xssss fw-400 error-rec-mobile text-danger"></span>
                                            </div>
                                            <div class="col-12 col-lg-3 receiver-message">
                                                <input type="text" class="form-control mb-3 credentails-field"
                                                    placeholder="Message for Receiver" name="receiver_msg"
                                                    id="receiver-msg">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-12">
                                                @if (\Auth::user())
                                                    <input type="submit"
                                                        class="form-control  float-right h60 bg-current text-white text-center font-xss fw-500 border-0 p-0 mt-4 mb-4 w100 login-button-color"
                                                        value="Pay Now" id="pay-now">
                                                @else
                                                    <a href="#"
                                                        class="form-control h60 bg-current float-right text-white text-center font-xss fw-500 border-0 p-0 mt-4 mb-4 w100 login-button-color"
                                                        data-toggle="modal" data-target="#Modallogin">Pay Now</a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row card-form add-gift-cards">
                        <h6 class="mb-3 fw-600 font-xss mt-2 text-center display3-size">Add Gift Cards to your Account</h6>
                        <div class="row">
                            <div class="col-lg-4 col-md-4 text-center">
                                <h2 class="fw-500 text-orange display2-size"><img
                                        src="{{ asset('images/addTemplate.png') }}" alt="amazepay_addTemp"></h2>
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
                                    <li>{{ $productDetails['description'] }}</li>
                                </ul>
                            </div>
                            <input type="radio" name="tabs" id="tabthree">
                            <label for="tabthree">Terms & Condition</label>
                            <div class="tab term-condition">
                                {!! $productDetails['tnc']->content !!}
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



            });


            // Gift Send Option toggle
            $('#customRadio').click(function() {
                $('.gifting-details').removeClass('d-none');

            });
            $('#customRadio1').click(function() {
                $('.gifting-details').addClass('d-none');
            });



            // initialzing gift send button
            var giftSendOption = $("input[name='gift_send_option']:checked").val();

            // Gift Send Option Change Handler
            $("input[name='gift_send_option']").change(function() {
                giftSendOption = $(this).val();
                console.log(giftSendOption);

            });

            $('form').on('submit', function(e) {
                e.preventDefault();
                // debugger;

                // Denomination validation
                var denominationBooleanValue = false;
                var denomination = '';
                var denominationmsg = '';
                var minDenominationValue = {{ $productDetails['minPrice'] }};
                var maxDenominationValue = {{ $productDetails['maxPrice'] }};

                if ($('.denomination-slab').attr('type') === 'radio') {

                    var checkedRadio = $("input[name='denomination']:checked");


                    if (checkedRadio.length > 0) {
                        denomination = checkedRadio.val();
                        denominationBooleanValue = true;
                    } else {
                        denomination = 0;
                        denominationBooleanValue = false;
                        denominationmsg = 'Please Select Denomination';
                    }
                } else if ($('#denomination-range').attr('type') === 'text') {
                    var enteredDenominationRangeValue = $('#denomination-range').val();

                    if (!enteredDenominationRangeValue || enteredDenominationRangeValue.length === 0) {
                        $('.error-rec-deno-slab').text('Enter Demonination');
                        denomination = 0;
                        denominationBooleanValue = false;
                    } else if (!/^\d+$/.test(enteredDenominationRangeValue)) {
                        $('.error-rec-deno-slab').text('Spaces and characters are not allowed');
                        denomination = 0;
                        denominationBooleanValue = false;
                    } else if (parseInt(enteredDenominationRangeValue) < minDenominationValue) {
                        $('.error-rec-deno-slab').text('Demonination must be greater than or equal to ' +
                            minDenominationValue);
                        denomination = 0;
                        denominationBooleanValue = false;
                    } else if (parseInt(enteredDenominationRangeValue) > maxDenominationValue) {
                        $('.error-rec-deno-slab').text('Demonination must be smaller than or equal to ' +
                            maxDenominationValue);
                        denomination = 0;
                        denominationBooleanValue = false;
                    } else {
                        $('.error-rec-deno-slab').empty();
                        denomination = 1;
                        denominationBooleanValue = true;
                    }
                } else {
                    denomination = 0;
                    denominationBooleanValue = false;
                }

                var denominationInvalid = !denomination || denomination === undefined || denomination === null ||
                    denomination === 0 || !denominationBooleanValue;
                if (denominationInvalid) {
                    $('.error-rec-deno').text(denominationmsg);
                } else {
                    $('.error-rec-deno').empty();
                }

                // Quantity validation
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

                // Validate recipient details only if giftSendOption is 'send_as_gift'

                if (giftSendOption === 'send_as_gift') {
                    function validateRecipient() {
                        var recName = $('#receiver-name').val();
                        var recEmail = $('#receiver-email').val();
                        var recMobile = $('#receiver-mobile').val();
                        var recMsg = $('#receiver-msg').val();
                        flag = true;

                        if (!recName || recName.length === 0) {
                            $('.error-rec-name').text('Name Required');
                            flag = false;
                        } else {
                            $('.error-rec-name').empty();
                        }

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

                        return flag;
                    }

                    flag = validateRecipient();
                }

                if (bool && flag && !denominationInvalid) {
                    var data = {
                        denomination: denomination,
                        quantity: quantity
                    };
                    window.localStorage.setItem('data', JSON.stringify(data));
                    this.submit();
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
        </script>
    @endpush
@endsection
