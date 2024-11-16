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
            <nav class="navbar navbar-expand-md navbar-light bg-light d-none d-md-block navz fixed-top">
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
                            <li class="nav-item {{ Request::is('/') ? 'active' : '' }}">
                                <a class="nav-link" href="/">Home</a>
                            </li>
                            <li class="nav-item {{ Request::is('about') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('about') }}">About</a>
                            </li>
                            <li class="nav-item {{ Request::is('contact-us') ? 'active' : '' }}">
                                <a class="nav-link" href="{{ route('contact-us') }}">Contact</a>
                            </li>
                        </ul>

                        <form class="form-inline my-2 my-lg-0 flex-grow-1 mr-3" action="{{ route('search') }}" method="GET">
                            <div class="input-group w-100">
                                <input type="text" class="form-control ml-5" placeholder="Search here..." name="query" required>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="submit">
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




            <!-- header wrapper mobile view -->
            {{-- <div class="container-fluid"> <!-- Use a container to control the width of the content --> --}}

            <nav class="navbar navbar-expand-lg navbar-light bg-light navz">
                <div class="container-fluid">
                    <!-- Logo -->
                    <a class="navbar-brand mr-auto" href="/">
                        <img src="{{ asset('images/logo.png') }}" alt="logo" class="custLogo">
                    </a>

                    <!-- Toggle button for collapsed navbar -->
                    <button class="navbar-toggler ml-2 ml-lg-0" type="button" data-toggle="collapse"
                        data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                        aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <!-- Navbar items (hidden by default) -->
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ml-auto">
                            <li class="nav-item">
                                <a class="nav-link" href="/">Home</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('about') }}">About</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('contact-us') }}">Contact Us</a>
                            </li>

                            @guest
                                <li class="nav-item">
                                    <a class="nav-link" href="#" data-toggle="modal"
                                        data-target="#ModalregisterD">New Customer?</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" data-toggle="modal"
                                        data-target="#Modallogin">Sign In</a>
                                </li>
                            @endguest

                            @auth
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('profile') }}">{{ Auth::user()->name }}</a>
                                </li>


                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('my-order') }}">My Order</a>
                                </li>

                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('userLogOut') }}">Logout</a>
                                </li>
                            @endauth

                            <!-- Search Bar -->
                            <form class="form-inline my-2 my-lg-0 ml-lg-3" action="{{ route('search') }}"
                                method="GET">
                                <div class="input-group">
                                    <input type="text"  class="form-control form-control-sm"
                                        placeholder="Search here..." name="query" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary btn-sm" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </ul>
                    </div>
                </div>
            </nav>


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
