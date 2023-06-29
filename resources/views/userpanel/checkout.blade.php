@extends('layouts.app')
@section('title')
    Amazepay | Checkout
@endsection
@section('content')
    <div class="container">
        <div class="faq-wrapper pt-4 pb-0">
            <h2 class="text-grey-900 fw-400 display1-size mb-4 pb-3 text-center">Checkout</h2>

            <form method="POST" name="customerData" action="{{ url('payment-process') }}" id="checkoutForm">
                @csrf
                <input type="hidden" name="redirect_url" value="{{ route('response_ccavenue') }}" />
                <input type="hidden" name="language" value="EN" />
                <input type="hidden" name="cancel_url" value="{{ url('payment-cancel') }}" />
                <div class="row">
                    <div class="col-lg-7">
                        <div class="table-content table-responsive mb-5 card border-0 bg-greyblue p-5">
                            <div class="page-title">
                                <h4 class="mont-font fw-500 font-xxl mb-5">Sender Details</h4>
                                <div class="row">
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-gorup">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">First Name</label>
                                            <input type="text" name="billing_name" class="form-control billingFormInput"
                                                value="{{ \Auth::user()->name }}">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-gorup">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">Email</label>
                                            <input type="text" name="billing_email" class="form-control billingFormInput"
                                                value="{{ \Auth::user()->email }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-gorup">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">Phone</label>
                                            <input type="text" name="billing_tel"
                                                class="form-control billingFormInput inputDiv">
                                            {{-- <i class="fa-solid fa-triangle-exclamation inputDivIcon failureIcon"></i>
                                            <i class="fa-sharp fa-solid fa-circle-check inputDivIcon successIcon"></i> --}}
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-gorup">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">Postcode</label>
                                            <input type="text" name="billing_zip" class="form-control billingFormInput">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-gorup">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">Address 1</label>
                                            <input type="text" name="billing_address"
                                                class="form-control billingFormInput">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-gorup">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">Address 2</label>
                                            <input type="text" name="billing_address_two"
                                                class="form-control billingFormInput">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-gorup">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">Town / City</label>
                                            <input type="text" name="billing_city" class="form-control billingFormInput">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-gorup">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">State</label>
                                            <input type="text" name="billing_state"
                                                class="form-control billingFormInput">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 cart-item">
                        <div class="row justify-content-center">
                            <div class="card border-0 mb-4">
                                <div class="card-header" id="headingTwo">
                                    <div class="row">
                                        <div class="col-lg-12 col-sm-12">
                                            <div class="row order-data">
                                                <div class="mont-font col-md-6 col-sm-4 col-xs-6 order-summary"><span>Order
                                                        Summary</span>
                                                </div>
                                                <div class="col-md-6 col-sm-4 col-xs-6"><a
                                                        href="{{ route('get-product-sku', ['slug' => $qsProd->sku]) }}"
                                                        class="float-right mont-font">Edit</a></div>
                                            </div>
                                            <div class="row cart-item-record">
                                                <div class="col-md-6 col-sm-4 col-xs-12">
                                                    <img class="cart-coupan-img"
                                                        src="{{ $qsProd['images']->small == null ? URL::asset('/images/hamburger.jpg') : $qsProd['images']->small }}"
                                                        alt="Avatar" style="width:100%;">
                                                </div>
                                                <div class="col-md-6 col-sm-4 col-xs-9">
                                                    <span class="product-name mont-font">{{ $qsProd->name }}</span>
                                                    <div class="row item-qty-subtotal">
                                                        <input type="hidden" name="sku"
                                                            value="{{ $qsProd->sku }}" />
                                                        <div class="col-md-4 col-sm-4 col-xs-6"><span>Qtn :
                                                                {{ $qsProd->prodData['quantity'] }}</span>
                                                        </div>
                                                        <input type="hidden" name="quantity"
                                                            value="{{ $qsProd->prodData['quantity'] }}" />
                                                        <div class="col-md-8 col-sm-4 col-xs-6"><span>Subtotal
                                                                :₹{{ $qsProd->prodData['denomination'] }}</span>
                                                        </div>
                                                        <input type="hidden" name="denomination"
                                                            value="{{ $qsProd->prodData['denomination'] }}" />
                                                        <input type="hidden" name="numericCode"
                                                            value="{{ $qsProd['currency']->numericCode }}" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row coupan-code">
                                                <div class="col-md-12 col-sm-4 col-xs-12">
                                                    <input type="text" class="coupan-code-input mont-font"
                                                        placeholder="Enter Coupan Code" id="coupan-code"><a
                                                        href="#"
                                                        id="apply-coupan"class="bg-current border-0 text-white apply-coupan-button mont-font ">Apply</a>
                                                    <span class="custLoaderDiv">
                                                        <img src="{{ URL::asset('images/preloader.svg') }}"
                                                            alt="" id="custLoaderImage"
                                                            class="custLoaderImage img-responsive hideLoader">
                                                    </span>
                                                    <div class="coupon-code-error-div"><span
                                                            class="error-coupon-code"></span></div>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="row total-amount">
                                                <div class="col-md-6 col-sm-4 col-xs-9 amount-text mont-font">
                                                    <span>Grand Total : </span>
                                                </div>
                                                <div class="col-md-6 col-sm-4 col-xs-3 amount mont-font">
                                                    <input type="hidden"
                                                        value="{{ $qsProd->prodData['denomination'] * $qsProd->prodData['quantity'] }}"
                                                        id="grand-amount"><span>₹{{ $qsProd->prodData['denomination'] * $qsProd->prodData['quantity'] }}</span>
                                                </div>
                                                <div class="coupan-code-amount" id="discountDiv">
                                                    <div
                                                        class="col-md-6 col-sm-4 col-xs-9 amount-text mont-font apply-coupan">
                                                        <span>Discount : </span>
                                                    </div>
                                                    <div
                                                        class="col-md-6 col-sm-4 col-xs-3 amount mont-font apply-coupan-amount">
                                                    </div>
                                                </div>
                                                <div class="col-md-6 col-sm-4 col-xs-9 amount-text mont-font"><span>Payable
                                                        Amount : </span>
                                                </div>
                                                <input type="hidden" name="currency" value="INR" />
                                                <input type="hidden" name="amount" class="hidden-total-payable-amount"
                                                    value="{{ $qsProd->prodData['denomination'] * $qsProd->prodData['quantity'] }}">
                                                <div
                                                    class="col-md-6 col-sm-4 col-xs-3 amount mont-font total-payable-amount">
                                                    <span>
                                                        ₹{{ $qsProd->prodData['denomination'] * $qsProd->prodData['quantity'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card shadow-none border-0">
                            <input
                                class="mont-font w-100 p-3 mt-3 mb-3 font-xsss text-center text-white bg-current rounded-lg text-uppercase fw-600 ls-3"
                                type="submit" value="Place Order">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @push('scripts')
        <script type="text/javascript">
            $(document).ready(function() {
                var storageData = JSON.parse(window.localStorage.getItem('data'));
                console.log(storageData);
            });
            $('.coupan-code-amount').css('display', 'none');
            $('#remove-coupan-code').css('display', 'none');

            // final step of order place api
            $('#place-order').click(function(e) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                e.preventDefault();
                var formData = new FormData();
                formData.append('coupan', $('#coupan-code').val());


                var type = "POST";
                var ajaxurl = "{{ url('/') }}";
                $.ajax({
                    type: type,
                    url: ajaxurl,
                    contentType: 'application/json',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(data) {
                        // debugger;
                        console.log(data);
                    },
                    error: function(data) {
                        console.log(data);
                    }
                });
                return false;
            });

            // Apply Coupan
            function couponCodeasd() {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                var formData = new FormData();
                formData.append('coupan', $('#coupan-code').val());
                formData.append('grand_total', $('#grand-amount').val());

                var type = "POST";
                var ajaxurl = "{{ url('/apply-coupan') }}";
                $.ajax({
                    type: type,
                    url: ajaxurl,
                    contentType: 'application/json',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(data) {
                        $('.apply-coupan-amount').text(data.coupan + '%');
                        $('.hidden-total-payable-amount').val(data.aftApplyCoupan);
                        $('.coupan-code-amount').css('display', 'contents');
                        $('#remove-coupan-code').css('display', 'contents');
                        $('.total-payable-amount').text('₹' + data.aftApplyCoupan);

                    },
                    error: function(data) {
                        console.log(data);
                    }
                });
            }
            // $('#apply-coupan').click( function(e) {

            //     return false;
            // });

            // on click apply coupon code starts here

            const applyButton = $('#apply-coupan');
            const loaderImage = $('#custLoaderImage');
            isHideLoaderPresent = loaderImage.hasClass('hideLoader');
            const inputFeild = $('#coupan-code');
            const discoutDiv = $('#discoutDiv');
            const coupanCodeInput = $('#coupan-code').val();
            // couponCode = false;
            couponCode = true;

            function showLoader() {
                if (isHideLoaderPresent) {
                    loaderImage.removeClass("hideLoader");
                    setTimeout(function() {
                        loaderImage.addClass('hideLoader');
                    }, 1000);
                }
            };

            function applyDiscount() {
                if (applyButton.html() === "Apply") {
                    couponCodeasd();
                    applyButton.html("Remove");
                    applyButton.addClass("red");
                    inputFeild.addClass("custDisabled");
                    $(".error-coupon-code").text('Coupon apllied successfully');
                    $(".error-coupon-code").addClass('greenColor');
                    console.log(applyButton.html());
                } else {
                    removeDiscount();
                    applyButton.html("Apply");
                    applyButton.removeClass("red");
                    inputFeild.removeClass("custDisabled");
                    discoutDiv.css("display", "none");
                    $(".error-coupon-code").css('display', 'none');
                };
            };




            applyButton.click(function(e) {
                e.preventDefault();
                if (!($("#coupan-code").val() == "")) { // value not empty
                    if (couponCode) {
                        showLoader();
                        applyDiscount();
                    } else {
                        $(".error-coupon-code").text('This is not a valid code');
                        $(".error-coupon-code").addClass('redColor');

                    }
                } else {
                    $(".error-coupon-code").text('Coupon code can not be BLANK');
                    $(".error-coupon-code").addClass('redColor');
                };
            });

            // on click apply button code ends here

            //  coupon code blank validation code starts here 



            //  coupon code blank validation code ends here
            // remove copuan code

            function removeDiscount() {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                var formData = new FormData();
                formData.append('grand_total', $('#grand-amount').val());


                var type = "POST";
                var ajaxurl = "{{ url('/remove-apply-coupan') }}";
                $.ajax({
                    type: type,
                    url: ajaxurl,
                    contentType: 'application/json',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(data) {
                        // debugger;
                        $('.apply-coupan-amount').text(data.coupan);
                        $('.coupan-code-amount').css('display', 'none');
                        $('#remove-coupan-code').css('display', 'none');
                        $('#coupan-code').val('');
                        $('.total-payable-amount').text('₹' + data.grandTotal);
                        $('.hidden-total-payable-amount').val(data.grandTotal);
                    },
                    error: function(data) {
                        console.log(data);
                    }
                });
            };

            //form validtation starts here

            $("#checkoutForm").validate({
                rules: {
                    billing_name: {
                        required: true,
                        lattersonly: true
                    },
                    billing_email: {
                        required: true,
                        email: true
                    },
                    billing_tel: {
                        required: true,
                        indianNumber: true,
                        minlength: 10,
                        maxlength: 10
                    },
                    billing_zip: {
                        required: true,
                        number: true,
                        minlength: 6,
                        maxlength: 6
                    },
                    billing_address: "required",
                    billing_address_two: "required",
                    billing_city: "required",
                    billing_state: "required"
                },
                messages: {
                    billing_name: {
                        required: "Please enter a name",
                        lattersonly: "Please enter a valid name"
                    },
                    billing_email: {
                        required: "Please enter an email",
                        email: "Please enter a valid email"
                    },
                    billing_tel: {
                        required: "Please enter your phone number",
                        indianNumber: "Please enter a valid Indian number"
                    },
                    billing_zip: {
                        required: "Please enter your zip code"
                    }
                }
            });

            jQuery.validator.addMethod('lattersonly', function(value, element) {
                return /^[a-zA-Z\s-]+$/.test(value);
            }, "Please enter a valid name");

            jQuery.validator.addMethod('indianNumber', function(value, element) {
                return /^[6-9]\d{9}$/.test(value);
            }, "Please enter a valid Indian number");
        </script>
    @endpush
@endsection
