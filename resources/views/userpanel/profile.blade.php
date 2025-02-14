@extends('layouts.app')

@section('title')
    Amazepay | Profile
@endsection

@section('content')
    <div class="dashboard-wrapper bg-greylight">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 d-none d-lg-block">
                    <div class="dashboard-nav bg-white rounded-lg shadow-xs">
                        <ul class="dash-menu-ul">
                            <li class="d-block rounded-lg active"><a href="{{ route('profile') }}"><i
                                        class="ti-user font-sm"></i><span> Profile</span></a></li>
                            <li class="d-block rounded-lg"><a href="{{ route('my-order') }}"><i
                                        class="ti-package font-sm"></i><span> My Order</span></a></li>
                            <li class="d-block rounded-lg"><a href="{{ route('change-password') }}"><i
                                        class="ti-lock font-sm"></i><span> Change Password</span></a></li>
                            <li class="d-block rounded-lg"><a href="{{ route('userLogOut') }}"><i
                                        class="ti-power-off font-sm"></i><span> Logout</span></a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-9">
                    <!-- Session Messages -->
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
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

                    <!-- Profile Form -->
                    <div class="dashboard-tab cart-wrapper p-5 bg-white rounded-lg shadow-xs">
                        <form id="profileForm" method="POST" action="{{ url('/update-profile') }}" novalidate="novalidate">
                            @csrf
                            <div class="row">
                                <!-- Name Field -->
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label class="mont-font fw-600 font-xsss" for="name">Name</label>
                                        <input type="text" name="name" id="profileName" class="form-control"
                                            value="{{ Auth::user()->name }}" required="">
                                        <span class="error-message text-danger" id="error-profileName"></span>
                                    </div>
                                </div>

                                <!-- Email Field -->
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label class="mont-font fw-600 font-xsss" for="email">Email</label>
                                        <input type="email" name="email" id="profileEmail" class="form-control"
                                            value="{{ Auth::user()->email }}" required="">
                                        <span class="error-message text-danger" id="error-profileEmail"></span>
                                    </div>
                                </div>

                                <!-- Mobile Number Field -->
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label class="mont-font fw-600 font-xsss" for="mobile">Mobile No.</label>
                                        <div class="input-group">
                                            <input type="text" id="profileMobile" name="mobile" class="form-control" value="{{ Auth::user()->mobile }}">
                                            <button type="button" class="sendOTPButton btn btn-secondary" style="display: none;">Send OTP</button>
                                            <button type="button" class="resendOTPButton btn btn-secondary" style="display: none;" disabled>
                                                Resend OTP (<span id="timer">60</span>)
                                            </button>
                                        </div>
                                        <label id="profileMobile-error" class="error text-danger" for="profileMobile" style="display: none;"></label>


                                    </div>
                                    <span class="error-message text-danger" id="error-profileMobile"></span>
                                </div>
                                <!-- Success Message for OTP -->
                                <div class="alert alert-success otp-success-message" style="display: none;"></div>
                                <!-- OTP Field (Initially Hidden) -->
                                <div class="col-lg-12 mb-3 otp-section" style="display: none;">
                                    <div class="form-group">
                                        <label class="mont-font fw-600 font-xsss" for="otp">Enter OTP</label>
                                        <input type="text" name="otp" maxlength="6" id="otp"
                                            class="form-control" placeholder="Enter OTP">
                                        <span class="error-message text-danger" id="error-otp"></span>
                                        <div class="otp-error-message mt-2"></div>
                                    </div>
                                </div>



                                <!-- Submit Button -->
                                <div class="col-lg-12 mb-3">
                                    <button type="submit"
                                        class="form-control rounded-lg updateProfileBtn text-white text-center font-xss fw-500 bg-current w150">Update
                                        Profile</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script type="text/javascript">
            $(document).ready(function() {
                const originalMobile = $('#profileMobile').val();
                let resendTimer;
                // Mobile field input detection
                $('#profileMobile').on('input', function() {
                    const currentMobile = $(this).val();
                    if (currentMobile !== originalMobile) {
                        $('.sendOTPButton').show();
                    } else {
                        $('.sendOTPButton').hide();
                        $('.otp-section').hide();
                        $('.resendOTPButton').hide();
                    }
                });

                // Send OTP button click
                $('.sendOTPButton').click(function(e) {
                    e.preventDefault();

                    const mobile = $('#profileMobile').val();
                    if (mobile !== originalMobile) {
                        // Check if the mobile number is already associated with another account
                        $.ajax({
                            type: 'POST',
                            url: '{{ route('check-mobile-number') }}',
                            data: {
                                _token: '{{ csrf_token() }}',
                                destination: mobile
                            },
                            success: function(response) {
                                if (response.status === 'available') {
                                    sendOtp(mobile); // Call the send OTP function
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
                    }
                });

                // Function to send OTP
                function sendOtp(mobile) {
                    $.ajax({
                        type: 'POST',
                        url: '{{ route('profile-update-send-otp') }}',
                        data: {
                            _token: '{{ csrf_token() }}',
                            destination: mobile
                        },
                        success: function(response) {
                            $('.otp-success-message').text('OTP has been sent to ' + mobile).show();
                            $('.otp-section').show();
                            $('.sendOTPButton').hide();
                            $('.resendOTPButton').show();
                            startResendTimer();

                            setTimeout(() => $('.otp-success-message').fadeOut(), 20000);
                        },
                        error: function(xhr) {
                            $('.otp-error-message').html(
                                '<span class="text-danger">Failed to send OTP. Please try again.</span>'
                            );
                        }
                    });
                }



                // Start the resend OTP timer
                function startResendTimer() {
                    let timeLeft = 60;
                    $('#timer').text(timeLeft);
                    $('.resendOTPButton').prop('disabled', true);

                    resendTimer = setInterval(function() {
                        timeLeft--;
                        $('#timer').text(timeLeft);

                        if (timeLeft <= 0) {
                            clearInterval(resendTimer);
                            $('.resendOTPButton').prop('disabled', false).text('Resend OTP');
                        }
                    }, 1000);
                }

                // Resend OTP button click
                $('.resendOTPButton').click(function(e) {
                    e.preventDefault();
                    const mobile = $('#profileMobile').val();
                    sendOtp(mobile);
                    $('.resendOTPButton').prop('disabled', true).html(
                        'Resend OTP (<span id="timer">60</span>)');
                    startResendTimer();
                });


                // Form Validation
                $('#profileForm').validate({
                    onfocusout: function(element) {
                        $(element).valid(); // Validate on focus out
                    },
                    onkeyup: function(element) {
                        $(element).valid(); // Validate in real-time
                    },
                    rules: {
                        name: {
                            required: true,
                            minlength: 3,
                            maxlength: 50,
                        },
                        email: {
                            required: true,
                            email: true,
                        },
                        mobile: {
                            required: false,
                            digits: true,
                            minlength: 10,
                            maxlength: 10,
                        },
                        otp: {
                            required: false,
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
                            email: "Enter a valid email",
                        },
                        mobile: {
                            digits: "Only numbers are allowed",
                            minlength: "Mobile number must be 10 digits long",
                            maxlength: "Mobile number must be 10 digits long",
                        },
                        otp: {
                            digits: "Only numbers are allowed",
                            minlength: "OTP must be 6 digits",
                            maxlength: "OTP must be 6 digits"
                        },
                        errorPlacement: function(error, element) {
                            // Place the error message below the input element
                            error.appendTo(element.parent().next('.error-message'));
                        }
                    }
                });
            });
        </script>
    @endpush
@endsection
