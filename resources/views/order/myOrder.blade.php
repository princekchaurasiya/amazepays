@extends('layouts.app')
@section('title')
    Amazepay | My Order
@endsection
@section('content')
    <div class="dashboard-wrapper bg-greylight">
        <div class="container">

            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif
            <div class="row">
                <div class="col-lg-3">
                    <div class="dashboard-nav bg-white rounded-lg shadow-xs sticky-top">
                        <a href="#" class="dash-menu d-none d-block-md"><i class="ti-package font-sm mr-2"></i> Menu <i
                                class="ti-angle-down font-xsss float-right "></i></a>
                        <ul class="dash-menu-ul">
                            <li class="d-block rounded-lg"><a href="{{ route('profile') }}"><i
                                        class="ti-user font-sm"></i><span> Profile</span></a></li>
                            <li class="d-block rounded-lg active"><a href="{{ route('my-order') }}"><i
                                        class="ti-package font-sm"></i><span> My Order</span></a></li>
                            <li class="d-block rounded-lg"><a href="{{ route('change-password') }}"><i
                                        class="ti-lock font-sm"></i><span> Change Password</span></a></li>
                            <!-- <li class="d-block rounded-lg "><a href="payment.html"><i class="ti-credit-card font-sm"></i><span> Payment</span></a></li> -->
                            <li class="d-block rounded-lg"><a href="#"><i class="ti-power-off font-sm"></i><span>
                                        Logout</span></a></li>
                        </ul>
                    </div>
                </div>
                {{-- <div class="col-md-9">
                    @if (!empty($order['cards']))
                        @foreach ($order['cards'] as $card)
                            <div class="dashboard-tab cart-wrapper p-5 bg-white rounded-lg shadow-xs mb-3">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="row">
                                            <div class="col-md-12"><span class="my-order-heading">Card Number</span></div>
                                            <div class="col-md-12">{{ $card['cardNumber'] }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12"><span class="my-order-heading">Card PIN</span></div>
                                            <div class="col-md-12">{{ $card['cardPin'] }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="row">
                                            <div class="col-md-12"><span class="my-order-heading">Activation Code</span>
                                            </div>
                                            <div class="col-md-12">{{ $card['activationCode'] }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12"><span class="my-order-heading">Validity</span></div>
                                            <div class="col-md-12">{{ $card['validity'] }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="row">
                                            <div class="col-md-12"><span class="my-order-heading">Activation URL</span>
                                            </div>
                                            <div class="col-md-12">{{ $card['activationUrl'] }}</div>
                                        </div>
                                        <!-- Add similar rows for other details specific to the third column -->
                                    </div>
                                    <div class="col-md-3">
                                        <!-- Add similar rows for other details specific to the fourth column -->
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="dashboard-tab cart-wrapper p-5 bg-white rounded-lg shadow-xs mb-3">
                            No Order Found
                        </div>
                    @endif
                </div> --}}






                @foreach ($order as $orderItem)
                    <div class="col-lg-9">
                        <div class="outer-order-wrapper-div">
                            <h5>My Order</h5>
                        </div>
                    </div>
                @endforeach





            </div>
        </div>
    </div>
    @push('scripts')
        <script></script>
    @endpush
@endsection
