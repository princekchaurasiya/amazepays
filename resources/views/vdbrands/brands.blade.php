@extends('layouts.app')

@section('content')
<div class="container py-5">
    @php
        $brand = json_decode($brands['original']['decrypted_data'], true)[0];
        $images = json_decode(str_replace("'", '"', $brand['Images']), true);
        $redeemSteps = $brand['RedeemSteps'];
    @endphp

    <div class="card mb-4 shadow">
        <div class="row g-0">
            <div class="col-md-4">
                <img src="{{ $images['featured'] }}" class="img-fluid rounded-start" alt="{{ $brand['BrandName'] }}">
            </div>
            <div class="col-md-8">
                <div class="card-body">
                    <h2 class="card-title">{{ $brand['BrandName'] }}</h2>
                    <p class="text-muted">{{ $brand['Category'] }}</p>
                    <p><strong>Discount:</strong> {{ $brand['Discount'] }}%</p>
                    <p><strong>Price Range:</strong> ₹{{ $brand['minPrice'] }} - ₹{{ $brand['maxPrice'] }}</p>
                    <p><strong>Available Denominations:</strong> {{ $brand['DenominationList'] }}</p>
                    <p><strong>Stock Available:</strong> {{ $brand['StockAvailable'] ? 'Yes' : 'No' }}</p>

                    <h5 class="mt-4">Description</h5>
                    <p>{!! nl2br(strip_tags($brand['Description'])) !!}</p>
                </div>
            </div>
        </div>
    </div>
<p> <a href="{{ url('/stores/filter') }}" class="btn btn-info btn-lg text-white">See Stores</a>
    <h4>Terms & Conditions</h4>
    <div class="mb-4">
        <p>{!! nl2br(strip_tags($brand['TnC'])) !!}</p>
    </div>

    <h4>Important Instructions</h4>
    <ul class="list-group mb-4">
        @foreach($instructions as $instruction)
            <li class="list-group-item">{{ $instruction }}</li>
        @endforeach
    </ul>
@else
    <p>{{ $brand['ImportantInstruction'] }}</p>
@endif

<p>test</p>

    <h4>How to Redeem</h4>
    <div class="row">
        @foreach($redeemSteps as $step)
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <img src="{{ $step['image'] }}" class="card-img-top" alt="Redeem Step">
                    <div class="card-body">
                        <p class="card-text">{!! nl2br($step['title']) !!}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
<!--<div class="justify-content-center row">
                        <div class="col-12 col-xl-10">
                            <h6 class="text-ornage fw-600 font-xs mt-4">E-Gift Card</h6>
                        </div>
                    </div>
                    <div class="row justify-content-center">
                        <div class="col-lg-10">
                            <div class="row justify-content-center">
                                <div class="col-lg-3">
                                 <div class="cardImage position-relative">
                                    @if (!empty($brand['Discount']) && $brand['Discount'] > 0)
                                        <div class="ribbon ribbon-product-page">
                                            <span>{{ $brand['Discount'] }}% off</span>
                                        </div>
                                    @endif
                                    <img src="{{ $images['featured'] }}" class="img-fluid rounded-start" alt="{{ $brand['BrandName'] }}">
                                </div>
                                        <div class="cardText">
                                            <h6 class="fw-600 font-md mt-2" name="brand_name">
                                            {{ $brand['BrandName'] }}
                                            </h6>
                                        </div>
                                        <p><strong>Price Range:</strong> ₹{{ $brand['minPrice'] }} - ₹{{ $brand['maxPrice'] }}</p>

                                        <p><strong>Available Denominations:</strong></p>
                                        @foreach($brand['DenominationList'] as $denomination)
                                            <button type="button" class="btn btn-outline-primary m-1">
                                                ₹{{ $denomination }}
                                            </button>
                                        @endforeach
                                    </div>
                                <div class="col-lg-4">
                                            <div class="row">
                                                <div class="order-3 mb-3 mb-lg-0 coupon-quantity">
                                                    <label
                                                        class="small-size fw-600 text-grey-900 font-xsss">Quantity</label>
                                                    <input type="text" class="form-control credentails-field"
                                                        placeholder="Quantity" name="quantity" id="quantity"
                                                        value="{{ old('quantity', 1) }}" maxlength="2">
                                                    <small class="float-right form-text text-current font-xsss">Min: 1 Max:
                                                        10</small>
                                                    <div class="font-xssss fw-400 error-rec-qnty text-danger mt-3"></div>
                                                </div>
                                            </div>
                                        </div>
                                    <div class="col-lg-4 mb-4 pl-lg-5">
                                            <h6 class="mb-3 fw-600 font-xss mt-2">Gift Send Option</h6>
                                            <div class="custom-control mr-4 custom-radio">
                                                <input type="radio" class="custom-control-input gift-option"
                                                    id="sendAsGiftRadio" name="gift_send_option" value="send_as_gift"
                                                    {{ old('gift_send_option', 'send_as_gift') == 'send_as_gift' ? 'checked' : '' }}>
                                                <label
                                                    class="custom-control-label small-size fw-500 text-grey-900 font-xssss"
                                                    for="sendAsGiftRadio">
                                                    Send as Gift
                                                </label>
                                            </div>
                                        <div class="custom-control mr-0 custom-radio">
                                                <input type="radio" class="custom-control-input gift-option"
                                                    id="buyForSelfRadio" name="gift_send_option" value="buy_for_self"
                                                    {{ old('gift_send_option', 'send_as_gift') == 'buy_for_self' ? 'checked' : '' }}>
                                                <label
                                                    class="custom-control-label small-size fw-500 text-grey-900 font-xssss"
                                                    for="buyForSelfRadio">
                                                    Buy for Self
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row justify-content-center mt-4 gifting-details" style="display: block;">
                                        <h6 class="mb-3 fw-600 font-xss mt-2">Gifting Details</h6>
                                        <div class="row">  Added .row to group the .col-lg-* elements 
                                            <div class="col-12 col-lg-3 receiver-name">

                                                <input type="text" class="form-control mb-3 credentails-field"
                                                    placeholder="Receiver Name" name="receiver_name" id="receiver-name">
                                                    </div>
                                            <div class="col-12 col-lg-3 receiver-email">
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
                                                    placeholder="Message for Receiver" name="receiver_msg">
                                                    </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12">
                                            @if (Auth::check())
                                                <input type="submit"
                                                    class="form-control float-right h60 bg-current text-white text-center font-xss fw-500 border-0 p-0 mt-4 mb-4 w250 login-button-color"
                                                    value="Go to Checkout Page" id="pay-now">
                                            @else
                                                <a href="#"
                                                    class="form-control h60 bg-current float-right text-white text-center font-xss fw-500 border-0 p-0 mt-4 mb-4 w250 login-button-color"
                                                    data-toggle="modal" data-target="#Modallogin">
                                                    Go to Checkout Page
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- How to Redeem Tab --}}
                                    
                                        <input type="radio" name="tabs" id="tabone" checked="checked">
                                        <label for="tabone">How to Redeem</label>
                                        <div class="tab p-3 font-xsss instructions text-black">
                                            <div class="row">
                                               @foreach($redeemSteps as $step)
                                                    <div class="col-md-4 mb-3">
                                                        <div class="card h-100">
                                                            <img src="{{ $step['image'] }}" class="card-img-top" alt="Redeem Step">
                                                            <div class="card-body">
                                                                <p class="card-text">{!! nl2br($step['title']) !!}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach -->
@endsection
