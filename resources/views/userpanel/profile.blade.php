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
                            <li class="d-block rounded-lg"><a href="{{ route('userLogOut') }}"><i
                                        class="ti-power-off font-sm"></i><span>
                                        Logout</span></a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-9">

                    @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                    <div class="dashboard-tab cart-wrapper p-5 bg-white rounded-lg shadow-xs">
                        <form action="{{ route('update-profile') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label class="mont-font fw-600 font-xsss" for="name">Name</label>
                                        <input type="text" name="name" class="form-control"
                                            value="{{ Auth::user()->name }}">
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label class="mont-font fw-600 font-xsss" for="email">Email</label>
                                        <input type="email" name="email" class="form-control"
                                            value="{{ Auth::user()->email }}">
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-3">
                                    <div class="form-group">
                                        <label class="mont-font fw-600 font-xsss" for="phone">Phone</label>
                                        <input type="text" name="phone" class="form-control"
                                            value="{{ Auth::user()->mobile }}">
                                    </div>
                                </div>
                                <div class="col-lg-12 mb-5">

                                    <button type="submit"
                                        class="form-control rounded-lg h20 float-left bg-current text-white text-center font-xss fw-500 border-2 border-0 p-0 w100">Update</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
