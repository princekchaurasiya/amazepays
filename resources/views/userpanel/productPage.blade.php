@extends('layouts.app')
@section('title')
    Amazepay | {{ $productDetails['name'] }}
@endsection
@section('content')
    <div class="gift-card-detail-page pt-lg--7 pb-lg--7 pb-5">
        <div class="container-fluid">
            <div class="row">
                <form action="{{ route('checkoutPage', ['slug' => $productDetails['slug']]) }}" method="POST"
                    id="giftCardPageForm">
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
                                        <h6 class=" fw-600 font-md mt-2" name="product_name">{{ $productDetails['name'] }}
                                        </h6>
                                        <p class="mb-3 font-xssss fw-600 mt-2">Validity: {{ $productDetails['expiry'] }}</p>
                                        <p>
                                            <span class="mb-3 fw-600 font-xs mt-2 text-orange">Brand</span>:
                                            <span
                                                class="mb-3 text-black font-xsss fw-400 mt-2">{{ $productDetails['brandName'] }}</span>
                                        </p>
                                        <p>
                                            <span class="mb-3 fw-600 font-xs mt-2 text-orange">Category</span>:
                                            <span class="mb-3 text-black font-xsss fw-400 mt-2">Fashion</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-lg-9">
                                    <div class="row">
                                        <div class="col-lg-4">
                                            <div class="order-2 mb-3 mb-lg-0 coupon-quantity">
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
                                                                    {{ old('denomination', session('giftCardFormValues.denomination')) == $denomination ? 'checked' : '' }}>
                                                                <label class="small-size fw-500 font-xsss"
                                                                    for="customRadio-{{ $denomination }}">{{ $denomination }}</label>
                                                            </div>
                                                        @endforeach
                                                        <span class="font-xssss fw-400 error-rec-deno text-danger"></span>
                                                    </div>
                                                @elseif ($productDetails['price']->type === 'RANGE')
                                                    <label class="small-size fw-600 text-grey-900 font-xsss">Enter
                                                        Denomination</label>
                                                    <input type="text"
                                                        class="form-control credentails-field denomination-range"
                                                        placeholder="Enter Denomination" name="denomination"
                                                        id="denomination-range"
                                                        value="{{ old('denomination', session('giftCardFormValues.denomination', $productDetails['minPrice'])) }}"
                                                        maxlength="6">
                                                    <small class="float-right form-text text-current font-xsss">Min:
                                                        ₹{{ $productDetails['minPrice'] }} Max:
                                                        ₹{{ $productDetails['maxPrice'] }}</small>
                                                    <div class="font-xssss fw-400 error-rec-deno-range text-danger mt-3">
                                                    </div>
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
                                                        value="{{ old('quantity', session('giftCardFormValues.quantity')) }}"
                                                        maxlength="2">
                                                    <small class="float-right form-text text-current font-xsss">Min: 1 Max:
                                                        10</small>
                                                    <div class="font-xssss fw-400 error-rec-qnty text-danger mt-3"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 mb-4 pl-lg-5">
                                            <h6 class="mb-3 fw-600 font-xss mt-2">Gift Send Option</h6>
                                            <div class="custom-control mr-4 custom-radio">
                                                <input type="radio" class="custom-control-input" id="customRadio"
                                                    name="gift_send_option" value="send_as_gift"
                                                    {{ old('gift_send_option', session('giftCardFormValues.gift_send_option')) == 'send_as_gift' ? 'checked' : '' }}>
                                                <label
                                                    class="custom-control-label small-size fw-500 text-grey-900 font-xssss"
                                                    for="customRadio">Send as Gift</label>
                                            </div>
                                            <div class="custom-control mr-0 custom-radio">
                                                <input type="radio" class="custom-control-input" id="customRadio1"
                                                    name="gift_send_option" value="buy_for_self"
                                                    {{ old('gift_send_option', session('giftCardFormValues.gift_send_option')) == 'buy_for_self' ? 'checked' : '' }}>
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
                                    </div>
                                    <div class="row justify-content-center mt-4 gifting-details">
                                        <h6 class="mb-3 fw-600 font-xss mt-2">Gifting Details</h6>
                                        <div class="col-12 col-lg-3 receiver-name">
                                            <input type="text" class="form-control mb-3 credentails-field"
                                                placeholder="Receiver Name" name="receiver_name" id="receiver-name"
                                                value="{{ old('receiver_name', session('giftCardFormValues.receiver_name')) }}">
                                            <span class="font-xssss fw-400 error-rec-name text-danger"></span>
                                        </div>
                                        <div class="col-lg-3 receiver-email">
                                            <input type="text" class="form-control mb-3 credentails-field"
                                                placeholder="Receiver Email" name="receiver_email" id="receiver-email"
                                                value="{{ old('receiver_email', session('giftCardFormValues.receiver_email')) }}">
                                            <span class="font-xssss fw-400 error-rec-email text-danger"></span>
                                        </div>
                                        <div class="col-12 col-lg-3 receiver-mobile">
                                            <input type="text" class="form-control mb-3 credentails-field"
                                                placeholder="Receiver Mobile Number" name="receiver_mobile"
                                                id="receiver-mobile"
                                                value="{{ old('receiver_mobile', session('giftCardFormValues.receiver_mobile')) }}">
                                            <span class="font-xssss fw-400 error-rec-mobile text-danger"></span>
                                        </div>
                                        <div class="col-12 col-lg-3 receiver-message">
                                            <input type="text" class="form-control mb-3 credentails-field"
                                                placeholder="Message for Receiver" name="receiver_msg" id="receiver-msg"
                                                value="{{ old('receiver_msg', session('giftCardFormValues.receiver_msg')) }}">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12">
                                            @if (Auth::check())
                                                <input type="submit"
                                                    class="form-control float-right h60 bg-current text-white text-center font-xss fw-500 border-0 p-0 mt-4 mb-4 w100 login-button-color"
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

                </form>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                // Before opening the login modal, save the form data to the session
                $('[data-target="#Modallogin"]').click(function() {
                    var formData = $('#giftCardPageForm').serialize();
                    $.ajax({
                        type: 'POST',
                        url: '{{ route('saveGiftCardFormValues') }}',
                        data: formData,
                        success: function(response) {
                            console.log('Form data saved successfully:', response);
                        },
                        error: function(response) {
                            console.error('Error saving form data:', response);
                        }
                    });
                });

                $('#Modallogin').on('hidden.bs.modal', function() {
                    // Check if the user is now authenticated
                    if (window.isAuthenticated) {
                        $('#giftCardPageForm').submit();
                    }
                });

                // Assuming you have a login function that handles the login process
                function handleLoginSuccess() {
                    // Set the global variable to true when login is successful
                    window.isAuthenticated = true;
                    // Close the login modal
                    $('#Modallogin').modal('hide');
                }

                // Call handleLoginSuccess() upon successful login
                // Add your login success logic here

                // Custom method to validate Indian mobile numbers
                $.validator.addMethod("indianMobile", function(value, element) {
                    return this.optional(element) || /^[6-9]\d{9}$/.test(value);
                }, "Please enter a valid mobile number.");

                // Custom method to validate denomination as a reasonable integer
                $.validator.addMethod("reasonableDenomination", function(value, element) {
                    return this.optional(element) || (/^\d{1,6}$/).test(value); // Allow only up to 6 digits
                }, "Please enter a valid denomination.");

                // Custom method to validate receiver name
                $.validator.addMethod("validReceiverName", function(value, element) {
                    return this.optional(element) || /^[a-zA-Z\s]+$/.test(value);
                }, "Please enter a valid name.");

                // Custom method to validate quantity as a reasonable integer without leading zeros
                $.validator.addMethod("validQuantity", function(value, element) {
                    return this.optional(element) || (/^[1-9]\d{0,2}$/).test(value); // Allow only 1 to 999
                }, "Please enter a valid quantity.");

                // Initialize the form validation
                $("#giftCardPageForm").validate({
                    rules: {
                        denomination: {
                            required: function(element) {
                                return $('input[name="denomination"]').is(':visible');
                            },
                            number: true,
                            min: {{ $productDetails['minPrice'] }},
                            max: {{ $productDetails['maxPrice'] }},
                            reasonableDenomination: true
                        },
                        quantity: {
                            required: true,
                            digits: true,
                            min: 1,
                            max: 10,
                            validQuantity: true

                        },
                        receiver_name: {
                            required: function(element) {
                                return $('input[name="gift_send_option"]:checked').val() === 'send_as_gift';
                            },
                            validReceiverName: true
                        },
                        receiver_email: {
                            required: function(element) {
                                return $('input[name="gift_send_option"]:checked').val() === 'send_as_gift';
                            },
                            email: true
                        },
                        receiver_mobile: {
                            required: function(element) {
                                return $('input[name="gift_send_option"]:checked').val() === 'send_as_gift';
                            },
                            indianMobile: true
                        }
                    },
                    messages: {
                        denomination: {
                            required: "Please select a denomination.",
                            number: "Please enter a valid denomination.",
                            min: "Denomination must be at least {{ $productDetails['minPrice'] }}.",
                            max: "Denomination cannot exceed {{ $productDetails['maxPrice'] }}.",
                            reasonableDenomination: "Please enter a reasonable denomination."
                        },
                        quantity: {
                            required: "Please enter a quantity.",
                            digits: "Please enter a valid quantity.",
                            min: "Quantity must be at least 1.",
                            max: "Quantity cannot exceed 10."
                        },
                        receiver_name: {
                            required: "Please enter the receiver's name.",
                            validReceiverName: "Please enter a valid name."
                        },
                        receiver_email: {
                            required: "Please enter the receiver's email address.",
                            email: "Please enter a valid email address."
                        },
                        receiver_mobile: {
                            required: "Please enter the receiver's mobile number.",
                            indianMobile: "Please enter a valid mobile number."
                        }
                    },
                    errorElement: 'span',
                    errorClass: 'text-danger',
                    highlight: function(element) {
                        $(element).addClass('is-invalid');
                    },
                    unhighlight: function(element) {
                        $(element).removeClass('is-invalid');
                    }
                });

                // Show/hide recipient details based on gift send option
                $('input[name="gift_send_option"]').change(function() {
                    if ($(this).val() === 'send_as_gift') {
                        $('.gifting-details').removeClass('d-none');
                    } else {
                        $('.gifting-details').addClass('d-none');
                    }
                });
            });
        </script>
    @endpush
@endsection
