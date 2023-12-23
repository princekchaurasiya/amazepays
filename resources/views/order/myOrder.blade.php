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
                <div class="col-lg-9">
                    <div class="row outer-order-wrapper-div">
                        <div><h1 class="font-weight-bold pt-2 pb-1">My Orders</h1></div>
                        @foreach ($order as $orderItem)
                            <div class="outer-order-wrapper-div">
                                <div class="card product-card">
                                    <div class="card-body my-order-card-body ">
                                        <div class="row">
                                            <div class="col-lg-5">
                                                <?php
                                                $images = json_decode($orderItem->images, true);
                                                ?>
                                                @if ($images && isset($images['mobile']))
                                                @endif
                                                <img class="my-order-image-div img-fluid" src="{{ $images['mobile'] }}" alt="">
                                            </div>
                                            <div class="col-lg-3">
<h2>{{ $orderItem->sku }}</h2>
                                            </div>
                                            <div class="col-lg-4"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script></script>
    @endpush
@endsection
