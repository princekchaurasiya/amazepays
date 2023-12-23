<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title> @yield('title')</title>
    @yield('css')
    <link rel="stylesheet" href="{{ URL::asset('css/themify-icons.css') }}">
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="{{ url('images/favicon.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css" />
    <!-- Bootstrap Stylesheet -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.0/css/bootstrap-grid.min.css" />
    <!-- Bootstrap Select Stylesheet -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/jquery.slick/1.6.0/slick.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/jquery.slick/1.6.0/slick-theme.css" rel="stylesheet" />
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="{{ URL::asset('css/style.css') }}">
</head>

<body class="color-theme-blue open-font">
    <div class="cotainer">
        <div class="preloader"></div>
        <div class="main-wrapper">
            <!-- header wrapper -->
            {{--
            <div class="upper-header bg-greylight">
               <div class="container">
                  <div class="row">
                     <div class="col-md-6 col-xs-6 d-none d-block-md">
                        <ul class="list-inline list-item-style mt-0 float-left pl-1">
                           <li class="list-inline-item pl-0"><a href="#">(+1)866-540-3229</a></li>
                        </ul>
                     </div>
                     @if (auth()->check())
                     <div class="col-md-6 col-xs-6 d-none d-block-md">
                        <ul class="list-inline list-item-style mt-0 float-right">
                           <li class="list-inline-item"><a href="#"><i class="ti-user mr-2"></i> My
                              Account</a>
                           </li>
                        </ul>
                     </div>
                     @endif
                     <div class="col-12 d-none d-lg-block">
                        <ul class="list-inline list-item-style mt-0 float-left pl-1">
                           <li class="list-inline-item pl-0"><a href="#">BECOME AN AGENT</a></li>
                           <li class="list-inline-item pl-0"><a href="tel:98211 99497">(+91) 98211 99497</a></li>
                        </ul>
                        <ul class="list-inline list-item-style mt-0 float-right">
                           <li class="list-inline-item"><a href="#">PRIVACY</a></li>
                           <li class="list-inline-item"><a href="#">CUSTOMER SERVICE </a></li>
                           <li class="list-inline-item"><a href="#"><i class="ti-location-pin mr-2"></i>Store
                              Locator</a>
                           </li>
                           <li class="list-inline-item"><a href="#"><i class="ti-user mr-2"></i> My Account</a>
                           </li>
                        </ul>
                     </div>
                  </div>
               </div>
            </div>
            --}}
            <!-- header wrapper desktop view -->
            <div class="header-wrapper d-none d-md-block">
                <div class="container">
                    <div class="row sticky-top">
                        <div class="col-7 navbar">
                            <a href="/" class="logo"><img src="{{ asset('images/logo.png') }}" alt="logo"
                                    class="custLogo"></a>
                            <div class="collapse navbar-collapse" id="navbarNavDropdown">
                                <ul class="navbar-nav nav-menu float-none text-center">
                                    <li class="nav-item"><a class="nav-link" href="/">Home</a></li>
                                    {{--
                              <li class="nav-item dropdown">
                                 <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">Services <i
                                    class="ti-angle-down"></i></a>
                                 <div class="dropdown-menu">
                                    <a class="dropdown-item" href="#">Money Transfer</a>
                                    <a class="dropdown-item" href="#">Bill payment</a>
                                 </div>
                              </li>
                              --}}
                                    {{--
                              <li class="nav-item dropdown">
                                 <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">Products <i
                                    class="ti-angle-down"></i></a>
                                 <div class="dropdown-menu">
                                    <a class="dropdown-item" href="#">Gift Cards</a>
                                    <a class="dropdown-item" href="#">Bank Gift Cards</a>
                                 </div>
                              </li>
                              --}}
                                    <li class="nav-item"><a class="nav-link" href="{{ route('about') }}">About</a></li>
                                    <li class="nav-item"><a class="nav-link" href="{{ route('contact-us') }}">Contact</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-5  text-right align-self-center">
                            @if (Auth::check())
                                <li class="dropdown">
                                    <button class="btn dropdown-toggle pr-0 pl-0" type="button"
                                        id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                        {{ Auth::user()->name }}
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                        <li><a class="dropdown-item" href="{{ route('profile') }}"><i class="ti-user font-sm"></i>
                                                Profile</a></li>
                                        <li><a class="dropdown-item" href="{{ route('my-order') }}"><i
                                                    class="fa-sharp fa-solid fa-cart-shopping"></i> My Order</a>
                                        </li>
                                        <li><a class="dropdown-item" href="{{ route('userLogOut') }}"><i
                                                    class="fa-sharp fa-solid fa-power-off"></i> Logout</a></li>
                                    </ul>
                                </li>
                            @else
                                <a href="#"
                                    class="header-btn bg-dark fw-500 text-white font-xssss login-button-color"
                                    data-toggle="modal" data-target="#Modallogin">Login</a>
                                <a href="#"
                                    class="header-btn bg-current fw-500 text-white font-xssss register-form register-button-color"
                                    data-toggle="modal" data-target="#ModalregisterD">Register</a>
                                {{-- <a href="{{ route('send-test-sms') }}" class="header-btn bg-current fw-500 text-white font-xssss">Send
                        SMS</a> --}}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <!-- header wrapper mobile view -->
            <div class="header-wrapper d-block d-md-none">
                <nav class="navbar navbar-expand-lg navbar-light navbar-mobile ">
                    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
                        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <a href="/" class="logo custMobLogo"><img src="{{ asset('images/logo.png') }}" alt="logo"
                            class="custLogo"></a>
                    <div class="user-profile">
                        <a href="{{ route('profile') }}" class="profile-link">
                            <i class="fas fa-user-circle"></i>
                        </a>
                    </div>

                </nav>
                <!-- Side Navigation -->
                <div class="sidenav ">
                    <ul class="sidenav-list">
                        <li class="sidenav-item">
                            <a class="sidenav-link" href="#">Home</a>
                        </li>
                        <li class="sidenav-item">
                            <a class="sidenav-link" href="#">Services</a>
                        </li>
                        <li class="sidenav-item">
                            <a class="sidenav-link" href="#">Products</a>
                        </li>
                        <li class="sidenav-item">
                            <a class="sidenav-link" href="#">About</a>
                        </li>
                        <li class="sidenav-item">
                            <a class="sidenav-link" href="#">Contact</a>
                        </li>
                    </ul>
                    <button class="btn close-sideNav-btn" onclick="closeNav()">&times;</button>
                </div>
            </div>
            <!-- header wrapper mobile view -->
        </div>
        <!-- header wrapper -->
        @yield('content')
        <!-- footer wrapper -->
        <div class="footer-wrapper mt-0">
            <div class="container">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="row">
                            <div class="col-md-12 col-lg-4 col-sm-9 col-xs-12 md-mb25">
                                <!-- <a href="index.html" class="logo"><img src="images/logo.png" alt="logo"></a> -->
                                <a href="/" class="logo"><img src="{{ asset('images/logo.png') }}"
                                        alt="logo" class="custLogo"></a>
                                <p class="w-100 mt-lg-5 mt-4">98-103, 4 Floor, Aditya Industrial Estate Co-op
                                    Premises Ltd<br /> Mindspace Behind Evershine
                                    Mall Off Link Road Malad West <br /> Mumbai -400064.
                                </p>
                            </div>
                            {{-- <div class="col-md-3 col-lg-2 col-sm-4 col-xs-6">
                                <h5>Channel</h5>
                                <ul>
                                    <li><a href="#">Gift Cards</a></li>
                                    <li><a href="#">bank Cards</a></li>
                                    <li><a href="#">Vouchers</a></li>
                                </ul>
                            </div> --}}
                            <div class="col-md-3 col-lg-2 col-sm-4 col-xs-6">
                                <h5>About</h5>
                                <ul>
                                    {{--
                              <li><a href="#">FAQ</a></li>
                              --}}
                                    <li><a href="{{ url('terms_of_use') }}">Term of use</a></li>
                                    <li><a href="{{ url('private_policy') }}">Privacy Policy</a></li>
                                </ul>
                            </div>
                            <div class="col-md-3 col-lg-4 col-sm-4 col-xs-6">
                                <h5 class="mb-3">Office</h5>
                                <p style="width: 100%;">98-103, 4 Floor, Aditya Industrial Estate Co-op Premises
                                    Ltd Mindspace Behind Evershine
                                    Mall Off Link Road Malad (West) Mumbai 400064.
                                </p>
                            </div>
                            <div class="col-md-3 col-lg-2 col-sm-3 col-xs-6 md-mb25">
                                <h5 class="mb-3">Follow us on</h5>
                                <ul class="list-inline">
                                    <li class="list-inline-item mr-3"><a href="#"><i
                                                class="ti-facebook"></i></a>
                                    </li>
                                    <li class="list-inline-item mr-3"><a href="#"><i
                                                class="ti-twitter-alt"></i></a>
                                    </li>
                                    <li class="list-inline-item mr-3"><a href="#"><i
                                                class="ti-linkedin"></i></a>
                                    </li>
                                    <li class="list-inline-item"><a href="#"><i class="ti-instagram"></i></a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="middle-footer mt-5 pt-4"></div>
                    </div>
                    <div class="col-sm-12 lower-footer pt-0"></div>
                    <div class="col-sm-6 col-xs-12">
                        <p class="copyright-text">© 2021 copyright. All rights reserved.</p>
                    </div>
                    <div class="col-sm-6 col-xs-12 text-right">
                        <p class="copyright-text float-right">Design & Develop by <a href="#"
                                class="">Toutle</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <!-- footer wrapper -->
    </div>
    <!-- Modal Register -->
    <div class="modal fade" id="ModalregisterD" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
        data-backdrop="true" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i
                        class="ti-close text-grey-500"></i></button>
                <div class="modal-body p-3 d-flex align-items-center bg-none">
                    <div class="card shadow-none rounded-0 w-100 p-2 pt-3 border-0">
                        <div class="card-body rounded-0 text-left pt-0">
                            <h2 class="fw-600 display2-size mb-4">Create your account</h2>
                            <form id="registration-form">
                                <span class="font-xssss fw-400 main-register-error text-center"></span>
                                <div class="form-group mb-3">
                                    <input type="text"
                                        class="form-control h60 border-2 bg-color-none text-grey-700"
                                        placeholder="Name" id="name" autocomplete="off">
                                    <span class="font-xssss fw-400 error-message error-name"></span>
                                </div>
                                <div class="form-group mb-3">
                                    <input type="text"
                                        class="form-control h60 border-2 bg-color-none text-grey-700"
                                        placeholder="Mobile Number" id="mobile">
                                    <span class="font-xssss fw-400  error-message error-mobile"></span>
                                </div>
                                <div class="form-group mb-3">
                                    <input type="text"
                                        class="form-control h60 border-2 bg-color-none text-grey-700"
                                        placeholder="Email" id="email" autocomplete="off" id="registerEmail"
                                        name="registerEmail">
                                    <span class="font-xssss fw-400 error-message error-email"></span>
                                </div>
                                <div class="form-group icon-tab mb-3">
                                    <input type="text"
                                        class="form-control h60 border-2 bg-color-none text-grey-700"
                                        placeholder="Password" id="password">
                                    <i class="ti-lock text-grey-700 pr-0"></i>
                                    <span class="font-xssss fw-400 error-password error-message"></span>
                                </div>
                                <div class="form-group icon-tab mb-3">
                                    <input type="text"
                                        class="form-control h60 border-2 bg-color-none text-grey-700"
                                        placeholder="Confirm Password" id="confmPassword">
                                    <i class="ti-lock text-grey-700 pr-0"></i>
                                    <span
                                        class="font-xssss fw-400 error-confmPass error-message error-confmPassword"></span>
                                </div>
                                <div class="form-group icon-tab mb-3">
                                    <a href="#"
                                        class="text-center form-control h60 bg-current text-white font-xss fw-500 border-2 border-0 p-0"
                                        id="createUser">Create an account</a>
                                </div>
                            </form>
                            <div class="col-sm-12 p-0 text-center">
                                <!-- <a href="#" class="form-control h60 bg-current text-white font-xss fw-500 border-2 border-0 p-0">Create an account</a> -->
                                <h6 class="text-grey-500 font-xsss fw-500 mt-2 mb-4 lh-32">Are you already member?
                                    <a href="#" class="fw-700 ml-1 text-current" data-toggle="modal"
                                        data-target="#Modallogin" data-dismiss="modal">Login</a>
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
                                <p class="fw-900 font-xssss text-grey-600 mt-2 pt-3 d-inline-block">
                                    By continuing, you agree to Amazepay's <a href="blog-single.html"
                                        class="text-current">Term ans Condition</a> and <a href="blog-single.html"
                                        class="text-current">Privacy Policy </a>.
                                </p>
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
                                <div class="row ">
                                    <div class="col-md-5 left-half d-lg-block d-none">
                                        <div class="left-side-body rounded-0 w-100 p-2 pt-5 border-0">
                                            <div class="card-body rounded-0 text-left pt-5 pb-2">
                                                <div class="single-line mb-2">
                                                    <p class="mr-3"><img src="https://www.gyftoo.com/public/assets/images/group1.png" alt="Group"></p>
                                                    <p class="text-white">Buy or Send Gift Cards Instantly</p>
                                                </div>
                                            </div>
                                            <div class="card-body rounded-0 text-left pt-3 pb-2">
                                                <div class="single-line mb-2">
                                                    <p class="mr-3"><img src="https://www.gyftoo.com/public/assets/images/group1.png" alt="Group"></p>
                                                    <p class="text-white">Buy or Send Gift Cards Instantly</p>
                                                </div>
                                            </div>
                                            <h4 class="fw-600 display2-size-sm mb-4 text-center">Cashback and Gift Cards</h4>
                                            <p class="text-center"><img src="https://www.gyftoo.com/public/assets/images/logo-org.png" alt="Gyftoo" class="img-fluid" width="150"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-7 right-half">
                                        <div class="card shadow-none rounded-0 w-100 p-2 pt-3 border-0">
                                            <div class="card-body rounded-0 text-left pt-0 pb-2">
                                                <h2 class="fw-600 display2-size mb-4">Login</h2>
                                                <span class="font-xssss fw-400 main-error text-center"></span>
                                                <form id="login-form">
                                                    <div class="form-group mb-3">
                                                        <input type="text"
                                                            class="form-control h60 border-2 bg-color-none text-grey-700 credentails-field"
                                                            placeholder="Enter Your Mobile Number" id="loginMobNumb">
                                                        <span class="font-xssss fw-400 error-loginMobNumb text-danger"></span>
                                                    </div>
                                                    <div class="form-group mb-3">
                                                        <input type="password"
                                                            class="form-control h60 border-2 bg-color-none text-grey-700 credentails-field"
                                                            placeholder="Enter Password" id="loginPass">
                                                        <span class="font-xssss fw-400 error-loginPass text-danger"></span>
                                                    </div>
                                                    <div class="form-check text-left mb-3">
                                                        <input type="checkbox" class="form-check-input mt-2" id="exampleCheck1">
                                                        <label class="form-check-label font-xsss text-grey-500"
                                                            for="exampleCheck1">Remember me</label>
                                                        <a href="#" class="fw-600 font-xsss text-grey-700 mt-1 float-right"
                                                            data-toggle="modal" data-target="#Modalforgotpassword"
                                                            data-dismiss="modal">Forgot your Password?</a>
                                                    </div>
                                                    <div class="form-group icon-tab mb-3">
                                                        <div class="row">
                                                            <div class="col-6 align-self-center">
                                                                <button type="button" class="btn btn-primary login-otp-link p-0"
                                                                id="loginWithOtpButton">Login With OTP</button>
                                                            </div>
                                                            <div class="col-6 align-self-center">
                                                                <button type="submit"
                                                                class="form-control rounded-lg h20 float-right bg-current text-white text-center font-xss fw-500 border-2 border-0 p-0 w100"
                                                                id="loginUser">Login</button>
                                                            </div>
                                                        </div>          
                                                    </div>
                                                    <!-- Hidden input to store the mobile number for OTP -->
                                                    <input type="hidden" class="destination-input" name="destination" id="destination"
                                                        required>
                                                </form>
                                                <!-- Get OTP button to trigger OTP modal -->
                                               
                                                <!-- Success message div -->
                                                <div class="col-sm-12 p-0 text-center">
                                                    <h6 class="text-grey-500 font-xsss fw-500 mt-2 mb-0 lh-32">Don't have an account <a
                                                            href="#" class="fw-700 ml-1 text-current register-form"
                                                            data-toggle="modal" data-target="#ModalregisterD"
                                                            data-dismiss="modal">Register</a></h6>
                                                </div>
                                                <div class="col-sm-12 p-0 text-center">
                                                    <p class="fw-500 font-xssss text-grey-600 mt-2 pt-3 d-inline-block text-center">
                                                        By continuing, you agree to Amazepay's <a href="{{ route('tnc') }}"
                                                            class="text-current">Terms and Conditions</a> and <a href="{{ route('private-policy') }}"
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
    <div class="modal fade" id="otpVerificationModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
        data-backdrop="true" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="otpModalLabel">OTP Verification</h5>
                    <button type="button" class="close otp-modal-close-style modal-otp-close" data-dismiss="modal"
                        aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h6>Please enter the one-time password<br>to verify your account</h6>
                    <div>
                        <span>A code has been sent to</span>
                        <small>*******</small>
                    </div>
                    <div id="otp" class="inputs d-flex flex-row justify-content-center mt-2">
                        <!-- Create input fields for the OTP digits (1 to 6) -->
                        <input class="m-2 text-center form-control rounded" type="text" id="first"
                            maxlength="1" />
                        <input class="m-2 text-center form-control rounded" type="text" id="second"
                            maxlength="1" />
                        <input class="m-2 text-center form-control rounded" type="text" id="third"
                            maxlength="1" />
                        <input class="m-2 text-center form-control rounded" type="text" id="fourth"
                            maxlength="1" />
                        <input class="m-2 text-center form-control rounded" type="text" id="fifth"
                            maxlength="1" />
                        <input class="m-2 text-center form-control rounded" type="text" id="sixth"
                            maxlength="1" />
                    </div>
                    <!-- Placeholder element for displaying error message -->
                    <div class="error-otpVerifyInput text-danger font-xssss mt-2"></div>
                </div>
                <div class="modal-footer">
                    <!-- Submit button to validate and verify the OTP -->
                    <button class="btn btn-danger px-4" id="otpVerificationButton">Submit</button>
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
                                        href="#" class="fw-700 ml-1 text-current" data-toggle="modal"
                                        data-target="#Modallogin" data-dismiss="modal">Login</a></h6>
                            </div>
                            <div class="col-sm-12 p-0 text-center">
                                <p class="fw-900 font-xssss text-grey-600 mt-2 pt-3 d-inline-block text-center">
                                    By continuing, you agree to Amazepay's <a href="{{ route('tnc') }} "
                                        class="text-current">Term ans Condition</a> and <a href="{{ route('private-policy') }}"
                                        class="text-current">Privacy Policy </a>.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end of Forgot Password Modal -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>
    <!-- Bootstrap Select Main JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.slim.min.js"></script>
    <script src="{{ URL::asset('js/plugin.js') }}"></script>
    <script src="{{ URL::asset('js/scripts.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.isotope/3.0.6/isotope.pkgd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/jquery.slick/1.6.0/slick.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.min.js"></script>
    <script>
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
        // for registration
        // $('.register-form').click(function(){
        //     $(".error-name").text('');
        //     $(".error-mobile").text('');
        //     $(".error-email").text('');
        //     $(".error-password").text('');
        // });

        $('#createUser').click(function(e) {
            e.preventDefault();
            var name = $('#name').val();
            var mobile = $('#mobile').val();
            var email = $('#email').val();
            var password = $('#password').val();
            var confmPassword = $('#confmPassword').val();
            var regxMobile = /^(?:(?:\+|0{0,2})91)?[789]\d{9}$/;
            var regxEmail = /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z]+$/;
            var status = true;

            // Clear previous error messages
            $('.error-message').empty();

            if (name.length === 0) {
                status = false;
                $(".error-name").text('Name is required').addClass('error-color');
            } else if (!/^[a-zA-Z\s]+$/.test(name)) {
                status = false;
                $(".error-name").text('Name should only contain letters and spaces').addClass('error-color');
            }

            if (mobile.length === 0) {
                status = false;
                $(".error-mobile").text('Mobile is required').addClass('error-color');
            } else if (!regxMobile.test(mobile) || mobile.length !== 10) {
                status = false;
                $(".error-mobile").text('Invalid mobile number').addClass('error-color');
            }

            if (email.length === 0) {
                status = false;
                $(".error-email").text('Email is required').addClass('error-color');
            } else if (!regxEmail.test(email)) {
                status = false;
                $(".error-email").text('Invalid email address').addClass('error-color');
            }

            if (password.length === 0) {
                status = false;
                $(".error-password").text('Password is required').addClass('error-color');
            } else if (confmPassword !== password) {
                status = false;
                $(".error-confmPassword").text('Password does not match').addClass('error-color');
            }

            if (status) {
                userRegister(name, mobile, email, password);
            }

            return status;
        });

        function userRegister(name, mobile, email, password) {
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
                            $('#' + key).siblings('.error-message').text(value).addClass('error-color');
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
                        if (response.status === 'success') {
                            // OTP verification successful, redirect the user or show a success message
                            window.location.href = '/'; // Redirect to the desired page
                        } else if (response.status === 'error' && response.message ===
                            'Invalid Mobile Number') {
                            // Invalid mobile number
                            $('.error-loginMobNumb').text(
                                'Invalid mobile number. Please try again.');
                        } else if (response.status === 'error' && response.message ===
                            'Invalid OTP.') {
                            // Invalid OTP
                            $('.error-otpVerifyInput').text('Invalid OTP. Please try again.');
                        } else if (response.status === 'error' && response.message ===
                            'OTP has expired.') {
                            // OTP has expired
                            $('.error-otpVerifyInput').text(
                                'OTP has expired. Please request a new OTP.');
                            // Show the "Resend OTP" button
                            $('#resendOtpButton').show();
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


        // When the resend button is clicked
        $('#resendOtpButton').click(function() {
            // Hide the resend button again
            $(this).hide();

            // Get the destination (mobile number)
            const destination = $('#loginMobNumb').val();

            // Make the AJAX request to resend OTP
            $.ajax({
                type: 'POST',
                url: '{{ route('send-sms') }}',
                data: {
                    _token: '{{ csrf_token() }}',
                    destination: destination
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // OTP sent successfully, show a message or perform any other action if needed
                        // ...
                    } else {
                        // Failed to send OTP, show the error message
                        $('.error-loginMobNumb').text(response.message ||
                            'Failed to send OTP. Please try again later.');
                    }
                },
                error: function() {
                    // AJAX request failed, show the error message
                    $('.error-loginMobNumb').text(
                        'An error occurred while sending the request. Please try again later.');
                }
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
