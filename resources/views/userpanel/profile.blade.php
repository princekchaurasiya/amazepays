@extends('layouts.app')

@section('title')
    Amazepay | Profile
@endsection

@section('content')
    <div class="dashboard-wrapper bg-greylight">
        <div class="container">
            <div class="row">
                <div class="col-lg-3">
                    <div class="dashboard-nav bg-white rounded-lg shadow-xs">
                        <a href="#" class="dash-menu d-none d-block-md">
                            <i class="ti-package font-sm mr-2"></i> Menu <i class="ti-angle-down font-xsss float-right"></i>
                        </a>
                        <ul class="dash-menu-ul">
                            <li class="d-block rounded-lg active"><a href="{{ route('profile') }}">
                                    <i class="ti-user font-sm"></i><span> Profile</span></a></li>
                            <li class="d-block rounded-lg"><a href="{{ route('my-order') }}">
                                    <i class="ti-package font-sm"></i><span> My Order</span></a></li>
                            <li class="d-block rounded-lg"><a href="{{ route('change-password') }}">
                                    <i class="ti-lock font-sm"></i><span> Change Password</span></a></li>
                            <li class="d-block rounded-lg"><a href="{{ route('userLogOut') }}">
                                    <i class="ti-power-off font-sm"></i><span> Logout</span></a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-9">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
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

                    <div class="dashboard-tab cart-wrapper p-5 bg-white rounded-lg shadow-xs">
                        @if (Auth::check())
                            <form id="profileForm">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-12 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-600 font-xsss" for="name">Name</label>
                                            <input type="text" name="name" id="profileName" class="form-control"
                                                value="{{ Auth::user()->name }}" required>
                                            <span class="error-message text-danger" id="error-profileName"></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-600 font-xsss" for="email">Email</label>
                                            <input type="email" name="email" id="profileEmail" class="form-control"
                                                value="{{ Auth::user()->email }}" required>
                                            <span class="error-message text-danger" id="error-profileEmail"></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 mb-3">
                                        <div class="form-group">
                                            <label class="mont-font fw-600 font-xsss" for="mobile">Mobile No.</label>
                                            <input type="text" id="profileMobile" name="mobile" class="form-control"
                                                value="{{ Auth::user()->mobile }}" required>
                                            <span class="error-message text-danger" id="error-profileMobile"></span>
                                        </div>
                                    </div>

                                    <div class="alert alert-success otp-success-message" style="display: none;"></div>

                                    <div class="col-lg-12 mb-3">
                                        <button type="button"
                                            class="profileUpdateSendOTPButton form-control rounded-lg h20 float-left bg-current text-white text-center font-xss fw-500 border-2 border-0 p-0 w125">
                                            Send OTP
                                        </button>
                                    </div>
                                    <div class="col-lg-12 mb-3 otp-section" style="display: none;">
                                        <div class="form-group">
                                            <label class="mont-font fw-600 font-xsss" for="otp">Enter OTP</label>
                                            <input type="text" name="otp" maxlength="6" id="otp"
                                                class="form-control" placeholder="Enter OTP" required>
                                            <span class="error-message text-danger" id="error-otp"></span>
                                            <div class="otp-error-message" style="margin-top: 10px;"></div>
                                        </div>
                                        <button type="submit"
                                            class="form-control rounded-lg h20 float-left profileUpdateVerifyOTP text-white text-center font-xss fw-500 border-2 border-0 p-0 bg-current w150">
                                            Verify OTP
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script type="text/javascript">
            $(document).ready(function() {
                console.log("Document is ready");
                $.validator.addMethod("letterswithspaces", function(value, element) {
                    return this.optional(element) || /^[a-zA-Z\s]*$/.test(value);
                }, "Please enter only letters and spaces.");

                // Initialize validation plugin
                $('#profileForm').validate({
                    rules: {
                        name: {
                            required: true,
                            minlength: 3,
                            maxlength: 50,
                            letterswithspaces: true
                        },
                        email: {
                            required: true,
                            email: true
                        },
                        mobile: {
                            required: true,
                            digits: true,
                            minlength: 10,
                            maxlength: 10
                        },
                        otp: {
                            required: true,
                            digits: true,
                            minlength: 6,
                            maxlength: 6
                        }
                    },
                    messages: {
                        name: {
                            required: "Please enter your name",
                            minlength: "Name must be at least 3 characters long",
                            maxlength: "Name must not exceed 50 characters",
                        },
                        email: {
                            required: "Please enter your email",
                            email: "Please enter a valid email address"
                        },
                        mobile: {
                            required: "Please enter your phone number",
                            digits: "Please enter only digits",
                            minlength: "Phone number must be exactly 10 digits",
                            maxlength: "Phone number must be exactly 10 digits"
                        },
                        otp: {
                            required: "Please enter the OTP",
                            digits: "OTP must be a 6-digit number",
                            minlength: "OTP must be exactly 6 digits",
                            maxlength: "OTP must be exactly 6 digits"
                        }
                    },
                    errorPlacement: function(error, element) {
                        console.log("Error for:", element.attr('name'), "Error message:", error.text());
                        error.insertAfter(element);
                    },
                    submitHandler: function(form) {
                        console.log("Form is valid. Submitting...");
                        form.submit();
                    }
                });

                // Hide OTP section initially
                $('.otp-section').hide();

                // Show OTP section after sending OTP
                $('.profileUpdateSendOTPButton').click(function() {

                    if ($('#profileForm').valid()) {
                        var destination = $('#profileMobile').val(); // Using destination as mobile number

                        // Reset previous error message
                        $('#error-profileMobile').text('');

                        if (destination) {
                            $.ajax({
                                type: 'POST',
                                url: '{{ route('check-mobile-number') }}',
                                data: {
                                    _token: '{{ csrf_token() }}',
                                    destination: destination
                                },
                                success: function(response) {
                                    if (response.status === 'available') {
                                        sendOtp(destination); // Call the send OTP function
                                    } else {
                                        $('#error-profileMobile').text(
                                            'Please try a different mobile number; the current one is already in use.'
                                        );
                                    }
                                },
                                error: function() {
                                    alert('Error while checking mobile number.');
                                }
                            });
                        } else {
                            $('#error-profileMobile').text('Please enter your mobile number.');
                        }

                    }

                });

                // Function to send OTP
                function sendOtp(destination) {
                    console.log("Sending OTP to", destination);

                    $.ajax({
                        type: 'POST',
                        url: '{{ route('profile-update-send-otp') }}',
                        data: {
                            _token: '{{ csrf_token() }}',
                            destination: destination
                        },
                        success: function(response) {
                            console.log("OTP sent successfully:", response);
                            $('.otp-success-message').text('OTP has been sent to ' + destination).show();
                            $('.otp-section').show();
                            $('.profileUpdateSendOTPButton').hide();
                            setTimeout(function() {
                                $('.otp-success-message').fadeOut();
                            }, 40000);
                        },
                        error: function(xhr) {
                            console.error("Failed to send OTP:", xhr);
                            $('.otp-error-message').text(
                                'An error occurred while sending OTP. Please try again.').show();
                        }
                    });
                }

                // Handle profile form submission with OTP verification
                console.log("Attaching submit handler");
                $('#profileForm').on('submit', function(e) {
                    e.preventDefault(); // Prevent the default form submission
                    console.log("Submitting form with OTP verification");
                    debugger; // Check form data before sending
                    // Clear any previous messages
                    $('.alert').remove();

                    // Get form values
                    var otp = $('#otp').val();
                    var mobile = $('#profileMobile').val();
                    var name = $('#profileName').val();
                    var email = $('#profileEmail').val();

                    // Debugger statement to inspect form values
                    console.log("Form data:", {
                        otp,
                        mobile,
                        name,
                        email
                    });


                    // Prepare the data object
                    var dataToSend = {
                        _token: '{{ csrf_token() }}', // Ensure the CSRF token is correct
                        otp: otp,
                        mobile: mobile,
                        name: name,
                        email: email,
                    };

                    $.ajax({
                        type: 'POST',
                        url: '{{ route('update-profile') }}', // Ensure this resolves correctly
                        data: JSON.stringify(dataToSend), // Convert data to JSON
                        dataType: 'json',
                        contentType: 'application/json', // Set content type
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}' // Set CSRF token in headers
                        },
                        success: function(response) {
                            console.log("Server response:", response);


                            if (response.status === 'success') {
                                console.log("Profile update successful.");
                                $('#profileForm').before(
                                    '<div class="alert alert-success">Profile updated successfully!</div>'
                                );
                            } else if (response.status === 'error') {
                                $('#otp').val(''); // Clear OTP input
                                $('#error-otp').text(response.msg ||
                                    'An error occurred. Please try again.');
                            }
                        },
                        error: function(xhr) {
                            console.error("Error:", xhr);
                            $('.alert').remove();
                            $('#profileForm').before(
                                '<div class="alert alert-danger">Error verifying OTP. Please try again.</div>'
                            );
                        }
                    });
                    console.log(12334);
                });




            });
        </script>
    @endpush
@endsection
