@extends('layouts.app')

@section('content')
<div class="container py-5">
    @php
        $brand = json_decode($brands['original']['decrypted_data'], true)[0];
        $images = json_decode(str_replace("'", '"', $brand['Images']), true);
        $redeemSteps = $brand['RedeemSteps'];
    @endphp
<form method="POST" id="giftCardPageForm">
    <div class="card mb-4 shadow">
        <div class="row g-0">
            <div class="col-md-4">
                <img src="{{ $images['featured'] }}" class="img-fluid rounded-start" alt="{{ $brand['BrandName'] }}">
            </div>
            <div class="col-md-8">
                <div class="card-body">
                    <h2 class="card-title">{{ $brand['BrandName'] }}</h2>
                    <p><strong>Discount:</strong> {{ $brand['Discount'] }}%</p>
                    <p class="text-muted">{{ $brand['Category'] }}</p>
                    <p><strong>Price Range:</strong> ₹{{ $brand['minPrice'] }} - ₹{{ $brand['maxPrice'] }}</p>
                    <label>Enter Denomination</label>
                    <input type="number" name="denomination" min="100" max="10000">
                    <p><strong>Available Denominations:</strong> {{ $brand['DenominationList'] }}</p>
                    <p><strong>Stock Available:</strong> {{ $brand['StockAvailable'] ? 'Yes' : 'No' }}</p>

                    <label>Quantity</label>
                    <input type="number" name="quantity" min="1" max="10">

                    <select name="gift_send_option" id="sendAsGiftRadio">
                    <option>Send as Gift</option>
                    <option>Buy for Self</option>
                    </select>

                    <div class="row justify-content-center mt-4 gifting-details" style="display: block;">
                                        <h6 class="mb-3 fw-600 font-xss mt-2">Gifting Details</h6>
                                        <div class="row"> <!-- Added .row to group the .col-lg-* elements -->
                                            <div class="col-12 col-lg-3">
                    <input type="text" class="form-control mb-3 credentails-field" placeholder="Receiver Name" name="receiver_name" id="receiver-name" value="" >
                    <input type="text" class="form-control mb-3 credentails-field" placeholder="Receiver Email" name="receiver_email" id="receiver-email" value="" >
                    <input type="text" class="form-control mb-3 credentails-field" placeholder="Receiver Mobile Number" name="receiver_mobile" id="receiver-mobile" value="" >
                    <input type="text" class="form-control mb-3 credentails-field" placeholder="Message for Receiver" name="receiver_msg" id="receiver-msg" value="" >
                                            </div>
                                        </div>
                    </div>
                    <div class="row g-0">
                    <a href="#" class="form-control h60 bg-current float-right text-white text-center font-xss fw-500 border-0 p-0 mt-4 mb-4 w250 login-button-color" data-toggle="modal" data-target="#Modallogin">
                                                    Go to Checkout Page
                                                </a>
                    </div>
<div class="tabs">

                                    
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
                                            @endforeach
                                        </div>
                                    </div>
                                    

                                    
                                                                            <input type="radio" name="tabs" id="tabtwo">
                                        <label for="tabtwo">Description</label>
                                        <div class="tab p-3 font-xsss text-black">
                                            <p>{!! nl2br(strip_tags($brand['Description'])) !!}</p>
                                        </div>
                                    
                                    

                                                                            <input type="radio" name="tabs" id="tabthree">
                                        <label for="tabthree">Terms &amp; Condition</label>
                                        <div class="tab term-condition p-3 font-xsss termsConditions text-black">
                                            <p>{!! nl2br(strip_tags($brand['TnC'])) !!}</p>
                                        </div>
                                    
                                </div>
                    
                </div>
            </div>
        </div>
    </div>
<p> <a href="{{ url('/stores/filter') }}" class="btn btn-info btn-lg text-white">See Stores</a>

    <h4>Important Instructions</h4>
    <ul class="list-group mb-4">
        @foreach($brand['ImportantInstruction'] as $instruction)
            <li class="list-group-item">{{ $instruction }}</li>
        @endforeach
    </ul>
</div>
</form>
@endsection
 @push('scripts')
    <script type="text/javascript">
        $(document).ready(function() {

        toggleReceiverFields();

                $('input[name="gift_send_option"]').change(function() {
                    toggleReceiverFields();
                });

                function toggleReceiverFields() {
                    // Check which radio option is selected
                    if ($('#sendAsGiftRadio').is(':checked')) {
                        // Show gifting details if "Send as Gift" is selected
                        $('.gifting-details').show();
                    } else {
                        // Hide gifting details if "Buy for Self" is selected
                        $('.gifting-details').hide();

                        // Clear receiver-related form fields when "Buy for Self" is selected

                    }
                }


  let debounceTimeout;
  let isAuthenticated = false; // Assume the user is not authenticated by default

                // Debounce function to limit the rate of AJAX requests
                function debounce(func, delay) {
                    clearTimeout(debounceTimeout);
                    debounceTimeout = setTimeout(func, delay);
                }
                
    function updateSessionData() {

                    var formData = {
                        vd_discount: {{ $brand['Discount'] }},
                        vd_denomination: $('input[name="denomination"]').val(),
                        vd_quantity: $('input[name="quantity"]').val(),
                        vd_gift_send_option: $('select[name="gift_send_option"]').val(),
                        vd_receiver_name: $('input[name="receiver_name"]').val(),
                        vd_receiver_email: $('input[name="receiver_email"]').val(),
                        vd_receiver_mobile: $('input[name="receiver_mobile"]').val(),
                        vd_receiver_msg: $('input[name="receiver_msg"]').val()
                    }
                
                $.ajax({
                        url: '{{ route('updateSessionData') }}', // Replace with your Laravel route
                        method: 'POST',
                        data: formData,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            console.log('Session data updated successfully', formData);
                        },
                        error: function(xhr, status, error) {
                            console.error('Error updating session data:', error);
                        }
                    });
                }
    
    function saveGiftCardFormData(callback) {
                    var formData = $('#giftCardPageForm').serialize(); // Serialize the form data

                    // AJAX POST request to save data to the session
                    $.ajax({
                        type: 'POST',
                        url: '{{ route('save-gift-card-form') }}',
                        data: formData,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {

                            if (callback) callback(); // Call the callback if provided
                        },
                        error: function(response) {
                            console.error('Error saving form data:', response);
                        }
                    });
                }

                function handleLoginSuccess() {
                    // Set the global variable to true when login is successful
                    window.isAuthenticated = true;
                    // Close the login modal
                    $('#Modallogin').modal('hide');
                }


                      // Event listener for changes in form fields to auto-save data
                $('input[name="denomination"], input[name="quantity"], input[name="gift_send_option"], input[name="receiver_name"], input[name="receiver_email"], input[name="receiver_mobile"], input[name="receiver_msg"]')
                    .on('input change', function() {
                        debounce(saveGiftCardFormData, 500); // Save data with a 500ms debounce
                    });

                $('[data-target="#Modallogin"]').click(function() {
                    saveGiftCardFormData(function() {
                        // After saving the data, check if the user is authenticated
                        if (isAuthenticated) {
                            $('#giftCardPageForm').submit(); // Submit the form if authenticated
                        } else {
                            $('#Modallogin').modal('show'); // Show the login modal if not authenticated
                        }
                    });
                });

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
                            required: true,
                            number: true,
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
                            required: "Please enter a denomination.",
                            number: "Please enter a valid denomination.",
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

        @if (!auth()->check())
                // Show login modal if user is not authenticated
                setTimeout(function() {
                    $('#Modallogin').modal('show');
                }, 1000);
            @endif

</script>