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
                           <li class="list-inline-item pl-0"><a href="tel:82088 93951">(+91) 82088 93951</a></li>
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
            <nav class="navbar navbar-expand-lg navbar-light bg-light d-none d-md-block navz fixed-top">
                <div class="container">
                    <a href="/" class="navbar-brand"><img src="{{ asset('images/logo.png') }}" alt="logo"
                            class="custLogo"></a>
                    <button class="navbar-toggler" type="button" data-toggle="collapse"
                        data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false"
                        aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarNavDropdown">
                        <ul class="navbar-nav mr-auto">
                            {{-- <li class="nav-item">
                                <a class="nav-link" href="javascript:void(0);" id="openModalLink">Open Modal</a>
                            </li> --}}

                            <li class="nav-item {{ Request::is('/') ? 'active' : '' }}">
                                <a class="nav-link" href="/">Home</a>
                            </li>
                            <li class="nav-item {{ Request::is('about') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('about') }}">About</a>
                            </li>
                            <li class="nav-item {{ Request::is('contact-us') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('contact-us') }}">Contact</a>
                            </li>
                            {{-- <li class="nav-item {{ Request::is('check-balance') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('showCheckBalanceForm') }}">Check Balance</a>
                            </li> --}}
                        </ul>

                        <form class="form-inline my-2 my-lg-0 flex-grow-1 mr-3" action="{{ route('search') }}" method="GET">
                            <div class="input-group w-100 amazepay-desktop-search-wrapper">
                                <input type="text" class="form-control amazepay-desktop-search-input" placeholder="Search here..." name="query" required>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary amazepay-desktop-search-btn" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>


                        <div class="ml-2">
                            @if (Auth::check())
                                <div class="dropdown">
                                    <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1"
                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        {{ Auth::user()->name }}
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                        <a class="dropdown-item" href="{{ route('profile') }}">
                                            <i class="ti-user font-sm"></i> Profile
                                        </a>
                                        <a class="dropdown-item" href="{{ route('my-order') }}">
                                            <i class="fa-sharp fa-solid fa-cart-shopping"></i> My Order
                                        </a>
                                        <a class="dropdown-item" href="{{ route('userLogOut') }}">
                                            <i class="fa-sharp fa-solid fa-power-off"></i> Logout
                                        </a>
                                    </div>
                                </div>
                            @else
                                <a href="#" class="btn navbar-btn bg-dark fw-500 text-white font-xsss login-button"
                                   data-toggle="modal" data-target="#Modallogin">Login</a>
                                <a href="#" class="btn navbar-btn bg-current fw-500 text-white font-xsss register-form register-button"
                                   data-toggle="modal" data-target="#ModalregisterD">Register</a>
                            @endif
                        </div>
                    </div>

                </div>
            </nav>




           <!-- Header Wrapper Mobile View -->
<!-- Combined Navbar and Search Bar -->
<div class="amazepay-mobile-header sticky-top">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light amazepay-nav amazepay-navbar-mobile">
        <div class="container-fluid d-flex align-items-center">
            <!-- Hamburger Menu (Sidebar Toggle) -->
            <button class="navbar-toggler amazepay-sidebar-toggle" type="button">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Logo (Immediately After Hamburger) -->
            <a class="navbar-brand navbar-brand-mobile" href="/">
                <img src="{{ asset('images/logo.png') }}" alt="logo" class="custLogo">
            </a>

            <!-- User Icon (Right Side) -->
            <div class="amazepay-user-icon ml-auto">
                @guest
                    <!-- Guest: Clicking Opens Login Modal -->
                    <a href="#" data-toggle="modal" data-target="#Modallogin">
                        <i class="fas fa-user mobile-nav-user-icon"></i>
                    </a>
                @endguest

                @auth
                    <!-- Logged-in User: Clicking Shows Dropdown -->
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle" id="userDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-user"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                            <a class="dropdown-item" href="{{ route('profile') }}">{{ Auth::user()->name }}</a>
                            <a class="dropdown-item" href="{{ route('my-order') }}">My Order</a>
                            <a class="dropdown-item" href="{{ route('userLogOut') }}">Logout</a>
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Search Bar -->
    <div class="container-fluid amazepay-search amazepay-search-mobile">
        <form class="form-inline" action="{{ route('search') }}" method="GET">
            <div class="input-group amazepay-mobile-search-input-div">
                <input type="text" class="form-control form-control-sm amazepay-mobile-search-input" placeholder="Search here..." name="query" required>
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary btn-sm amazepay-mobile-search-button" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Sidebar -->
<div class="amazepay-sidebar">
    <button class="amazepay-sidebar-close">&times;</button>
    <ul class="amazepay-sidebar-menu">
        <li><a href="/">Home</a></li>
        <li><a href="{{ url('about') }}">About Us</a></li>
        <li><a href="{{ url('contact-us') }}">Contact Us</a></li>
    </ul>
</div>

<!-- Overlay (When Sidebar is Open) -->
<div class="amazepay-overlay"></div>





            {{-- </div> --}}

            <!-- Side Navigation -->
            {{-- <div class="sidenav">
                <ul class="sidenav-list">
                    <li class="sidenav-item">
                        <a class="sidenav-link" href="#">Home</a>
                    </li>
                    <li class="sidenav-item">
                        <a class="sidenav-link" href="{{ url('about') }}">About</a>
                    </li>
                    <li class="sidenav-item">
                        <a class="sidenav-link" href="{{ url('contact_us') }}">Contact Us</a>
                    </li>
                </ul>
                <button class="btn close-sideNav-btn" onclick="closeNav()">&times;</button>
            </div> --}}
            <!-- header wrapper mobile view -->
