@extends('layouts.app')
@section('title')
    Amazepay | View Card
@endsection
@section('content')
    <div class="dashboard-wrapper bg-greylight">
        <div class="container">
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

                @dd($order)
                <div class="col-lg-9">
                    <div class="row outer-order-wrapper-div">
                        <div>
                            <h1 class="font-weight-bold pt-2 pb-1">My Orders</h1>
                        </div>
                        @foreach ($order as $orderItem)
                            <?php
                            $images = json_decode($orderItem->images, true);
                            ?>
                            <a href="#" class="order-link" data-order-id="{{ $orderItem->woohoo_order_id }}"
                                data-image="{{ $images['small'] }}">
                                <div class="outer-order-wrapper-div">
                                    <div class="card product-card">
                                        <div class="card-body my-order-card-body">
                                            <div class="row">
                                                <div class="col-lg-auto">
                                                    @if ($images && isset($images['small']))
                                                        <img class="my-order-image-div img-fluid mb-3 mb-lg-0"
                                                            src="{{ $images['small'] }}" alt="">
                                                    @endif
                                                </div>
                                                <div class="col-lg-auto">
                                                    <h2>{{ $orderItem->sku }}</h2>
                                                    <p class="mb-0">Brand: <b>{{ $orderItem->brandName }}</b></p>
                                                    <p class="mb-0">Amount: <b>{{ $orderItem->denomination }}</b></p>
                                                    <p class="mb-0">Quantity: <b>{{ $orderItem->quantity }}</b></p>
                                                </div>
                                                <div class="col-lg-auto ml-auto text-lg-right mt-3 mt-lg-0">
                                                    <h2>
                                                        <span
                                                            class="{{ $orderItem->order_status == 'COMPLETE' ? 'text-success' : 'text-danger' }} font-weight-bold">
                                                            Order {{ ucfirst($orderItem->order_status) }}
                                                        </span>
                                                    </h2>
                                                    <p class="mb-0">Order #: <b>{{ $orderItem->woohoo_order_id }}</b></p>
                                                    <p class="mb-0">Discount: <b>0</b></p>
                                                    <p class="mb-0">Total Amount: <b>{{ $orderItem->amount }}</b></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <form action="{{ route('view-card-details') }}" method="post" id="orderForm"
                                    style="display: none;">
                                    @csrf
                                    <input type="hidden" name="orderId" id="orderIdInput">
                                    <input type="hidden" name="imageDetail" id="imageDetail">
                                </form>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
    @endpush
@endsection
