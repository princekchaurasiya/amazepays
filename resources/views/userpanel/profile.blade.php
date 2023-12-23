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
                        <a href="#" class="dash-menu d-none d-block-md"><i class="ti-package font-sm mr-2"></i> Menu <i
                                class="ti-angle-down font-xsss float-right "></i></a>
                        <ul class="dash-menu-ul">
                           
                            <li class="d-block rounded-lg active"><a href="{{ route('profile') }}"><i
                                        class="ti-user font-sm"></i><span> Profile</span></a></li>
                            <li class="d-block rounded-lg"><a href="{{ route('my-order') }}"><i
                                        class="ti-package font-sm"></i><span> My Order</span></a></li>
                            <li class="d-block rounded-lg "><a href="{{ route('change-password') }}"><i
                                        class="ti-lock font-sm"></i><span> Change Password</span></a></li>
                            <!-- <li class="d-block rounded-lg "><a href="payment.html"><i class="ti-credit-card font-sm"></i><span> Payment</span></a></li> -->
                            <li class="d-block rounded-lg"><a href="{{ route('userLogOut') }}"><i class="ti-power-off font-sm"></i><span>
                                        Logout</span></a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="dashboard-tab cart-wrapper p-5 bg-white rounded-lg shadow-xs">
                        <form action="{{ route('update-profile') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-lg-6 mb-3">
                                    <div class="form-gorup">
                                        <label class="mont-font fw-600 font-xsss" for="comment-name"> Name</label>
                                        <input type="text" name="comment-name" class="form-control">
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <div class="form-gorup">
                                        <label class="mont-font fw-600 font-xsss" for="comment-name">Email</label>
                                        <input type="text" name="comment-name" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6 mb-3">
                                    <div class="form-gorup">
                                        <label class="mont-font fw-600 font-xsss" for="comment-name">Phone</label>
                                        <input type="text" name="comment-name" class="form-control">
                                    </div>
                                </div>
                            </div>
    
                            <div class="row">
                                <div class="col-lg-12 mb-5">
                                    {{-- <button type="submit" class="bg-current text-center text-white font-xsss fw-600 p-3 w175 rounded-lg d-inline-block">Save</button> --}}
                                    <button type="submit" class="form-control rounded-lg h20 float-right bg-current text-white text-center font-xss fw-500 border-2 border-0 p-0 w100">Update</button>
                                </div>
                            </div>
                            </div>
                        </form>
                    </div>

                    
            </div>
        </div>
    </div>
    </div>
    </div>
    @push('scripts')
        <script>
            //   $(".dashboard-tab").height( $('.dashboard-nav').height() - 65 );
            //     $(".dashboard-tab").niceScroll({
            //         cursorcolor: "#999", // change cursor color in hex
            //   });
        </script>
    @endpush
@endsection
