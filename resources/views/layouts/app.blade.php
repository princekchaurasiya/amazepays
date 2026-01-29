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
        
        <style>
            .verify-email {
                margin-top: 10px;
                width: 100%;
                border-radius: 8px;
                font-weight: 500;
                transition: all 0.3s ease;
            }
            /* Working time ribbon */
            .working-ribbon {
                background:rgb(253, 13, 13,0.5);
                color: #fff;
                width: 100%;
                overflow: hidden;
                white-space: nowrap;
                position: relative;
                z-index: 1030; /* above navbar backgrounds */
            }
            .working-ribbon-inner {
                display: inline-block;
                padding: 8px 0;
                animation: ribbon-marquee 18s linear infinite;
            }
            .working-ribbon-text {
                font-weight: 600;
                letter-spacing: 0.3px;
            }
            @keyframes ribbon-marquee {
                0% { transform: translateX(100%); }
                100% { transform: translateX(-100%); }
            }
            
            .verify-email:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
            }
            
            .alert {
                border-radius: 10px;
                border: none;
            }
            
            .alert-success {
                background-color: #d4edda;
                color: #155724;
                border-left: 4px solid #28a745;
            }
            
            .alert-info {
                background-color: #d1ecf1;
                color: #0c5460;
                border-left: 4px solid #17a2b8;
            }
            
            .alert-danger {
                background-color: #f8d7da;
                color: #721c24;
                border-left: 4px solid #dc3545;
            }
            .position-relative { position: relative; }
            .stock-badge {
                position: absolute;
                top: 12px;
                left: 12px;
                background: rgba(0,0,0,0.7);
                color: #fff;
                padding: 6px 10px;
                font-size: 12px;
                border-radius: 4px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
        </style>
    </head>
    <body class="color-theme-blue open-font">
        <div class="cotainer-fluid">
            <div class="preloader"></div>
            <div class="main-wrapper">
                <!-- navigation wrapper starts here -->
                @include('layouts.partials.navbar')
                <!-- navigation wrapper ends here -->
                @if(request()->routeIs('home'))
                <!--<div class="working-ribbon">
                    <div class="working-ribbon-inner">
                        <span class="working-ribbon-text"></span>
                    </div>
                </div>-->
                @endif
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
                                                        <span class="mr-3">
                                                            @if(file_exists(public_path('images/group1.png')))
    <img src="{{ asset('images/group1.png') }}" alt="Group">
@endif

                                                        </span>
                                                        <span class="text-white">Buy or Send Gift Cards Instantly</span>
                                                    </div>
                                                </div>
                                                <div class="rounded-0 text-left pt-3 pb-2">
                                                    <div class="single-line mb-2">
                                                        <span class="mr-3">
                                                            @if(file_exists(public_path('images/group2.png')))
    <img src="{{ asset('images/group2.png') }}" alt="Group">
@endif

                                                        </span>
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
                                                    <span class="font-xssss fw-400 error-message error-otp-display"></span>
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
                                                                    <button class="btn btn-primary registerSendOTP" id = "registerSendOTP"
                                                                        type="button" >Send OTP</button>
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
                                                                        class="input-group-text text-decoration-none resend-otp-link hidden resendOtpButton"
                                                                        hidden type="button">Resend OTP</button>
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
                                                        <div class="form-group icon-tab mb-3 position-relative">
                                                            <input type="password"
                                                                class="form-control h60 border-2 bg-color-none text-grey-700 credentails-field  pl-2"
                                                                placeholder="Password" id="regstPasswordInput" autocomplete="new-password">

                                                            <i class="fa fa-eye-slash toggle-register-Password-icon pr-0"
                                                                id="toggleRegstPasswordButton"></i>
                                                            <span
                                                                class="font-xssss fw-400 error-password error-message"></span>
                                                        </div>
                                                        <div class="form-group icon-tab mb-3 position-relative">
                                                            <input type="password"
                                                                class="form-control h60 border-2 bg-color-none text-grey-700 credentails-field  pl-2"
                                                                placeholder="Confirm Password" id="confmRegstPasswordInput" autocomplete="new-password">



                                                            <i class="fa fa-eye-slash toggle-register-Password-icon pr-0"
                                                                id="toggleRegstConfirmPasswordButton"></i>
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
                                                        {{--
                                                        <div class="row">
                                                            <div class="col-6 pr-1"><a href="#"
                                                                class="form-control h60 p-0 pl-5 bg-lightblue text-grey-700 border-2 border-0 font-xssss fw-600  position-relative">Login
                                                                with OTP</a>
                                                            </div>
                                                            <div class="col-6 pl-1"><a href="#"
                                                                class="form-control h60 p-0 pl-5 bg-lightblue text-grey-700 border-2 border-0 font-xssss fw-600  position-relative">Forgot
                                                                Password?</a>
                                                            </div>
                                                        </div>
                                                        --}}
                                                        <p
                                                            class="fw-500 font-xssss text-grey-600 mt-2 pt-3 d-inline-block">
                                                            By continuing, you agree to Amazepay's <a
                                                                href="{{ route('terms-of-use') }}" class="text-current">Term and
                                                            Condition</a> and <a href="{{ route('privacy-policy') }}"
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
                                                        <span class="mr-3">
                                                            @if(file_exists(public_path('images/group2.png')))
                                                                <img src="{{ asset('images/group2.png') }}" alt="Group">
                                                            @endif

                                                        </span>
                                                        <span class="text-white">Buy or Send Gift Cards Instantly</span>
                                                    </div>
                                                </div>
                                                <div class="rounded-0 text-left pt-3 pb-2">
                                                    <div class="single-line mb-2">
                                                        <span class="mr-3">
                                                            @if(file_exists(public_path('images/group1.png')))
                                                                 <img src="{{ asset('images/group1.png') }}" alt="Group">
                                                            @endif

                                                        </span>
                                                        <span class="text-white">Send Gift Cards to Friends and Family
                                                        Choose from Hundreds of Popular Brands.</span>
                                                    </div>
                                                </div>
                                                <h4 class="fw-600 display2-size-sm mb-4 text-center">Cashback and Gift
                                                    Cards
                                                </h4>
                                                <p class="text-center"><img src="{{ asset('images/logo_white.png') }}"
                                                    alt="AmazePays" class="img-fluid" width="150"></p>
                                            </div>
                                        </div>
                                        <div class="col-lg-7">
                                            <div class="card shadow-none rounded-0 w-100 p-2 pt-3 border-0">
                                                <div class="card-body rounded-0 text-left pt-0 pb-2">
                                                    <h2 class="fw-600 display2-size mb-4 ">Login</h2>
                                                    <div class="alert alert-success text-center" role="alert" id="passwordSuccessMessageLogin" style="display: none;">
                                                        Password changed successfully! You can now log in with your new password
                                                    </div>

                                                    <span class="font-xssss fw-400 main-error text-center"></span>
                                                    <form id="login-form">
                                                        <div class="form-group mb-3">
                                                            <input type="text"
                                                                class="form-control h60 border-2 bg-color-none text-grey-700 credentails-field"
                                                                placeholder="Enter Your Mobile Number" id="loginMobNumb">
                                                            <span
                                                                class="font-xssss fw-400 error-loginMobNumb text-danger"></span>
                                                        </div>
                                                        <div class="form-group mb-3 position-relative">
                                                            <input type="password" class="form-control h60 border-2 bg-color-none text-grey-700 credentails-field" placeholder="Enter Password" id="loginPasswordInput" autocomplete="current-login-password">
                                                            <i class="fa fa-eye-slash position-absolute toggle-password" id="toggleLoginPasswordButton"
                                                               style="top: 50%; right: 15px; transform: translateY(-50%); cursor: pointer;"></i>
                                                            <span class="font-xssss fw-400 error-loginPass text-danger"></span>
                                                        </div>


                                                        <div class="form-check text-left mb-3">
                                                            <input type="checkbox" class="form-check-input mt-2"
                                                                id="exampleCheck1">
                                                            <label class="form-check-label font-xsss text-grey-500"
                                                                for="exampleCheck1">Remember me</label>
                                                            <a href="#" class="fw-600 font-xsss text-grey-700 mt-1 float-right"
                                                                data-toggle="modal" data-target="#ModalForgotAndRsetPassword"
                                                                data-dismiss="modal">Forgot your Password?</a>
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
                                                                data-dismiss="modal">Register</a>
                                                        </h6>
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
                    <div class="modal-header otp-modal-header py-4">
                        <h5 class="modal-title font-weight-bold otp-modal-header-text" id="otpModalLabel">OTP Verification</h5>
                        <button type="button" class="close text-white otp-verification-close-button" data-dismiss="modal" aria-label="Close"
                            id="closeModalButton">
                        <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body otp-modal-body">
                        <h6 class="text-center otp-modal-body-text-primary">Please enter the one-time password<br>to verify your account
                        </h6>
                        <div class="text-center mt-3 otp-modal-body-text-secondary">
                            <span>A code has been sent to</span>
                            <small class="font-weight-bold">******* <span id="maskedPhone">1234</span></small>
                        </div>
                        <div id="otp" class="inputs d-flex justify-content-center mt-4">
                            <!-- Create input fields for the OTP digits (1 to 6) -->
                            <input class="m-2 text-center form-control rounded otp-input" type="text" id="first"
                                maxlength="1" inputmode="numeric"  oninput="moveToNext(this, 'second')" />
                            <input class="m-2 text-center form-control rounded otp-input" type="text" id="second"
                                maxlength="1" inputmode="numeric"  oninput="moveToNext(this, 'third')" />
                            <input class="m-2 text-center form-control rounded otp-input" type="text" id="third"
                                maxlength="1" inputmode="numeric"  oninput="moveToNext(this, 'fourth')" />
                            <input class="m-2 text-center form-control rounded otp-input" type="text" id="fourth"
                                maxlength="1" inputmode="numeric"  oninput="moveToNext(this, 'fifth')" />
                            <input class="m-2 text-center form-control rounded otp-input" type="text" id="fifth"
                                maxlength="1" inputmode="numeric"  oninput="moveToNext(this, 'sixth')" />
                            <input class="m-2 text-center form-control rounded otp-input" type="text" id="sixth"
                                maxlength="1" />
                        </div>
                        <!-- Placeholder element for displaying error message -->
                        <div class="error-otpVerifyInput text-center text-danger mt-3 font-weight-bold"></div>
                    </div>
                    <div class="modal-footer justify-content-center py-3">
                        <!-- Submit button to validate and verify the OTP -->
                        <button class="btn  px-4 py-2 rounded-pill font-weight-bold otp-submit-button"
                            id="otpVerificationButton">Submit</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- modal otp validation ends here -->
        <!-- Modal Forgot Password Starts Here -->
        <div class="modal fade ModalForgotAndRsetPassword" id="ModalForgotAndRsetPassword" tabindex="-1" role="dialog"
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
                                                        <span class="mr-3">
                                                            @if(file_exists(public_path('images/group1.png')))
    <img src="{{ asset('images/group1.png') }}" alt="Group">
@endif

                                                        </span>
                                                        <span class="text-white">Buy or Send Gift Cards Instantly</span>
                                                    </div>
                                                </div>
                                                <div class="rounded-0 text-left pt-3 pb-2">
                                                    <div class="single-line mb-2">
                                                        <span class="mr-3">
                                                            @if(file_exists(public_path('images/group2.png')))
    <img src="{{ asset('images/group2.png') }}" alt="Group">
@endif

                                                        </span>
                                                        <span class="text-white">Send Gift Cards to Friends and Family
                                                        Choose from Hundreds of Popular Brands.</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-7">
                                            <div class="card shadow-none rounded-0 w-100 p-2 mt-3 pt-3 border-0">
                                                <div class="card-body rounded-0 text-left pt-0">
                                                    <!-- Success Message (Placed at the Top) -->
                                                    <h2 class="fw-600 display2-size mb-4">Forgot Password</h2>
                                                    <p class="my-4">First, verify your mobile number with the OTP sent to the number linked to your account.</p>

                                                    <form id="forgotReset-form">
                                                        <span class="font-xssss fw-400 main-forget-password-error text-center"></span>

                                                        <div class="form-group mb-3">
                                                            <div class="input-group">
                                                                <input type="text"
                                                                    class="form-control h60 border-2 bg-color-none text-grey-700 credentails-field"
                                                                    placeholder="Mobile Number" id="forgetPasswordMobile">
                                                                <div class="input-group-append">
                                                                    <button class="btn btn-primary forgotPasswordSendOTP"
                                                                        type="button" id="forgotPasswordSendOTP">Send OTP</button>
                                                                </div>
                                                            </div>
                                                            <span class="font-xssss fw-400 error-forgetPasswordOtp error-message text-danger error-mobile"></span>
                                                        </div>

                                                        <div class="form-group mb-3 otp-section-forgot" style="display: none;">
                                                            <div class="input-group">
                                                                <input type="text"
                                                                    class="form-control h60 border-2 bg-color-none text-grey-700 credentails-field forgetPasswordOtp"
                                                                    placeholder="Enter Your OTP code" id="forgetPasswordOtp">
                                                                <div class="input-group-append">
                                                                    <button id="resendForgetPasswordOtpButton"
                                                                        class="input-group-text text-decoration-none resend-otp-link hidden resendOtpButton"
                                                                        hidden type="button">Resend OTP</button>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="form-group icon-tab mb-3 position-relative">
                                                            <input type="password"
                                                                class="form-control h60 border-2 bg-color-none text-grey-700 credentails-field pl-2"
                                                                placeholder="Password" id="forgetNewPasswordInput" autocomplete="forget-new-password">
                                                            <i class="fa fa-eye-slash toggle-register-Password-icon pr-0"
                                                                id="toggleForgetPasswordButton"></i>
                                                            <!-- ✅ Updated span class -->
                                                            <span class="font-xssss fw-400 error-forget-password error-message"></span>
                                                        </div>

                                                        <div class="form-group icon-tab mb-3 position-relative">
                                                            <input type="password"
                                                                class="form-control h60 border-2 bg-color-none text-grey-700 credentails-field pl-2"
                                                                placeholder="Confirm Password" id="forgetConfmNewPasswordInput"
                                                                autocomplete="forget-confirm-new-password">
                                                            <i class="fa fa-eye-slash toggle-register-Password-icon pr-0"
                                                                id="toggleForgetConfirmPasswordButton"></i>
                                                            <span class="font-xssss fw-400 error-forget-confmPassword error-message"></span>
                                                        </div>

                                                        <div class="form-group icon-tab mb-3">
                                                            <a href="#"
                                                                class="text-center register-button-color form-control h60 bg-current text-white font-xss fw-500 border-0 p-0"
                                                                id="forgotPasswordSubmit">Submit</a>
                                                        </div>
                                                    </form>

                                                    <div class="col-sm-12 p-0 text-center">
                                                        <h6 class="text-grey-500 font-xsss fw-500 mt-2 mb-4 lh-32">
                                                            Are you already a member?
                                                            <a href="#" class="fw-700 ml-1 text-orange"
                                                                data-toggle="modal" data-target="#Modallogin"
                                                                data-dismiss="modal">Login</a>
                                                        </h6>

                                                        <div class="alert alert-success text-center" role="alert" id="passwordSuccessMessage" style="display: none;">
                                                            Password changed successfully! You can now log in with your new password.
                                                        </div>

                                                        <p class="fw-500 font-xssss text-grey-600 mt-2 pt-3 d-inline-block">
                                                            By continuing, you agree to Amazepay's
                                                            <a href="{{ route('terms-of-use') }}" class="text-current">Terms and Conditions</a> and
                                                            <a href="{{ route('privacy-policy') }}" class="text-current">Privacy Policy</a>.
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
        <!-- Modal Forgot Password Ends Here -->
        <!-- Script links starts here -->
        @include('layouts.partials.script-links')
        <!-- Script links ends here -->
        <script>
            function verifyemail()
            {
                var email = $('#email').val();
                if(email=='')
                {
                    alert('Please enter email');
                    return false;
                }
                $.ajax({
                    url: "{{ route('verify.email') }}",
                    type: "POST",
                    data: {
                        email: email,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            alert(response.message);
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error: ", status, error);
                        alert('An error occurred while verifying the email. Please try again.');
                    }
                });
            }

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



            $(document).ready(function () {
                // Function to toggle password visibility
                function togglePassword(toggleButtonId, inputFieldId) {
                    $(toggleButtonId).click(function () {
                        const inputField = $(inputFieldId);
                        const isPassword = inputField.attr("type") === "password";

                        // Toggle input type between password and text
                        inputField.attr("type", isPassword ? "text" : "password");

                        // Toggle icon between fa-eye and fa-eye-slash
                        $(this).toggleClass("fa-eye fa-eye-slash");
                    });
                }

                // Apply the function to respective fields
                togglePassword("#toggleLoginPasswordButton", "#loginPasswordInput");
                togglePassword("#toggleRegstPasswordButton", "#regstPasswordInput");
                togglePassword("#toggleRegstConfirmPasswordButton", "#confmRegstPasswordInput");
                togglePassword("#toggleForgetPasswordButton", "#forgetNewPasswordInput");
                togglePassword("#toggleForgetConfirmPasswordButton", "#forgetConfmNewPasswordInput");
            });



            $('#createUser').click(function(e) {
                e.preventDefault();
                var name = $('#name').val();
                var mobile = $('#mobile').val();
                var email = $('#email').val();
                var password = $('#regstPasswordInput').val();
                console.log("12233 Prince");


                // var otp = $('#registerOTP').val();
                var confmPassword = $('#confmRegstPasswordInput').val();


                var regxMobile = /^(?:(?:\+|0{0,2})91)?[789]\d{9}$/;
                var regxEmail = /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z]+$/;
                var registerSendOtp = $('#registerOTP').val();

                var status = true;





                // Clear previous error messages
                $('.error-message').empty();
                $('.error-name').empty();
                $('.error-mobile').empty();
                $('.error-email').empty();
                $('.error-password').empty();
                $('.error-confmPassword').empty();
                $('.error-otp-display').empty();

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
                    $(".error-otp-display").text('Please enter the OTP to verify your mobile number').addClass(
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
                let passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).+$/;

                if (password.length === 0) {
                    status = false;
                    $(".error-password").text('Password is required').addClass('error-color fw-800');
                } else if (confmPassword !== password) {
                    status = false;
                    $(".error-confmPassword").text('Password does not match').addClass('error-color fw-800');
                }else if (password.length < 8) {
                    isValid = false;
                    $('.error-password').text('Password must be at least 8 characters');
                } else if (!passwordRegex.test(password)) {
                    isValid = false;
                    $('.error-password').text('Password must contain lowercase, uppercase, number, and special character');
                }

                if (status) {
                    userRegister(name, mobile, email, password, confmPassword, registerSendOtp);
                }

                return status;
            });







            function userRegister(name, mobile, email, password, confmPassword, registerSendOtp) {
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

                formData.append('otp', registerSendOtp);

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
                            // Redirect to verify email page after successful registration
                            window.location.href = '{{ route("verify.email") }}';
                        } else if (data.status == 400 && data.errors) {
                            // Display duplicate entry errors within the modal
                            $.each(data.errors, function(key, value) {
                                // debugger;
                                if (key == 'registerOTP') {
                                    $('.error-otp-display').text(value).addClass('error-color');
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


            $('#forgotPasswordSubmit').click(function (e) {
    console.log('Forgot Password Submit button clicked');
    e.preventDefault();

    // Clear previous errors before submitting
    $(".error-message").text("").removeClass('error-color');

    // Trim and log inputs to ensure values are present
    var mobile = $('#forgetPasswordMobile').val().trim();
    var forgetNewPassword = $('#forgetNewPasswordInput').val().trim();
    var forgetConfmNewPassword = $('#forgetConfmNewPasswordInput').val().trim();
    var forgetPasswordOtp = $('#forgetPasswordOtp').val().trim();

    console.log('Mobile:', mobile || "EMPTY");
    console.log('New Password:', forgetNewPassword || "EMPTY");
    console.log('Confirm Password:', forgetConfmNewPassword || "EMPTY");
    console.log('OTP:', forgetPasswordOtp || "EMPTY");

    // Front-end validation
    var isValid = true;

    if (mobile === "") {
        $(".error-mobile").text("Mobile number is required").addClass('error-color');
        isValid = false;
    }

    if (forgetPasswordOtp === "") {
        $(".error-forgetPasswordOtp").text("OTP is required").addClass('error-color');
        isValid = false;
    }

    if (forgetNewPassword === "") {
        $(".error-forget-password").text("New password is required").addClass('error-color');
        isValid = false;
    } else if (forgetNewPassword.length < 6) {
        $(".error-forget-password").text("Password must be at least 6 characters").addClass('error-color');
        isValid = false;
    }

    if (forgetConfmNewPassword === "") {
        $(".error-forget-confmPassword").text("Confirm password is required").addClass('error-color');
        isValid = false;
    } else if (forgetNewPassword !== forgetConfmNewPassword) {
        $(".error-forget-confmPassword").text("Passwords do not match").addClass('error-color');
        isValid = false;
    }

    // If all validations pass, proceed with submission
    if (isValid) {
        forgetPasswordSubmit(mobile, forgetNewPassword, forgetConfmNewPassword, forgetPasswordOtp);
    }

    return false; // Prevent default form submission
});



function forgetPasswordSubmit(mobile, forgetNewPassword, forgetConfmNewPassword, forgetPasswordOtp) {
    console.log('You are in forgetPasswordSubmit function');

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    var formData = new FormData();
    formData.append('mobile', mobile);
    formData.append('newPassword', forgetNewPassword);
    formData.append('confirm_new_password', forgetConfmNewPassword);
    formData.append('otp', forgetPasswordOtp);

    console.log("Sending data:", {
        mobile,
        newPassword: forgetNewPassword,
        confirm_new_password: forgetConfmNewPassword,
        otp: forgetPasswordOtp
    });

    $.ajax({
    type: 'POST',
    url: "{{ route('user-forgot-password') }}",
    data: formData,
    processData: false,
    contentType: false,
    dataType: 'json',
    success: function(data) {
        console.log("Response:", data);
        if (data.status == 200) {
    // Clear input fields
    $("#ModalForgotAndRsetPassword input").val("");

    // Hide the Reset Password Modal properly
    $("#ModalForgotAndRsetPassword").modal("hide");

    // Ensure modal completely disappears before showing new one
    setTimeout(() => {
        $(".modal-backdrop").remove();  // Remove backdrop
        $("body").removeClass("modal-open"); // Remove modal-open class from body
        $("#ModalForgotAndRsetPassword").css("display", "none"); // Force hide modal

        // Show success message
        $("#passwordSuccessMessageLogin").text(data.message).show();

        // Set mobile number in login form and clear password field
        $("#loginMobNumb").val(mobile);
        $("#loginPass").val("");

        // Show login modal properly
        $("#Modallogin").modal("show");
    }, 300);
}
else if (data.status == 400 && data.errors) {
            $(".error-message").text("").removeClass('error-color'); // Clear previous errors
            $.each(data.errors, function(key, value) {
                let errorMsg = Array.isArray(value) ? value[0] : value;
                console.log(`Error Key: ${key}, Value: ${errorMsg}`);

                if (key === 'mobile') $('.error-mobile').text(errorMsg).addClass('error-color');
                else if (key === 'otp') $('.error-forgetPasswordOtp').text(errorMsg).addClass('error-color');
                else if (key === 'newPassword') $('.error-forget-password').text(errorMsg).addClass('error-color');
                else if (key === 'confirm_new_password') $('.error-forget-confmPassword').text(errorMsg).addClass('error-color');
            });
        } else {
            $(".main-forget-password-error").text(data.message || "Something went wrong. Please try again later.").addClass('error-color');
        }
    },
    error: function(jqXHR) {
        console.log("AJAX Error:", jqXHR);
        $(".main-forget-password-error").text(jqXHR.responseJSON?.message || "Something went wrong.");
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
                console.log(mobile);
                var password = $('#loginPasswordInput').val();
                console.log(password);

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
                var ajaxurl = "{{ route('user-login') }}";

                $.ajax({
                    type: type,
                    url: ajaxurl,
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(data) {
                        if (data.status == 200) {
                            // Only redirect on successful login
                            window.location.href = data.redirect || "{{ route('home') }}";
                        } else if (data.status == 403) {
                            // Show error message for blocked user
                            var errorMessage = data.msg || 'Your account has been restricted. Please contact support.';
                            var errorHtml = '<div class="alert alert-danger">' +
                                errorMessage +
                                (data.contact_info ? '<hr class="my-2">' +
                                '<p class="mb-1"><i class="fa fa-envelope mr-2"></i>' + data.contact_info.email + '</p>' +
                                '<p class="mb-0"><i class="fa fa-phone mr-2"></i>' + data.contact_info.phone + '</p>' : '') +
                                '</div>';

                            $(".main-error").html(errorHtml).show();
                            $('#loginPasswordInput').val('');
                        } else {
                            // Show invalid credentials message
                            $(".main-error").html('<div class="alert alert-danger">Invalid credentials</div>').show();
                            // Clear only the password field
                            $('#loginPasswordInput').val('');
                        }
                    },
                    error: function(xhr) {
                        // Show error message from server or default message
                        var errorMessage = xhr.responseJSON?.message || 'An error occurred. Please try again.';
                        $(".main-error").html('<div class="alert alert-danger">' + errorMessage + '</div>').show();
                        // Clear only the password field
                        $('#loginPasswordInput').val('');
                    }
                });

                return false;
            }
            // end login

            //otp modal keyboard button handling code starts here
            $(document).ready(function () {
            function OTPInput() {
            const inputs = $('#otp > .otp-input');

            // Restrict input to numbers only
            inputs.on('input', function () {
                const value = $(this).val();
                if (!/^\d$/.test(value)) {
                    $(this).val(''); // Clear if not a digit
                }
            });

            // Handle backspace and navigation
            inputs.on('keydown', function (event) {
                const index = inputs.index(this);

                if (event.key === "Backspace") {
                    $(this).val('');
                    if (index !== 0) inputs.eq(index - 1).focus();
                }
            });

            // Handle pasting OTP (for both desktop & mobile)
            inputs.on('paste', function (event) {
                event.preventDefault();

                let pastedData = '';

                if (event.originalEvent.clipboardData) {
                    // Desktop: Get clipboard data
                    pastedData = event.originalEvent.clipboardData.getData('text').trim();
                } else if (window.clipboardData) {
                    // Mobile fallback
                    pastedData = window.clipboardData.getData('Text').trim();
                }

                if (/^\d{6}$/.test(pastedData)) { // Ensure exactly 6 digits
                    const digits = pastedData.split('');
                    inputs.each(function (i) {
                        $(this).val(digits[i]);
                    });
                    inputs.last().focus(); // Move to last input
                }
            });

            // Mobile keyboard support (auto-jump to next field)
            inputs.on('input', function () {
                const index = inputs.index(this);
                if ($(this).val() !== '' && index < inputs.length - 1) {
                    inputs.eq(index + 1).focus();
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

            //         $(document).ready(function () {
            //     $("#openModalLink").click(function () {
            //         $("#otpVerificationModal").modal("show");
            //     });
            // });

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


            $(document).ready(function () {
            // Open Sidebar
            $('.amazepay-sidebar-toggle').click(function () {
            $('.amazepay-sidebar').addClass('active');
            $('.amazepay-overlay').addClass('active');
            });

            // Close Sidebar
            $('.amazepay-sidebar-close, .amazepay-overlay').click(function () {
            $('.amazepay-sidebar').removeClass('active');
            $('.amazepay-overlay').removeClass('active');
            });
            });

            $(document).ready(function () {
    $('.modal').on('shown.bs.modal', function () {
        $(this).removeAttr('aria-hidden').removeAttr('inert');
    });

    $('.modal').on('hidden.bs.modal', function () {
        $(this).attr('aria-hidden', 'true').attr('inert', '');

        // Ensure focus moves outside the modal when hidden
        $('body').focus();
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


            $('#registerSendOTP').click(function() {
            console.log('Register Send OTP button clicked inside #registerSendOTP');

            const destination = $('#mobile').val();
            console.log('Destination mobile number:', destination);

            // Call sendotp function with actionType as 'register'
            sendotp(destination, 'register');
            });

            $('#forgotPasswordSendOTP').click(function() {
            console.log('Forgot Password Send OTP button clicked inside #forgotPasswordSendOTP');

            const destination = $('#forgetPasswordMobile').val();
            console.log('Destination mobile number:', destination);

            // Call sendotp function with actionType as 'forgotPassword'
            sendotp(destination, 'forgotPassword');
            });


            $('.resendOtpButton').click(function() {

            console.log('Resend OTP button clicked');
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





            function sendotp(destination, actionType) {
            let url = '';

            // Determine the URL based on action type
            if (actionType === 'register') {
            url = '{{ route('send-register-otp') }}';
            } else if (actionType === 'forgotPassword') {
            url = '{{ route('send-forgot-password-otp') }}';
            }

            $.ajax({
            type: 'POST',
            url: url,
            data: {
                _token: '{{ csrf_token() }}',
                destination: destination
            },
            dataType: 'json',
            success: function(response) {
                console.log('Response:', response);
                if (response.status === 'success') {
                    console.log('OTP sent successfully.');

                    if (actionType === 'register') {
                        $('.registerSendOTP').closest('.form-group').hide();
                        $('.otp-section').show();
                    } else if (actionType === 'forgotPassword') {
                        $('.forgotPasswordSendOTP').closest('.form-group').hide();
                        $('.otp-section-forgot').show();
                    }

                    setTimeout(function() {
                        $('.resendOtpButton').removeAttr('hidden');
                    }, 30000);
                } else {
                    const errorMessage = response.message || 'Failed to send OTP. Please try again later.';

                    if (actionType === 'register') {
                        $('.error-registerMobNumb').text(errorMessage);
                    } else if (actionType === 'forgotPassword') {
                        $('.error-forgetPasswordOtp').text(errorMessage);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                const errorText = 'An error occurred while sending the request. Please try again later.';

                if (actionType === 'register') {
                    $('.error-registerMobNumb').text(errorText);
                } else if (actionType === 'forgotPassword') {
                    $('.error-forgetPasswordOtp').text(errorText);
                }
            }
            });
            }







            // Optional: Handle resend OTP click (if needed)


            let isOtpVerified = false;









        </script>
        @stack('scripts')
        
        <!-- Value Design Brands Loader - Only load on home page -->
        {{-- Commented out - Value Design gift cards not needed for now --}}
        {{-- @if(request()->routeIs('home') || request()->is('/'))
            <link rel="stylesheet" href="{{ asset('css/vd-brands.css') }}">
            <script src="{{ asset('js/vd-brands-loader.js') }}"></script>
        @endif --}}
        </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="errorModalLabel">Account Restricted</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Error content will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="mailto:{{ config('app.support_email', 'support@amazepay.com') }}" class="btn btn-primary">Contact Support</a>
                </div>
            </div>
        </div>
    </div>

    </body>
</html>
