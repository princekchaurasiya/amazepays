<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title> @yield('title')</title>
    @yield('css')
    @include('layouts.partials.css-links')
</head>

<body class="color-theme-blue open-font">
    <div class="cotainer-fluid">
        <div class="preloader"></div>
        <div class="main-wrapper">
            <!-- navigation wrapper starts here -->
            @include('layouts.partials.navbar')
            <!-- navigation wrapper ends here -->
        </div>
        <!-- header wrapper -->
        @yield('content')
        <!-- footer wrapper -->
        @include('layouts.partials.footer')
        <!-- footer wrapper -->
    </div>
    <!-- Modal Register -->
    <div class="modal fade ModalregisterD" id="ModalregisterD" tabindex="-1" role="dialog"
        aria-labelledby="myModalLabel" data-backdrop="true" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md modal-lg" role="document">
            <div class="modal-content border-0">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i
                        class="ti-close text-grey-500"></i></button>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="login-signup">
                                <div class="row">
                                    <div class="col-lg-5 left-side d-lg-block d-none">
                                        <div class="rounded-0 w-100 border-0">
                                            <p class="text-center pt-7 mb-4"><img
                                                    src="{{ asset('images/logo_white.png') }}" alt="AmazePays"
                                                    class="img-fluid" width="150"></p>
                                            <h4 class="fw-600 font-xxl mb-3 text-center">All Gifts are Here</h4>
                                            <div class="rounded-0 text-left pt-5 pb-2">
                                                <div class="single-line mb-2">
                                                    <span class="mr-3"><img
                                                            src="https://www.gyftoo.com/public/assets/images/group1.png"
                                                            alt="Group"></span>
                                                    <span class="text-white">Buy or Send Gift Cards Instantly</span>
                                                </div>
                                            </div>
                                            <div class="rounded-0 text-left pt-3 pb-2">
                                                <div class="single-line mb-2">
                                                    <span class="mr-3"><img
                                                            src="https://www.gyftoo.com/public/assets/images/group1.png"
                                                            alt="Group"></span>
                                                    <span class="text-white">Send Gift Cards to Friends and Family
                                                        Choose from Hundreds of Popular Brands.</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-7">
                                        <div class="card shadow-none rounded-0 w-100 p-2 mt-3 pt-3 border-0">
                                            <div class="card-body rounded-0 text-left pt-0">
                                                <h2 class="fw-600 display2-size mb-4">Create your account</h2>

                                                <span class="font-xssss fw-400 error-message error-registerOtp"></span>
                                                <form id="registration-form">

                                                    <span
                                                        class="font-xssss fw-400 main-register-error text-center"></span>
                                                    <div class="form-group mb-3">
                                                        <input type="text"
                                                            class="form-control h60 border-2 bg-color-none text-grey-700 credentails-field"
                                                            placeholder="Name" id="name" autocomplete="off">
                                                        <span class="font-xssss fw-400 error-message error-name"></span>
                                                    </div>
                                                    <div class="form-group mb-3">
                                                        <div class="input-group">
                                                            <input type="text"
                                                                class="form-control h60 border-2 bg-color-none text-grey-700 credentails-field"
                                                                placeholder="Mobile Number" id="mobile">
                                                            <div class="input-group-append">
                                                                <button class="btn btn-primary registerSendOTP"
                                                                    type="button" id="registerSendOTP">Send
                                                                    OTP</button>
                                                            </div>


                                                        </div>
                                                        <span
                                                            class="font-xssss fw-400 error-registerMobNumb error-message text-danger error-mobile"></span>


                                                    </div>

                                                    <div class="form-group mb-3 otp-section" style="display: none;">
                                                        <div class="input-group">
                                                            <input type="text"
                                                                class="form-control h60 border-2 bg-color-none text-grey-700 credentails-field registerOTP"
                                                                placeholder="Enter Your OTP code" id="registerOTP">
                                                            <div class="input-group-append">
                                                                <button id="resendRegistrationOtpButton"
                                                                    class="input-group-text text-decoration-none resend-otp-link hidden"
                                                                    hidden type="button">Resend
                                                                    OTP</button>
                                                            </div>
                                                        </div>

                                                    </div>

                                                    <div class="form-group mb-3">
                                                        <input type="text"
                                                            class="form-control h60 border-2 bg-color-none text-grey-700 credentails-field"
                                                            placeholder="Email" id="email" autocomplete="off"
                                                            id="registerEmail" name="registerEmail">
                                                        <span
                                                            class="font-xssss fw-400 error-message error-email"></span>
                                                    </div>
                                                    <div class="form-group icon-tab mb-3">
                                                        <input type="password"
                                                            class="form-control h60 border-2 bg-color-none text-grey-700 credentails-field"
                                                            placeholder="Password" id="password">
                                                        <i class="ti-lock text-grey-700 pr-0"></i>
                                                        <i class="ti-eye toggle-register-Password-icon"
                                                            id="toggleRegstPassword"></i>
                                                        <span
                                                            class="font-xssss fw-400 error-password error-message"></span>
                                                    </div>
                                                    <div class="form-group icon-tab mb-3">
                                                        <input type="password"
                                                            class="form-control h60 border-2 bg-color-none text-grey-700 credentails-field"
                                                            placeholder="Confirm Password" id="confmPassword">
                                                        <i class="ti-eye toggle-register-Password-icon"
                                                            id="toggleRegstConfirmPassword"></i>
                                                        <i class="ti-lock text-grey-700 pr-0"></i>
                                                        <span
                                                            class="font-xssss fw-400 error-confmPass error-message error-confmPassword"></span>
                                                    </div>


                                                    <div class="form-group icon-tab mb-3">
                                                        <a href="#"
                                                            class="text-center register-button-color form-control h60 bg-current text-white font-xss fw-500 border-0 p-0"
                                                            id="createUser">Create an account</a>
                                                    </div>
                                                </form>
                                                <div class="col-sm-12 p-0 text-center">
                                                    <!-- <a href="#" class="form-control h60 bg-current text-white font-xss fw-500 border-2 border-0 p-0">Create an account</a> -->
                                                    <h6 class="text-grey-500 font-xsss fw-500 mt-2 mb-4 lh-32">Are you
                                                        already member?
                                                        <a href="#" class="fw-700 ml-1 text-orange "
                                                            data-toggle="modal" data-target="#Modallogin"
                                                            data-dismiss="modal">Login</a>
                                                    </h6>
                                                    {{-- <div class="row">
                                                        <div class="col-6 pr-1"><a href="#"
                                                                class="form-control h60 p-0 pl-5 bg-lightblue text-grey-700 border-2 border-0 font-xssss fw-600  position-relative">Login
                                                                with OTP</a>
                                                        </div>
                                                        <div class="col-6 pl-1"><a href="#"
                                                                class="form-control h60 p-0 pl-5 bg-lightblue text-grey-700 border-2 border-0 font-xssss fw-600  position-relative">Forgot
                                                                Password?</a>
                                                        </div>
                                                    </div> --}}
                                                    <p
                                                        class="fw-500 font-xssss text-grey-600 mt-2 pt-3 d-inline-block">
                                                        By continuing, you agree to Amazepay's <a
                                                            href="blog-single.html" class="text-current">Term ans
                                                            Condition</a> and <a href="blog-single.html"
                                                            class="text-current">Privacy Policy </a>.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end of Modal Register -->

    <!-- Modal Login -->
    <div class="modal bottom fade Modallogin" id="Modallogin" tabindex="-1" role="dialog"
        aria-labelledby="myModalLabel" data-backdrop="true" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md modal-lg" role="document">
            <div class="modal-content border-0">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i
                        class="ti-close text-grey-500"></i></button>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="login-signup">
                                <div class="row">
                                    <div class="col-lg-5 left-side d-lg-block d-none">
                                        <div class="rounded-0 w-100 p-2 pt-5 border-0">
                                            <div class="rounded-0 text-left pt-5 pb-2">
                                                <div class="single-line mb-2">
                                                    <span class="mr-3"><img
                                                            src="https://www.gyftoo.com/public/assets/images/group2.png"
                                                            alt="Group"></span>
                                                    <span class="text-white">Buy or Send Gift Cards Instantly</span>
                                                </div>
                                            </div>
                                            <div class="rounded-0 text-left pt-3 pb-2">
                                                <div class="single-line mb-2">
                                                    <span class="mr-3"><img
                                                            src="https://www.gyftoo.com/public/assets/images/group1.png"
                                                            alt="Group"></span>
                                                    <span class="text-white">Send Gift Cards to Friends and Family
                                                        Choose from Hundreds of Popular Brands.</span>
                                                </div>
                                            </div>
                                            <h4 class="fw-600 display2-size-sm mb-4 text-center">Cashback and Gift
                                                Cards</h4>
                                            <p class="text-center"><img src="{{ asset('images/logo_white.png') }}"
                                                    alt="AmazePays" class="img-fluid" width="150"></p>
                                        </div>
                                    </div>
                                    <div class="col-lg-7">
                                        <div class="card shadow-none rounded-0 w-100 p-2 pt-3 border-0">
                                            <div class="card-body rounded-0 text-left pt-0 pb-2">
                                                <h2 class="fw-600 display2-size mb-4">Login</h2>
                                                <span class="font-xssss fw-400 main-error text-center"></span>
                                                <form id="login-form">
                                                    <div class="form-group mb-3">
                                                        <input type="text"
                                                            class="form-control h60 border-2 bg-color-none text-grey-700 credentails-field"
                                                            placeholder="Enter Your Mobile Number" id="loginMobNumb">
                                                        <span
                                                            class="font-xssss fw-400 error-loginMobNumb text-danger"></span>
                                                    </div>
                                                    <div class="form-group mb-3">
                                                        <input type="password"
                                                            class="form-control h60 border-2 bg-color-none text-grey-700 credentails-field"
                                                            placeholder="Enter Password" id="loginPass">
                                                        <i class="ti-eye toggle-login-Password-icon"
                                                            id="togglePassword"></i>
                                                        <span
                                                            class="font-xssss fw-400 error-loginPass text-danger"></span>
                                                    </div>
                                                    <div class="form-check text-left mb-3">
                                                        <input type="checkbox" class="form-check-input mt-2"
                                                            id="exampleCheck1">
                                                        <label class="form-check-label font-xsss text-grey-500"
                                                            for="exampleCheck1">Remember me</label>
                                                        {{-- <a href="#" class="fw-600 font-xsss text-grey-700 mt-1 float-right"
                                                            data-toggle="modal" data-target="#Modalforgotpassword"
                                                            data-dismiss="modal">Forgot your Password?</a> --}}
                                                    </div>
                                                    <div class="form-group icon-tab mb-3">
                                                        <div class="row">
                                                            <div class="col-6 align-self-center">
                                                                <button type="button"
                                                                    class="btn btn-primary login-otp-link p-0"
                                                                    id="loginWithOtpButton">Login With OTP</button>
                                                            </div>
                                                            <div class="col-6 align-self-center">
                                                                <button type="submit"
                                                                    class="form-control h20 float-right bg-current text-white text-center font-xss fw-500 border-0 p-0 w100 login-button-color"
                                                                    id="loginUser">Login</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Hidden input to store the mobile number for OTP -->
                                                    <input type="hidden" class="destination-input"
                                                        name="destination" id="destination" required>
                                                </form>
                                                <!-- Get OTP button to trigger OTP modal -->

                                                <!-- Success message div -->
                                                <div class="col-sm-12 p-0 text-center">
                                                    <h6 class="text-grey-500 font-xsss fw-500 mt-2 mb-0 lh-32">Don't
                                                        have an account? <a href="#"
                                                            class="fw-700 ml-1 text-current register-form modal-register-link"
                                                            data-toggle="modal" data-target="#ModalregisterD"
                                                            data-dismiss="modal">Register</a></h6>
                                                </div>
                                                <div class="col-sm-12 p-0 text-center">
                                                    <p
                                                        class="fw-500 font-xssss text-grey-600 mt-2 pt-3 d-inline-block text-center">
                                                        By continuing, you agree to Amazepay's <a
                                                            href="{{ route('terms-of-use') }}"
                                                            class="text-current">Terms and
                                                            Conditions</a> and <a href="{{ route('privacy-policy') }}"
                                                            class="text-current">Privacy Policy</a>.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- end of Modal Login -->
    <!-- modal otp verificationstarts here  -->
    <!-- OTP Verification Modal -->
    <div class="modal fade" id="otpVerificationModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
        data-backdrop="true" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg border-0 rounded-lg">
                <div class="modal-header bg-primary text-white py-4">
                    <h5 class="modal-title font-weight-bold" id="otpModalLabel">OTP Verification</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"
                        id="closeModalButton">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h6 class="text-center text-secondary">Please enter the one-time password<br>to verify your account
                    </h6>
                    <div class="text-center mt-3">
                        <span>A code has been sent to</span>
                        <small class="font-weight-bold">******* <span id="maskedPhone">1234</span></small>
                    </div>
                    <div id="otp" class="inputs d-flex justify-content-center mt-4">
                        <!-- Create input fields for the OTP digits (1 to 6) -->
                        <input class="m-2 text-center form-control rounded otp-input" type="text" id="first"
                            maxlength="1" oninput="moveToNext(this, 'second')" />
                        <input class="m-2 text-center form-control rounded otp-input" type="text" id="second"
                            maxlength="1" oninput="moveToNext(this, 'third')" />
                        <input class="m-2 text-center form-control rounded otp-input" type="text" id="third"
                            maxlength="1" oninput="moveToNext(this, 'fourth')" />
                        <input class="m-2 text-center form-control rounded otp-input" type="text" id="fourth"
                            maxlength="1" oninput="moveToNext(this, 'fifth')" />
                        <input class="m-2 text-center form-control rounded otp-input" type="text" id="fifth"
                            maxlength="1" oninput="moveToNext(this, 'sixth')" />
                        <input class="m-2 text-center form-control rounded otp-input" type="text" id="sixth"
                            maxlength="1" />
                    </div>
                    <!-- Placeholder element for displaying error message -->
                    <div class="error-otpVerifyInput text-center text-danger mt-3 font-weight-bold"></div>
                </div>
                <div class="modal-footer justify-content-center py-3">
                    <!-- Submit button to validate and verify the OTP -->
                    <button class="btn btn-danger px-4 py-2 rounded-pill font-weight-bold"
                        id="otpVerificationButton">Submit</button>
                </div>
            </div>
        </div>
    </div>





    <!-- modal otp validation ends here -->
    <!-- Forgot Password Modal -->
    <div class="modal bottom fade" id="Modalforgotpassword" tabindex="-1" role="dialog"
        aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i
                        class="ti-close text-grey-500"></i></button>
                <div class="modal-body p-3 d-flex align-items-center bg-none">
                    <div class="card shadow-none rounded-0 w-100 p-2 pt-3 border-0">
                        <div class="card-body rounded-0 text-left pt-0 pb-2">
                            <h2 class="fw-600 display1-size mb-4">Forgot Password</h2>
                            <form>
                                <div class="form-group mb-3">
                                    <input type="text"
                                        class="form-control h60 border-2 bg-color-none text-grey-700"
                                        placeholder="Email">
                                </div>
                            </form>
                            <div class="col-sm-12 p-0 text-center">
                                <a href="#"
                                    class="form-control h60 bg-current text-white font-xss fw-500 border-2 border-0 p-0">Submit</a>
                                <h6 class="text-grey-500 font-xsss fw-500 mt-2 mb-0 lh-32">Dont have account <a
                                        href="#" class="fw-700 ml-1 text-orange Modallogin" data-toggle="modal"
                                        data-target="#Modallogin" data-dismiss="modal">Login</a></h6>
                            </div>
                            <div class="col-sm-12 p-0 text-center">
                                <p class="fw-900 font-xssss text-grey-600 mt-2 pt-3 d-inline-block text-center">
                                    By continuing, you agree to Amazepay's <a href="{{ route('terms-of-use') }} "
                                        class="text-current">Term ans Condition</a> and <a
                                        href="{{ route('privacy-policy') }}" class="text-current">Privacy Policy
                                    </a>.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end of Forgot Password Modal -->

    <!-- Script links starts here -->
    @include('layouts.partials.script-links')
    <!-- Script links ends here -->



    <script>
        function moveToNext(currentInput, nextInputId) {
            if (currentInput.value.length >= currentInput.maxLength) {
                document.getElementById(nextInputId).focus();
            }
        }
        // close button
        $('.close').click(function() {
            $(".error-name").text('');
            $(".error-mobile").text('');
            $(".error-email").text('');
            $(".error-password").text('');

            $(".error-loginMobNumb").text('');
            $(".error-loginPass").text('');

            $(".main-error").text('');
        });





        document.getElementById('closeModalButton').addEventListener('click', function() {
            $('#otpVerificationModal').modal('hide');
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');

        });




        $('.close').click(function() {
            $('.modal').modal('hide');
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');

        });


        // Close modal on outside (backdrop) click
        $(document).on('click', function(event) {


            if ($(event.target).hasClass('modal-backdrop')) {
                $('.modal').modal('hide');
                // Remove the backdrop
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');

                console.log('Modal closed on backdrop click');
            }
        });

        $(".modal-register-link").click(function() {
            $('.Modallogin').modal('hide');
        });



        $(document).ready(function() {
            // Toggle visibility for login password
            $("#togglePassword").click(function() {
                const loginPass = $("#loginPass");
                const type = loginPass.attr("type") === "password" ? "text" : "password";
                loginPass.attr("type", type);
                $(this).toggleClass("fa-eye fa-eye-slash");
            });

            // Toggle visibility for register password
            $("#toggleRegstPassword").click(function() {
                const password = $("#password");
                const type = password.attr("type") === "password" ? "text" : "password";
                password.attr("type", type);
                $(this).toggleClass("fa-eye fa-eye-slash");
            });

            // Toggle visibility for register confirm password
            $("#toggleRegstConfirmPassword").click(function() {
                const confmPassword = $("#confmPassword");
                const type = confmPassword.attr("type") === "password" ? "text" : "password";
                confmPassword.attr("type", type);
                $(this).toggleClass("fa-eye fa-eye-slash");
            });
        });



        $('#createUser').click(function(e) {
            e.preventDefault();
            var name = $('#name').val();
            var mobile = $('#mobile').val();
            var email = $('#email').val();
            var password = $('#password').val();
            var otp = $('#registerOTP').val();
            var confmPassword = $('#confmPassword').val();
            var regxMobile = /^(?:(?:\+|0{0,2})91)?[789]\d{9}$/;
            var regxEmail = /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z]+$/;
            var registerSendOtp = $('.registerOTP').val();
            var status = true;


            // Clear previous error messages
            $('.error-message').empty();
            $('.error-name').empty();
            $('.error-mobile').empty();
            $('.error-email').empty();
            $('.error-password').empty();
            $('.error-confmPassword').empty();
            $('.error-registerOtp').empty();

            if (name.length === 0) {
                status = false;
                $(".error-name").text('Name is required').addClass('error-color fw-800');
            } else if (!/^[a-zA-Z\s]+$/.test(name)) {
                status = false;
                $(".error-name").text('Name should only contain letters and spaces').addClass('error-color fw-800');
            }

            if (mobile.length === 0) {
                status = false;
                $(".error-mobile").text('Mobile Number is required').addClass('error-color fw-800');
            } else if (!regxMobile.test(mobile) || mobile.length !== 10) {
                status = false;
                $(".error-mobile").text('Invalid mobile number').addClass('error-color fw-800');
            }

            // OTP validation
            if (registerSendOtp.length === 0) {
                status = false;
                $(".error-registerOtp").text('Please enter the OTP to verify your mobile number').addClass(
                    'error-color');
                $(".error-registerMobNumb").text('Please enter the OTP to verify your mobile number').addClass(
                    'error-color');

            }

            if (email.length === 0) {
                status = false;
                $(".error-email").text('Email is required').addClass('error-color fw-800');
            } else if (!regxEmail.test(email)) {
                status = false;
                $(".error-email").text('Invalid email address').addClass('error-color fw-800');
            }

            if (password.length === 0) {
                status = false;
                $(".error-password").text('Password is required').addClass('error-color fw-800');
            } else if (confmPassword !== password) {
                status = false;
                $(".error-confmPassword").text('Password does not match').addClass('error-color fw-800');
            }

            if (status) {
                userRegister(name, mobile, email, password, confmPassword, otp);
            }

            return status;
        });






        function userRegister(name, mobile, email, password, confmPassword, otp) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            var formData = new FormData();
            formData.append('name', name);
            formData.append('mobile', mobile);
            formData.append('email', email);
            formData.append('password', password);
            formData.append('password_confirmation', confmPassword);

            formData.append('otp', otp);
            var type = 'POST';
            var ajaxurl = '{{ route('user-registration') }}';
            $.ajax({
                type: type,
                url: ajaxurl,
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json', // Corrected: 'json' instead of 'Json'
                success: function(data) {
                    if (data.status == 200) {
                        location.reload(true);
                    } else if (data.status == 400 && data.errors) {
                        // Display duplicate entry errors within the modal
                        $.each(data.errors, function(key, value) {
                            // debugger;
                            if (key == 'registerOTP') {
                                $('.error-registerOtp').text(value).addClass('error-color');
                            }
                            if (key == 'mobile') {
                                $('.error-registerMobNumb').text(value).addClass('error-color');
                            }
                            $('#' + key).siblings('.error-message').text(value).addClass('error-color');
                            // $('#' + key+"+span").text(value).addClass('error-color');

                        });
                    } else {
                        $(".main-register-error").text(data.msg).addClass('error-color');
                    }
                },
                error: function(jqXHR) {
                    $(".main-register-error").text('Something went wrong. Please try again later.');
                    console.log(jqXHR); // Log the error for debugging purposes
                }
            });
            return false;
        }



        //  end registration

        // for login
        // $('.login-form').click(function(){
        //         $(".error-loginMobNumb").text('');
        //         $(".error-loginPass").text('');
        //     });

        $('#loginUser').click(function(e) {
            e.preventDefault();
            var mobile = $('#loginMobNumb').val();
            var password = $('#loginPass').val();
            var regxMobile = /^(?:(?:\+|0{0,2})91(\s*[\-]\s*)?|[0]?)?[789]\d{9}$/;
            var status = true;

            if (mobile.length === 0) {
                $(".error-loginMobNumb").text('Mobile number is required');
                status = false;
            } else if (!regxMobile.test(mobile) || mobile.length !== 10) {
                $(".error-loginMobNumb").text('Invalid mobile number');
                status = false;
            } else {
                $(".error-loginMobNumb").empty();
            }

            if (password.length === 0) {
                $(".error-loginPass").text('Password is required');
                status = false;
            } else {
                $(".error-loginPass").empty();
            }

            if (status) {
                userLogin(mobile, password);
            }
        });

        function userLogin(mobile, password) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            var formData = new FormData();
            formData.append('mobile', mobile);
            formData.append('password', password);

            var type = "POST";
            var ajaxurl = "{{ url('/user-login') }}";

            $.ajax({
                type: type,
                url: ajaxurl,
                data: formData,
                processData: false,
                contentType: false,
                success: function(data) {
                    if (data.status == 200) {
                        location.reload(true);
                    } else {
                        $(".main-error").text('Invalid credentials').addClass('error-color');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR);
                }
            });

            return false;
        }
        // end login

        //otp modal keyboard button handling code starts here
        $(document).ready(function() {
            function OTPInput() {
                const inputs = $('#otp > *[id]');

                inputs.on('keydown', function(event) {
                    if (event.key === "Backspace") {
                        $(this).val('');
                        const index = inputs.index(this);
                        if (index !== 0) inputs.eq(index - 1).focus();
                    } else {
                        const key = event.key;

                        if (event.keyCode > 47 && event.keyCode < 58) {
                            const index = inputs.index(this);
                            $(this).val(key);
                            if (index !== inputs.length - 1) inputs.eq(index + 1).focus();
                            event.preventDefault();
                        } else if (event.keyCode > 64 && event.keyCode < 91) {
                            const index = inputs.index(this);
                            $(this).val(key);
                            if (index !== inputs.length - 1) inputs.eq(index + 1).focus();
                            event.preventDefault();
                        }
                    }
                });
            }

            OTPInput();
        });
        //otp modal keyboard button handling code ends here


        // login with otp validation starts here
        $(document).ready(function() {
            // When the "Login With OTP" button is clicked
            $('#loginWithOtpButton').click(function(e) {
                e.preventDefault();
                const destination = $('#loginMobNumb').val();

                // Validate the destination (mobile number)
                if (validateMobileNumber(destination)) {
                    // Make the AJAX request to the server
                    $.ajax({
                        type: 'POST',
                        url: '{{ route('send-sms') }}',
                        data: {
                            _token: '{{ csrf_token() }}',
                            destination: destination
                        },
                        dataType: 'json',
                        success: function(response) {
                            // Handle the response from the server based on the status code
                            if (response.status === 'success') {
                                // OTP sent successfully
                                $('#Modallogin').fadeOut(100, function() {
                                    // Once the fade-out animation is complete (100 milliseconds in this example), show the OTP modal
                                    var firstTwoDigits = destination.substr(0, 2);
                                    var lastTwoDigits = destination.substr(-2);
                                    $('#otpVerificationModal small').text(
                                        firstTwoDigits + '*******' + lastTwoDigits
                                    );
                                    $('#otpVerificationModal').modal('show');
                                });
                            } else {
                                // Failed to send OTP, show the error message
                                $('.error-loginMobNumb').text(
                                    response.message ||
                                    'Failed to send OTP. Please try again later.'
                                );
                            }
                        },
                        error: function() {
                            // AJAX request failed, show the error message
                            $('.error-loginMobNumb').text(
                                'An error occurred while sending the request. Please try again later.'
                            );
                        }
                    });
                }
            });
        });

        // When the OTP verification form is submitted

        $(document).ready(function() {
            // When the "Submit" button is clicked for OTP verification
            $('#otpVerificationButton').click(function() {
                // Get the OTP digits entered by the user
                const first = $('#first').val();
                const second = $('#second').val();
                const third = $('#third').val();
                const fourth = $('#fourth').val();
                const fifth = $('#fifth').val();
                const sixth = $('#sixth').val();

                // Validate if all OTP fields are filled
                if (!first || !second || !third || !fourth || !fifth || !sixth) {
                    $('.error-otpVerifyInput').text('Please enter the complete OTP.');
                    return;
                }

                // Validate if OTP contains only numeric characters
                if (!/^[0-9]+$/.test(first + second + third + fourth + fifth + sixth)) {
                    $('.error-otpVerifyInput').text('Invalid OTP format. Please enter a valid OTP.');
                    return;
                }

                // Combine the OTP digits into a single string
                const otp = first + second + third + fourth + fifth + sixth;
                const destination = $('#loginMobNumb').val();

                // Make the AJAX request to verify OTP
                $.ajax({
                    type: 'POST',
                    url: '{{ route('verify-otp') }}',
                    data: {
                        _token: '{{ csrf_token() }}',
                        otp: otp,
                        destination: destination
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log(
                            response); // Always log the response to see the exact structure

                        if (response.status === 'success') {
                            // OTP verification successful, redirect the user or show a success message
                            window.location.href = response.redirect_url; // Redirect to the desired page
                        } else if (response.status === 'error') {
                            // Handle error based on message content
                            if (response.message.includes('Invalid Mobile Number')) {
                                // Invalid mobile number
                                $('.error-loginMobNumb').text(
                                    'Invalid mobile number. Please try again.');
                            } else if (response.message.includes('Invalid OTP')) {
                                // Invalid OTP
                                $('.error-otpVerifyInput').text(
                                    'Invalid OTP. Please try again.');
                            } else if (response.message.includes('OTP has expired')) {
                                // OTP has expired
                                $('.error-otpVerifyInput').text(
                                    'OTP has expired. Please request a new OTP.');
                                // Show the "Resend OTP" button
                                $('#resendOtpButton').show();
                            } else {
                                // Handle any other unexpected errors
                                $('.error-otpVerifyInput').text(
                                    'An unexpected error occurred. Please try again.');
                            }
                        }
                    },
                    error: function() {
                        // AJAX request failed, show the error message
                        $('.error-otpVerifyInput').text(
                            'An error occurred while verifying the OTP. Please try again later.'
                        );
                    }
                });

            });
        });



        $(document).ready(function() {
            // Toggle the 'collapsed' class and side navigation width when the collapse button is clicked
            $(".navbar-toggler").click(function(event) {
                event.preventDefault(); // Prevent the default behavior of the button
                $(".navbar").toggleClass("collapsed");
                var sidenav = $(".sidenav");
                if (sidenav.width() === 0) {
                    sidenav.css("width", "250px"); // Adjust width as needed
                } else {
                    sidenav.css("width", "0");
                }
            });
        });

        function closeNav() {
            // Get the side navigation element
            var sidenav = document.querySelector(".sidenav");

            // Close the side navigation by collapsing the navbar
            var navbar = document.querySelector(".navbar");
            navbar.classList.add("collapsed");

            // Hide the side navigation by setting its width to 0
            sidenav.style.width = "0";
        }

        // Validate the mobile number (You can add this function to your existing JS code)
        function validateMobileNumber(mobileNumber) {
            var indianNumberRegex = /^[6-9]\d{9}$/;
            if (!mobileNumber || mobileNumber.trim() === '') {
                $('.error-loginMobNumb').text('Mobile number is required');
                return false;
            } else if (!indianNumberRegex.test(mobileNumber)) {
                $('.error-loginMobNumb').text('Please enter a valid 10-digit Indian mobile number.');
                return false;
            }
            return true;
        };


        $('.registerSendOTP').click(function() {


            const destination = $('#mobile').val();
            var mobile = $('#mobile').val();
            console.log('Destination mobile number:', destination);
            console.log('Destination mobile number:', mobile);
            sendotp(destination)


        });
        $('#resendRegistrationOtpButton').click(function() {


            const destination = $('#mobile').val();
            var mobile = $('#mobile').val();
            console.log('Destination mobile number:', destination);
            console.log('Destination mobile number:', mobile);
            $(this).hide();

            sendotp(destination);
            setTimeout(function() {
                $(this).show();
            }, 30000);


        });



        function sendotp(destination) {

            $.ajax({
                type: 'POST',
                url: '{{ route('send-regsiter-otp') }}',
                data: {
                    _token: '{{ csrf_token() }}',
                    destination: destination
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Response:', response);
                    if (response.status === 'success') {
                        console.log('OTP sent successfully.');
                        $('.registerSendOTP').closest('.form-group').hide();
                        $('.otp-section').show();
                        setTimeout(function() {
                            $('#resendRegistrationOtpButton').removeAttr('hidden');
                        }, 30000);

                    } else {
                        $('.error-registerMobNumb').text(response.message ||
                            'Failed to send OTP. Please try again later.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    $('.error-loginMobNumb').text(
                        'An error occurred while sending the request. Please try again later.');
                }
            });
        }


        // Optional: Handle resend OTP click (if needed)


        let isOtpVerified = false;
    </script>
    @stack('scripts')
    </div>
</body>

</html>
