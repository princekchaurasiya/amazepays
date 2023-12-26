@extends('layouts.app')
@section('title')
    Amazepay | Change Password
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

                            <li class="d-block rounded-lg"><a href="{{ route('profile') }}"><i
                                        class="ti-user font-sm"></i><span> Profile</span></a></li>
                            <li class="d-block rounded-lg"><a href="{{ route('my-order') }}"><i
                                        class="ti-package font-sm"></i><span> My Order</span></a></li>
                            {{-- <li class="d-block rounded-lg active"><a href="{{ route('change-password') }}"><i
                                        class="ti-lock font-sm"></i><span> Chnage Password</span></a></li> --}}
                            <!-- <li class="d-block rounded-lg "><a href="payment.html"><i class="ti-credit-card font-sm"></i><span> Payment</span></a></li> -->
                            <li class="d-block rounded-lg"><a href="#"><i class="ti-power-off font-sm"></i><span>
                                        Logout</span></a></li>

                        </ul>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="dashboard-tab cart-wrapper p-5 bg-white rounded-lg shadow-xs">

                        <form action="#">
                            <div class="row">
                                <div class="col-lg-12">
                                    <h4 class="mb-4 font-xs fw-700 mont-font mt-3">Change Password</h4>
                                </div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-gorup">
                                        <label class="mont-font fw-600 font-xssss" for="comment-name">Current
                                            Password</label>
                                        <input type="text" name="comment-name" class="form-control">
                                    </div>
                                </div>

                                <div class="col-lg-12 mb-3">
                                    <div class="form-gorup">
                                        <label class="mont-font fw-600 font-xssss" for="comment-name">Change
                                            Password</label>
                                        <input type="text" name="comment-name" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-12 mb-3">
                                    <div class="form-gorup">
                                        <label class="mont-font fw-600 font-xssss" for="comment-name">Confirm Change
                                            Password</label>
                                        <input type="text" name="comment-name" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 mb-0">
                                    <a href="#"
                                        class="bg-current text-center text-white font-xsss fw-600 p-3 w175 rounded-lg d-inline-block">Save</a>
                                </div>
                            </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>
    @push('scripts')
        <script></script>
    @endpush
@endsection
