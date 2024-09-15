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
    <div class="modal fade" id="ModalregisterD" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
        data-backdrop="true" aria-hidden="true">
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
                                                <form id="registration-form">
                                                    <!-- Error message area at the top of the modal -->
                                                    <div class="alert alert-danger d-none" id="otp-error-alert">
                                                        <span class="font-xssss fw-400 error-message error-otp"></span>
                                                    </div>
                                                    <span
                                                        class="font-xssss fw-400 main-register-error text-center"></span>

                                                    <!-- Success message area -->
                                                    <div class="alert alert-success d-none" id="otp-success-alert">
                                                        OTP has been sent to your mobile number. Please enter the OTP
                                                        for verification.
                                                    </div>

                                                    <!-- Name Field -->
                                                    <div class="form-group mb-3">
                                                        <input type="text"
                                                            class="form-control h60 border-2 bg-color-none text-grey-700 credentails-field"
                                                            placeholder="Name" id="name" autocomplete="off"
                                                            name="name">
                                                        <span class="error-message error-name"></span>
                                                    </div>

                                                    <!-- Mobile Number Field -->
                                                    <div class="form-group mb-3">
                                                        <div class="input-group">
                                                            <input type="text"
                                                                class="form-control h60 border-2 bg-color-none text-grey-700 credentails-field"
                                                                placeholder="Mobile Number" id="mobile"
                                                                name="mobile">
                                                        </div>
                                                        <span class="error-message error-mobile"></span>
                                                    </div>

                                                    <!-- Email Field -->
                                                    <div class="form-group mb-3">
                                                        <input type="email"
                                                            class="form-control h60 border-2 bg-color-none text-grey-700 credentails-field"
                                                            placeholder="Email" id="email" autocomplete="off"
                                                            name="email">
                                                        <span
                                                            class="font-xssss fw-400 error-message error-email"></span>
                                                    </div>

                                                    <!-- Password Field -->
                                                    <div class="form-group mb-3">
                                                        <input type="password"
                                                            class="form-control h60 border-2 bg-color-none text-grey-700 credentails-field"
                                                            placeholder="Password" id="password" name="password">
                                                        <span
                                                            class="font-xssss fw-400 error-message error-password"></span>
                                                    </div>

                                                    <!-- Confirm Password Field -->
                                                    <div class="form-group mb-3">
                                                        <input type="password"
                                                            class="form-control h60 border-2 bg-color-none text-grey-700 credentails-field"
                                                            placeholder="Confirm Password" id="confmPassword"
                                                            name="confmPassword">
                                                        <span
                                                            class="font-xssss fw-400 error-message error-confmPassword"></span>
                                                    </div>

                                                    <!-- Submit Button for initial validation and sending OTP -->
                                                    <div class="form-group mb-3">
                                                        <button type="submit"
                                                            class="form-control bg-current text-white submit1"
                                                            id="submit1">Submit One</button>
                                                    </div>

                                                    <!-- OTP Section -->
                                                    <div class="form-group mb-3 otp-section d-none">
                                                        <input type="text"
                                                            class="form-control h60 border-2 bg-color-none text-grey-700"
                                                            placeholder="Enter OTP" id="registerOTP" name="otp">
                                                        <span class="font-xssss fw-400 error-message error-otp"></span>
                                                    </div>

                                                    <!-- Submit Button for OTP verification -->
                                                    <div class="form-group mb-3 otp-section d-none">
                                                        <button type="submit" id="submit2"
                                                            class="form-control bg-current text-white submit2">Submit
                                                            Two</button>
                                                    </div>
                                                </form>


                                                <!-- Footer -->
                                                <div class="col-sm-12 p-0 text-center">
                                                    <h6 class="text-grey-500 font-xsss fw-500 mt-2 mb-4 lh-32">Already
                                                        a member?
                                                        <a href="#" class="fw-700 ml-1 text-orange"
                                                            data-toggle="modal" data-target="#Modallogin"
                                                            data-dismiss="modal">Login</a>
                                                    </h6>
                                                    <p
                                                        class="fw-500 font-xssss text-grey-600 mt-2 pt-3 d-inline-block">
                                                        By continuing, you agree to Amazepay's
                                                        <a href="blog-single.html" class="text-current">Terms and
                                                            Conditions</a>
                                                        and <a href="blog-single.html" class="text-current">Privacy
                                                            Policy</a>.
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
    <div class="modal bottom fade" id="Modallogin" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
        data-backdrop="true" aria-hidden="true">
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
                                                        {{-- <i class="ti-eye toggle-login-Password-icon"
                                                            id="togglePassword"></i> --}}
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
                                                            class="fw-700 ml-1 text-current register-form"
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
                                        href="#" class="fw-700 ml-1 text-orange" data-toggle="modal"
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


            // if ($(event.target).hasClass('modal-backdrop')) {
            //     // Hide both modals
            //     $('.modal').modal('hide');
            //     // $('.Modallogin').modal('hide');

            //     // Remove the backdrop
            //     $('.modal-backdrop').remove();
            //     $('body').removeClass('modal-open');

            //     console.log('Modal closed on backdrop click');
            // }
        });


        // Close modal on outside (backdrop) click
        $(document).on('click', function(event) {


            if ($(event.target).hasClass('modal-backdrop')) {
                // Hide both modals
                $('.ModalregisterD').modal('hide');
                $('.Modallogin').modal('hide');

                // Remove the backdrop
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');

            }
        });




        // for registration
        // $('.register-form').click(function(){
        //     $(".error-name").text('');
        //     $(".error-mobile").text('');
        //     $(".error-email").text('');
        //     $(".error-password").text('');
        // });

        // for hide and show login password



        // const togglePassword = document.querySelector("#togglePassword");
        // const loginPass = document.querySelector("#loginPass");

        // togglePassword.addEventListener("click", function() {
        //     // toggle the type attribute
        //     const type = loginPass.getAttribute("type") === "password" ? "text" : "password";
        //     loginPass.setAttribute("type", type);

        //     // toggle the icon
        //     // this.classList.toggle("fa fa-eye-slash");
        // });

        // // for hide and show Register password

        // const toggleRegstPassword = document.querySelector("#toggleRegstPassword");
        // const password = document.querySelector("#password");

        // toggleRegstPassword.addEventListener("click", function() {
        //     // toggle the type attribute
        //     const type = password.getAttribute("type") === "password" ? "text" : "password";
        //     password.setAttribute("type", type);

        //     // toggle the icon
        //     // this.classList.toggle("fa fa-eye-slash");
        // });

        // // for hide and show Register Confirm password

        // const toggleRegstConfirmPassword = document.querySelector("#toggleRegstConfirmPassword");
        // const confmPassword = document.querySelector("#confmPassword");

        // toggleRegstConfirmPassword.addEventListener("click", function() {
        //     // toggle the type attribute
        //     const type = confmPassword.getAttribute("type") === "password" ? "text" : "password";
        //     confmPassword.setAttribute("type", type);

        //     // toggle the icon
        //     // this.classList.toggle("fa fa-eye-slash");
        // });

        // // prevent form submit
        // // const form = document.querySelector("form");
        // // form.addEventListener('submit', function (e) {
        // //     e.preventDefault();
        // // });



        // $('#createUser').click(function(e) {
        //     e.preventDefault();
        //     var name = $('#name').val();
        //     var mobile = $('#mobile').val();
        //     var email = $('#email').val();
        //     var password = $('#password').val();
        //     var confmPassword = $('#confmPassword').val();
        //     var regxMobile = /^(?:(?:\+|0{0,2})91)?[789]\d{9}$/;
        //     var regxEmail = /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z]+$/;
        //     var registerSendOtp = $('.registerOTP').val();
        //     var status = true;


        //     // Clear previous error messages
        //     $('.error-message').empty();
        //     $('.error-name').empty();
        //     $('.error-mobile').empty();
        //     $('.error-email').empty();
        //     $('.error-password').empty();
        //     $('.error-confmPassword').empty();
        //     $('.error-otp').empty();

        //     if (name.length === 0) {
        //         status = false;
        //         $(".error-name").text('Name is required').addClass('error-color');
        //     } else if (!/^[a-zA-Z\s]+$/.test(name)) {
        //         status = false;
        //         $(".error-name").text('Name should only contain letters and spaces').addClass('error-color');
        //     }

        //     if (mobile.length === 0) {
        //         status = false;
        //         $(".error-mobile").text('Mobile is required').addClass('error-color');
        //     } else if (!regxMobile.test(mobile) || mobile.length !== 10) {
        //         status = false;
        //         $(".error-mobile").text('Invalid mobile number').addClass('error-color');
        //     }

        //     // OTP validation
        //     if (registerSendOtp.length === 0) {
        //         status = false;
        //         $(".error-otp").text('Please enter the OTP to verify your mobile number').addClass('error-color');
        //     }

        //     if (email.length === 0) {
        //         status = false;
        //         $(".error-email").text('Email is required').addClass('error-color');
        //     } else if (!regxEmail.test(email)) {
        //         status = false;
        //         $(".error-email").text('Invalid email address').addClass('error-color');
        //     }

        //     if (password.length === 0) {
        //         status = false;
        //         $(".error-password").text('Password is required').addClass('error-color');
        //     } else if (confmPassword !== password) {
        //         status = false;
        //         $(".error-confmPassword").text('Password does not match').addClass('error-color');
        //     }

        //     if (status) {
        //         userRegister(name, mobile, email, password);
        //     }

        //     return status;
        // });









        // function userRegister(name, mobile, email, password, otp) {
        //     $.ajaxSetup({
        //         headers: {
        //             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        //         }
        //     });
        //     var formData = new FormData();
        //     formData.append('name', name);
        //     formData.append('mobile', mobile);
        //     formData.append('email', email);
        //     formData.append('password', password);
        //     formData.append('otp', otp);
        //     var type = 'POST';
        //     var ajaxurl = '{{ route('user-registration') }}';
        //     $.ajax({
        //         type: type,
        //         url: ajaxurl,
        //         data: formData,
        //         processData: false,
        //         contentType: false,
        //         dataType: 'json', // Corrected: 'json' instead of 'Json'
        //         success: function(data) {
        //             if (data.status == 200) {
        //                 location.reload(true);
        //             } else if (data.status == 400 && data.errors) {
        //                 // Display duplicate entry errors within the modal
        //                 $.each(data.errors, function(key, value) {
        //                     $('#' + key).siblings('.error-message').text(value).addClass('error-color');
        //                 });
        //             } else {
        //                 $(".main-register-error").text(data.msg).addClass('error-color');
        //             }
        //         },
        //         error: function(jqXHR) {
        //             $(".main-register-error").text('Something went wrong. Please try again later.');
        //             console.log(jqXHR); // Log the error for debugging purposes
        //         }
        //     });
        //     return false;
        // }



        // Validation and OTP sending logic
        $('#registration-form').validate({
            rules: {
                name: {
                    required: true,
                    lettersonly: true
                },
                mobile: {
                    required: true,
                    digits: true,
                    minlength: 10,
                    maxlength: 10
                },
                email: {
                    required: true,
                    email: true
                },
                password: {
                    required: true,
                    minlength: 8
                },
                confmPassword: {
                    required: true,
                    equalTo: "#password"
                }
            },
            messages: {
                name: {
                    required: "Name is required",
                    lettersonly: "Name should only contain letters and spaces"
                },
                mobile: {
                    required: "Mobile number is required",
                    digits: "Enter a valid 10-digit mobile number"
                },
                email: {
                    required: "Email is required",
                    email: "Enter a valid email address"
                },
                password: {
                    required: "Password is required",
                    minlength: "Password must be at least 8 characters long"
                },
                confmPassword: {
                    required: "Please confirm your password",
                    equalTo: "Passwords do not match"
                }
            },
            submitHandler: function(form) {
                var formData = $(form).serialize();
                $.ajax({
                    type: 'POST',
                    url: '{{ route('user-registration') }}',
                    data: formData,
                    dataType: 'json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        if (data.status === 200) {
                            // Display success message and show OTP input
                            $('#otp-success-alert').removeClass('d-none');
                            $('.otp-section').removeClass('d-none');
                        } else if (data.status === 400 && data.errors) {
                            $.each(data.errors, function(key, value) {
                                $('#' + key).siblings('.error-message').text(value)
                                    .addClass('error-color');
                            });
                        } else {
                            $(".main-register-error").text(data.msg).addClass('error-color');
                        }
                    },
                    error: function(jqXHR) {
                        $(".main-register-error").text(
                            'Something went wrong. Please try again later.');
                    }
                });
            }
        });

        // Add method for name validation
        jQuery.validator.addMethod("lettersonly", function(value, element) {
            return this.optional(element) || /^[a-zA-Z\s]+$/.test(value);
        }, "Name should only contain letters and spaces");


        $('.submit1').click(function() {

            $('.otp-section').show();


            const destination = $('#mobile').val();
            var mobile = $('#mobile').val();
            console.log('Destination mobile number:', destination);
            console.log('Destination mobile number:', mobile);

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
                    } else {
                        $('.error-loginMobNumb').text(response.message ||
                            'Failed to send OTP. Please try again later.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    $('.error-loginMobNumb').text(
                        'An error occurred while sending the request. Please try again later.');
                }
            });
        });


        // Optional: Handle resend OTP click (if needed)
        $('.resend-otp-link').on('click', function(event) {
            event.preventDefault();
            // // Logic to resend OTP goes here
            // alert('Resend OTP functionality needs to be implemented.');
        });




        OTP verification logic (submit2)
        $('#submit2').on('click', function(e) {
            e.preventDefault();
            var otpData = {
                mobile: $('#mobile').val(),
                otp: $('#registerOTP').val(),
                _token: '{{ csrf_token() }}'
            };

            $.ajax({
                type: 'POST',
                url: '{{ route('verify-register-otp') }}',
                data: otpData,
                success: function(response) {
                    if (response.status === 200) {
                        alert('OTP verified successfully! User registered.');
                        window.location.href = '{{ route('home') }}';
                    } else {
                        $('#otp-error-alert').removeClass('d-none').find('.error-message').text(response
                            .message);
                    }
                },
                error: function() {
                    $('#otp-error-alert').removeClass('d-none').find('.error-message').text(
                        'OTP verification failed.');
                }
            });
        });

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
                            window.location.href = '/'; // Redirect to the desired page
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
    </script>
    @stack('scripts')
    </div>
</body>

</html>
