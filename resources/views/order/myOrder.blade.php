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
                        <a href="#" class="dash-menu d-none d-block-md"><i class="ti-package font-sm mr-2"></i> Menu <i class="ti-angle-down font-xsss float-right "></i></a>
                        <ul class="dash-menu-ul">
                            <li class="d-block rounded-lg"><a href="{{ route('profile') }}"><i class="ti-user font-sm"></i><span> Profile</span></a></li>
                            <li class="d-block rounded-lg active"><a href="{{ route('myOrder') }}"><i class="ti-package font-sm"></i><span> My Order</span></a></li>
                            <li class="d-block rounded-lg"><a href="{{ route('change-password') }}"><i class="ti-lock font-sm"></i><span> Change Password</span></a></li>
                            <!-- <li class="d-block rounded-lg "><a href="payment.html"><i class="ti-credit-card font-sm"></i><span> Payment</span></a></li> -->
                            <li class="d-block rounded-lg"><a href="#"><i class="ti-power-off font-sm"></i><span> Logout</span></a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-9">
                    @if(!empty($orderDetails))
                        @foreach ($orderDetails as $order)
                            {{-- {{dd($order['order_id'])}} --}}
                            <div class="dashboard-tab cart-wrapper p-5 bg-white rounded-lg shadow-xs mb-3">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="row">
                                            <div class="col-md-12"><span class="my-order-heading">Order Id</span></div>
                                            <div class="col-md-12">{{ $order['order_id'] }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12"><span class="my-order-heading">Tracking Id</span></div>
                                            <div class="col-md-12">{{ $order['tracking_id'] }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="row">
                                            <div class="col-md-12"><span class="my-order-heading">Bank Ref No.</span></div>
                                            <div class="col-md-12">{{ $order['bank_ref_no'] }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12"><span class="my-order-heading">Order Status</span></div>
                                            <div class="col-md-12">{{ $order['order_status'] }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="row">
                                            <div class="col-md-12"><span class="my-order-heading">Card Name</span></div>
                                            <div class="col-md-12">{{ $order['sku'] }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12"><span class="my-order-heading">Amount</span></div>
                                            <div class="col-md-12">{{ $order['amount'] }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="row">
                                            <div class="col-md-12"><span class="my-order-heading">Denomination</span></div>
                                            <div class="col-md-12">{{ $order['price'] }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12"><span class="my-order-heading">Quantity</span></div>
                                            <div class="col-md-12">{{ $order['qty'] }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                    <div class="dashboard-tab cart-wrapper p-5 bg-white rounded-lg shadow-xs mb-3">
                        No Order Found
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script></script>
    @endpush
@endsection
