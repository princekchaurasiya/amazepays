<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title> @yield('title')</title>

    <link rel="stylesheet" href="{{URL::asset('css/themify-icons.css')}}">
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="{{url('images/favicon.png')}}">
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="{{URL::asset('css/style.css')}}">


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css" />

    <!-- Bootstrap Stylesheet -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.0/css/bootstrap-grid.min.css" />

<!-- Bootstrap Select Stylesheet -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/jquery.slick/1.6.0/slick.css" rel="stylesheet"/>
<link href="https://cdn.jsdelivr.net/jquery.slick/1.6.0/slick-theme.css" rel="stylesheet"/>
    
    @yield('css')
    

</head>

<body class="color-theme-blue open-font">

<div class="preloader"></div>
<div class="main-wrapper">
        <!-- header wrapper -->
        <div class="upper-header bg-greylight">
            <div class="container">
                <div class="row">
                    <div class="col-md-6 col-xs-6 d-none d-block-md">
                        <ul class="list-inline list-item-style mt-0 float-left pl-1">
                            <li class="list-inline-item pl-0"><a href="#">(+1)866-540-3229</a></li>
                        </ul>
                    </div>
                    <div class="col-md-6 col-xs-6 d-none d-block-md">
                        <ul class="list-inline list-item-style mt-0 float-right">
                            <li class="list-inline-item"><a href="#"><i class="ti-user mr-2"></i> My Account</a></li>
                        </ul>
                    </div>
                    <div class="col-12 d-none d-lg-block">
                        <ul class="list-inline list-item-style mt-0 float-left pl-1">
                            <!-- <li class="list-inline-item pl-0"><a href="#">BECOME AN AGENT</a></li> -->
                            <li class="list-inline-item pl-0"><a href="#">(+1)866-540-3229</a></li>                         
                        </ul>

                        <ul class="list-inline list-item-style mt-0 float-right">
                            <li class="list-inline-item"><a href="#">PRIVACY</a></li>
                            <li class="list-inline-item"><a href="#">CUSTOMER SERVICE </a></li>  
                            <!-- <li class="list-inline-item"><a href="#"><i class="ti-location-pin mr-2"></i>Store Locator</a></li> -->
                            <!-- <li class="list-inline-item"><a href="#"><i class="ti-user mr-2"></i> My Account</a></li> -->
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="header-wrapper">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 navbar">
                         <a href="/" class="logo"><h1 class="fredoka-font ls-3 fw-700 text-current display1-size">Amazepays</h1></a>
                        <button class="navbar-toggler" type="button" data-toggle="collapse"
                            data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown"
                            aria-expanded="false" aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="navbarNavDropdown">
                            <ul class="navbar-nav nav-menu float-none text-center">
                            <li class="nav-item"><a class="nav-link" href="/">Home</a></li>
                                <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">Services <i class="ti-angle-down"></i></a>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="#">Money Transfer</a>
                                        <a class="dropdown-item" href="#">Bill payment</a>
                                        
                                    </div>
                                </li>
                                <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">Products <i class="ti-angle-down"></i></a>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="#">Gift Cards</a>
                                        <a class="dropdown-item" href="#">Bank Gift Cards</a>
                                    </div>
                                </li>
                                <li class="nav-item"><a class="nav-link" href="{{ url('about') }}">About</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ url('contact_us') }}">Contact</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ url('f&q') }}">F&Q</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-4 text-right">
                        @if(\Auth::check())
                            <a href="{{route('user-logout')}}" class="header-btn bg-dark fw-500 text-white font-xssss">Logout</a>
                        @else
                            <a href="#" class="header-btn bg-dark fw-500 text-white font-xssss" data-toggle="modal" data-target="#Modallogin">Login</a>
                            <a href="#" class="header-btn bg-current fw-500 text-white font-xssss register-form" data-toggle="modal" data-target="#ModalregisterD">Register</a>
                        @endif
                        
                    </div>
                </div>
            </div>
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
                                <a href="/" class="logo"><h1 class="fredoka-font ls-3 fw-700 text-current display1-size">Amazepays</h1></a>
                                <p class="w-100 mt-lg-5 mt-4">293/2330,Ashirwad CHS Ltd.<br/> Motilal Nagar, M.G.Road, Opp. Ganesh Maidan, Goregoan (West) <br/> Mumbai – 400104</p>                               
                            </div>
                            
                            <div class="col-md-3 col-lg-2 col-sm-4 col-xs-6">
                                <h5>Channel</h5>
                                <ul>
                                    <li><a href="#">Gift Cards</a></li>
                                    <li><a href="#">bank Cards</a></li>
                                    <li><a href="#">Vouchers</a></li>
                                </ul>
                            </div>
                            <div class="col-md-3 col-lg-2 col-sm-4 col-xs-6">
                                <h5>About</h5>
                                <ul>
                                    <li><a href="#">FAQ</a></li>
                                    <li><a href="{{ url('terms_of_use') }}">Term of use</a></li>
                                    <li><a href="{{ url('private_policy') }}">Privacy Policy</a></li>
                                </ul>
                            </div>
                            <div class="col-md-3 col-lg-2 col-sm-4 col-xs-6">
                                <h5 class="mb-3">Office</h5>
                                <p style="width: 100%;">293/2330,Ashirwad CHS Ltd., Motilal Nagar, M.G.Road, Opp. Ganesh Maidan, Goregoan (West), Mumbai – 400104</p>
                            </div>
                            <div class="col-md-3 col-lg-2 col-sm-3 col-xs-6 md-mb25">
                                <h5 class="mb-3">Follow us on</h5>
                                <ul class="list-inline">
                                    <li class="list-inline-item mr-3"><a href="#"><i class="ti-facebook"></i></a></li>
                                    <li class="list-inline-item mr-3"><a href="#"><i class="ti-twitter-alt"></i></a></li>
                                    <li class="list-inline-item mr-3"><a href="#"><i class="ti-linkedin"></i></a></li>
                                    <li class="list-inline-item"><a href="#"><i class="ti-instagram"></i></a></li>
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
                        <p class="copyright-text float-right">Design & Develop by <a href="#" class="">Toutle</a></p>
                    </div>
                </div>
            </div>
        </div>
        <!-- footer wrapper -->

    </div> 

    <!-- Modal Register -->
    <div class="modal fade" id="ModalregisterD" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" data-backdrop="static" data-keyboard="false" aria-hidden="true">
         <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="ti-close text-grey-500"></i></button>
                <div class="modal-body p-3 d-flex align-items-center bg-none">
                    <div class="card shadow-none rounded-0 w-100 p-2 pt-3 border-0">
                        <div class="card-body rounded-0 text-left pt-0">
                            <h2 class="fw-600 display2-size mb-4">Create <br>your account</h2>
                            <form id="registration-form">
                            <span class="font-xssss fw-400 main-register-error text-center"></span> 
                                <div class="form-group mb-3">
                                    <input type="text" class="form-control h60 border-2 bg-color-none text-grey-700" placeholder="Name" id="name"> 
                                    <span class="font-xssss fw-400 error-name"></span>                       
                                </div>
                                <div class="form-group mb-3">
                                    <input type="text" class="form-control h60 border-2 bg-color-none text-grey-700" placeholder="Mobile Number" id="mobile"> 
                                    <span class="font-xssss fw-400 error-mobile"></span>                       
                                </div>
                                <div class="form-group mb-3">
                                    <input type="text" class="form-control h60 border-2 bg-color-none text-grey-700" placeholder="Email" id="email">    
                                    <span class="font-xssss fw-400 error-email"></span>                    
                                </div>
                                <div class="form-group icon-tab mb-3">
                                    <input type="text" class="form-control h60 border-2 bg-color-none text-grey-700" placeholder="Password" id="password">
                                    <i class="ti-lock text-grey-700 pr-0"></i>
                                    <span class="font-xssss fw-400 error-password"></span>
                                </div>
                                <div class="form-group icon-tab mb-3">
                                    <input type="text" class="form-control h60 border-2 bg-color-none text-grey-700" placeholder="Confirm Password" id="confmPassword">
                                    <i class="ti-lock text-grey-700 pr-0"></i>
                                    <span class="font-xssss fw-400 error-confmPass"></span>
                                </div>
                                <div class="form-group icon-tab mb-3">
                                    <a href="#" class="text-center form-control h60 bg-current text-white font-xss fw-500 border-2 border-0 p-0" id="createUser">Create an account</a>
                                </div>
                                
                            </form>
                             
                            <div class="col-sm-12 p-0 text-center">
                                <!-- <a href="#" class="form-control h60 bg-current text-white font-xss fw-500 border-2 border-0 p-0">Create an account</a> -->
                                <h6 class="text-grey-500 font-xsss fw-500 mt-2 mb-4 lh-32">Are you already member? <a href="#" class="fw-700 ml-1 text-current" data-toggle="modal" data-target="#Modallogin" data-dismiss="modal">Login</a></h6>
                                <div class="row">
                                    <div class="col-6 pr-1"><a href="#" class="form-control h60 p-0 pl-5 bg-lightblue text-grey-700 border-2 border-0 font-xssss fw-600  position-relative">Login with OTP</a></div>
                                    <div class="col-6 pl-1"><a href="#" class="form-control h60 p-0 pl-5 bg-lightblue text-grey-700 border-2 border-0 font-xssss fw-600  position-relative">Forgot Password?</a></div>
                                </div>
                                <p class="fw-900 font-xssss text-grey-600 mt-2 pt-3 d-inline-block">
                                    By continuing, you agree to Amazepay's <a href="blog-single.html" class="text-current">Term ans Condition</a> and <a href="blog-single.html" class="text-current">Privacy Policy </a>.
                                </p>
                            </div>
                            
                        </div>
                    </div>                    
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Login -->
    <div class="modal bottom fade" id="Modallogin" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" data-backdrop="static" data-keyboard="false" aria-hidden="true">
         <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="ti-close text-grey-500"></i></button>
                <div class="modal-body p-3 d-flex align-items-center bg-none">
                    <div class="card shadow-none rounded-0 w-100 p-2 pt-3 border-0">
                        <div class="card-body rounded-0 text-left pt-0 pb-2">
                            <h2 class="fw-600 display2-size mb-4">Login into <br>your account</h2>
                                    <span class="font-xssss fw-400 main-error text-center"></span> 
                            <form id="login-form">
                                
                                <div class="form-group mb-3">
                                    <input type="text" class="form-control h60 border-2 bg-color-none text-grey-700" placeholder="Mobile Number" id="loginMobNumb"> 
                                    <span class="font-xssss fw-400 error-loginMobNumb"></span>                       
                                </div>
                                <div class="form-group mb-3">
                                    <input type="password" class="form-control h60 border-2 bg-color-none text-grey-700" placeholder="Password" id="loginPass"> 
                                    <span class="font-xssss fw-400 error-loginPass"></span>                       
                                </div>
                                <div class="form-check text-left mb-3">
                                    <input type="checkbox" class="form-check-input mt-2" id="exampleCheck1">
                                    <label class="form-check-label font-xsss text-grey-500" for="exampleCheck1">Remember me</label>
                                    <a href="#" class="fw-600 font-xsss text-grey-700 mt-1 float-right" data-toggle="modal" data-target="#Modalforgotpassword"  data-dismiss="modal">Forgot your Password?</a>
                                </div>

                                <div class="form-group icon-tab mb-3">
                                    <a href="#" class="text-center form-control h60 bg-current text-white font-xss fw-500 border-2 border-0 p-0" id="loginUser">Login</a>
                                </div>
                            </form>
                             
                            <div class="col-sm-12 p-0 text-center">
                                <h6 class="text-grey-500 font-xsss fw-500 mt-2 mb-0 lh-32">Dont have account <a href="#" class="fw-700 ml-1 text-current register-form" data-toggle="modal" data-target="#ModalregisterD" data-dismiss="modal">Register</a></h6>
                            </div>

                            <div class="col-sm-12 p-0 text-center">
                                <p class="fw-900 font-xssss text-grey-600 mt-2 pt-3 d-inline-block text-center">
                                    By continuing, you agree to Amazepay's <a href="blog-single.html" class="text-current">Term ans Condition</a> and <a href="blog-single.html" class="text-current">Privacy Policy </a>.
                                </p>
                            </div>
                        </div>
                    </div>                    
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal bottom fade" id="Modalforgotpassword" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
         <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="ti-close text-grey-500"></i></button>
                <div class="modal-body p-3 d-flex align-items-center bg-none">
                    <div class="card shadow-none rounded-0 w-100 p-2 pt-3 border-0">
                        <div class="card-body rounded-0 text-left pt-0 pb-2">
                            <h2 class="fw-600 display1-size mb-4">Forgot Password</h2>
                            <form>
                                
                                <div class="form-group mb-3">
                                    <input type="text" class="form-control h60 border-2 bg-color-none text-grey-700" placeholder="Email">                        
                                </div>
                               
                            </form>
                             
                            <div class="col-sm-12 p-0 text-center">
                                <a href="#" class="form-control h60 bg-current text-white font-xss fw-500 border-2 border-0 p-0">Submit</a>
                                <h6 class="text-grey-500 font-xsss fw-500 mt-2 mb-0 lh-32">Dont have account <a href="#" class="fw-700 ml-1 text-current" data-toggle="modal" data-target="#Modallogin" data-dismiss="modal">Login</a></h6>
                            </div>

                            <div class="col-sm-12 p-0 text-center">
                                <p class="fw-900 font-xssss text-grey-600 mt-2 pt-3 d-inline-block text-center">
                                    By continuing, you agree to Amazepay's <a href="blog-single.html" class="text-current">Term ans Condition</a> and <a href="blog-single.html" class="text-current">Privacy Policy </a>.
                                </p>
                            </div>
                        </div>
                    </div>                    
                </div>
            </div>
        </div>
    </div>
   
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <!-- Bootstrap JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.0/js/bootstrap.min.js"></script>

    <!-- Bootstrap Select Main JavaScript -->
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.slim.min.js"></script>
    
    <script src="{{URL::asset('js/plugin.js')}}"></script>
    <script src="{{URL::asset('js/scripts.js')}}"></script>
    
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta3/js/bootstrap-select.min.js"></script> -->
    <!-- for filter select -->
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.10.0/js/bootstrap-select.min.js"></script> -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.isotope/3.0.6/isotope.pkgd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/jquery.slick/1.6.0/slick.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.min.js"></script>
    <script>

        // close button
        $('.close').click(function(){
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
            
            $('#createUser').click(function(e){
                e.preventDefault();
                // $(this).find('form')[0].reset();
                var name = $('#name').val();
                var mobile = $('#mobile').val();
                var email = $('#email').val();
                var password = $('#password').val();
                var confmPassword = $('#confmPassword').val();
                var regxMobile = /^(?:(?:\+|0{0,2})91(\s*[\-]\s*)?|[0]?)?[789]\d{9}$/;
                var regxEmail = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/;
                var status = false;
                if(name.length != ''){
                    status = true;
                }else {
                    status = false;
                    $(".error-name").text('Name required');
                }
                if(regxMobile.test(mobile) && mobile.length ==10 && mobile.length!= ''){
                    status = true;
                } else {
                    status = false;
                    $(".error-mobile").text('Mobile required');
                }
                if(regxEmail.test(email) && email.length!= ''){
                    status = true;
                } else {
                    $(".error-email").text('Email required');
                    status = false;
                }
                if(confmPassword == password && password.length!= ''){
                    status = true;
                } else {
                    $(".error-password").text('Password required/ Password does not match');
                    status = false;
                }
                if(status == true){
                    userRegister(name,mobile,email,password);
                } else {
                    return status;
                }
            });

            function userRegister(name,mobile,email,password){
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                var formData = new FormData();
                formData.append('name',name);
                formData.append('mobile',mobile);
                formData.append('email',email);
                formData.append('password',password);
                var type = "POST";
                var ajaxurl = "{{url('/user-registration')}}";
                $.ajax({
                    type: type,
                    url: ajaxurl,
                    contentType: 'application/json',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function (data) {
                        if(data.status == 200){
                            location.reload(true);
                        } else {
                            $(".main-register-error").text(data.msg);
                        }
                    },
                    error: function (data) {
                        console.log(data);
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
            
            $('#loginUser').click(function(e){
                e.preventDefault();
                // $(this).find('form')[0].reset();
                var mobile = $('#loginMobNumb').val();
                var password = $('#loginPass').val();
                var regxMobile = /^(?:(?:\+|0{0,2})91(\s*[\-]\s*)?|[0]?)?[789]\d{9}$/;
                var status = true;
                // if(regxMobile.test(mobile) && mobile.length ==10 && mobile.length!= ''){
                //     status = true;
                // } else {
                //     status = false;
                //     $(".error-loginMobNumb").text('Mobile required');
                // }
                // if(password.length != ''){
                //     status = true;
                // } else {
                //     $(".error-loginPass").text('Password required');
                //     status = false;
                // }
                if(status == true){
                    userLogin(mobile,password);
                } else {
                    return status;
                }
            });

            function userLogin(mobile,password){
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                var formData = new FormData();
                formData.append('email',mobile);
                formData.append('password',password);
                var type = "POST";
                var ajaxurl = "{{url('/user-login')}}";
                $.ajax({
                    type: type,
                    url: ajaxurl,
                    contentType: 'application/json',
                    data: formData,
                    processData: false,
                    contentType: false,
                    // dataType: 'json',
                    success: function (data) {
                        if(data.status == 200){
                            location.reload(true);
                        } else {
                            $(".main-error").text('Something went wrong');
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        console.log(jqXHR);
                    }
                });
                return false;
            }
        // end login
    </script>
    @stack('scripts')   
</body>

</html>