@extends('layouts.app')
@section('title')
    Amazepay | Checkout
@endsection
@section('content')
    <div class="container">

        <div class="faq-wrapper pt-4 pb-0">
            <h2 class="text-grey-900 fw-400 display1-size mb-4 pb-3 text-center">Checkout</h2>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

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
                                        <div class="form-group">

                                            <label class="mont-font fw-500 font-xsss" for="comment-name">Full Name</label>
                                            <input type="text" name="billing_name" class="form-control billingFormInput"
                                                value="{{ old('billing_name', $checkoutData['billing_name'] ?? (Auth::user()->name ?? '')) }}"
                                                maxlength="255">
                                            @error('billing_name')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">Email</label>
                                            <input type="text" name="billing_email" class="form-control billingFormInput"
                                                value="{{ old('billing_email', $checkoutData['billing_email'] ?? (Auth::user()->email ?? '')) }}"
                                                maxlength="255">
                                            @error('billing_email')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">Phone</label>
                                            <input type="text" name="billing_tel"
                                                class="form-control billingFormInput inputDiv"
                                                value="{{ old('billing_tel', $checkoutData['billing_tel'] ?? (Auth::user()->mobile ?? '')) }}"
                                                maxlength="10">
                                            @error('billing_tel')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">Postcode</label>
                                            <input type="text" name="billing_zip" class="form-control billingFormInput"
                                                value="{{ old('billing_zip', $checkoutData['billing_zip'] ?? '') }}"
                                                maxlength="6">
                                            @error('billing_zip')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">Address 1</label>
                                            <input type="text" name="billing_address"
                                                class="form-control billingFormInput"
                                                value="{{ old('billing_address', $checkoutData['billing_address'] ?? '') }}"
                                                maxlength="255">
                                            @error('billing_address')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">Address 2</label>
                                            <input type="text" name="billing_address_two"
                                                class="form-control billingFormInput"
                                                value="{{ old('billing_address_two', $checkoutData['billing_address_two'] ?? '') }}"
                                                maxlength="255">
                                            @error('billing_address_two')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">Town / City</label>
                                            <input type="text" name="billing_city" class="form-control billingFormInput"
                                                value="{{ old('billing_city', $checkoutData['billing_city'] ?? '') }}"
                                                maxlength="255">
                                            @error('billing_city')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">State</label>
                                            <input type="text" name="billing_state"
                                                class="form-control billingFormInput"
                                                value="{{ old('billing_state', $checkoutData['billing_state'] ?? '') }}"
                                                maxlength="255">
                                            @error('billing_state')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">Country</label>
                                            <input type="text" name="billing_country"
                                                class="form-control billingFormInput"
                                                value="{{ old('billing_country', $checkoutData['billing_country'] ?? '') }}"
                                                maxlength="255">
                                            @error('billing_country')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-500 font-xsss" for="comment-name">GST Number
                                                (Optional)</label>
                                            <input type="text" name="billing_gst_number"
                                                class="form-control billingFormInput"
                                                value="{{ old('billing_gst_number', $checkoutData['billing_gst_number'] ?? '') }}"
                                                maxlength="15">
                                            @error('billing_gst_number')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
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
                                                <div class="col-md-6 col-sm-4 col-xp-6"><a
                                                        href="{{ route('get-product-by-slug', ['slug' => $qsProd->slug]) }}"
                                                        class="float-right mont-font">Edit</a></div>
                                            </div>
                                            <div class="row cart-item-record">
                                                <div class="col-md-5 col-sm-4 col-xs-12">
                                                    <img class="cart-coupan-img"
                                                        src="{{ $qsProd['images']->small == null ? URL::asset('/images/hamburger.jpg') : $qsProd['images']->small }}"
                                                        alt="Avatar" style="width:100%;">
                                                </div>
                                                <div class="col-md-7 col-sm-4 col-xs-9 ">
                                                    <span class="product-name mont-font">{{ $qsProd->name }}</span>
                                                    <div class="row item-qty-subtotal">
                                                        <div class="col-md-8 col-sm-4 col-xs-6"><span>Denomination
                                                            : ₹{{ $qsProd->prodData['denomination'] }}</span>
                                                    </div>
                                                    <input type="hidden" name="denomination"
                                                        value="{{ $qsProd->prodData['denomination'] }}" />
                                                    <input type="hidden" name="numericCode"
                                                        value="{{ $qsProd['currency']->numericCode }}" />
                                                        <input type="hidden" name="sku"
                                                            value="{{ $qsProd->sku }}" />
                                                        <div class="col-md-12 col-sm-4 col-xs-6"><span>Qty :
                                                                {{ $qsProd->prodData['quantity'] }}</span>
                                                        </div>
                                                        <input type="hidden" name="quantity"
                                                            value="{{ $qsProd->prodData['quantity'] }}" />

                                                    </div>
                                                </div>
                                            </div>

                                            <hr>
                                            <div class="row total-amount justify-content-center">
                                                <div class="row">
                                                    <div class="col-md-6 col-sm-4 col-xs-9 amount-text mont-font">
                                                        <span>Grand Total : </span>
                                                    </div>
                                                    <div class="col-md-6 col-sm-4 col-xs-3 amount mont-font">
                                                        <input type="hidden"
                                                            value="{{ $qsProd->prodData['denomination'] * $qsProd->prodData['quantity'] }}"
                                                            id="grand-amount"><span>₹{{ $qsProd->prodData['denomination'] * $qsProd->prodData['quantity'] }}</span>
                                                    </div>
                                                </div>

                                                <div class="row coupan-code-amount" id="discountDiv">
                                                    <div
                                                        class="col-md-6 col-sm-4 col-xs-9 amount-text mont-font apply-coupan">
                                                        <span>Discount : </span>
                                                    </div>
                                                    <div
                                                        class="col-md-6 col-sm-4 col-xs-3 amount mont-font apply-coupan-amount">
                                                        ₹
                                                        {{ $qsProd->prodData['denomination'] * $qsProd->prodData['quantity'] * ($qsProd->discount_percentage / 100) }}
                                                    </div>
                                                </div>
                                                <div class="row ">
                                                    <div class="col-md-6 col-sm-4 col-xs-9 amount-text mont-font">
                                                        <span>Payable
                                                            Amount : </span>
                                                    </div>

                                                    <input type="hidden" name="currency" value="INR" />
                                                    <input type="hidden" name="amount"
                                                        class="hidden-total-payable-amount"
                                                        value="{{ $qsProd->prodData['denomination'] * $qsProd->prodData['quantity'] - $qsProd->prodData['denomination'] * $qsProd->prodData['quantity'] * ($qsProd->discount_percentage / 100) }}">
                                                    <div
                                                        class="col-md-6 col-sm-4 col-xs-3 amount mont-font total-payable-amount">
                                                        <span>₹{{ $qsProd->prodData['denomination'] * $qsProd->prodData['quantity'] - $qsProd->prodData['denomination'] * $qsProd->prodData['quantity'] * ($qsProd->discount_percentage / 100) }}</span>
                                                    </div>
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
                                type="submit" value="Place Order" id="placeOrder">
                        </div>
                        <div class="tex-center justify-content-center">
                            <img src="{{ URL::asset('images/preloader.svg') }}" alt="" id="custLoaderImage"
                                class="custLoaderImage img-responsive hideLoader text-center">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @push('scripts')
        <script type="text/javascript">
            $(document).ready(function() {





                function updateSessionData() {

                    console.log(123);
                    var formData = {
                        billing_name: $('input[name="billing_name"]').val(),
                        billing_email: $('input[name="billing_email"]').val(),
                        billing_tel: $('input[name="billing_tel"]').val(),
                        billing_zip: $('input[name="billing_zip"]').val(),
                        billing_address: $('input[name="billing_address"]').val(),
                        billing_address_two: $('input[name="billing_address_two"]').val(),
                        billing_city: $('input[name="billing_city"]').val(),
                        billing_state: $('input[name="billing_state"]').val(),
                        billing_country: $('input[name="billing_country"]').val(),
                        billing_gst_number: $('input[name="billing_gst_number"]').val(),
                        // Add other form fields as needed
                    };

                    // AJAX POST request to Laravel backend to update session data
                    $.ajax({
                        url: '{{ route('updateSessionData') }}', // Replace with your Laravel route
                        method: 'POST',
                        data: formData,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            console.log('Session data updated successfully');
                        },
                        error: function(xhr, status, error) {
                            console.error('Error updating session data:', error);
                        }
                    });
                }

                // Bind updateSessionData function to the input event of input fields
                $('input[name="billing_name"], input[name="billing_email"], input[name="billing_tel"], input[name="billing_zip"], input[name="billing_address"], input[name="billing_address_two"], input[name="billing_city"], input[name="billing_state"], input[name="billing_country"], input[name="billing_gst_number"]')
                    .on('input', function() {
                        updateSessionData();
                    });


                // Optionally bind updateSessionData function to form submit event
                $('form').submit(function(event) {
                    updateSessionData();
                });



                var storageData = JSON.parse(window.localStorage.getItem('data'));

                $("#checkoutForm").validate({
                    rules: {
                        billing_name: {
                            required: true,
                            lattersonly: true,
                            maxlength: 30
                        },
                        billing_email: {
                            required: true,
                            email: true,
                            maxlength: 50
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
                        billing_address: {
                            required: true,
                            maxlength: 50
                        },
                        billing_address_two: {
                            required: true,
                            maxlength: 50
                        },
                        billing_city: {
                            required: true,
                            maxlength: 40
                        },
                        billing_state: {
                            required: true,
                            maxlength: 40
                        },
                        billing_country: {
                            required: true,
                            maxlength: 40
                        },
                        billing_gst_number: {
                            maxlength: 15
                        }
                    },
                    messages: {
                        billing_name: {
                            required: "Please enter a name",
                            lattersonly: "Please enter a valid name",
                            maxlength: "Name cannot exceed 255 characters"
                        },
                        billing_email: {
                            required: "Please enter an email",
                            email: "Please enter a valid email",
                            maxlength: "Email cannot exceed 50 characters"
                        },
                        billing_tel: {
                            required: "Please enter your phone number",
                            indianNumber: "Please enter a valid Indian number",
                            minlength: "Phone number must be 10 digits",
                            maxlength: "Phone number must be 10 digits"
                        },
                        billing_zip: {
                            required: "Please enter your zip code",
                            number: "Please enter a valid zip code",
                            minlength: "Zip code must be 6 digits",
                            maxlength: "Zip code must be 6 digits"
                        },
                        billing_address: {
                            required: "Please enter your address",
                            maxlength: "Address cannot exceed 50 characters"
                        },
                        billing_address_two: {
                            required: "Please enter your address",
                            maxlength: "Address cannot exceed 50 characters"
                        },
                        billing_city: {
                            required: "Please enter your city",
                            maxlength: "City cannot exceed 40 characters"
                        },
                        billing_state: {
                            required: "Please enter your state",
                            maxlength: "State cannot exceed 40 characters"
                        },
                        billing_country: {
                            required: "Please enter your country",
                            maxlength: "Country cannot exceed 40 characters"
                        },
                        billing_gst_number: {
                            maxlength: "GST number cannot exceed 15 characters"
                        }
                    },
                    submitHandler: function(form) {
                        form.submit();
                    }
                });

                jQuery.validator.addMethod('lattersonly', function(value, element) {
                    return /^[a-zA-Z\s-]+$/.test(value);
                }, "Please enter a valid name");

                jQuery.validator.addMethod('indianNumber', function(value, element) {
                    return /^[6-9]\d{9}$/.test(value);
                }, "Please enter a valid Indian number");
            });
        </script>
    @endpush
@endsection
