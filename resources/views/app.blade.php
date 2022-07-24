<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title> @yield('title')</title>

    <link rel="stylesheet" href="css/themify-icons.css">
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon.png">
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="css/style.css">




    <!-- Bootstrap Stylesheet -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.0/css/bootstrap-grid.min.css" />

<!-- Bootstrap Select Stylesheet -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.18/css/bootstrap-select.min.css" rel="stylesheet">




    
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
                            <li class="list-inline-item pl-0"><a href="#">BECOME AN AGENT</a></li>
                            <li class="list-inline-item"><a href="#">PRIVACY</a></li>
                            <li class="list-inline-item"><a href="#">CUSTOMER SERVICE </a></li>                            
                        </ul>

                        <ul class="list-inline list-item-style mt-0 float-right">
                            <li class="list-inline-item"><a href="#"><i class="ti-location-pin mr-2"></i>Store Locator</a></li>
                            <li class="list-inline-item"><a href="#"><i class="ti-user mr-2"></i> My Account</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="header-wrapper">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 navbar">
                         <a href="index.html" class="logo"><h1 class="fredoka-font ls-3 fw-700 text-current display1-size">Gift&Giggles</h1></a>
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
                                        <a class="dropdown-item" href="#">Service 1</a>
                                        <a class="dropdown-item" href="#">Service 2</a>
                                        
                                    </div>
                                </li>
                                <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">Products <i class="ti-angle-down"></i></a>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="#">Product 1</a>
                                        <a class="dropdown-item" href="#">Product 2</a>
                                    </div>
                                </li>
                                <li class="nav-item"><a class="nav-link" href="{{ url('about') }}">About</a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ url('contact_us') }}">Contact</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-4 text-right">
                        <a href="#" class="header-btn bg-dark fw-500 text-white font-xssss" data-toggle="modal" data-target="#Modallogin">Login</a>
                        <a href="#" class="header-btn bg-current fw-500 text-white font-xssss" data-toggle="modal" data-target="#ModalregisterD">Register</a>
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
                                <a href="index.html" class="logo"><img src="images/logo.png" alt="logo"></a>
                                <p class="w-100 mt-lg-5 mt-4">41 madison ave, floor 24 new work, <br>NY 10010 1-877-932-7111 <br> support@mail.com</p>
                                <ul class="list-inline">
                                <li class="list-inline-item mr-3"><a href="#"><i class="ti-facebook"></i></a></li>
                                <li class="list-inline-item mr-3"><a href="#"><i class="ti-twitter-alt"></i></a></li>
                                <li class="list-inline-item mr-3"><a href="#"><i class="ti-linkedin"></i></a></li>
                                <li class="list-inline-item"><a href="#"><i class="ti-instagram"></i></a></li>
                            </ul>
                            </div>
                            <div class="col-md-3 col-lg-2 col-sm-3 col-xs-6 md-mb25">
                                <h5>Language</h5>
                                <ul>
                                    <li><a href="#">English</a></li>
                                    <li><a href="#">Spanish</a></li>
                                    <li><a href="#">Arab</a></li>
                                    <li><a href="#">Urdu</a></li>
                                    <li><a href="#">Brazil</a></li>
                                </ul>
                            </div>
                            <div class="col-md-3 col-lg-2 col-sm-4 col-xs-6">
                                <h5>Channel</h5>
                                <ul>
                                    <li><a href="#">Makeup</a></li>
                                    <li><a href="#">Dresses</a></li>
                                    <li><a href="#">Girls</a></li>
                                    <li><a href="#">Sandals</a></li>
                                    <li><a href="#">Headphones</a></li>
                                </ul>
                            </div>
                            <div class="col-md-3 col-lg-2 col-sm-4 col-xs-6">
                                <h5>About</h5>
                                <ul>
                                    <li><a href="#">FAQ</a></li>
                                    <li><a href="#">Term of use</a></li>
                                    <li><a href="#">Privacy Policy</a></li>
                                    <li><a href="#">Feedback</a></li>
                                    <li><a href="#">Careers</a></li>
                                </ul>
                            </div>
                            <div class="col-md-3 col-lg-2 col-sm-4 col-xs-6">
                                <h5 class="mb-3">Office</h5>
                                <p style="width: 100%;">41 madison ave, floor 24 new work, NY 10010 <br>1-877-932-7111</p>
                                <p style="width: 100%;">41 madison ave, floor 24 new work, NY 10010 <br>1-877-932-7111</p>
                            </div>
                        </div>
                        <div class="middle-footer mt-5 pt-4"></div>
                    </div>
                    <div class="col-sm-12 lower-footer pt-0"></div>
                    <div class="col-sm-6 col-xs-12">
                        <p class="copyright-text">© 2021 copyright. All rights reserved.</p>
                    </div>
                    <div class="col-sm-6 col-xs-12 text-right">
                        <p class="copyright-text float-right">Design by <a href="#" class="">uitheme</a></p>
                    </div>
                </div>
            </div>
        </div>
        <!-- footer wrapper -->

    </div> 

    <!-- Modal Register -->
    <div class="modal bottom fade" id="ModalregisterD" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
         <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="ti-close text-grey-500"></i></button>
                <div class="modal-body p-3 d-flex align-items-center bg-none">
                    <div class="card shadow-none rounded-0 w-100 p-2 pt-3 border-0">
                        <div class="card-body rounded-0 text-left pt-0">
                            <h2 class="fw-600 display2-size mb-4">Create <br>your account</h2>
                            <form>
                                <div class="form-group mb-3">
                                    <input type="text" class="form-control h60 border-2 bg-color-none text-grey-700" value="Name">                        
                                </div>
                                <div class="form-group mb-3">
                                    <input type="text" class="form-control h60 border-2 bg-color-none text-grey-700" value="Email">                        
                                </div>
                                <div class="form-group icon-tab mb-3">
                                    <input type="text" class="form-control h60 border-2 bg-color-none text-grey-700" value="Password">
                                    <i class="ti-lock text-grey-700 pr-0"></i>
                                </div>
                                <div class="form-group icon-tab mb-3">
                                    <input type="text" class="form-control h60 border-2 bg-color-none text-grey-700" value="Confirm Password">
                                    <i class="ti-lock text-grey-700 pr-0"></i>
                                </div>
                            </form>
                             
                            <div class="col-sm-12 p-0 text-center">
                                <a href="#" class="form-control h60 bg-current text-white font-xss fw-500 border-2 border-0 p-0">Create an account</a>
                                <h6 class="text-grey-500 font-xsss fw-500 mt-2 mb-4 lh-32">Are you already member? <a href="#" class="fw-700 ml-1">Login</a></h6>
                                <div class="row">
                                    <div class="col-6 pr-1"><a href="#" class="form-control h60 p-0 pl-5 bg-lightblue text-grey-700 border-2 border-0 font-xssss fw-600 text-left position-relative"><img src="images/icon-facebook.png" style="width: 30px; position: absolute; left:10px; top:15px;" alt=""> Connect with Facebook</a></div>
                                    <div class="col-6 pl-1"><a href="#" class="form-control h60 p-0 pl-5 bg-lightblue text-grey-700 border-2 border-0 font-xssss fw-600 text-left position-relative"><img src="images/google-icon.png" style="width: 30px; position: absolute; left:10px; top:15px;" alt=""> Connect with Google</a></div>
                                </div>
                            </div>
                            
                        </div>
                    </div>                    
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Register -->
    <div class="modal bottom fade" id="Modallogin" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
         <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="ti-close text-grey-500"></i></button>
                <div class="modal-body p-3 d-flex align-items-center bg-none">
                    <div class="card shadow-none rounded-0 w-100 p-2 pt-3 border-0">
                        <div class="card-body rounded-0 text-left pt-0 pb-2">
                            <h2 class="fw-600 display2-size mb-4">Login into <br>your account</h2>
                            <form>
                                
                                <div class="form-group mb-3">
                                    <input type="text" class="form-control h60 border-2 bg-color-none text-grey-700" value="Email">                        
                                </div>
                                <div class="form-group icon-tab mb-1">
                                    <input type="text" class="form-control h60 border-2 bg-color-none text-grey-700" value="Password">
                                    <i class="ti-lock text-grey-700 pr-0"></i>
                                </div>
                                <div class="form-check text-left mb-3">
                                    <input type="checkbox" class="form-check-input mt-2" id="exampleCheck1">
                                    <label class="form-check-label font-xsss text-grey-500" for="exampleCheck1">Remember me</label>
                                    <a href="#" class="fw-600 font-xsss text-grey-700 mt-1 float-right">Forgot your Password?</a>
                                </div>
                            </form>
                             
                            <div class="col-sm-12 p-0 text-center">
                                <a href="#" class="form-control h60 bg-current text-white font-xss fw-500 border-2 border-0 p-0">Login</a>
                                <h6 class="text-grey-500 font-xsss fw-500 mt-2 mb-0 lh-32">Dont have account <a href="#" class="fw-700 ml-1">Register</a></h6>
                            </div>
                            
                        </div>
                    </div>                    
                </div>
            </div>
        </div>
    </div>

    <!-- Other transaction Modal -->
    <div class="modal bottom fade" id="Modalmore" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0">
                <div class="row">
                    <div class="col-md-4 more-card">
                        <div class="owl-items text-center">
                            <div class="card w-100 p-4 border-0 shadow-md rounded-lg">
                                <i class="ti-world mt-4 font-xl text-current"></i>
                                <h4 class="font-xsss fw-700 mt-3 text-grey-900">Service 1</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 more-card">
                        <div class="owl-items text-center">
                            <div class="card w-100 p-4 border-0 shadow-md rounded-lg">
                                <i class="ti-world mt-4 font-xl text-current"></i>
                                <h4 class="font-xsss fw-700 mt-3 text-grey-900">Service 2</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 more-card">
                        <div class="owl-items text-center">
                            <div class="card w-100 p-4 border-0 shadow-md rounded-lg">
                                <i class="ti-world mt-4 font-xl text-current"></i>
                                <h4 class="font-xsss fw-700 mt-3 text-grey-900">Service 3</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 more-card">
                        <div class="owl-items text-center">
                            <div class="card w-100 p-4 border-0 shadow-md rounded-lg">
                                <i class="ti-world mt-4 font-xl text-current"></i>
                                <h4 class="font-xsss fw-700 mt-3 text-grey-900">Service 1</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 more-card">
                        <div class="owl-items text-center">
                            <div class="card w-100 p-4 border-0 shadow-md rounded-lg">
                                <i class="ti-world mt-4 font-xl text-current"></i>
                                <h4 class="font-xsss fw-700 mt-3 text-grey-900">Service 2</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 more-card">
                        <div class="owl-items text-center">
                            <div class="card w-100 p-4 border-0 shadow-md rounded-lg">
                                <i class="ti-world mt-4 font-xl text-current"></i>
                                <h4 class="font-xsss fw-700 mt-3 text-grey-900">Service 3</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 more-card">
                        <div class="owl-items text-center">
                            <div class="card w-100 p-4 border-0 shadow-md rounded-lg">
                                <i class="ti-world mt-4 font-xl text-current"></i>
                                <h4 class="font-xsss fw-700 mt-3 text-grey-900">Service 1</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 more-card">
                        <div class="owl-items text-center">
                            <div class="card w-100 p-4 border-0 shadow-md rounded-lg">
                                <i class="ti-world mt-4 font-xl text-current"></i>
                                <h4 class="font-xsss fw-700 mt-3 text-grey-900">Service 2</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 more-card">
                        <div class="owl-items text-center">
                            <div class="card w-100 p-4 border-0 shadow-md rounded-lg">
                                <i class="ti-world mt-4 font-xl text-current"></i>
                                <h4 class="font-xsss fw-700 mt-3 text-grey-900">Service 3</h4>
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

    <script src="js/plugin.js"></script>
    <script src="js/scripts.js"></script>
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta3/js/bootstrap-select.min.js"></script> -->
    <!-- for filter select -->
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.10.0/js/bootstrap-select.min.js"></script> -->
    @stack('scripts')

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.18/js/bootstrap-select.min.js"></script>
</body>

</html>