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

        <form method="POST" name="customerData" action="{{ url('payment-process') }}" id="checkoutForm">
            @csrf
            <div class="row">
                <div class="col-lg-7">
                    <div class="table-content table-responsive mb-5 card border-0 bg-greyblue p-5">
                        <div class="page-title">
                            <h4 class="mont-font fw-500 font-xxl mb-5">Sender Details</h4>
                            <div class="row">
                                @foreach(['Name' => 'billing_name', 'Email' => 'billing_email', 'Phone' => 'billing_tel', 'Postcode' => 'billing_zip'] as $label => $name)
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-500 font-xsss">{{ $label }}</label>
                                            <input type="text" name="{{ $name }}" class="form-control billingFormInput"
                                                value="{{ old($name, $checkoutData[$name] ?? (Auth::user()->$name ?? '')) }}"
                                                maxlength="{{ $name == 'billing_tel' ? '10' : '255' }}">
                                            @error($name)
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                @endforeach
                                @foreach(['Address 1' => 'billing_address', 'Address 2' => 'billing_address_two', 'Town / City' => 'billing_city', 'State' => 'billing_state', 'Country' => 'billing_country', 'GST Number (Optional)' => 'billing_gst_number'] as $label => $name)
                                    <div class="col-lg-6 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-500 font-xsss">{{ $label }}</label>
                                            <input type="text" name="{{ $name }}" class="form-control billingFormInput"
                                                value="{{ old($name, $checkoutData[$name] ?? '') }}"
                                                maxlength="{{ $name == 'billing_gst_number' ? '15' : '255' }}">
                                            @error($name)
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    // Fetch product details and calculate total amount and discount
                    $denomination = $qsProd->prodData['denomination'];
                    $quantity = $qsProd->prodData['quantity'];
                    $discountPercentage = $qsProd->discount_percentage;

                    // Calculate total and discounted amounts
                    $totalAmount = $denomination * $quantity;
                    $discountAmount = $totalAmount * ($discountPercentage / 100);
                    $totalPayableAmountAfterDiscount = $totalAmount - $discountAmount;

                    // Store the values in the session
                    session([
                        'denomination' => $denomination,
                        'quantity' => $quantity,
                        'discount_percentage' => $discountPercentage,
                        'total_amount' => $totalAmount,
                        'discount_amount' => $discountAmount,
                        'total_payable_amount_after_discount' => $totalPayableAmountAfterDiscount,
                    ]);
                @endphp

                <div class="col-lg-5 cart-item">
                    <div class="row justify-content-center">
                        <div class="card border-0 mb-4">
                            <div class="card-header" id="headingTwo">
                                <div class="row">
                                    <div class="col-lg-12 col-sm-12">
                                        <div class="row order-data">
                                            <div class="mont-font col-md-6 col-sm-4 col-xs-6 order-summary">
                                                <span>Order Summary</span>
                                            </div>
                                            <div class="col-md-6 col-sm-4 col-xp-6">
                                                <a href="{{ route('get-product-by-slug', ['slug' => $qsProd->slug]) }}"
                                                    class="float-right mont-font">Edit</a>
                                            </div>
                                        </div>
                                        <div class="row cart-item-record">
                                            <div class="col-md-5 col-sm-4 col-xs-12">
                                                <img class="cart-coupan-img"
                                                    src="{{ $qsProd['images']->small ?? URL::asset('/images/hamburger.jpg') }}"
                                                    alt="Avatar" style="width:100%;">
                                            </div>
                                            <div class="col-md-7 col-sm-4 col-xs-12">
                                                <span class="product-name mont-font">{{ $qsProd->name }}</span>
                                                <div class="row item-qty-subtotal">
                                                    <div class="col-12">
                                                        <span>Denomination: ₹{{ session('denomination') }}</span>
                                                    </div>
                                                    <div class="col-12">
                                                        <span>Qty: {{ session('quantity') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <hr>

                                        <div class="row total-amount justify-content-center">
                                            <div class="row">
                                                <div class="col-md-6 col-sm-4 col-xs-9 amount-text mont-font">
                                                    <span>Grand Total:</span>
                                                </div>
                                                <div class="col-md-6 col-sm-4 col-xs-3 amount mont-font">
                                                    <span>₹{{ session('total_amount') }}</span>
                                                </div>
                                            </div>

                                            <div class="row coupan-code-amount" id="discountDiv">
                                                <div class="col-md-6 col-sm-4 col-xs-9 amount-text mont-font apply-coupan">
                                                    <span>Discount:</span>
                                                </div>
                                                <div class="col-md-6 col-sm-4 col-xs-3 amount mont-font apply-coupan-amount">
                                                    ₹{{ session('discount_amount') }}
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 col-sm-4 col-xs-9 amount-text mont-font">
                                                    <span>Payable Amount:</span>
                                                </div>
                                                <div class="col-md-6 col-sm-4 col-xs-3 amount mont-font">
                                                    <span>₹{{ session('total_payable_amount_after_discount') }}</span>
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
                </div>
            </div>
        </form>
    </div>
</div>

    @push('scripts')
        <script type="text/javascript">
            $(document).ready(function() {
                let debounceTimeout;

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

                // Debounced function
                function debouncedUpdateSessionData() {
                    clearTimeout(debounceTimeout);
                    debounceTimeout = setTimeout(updateSessionData,
                        600); // Adjust the delay as needed (600ms in this example)
                }

                // Bind debouncedUpdateSessionData function to the input event of input fields
                $('input[name="billing_name"], input[name="billing_email"], input[name="billing_tel"], input[name="billing_zip"], input[name="billing_address"], input[name="billing_address_two"], input[name="billing_city"], input[name="billing_state"], input[name="billing_country"], input[name="billing_gst_number"]')
                    .on('input', function() {
                        debouncedUpdateSessionData();
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
