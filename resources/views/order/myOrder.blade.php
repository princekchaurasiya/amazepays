@extends('layouts.app')

@section('title')
    Amazepay | My Order
@endsection

@section('content')
    <div class="dashboard-wrapper bg-greylight">
        <div class="container">
            <div class="row">
                <div class="col-lg-3">
                    <div class="dashboard-nav bg-white rounded-lg shadow-xs sticky-top-changed">
                        <a href="#" class="dash-menu d-none d-block-md"><i class="ti-package font-sm mr-2"></i> Menu <i
                                class="ti-angle-down font-xsss float-right "></i></a>
                        <ul class="dash-menu-ul">
                            <li class="d-block rounded-lg"><a href="{{ route('profile') }}"><i
                                        class="ti-user font-sm"></i><span> Profile</span></a></li>
                            <li class="d-block rounded-lg active"><a href="{{ route('my-order') }}"><i
                                        class="ti-package font-sm"></i><span> My Order</span></a></li>
                            <li class="d-block rounded-lg"><a href="{{ route('change-password') }}"><i
                                        class="ti-lock font-sm"></i><span> Change Password</span></a></li>
                            <li class="d-block rounded-lg"><a href="{{ route('userLogOut') }}"><i
                                        class="ti-power-off font-sm"></i><span> Logout</span></a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-9">
                    <div class="row outer-order-wrapper-div pb-5">
                        <div>
                            <h1 class="font-weight-bold pt-2 pb-1">My Orders</h1>
                        </div>
                        @foreach ($order as $orderItem)
                            @if ($orderItem->refno)
                                <?php
                                $images = json_decode($orderItem->images, true);
                                $isClickable = $orderItem->order_status == 'COMPLETE';
                                ?>

                                <a href="#" class="order-link {{ !$isClickable ? 'disabled-link' : '' }}"
                                    data-order-id="{{ $orderItem->woohoo_order_id }}" data-image="{{ $images['small'] }}"
                                    style="{{ !$isClickable ? 'pointer-events: none;' : '' }}">
                                    <div class="outer-order-wrapper-div">
                                        <div class="card product-card">
                                            <div class="card-body my-order-card-body">
                                                <div class="row">
                                                    <div class="col-lg-4">
                                                        @if ($images && isset($images['small']))
                                                            <img class="my-order-image-div img-fluid mb-3 mb-lg-0"
                                                                src="{{ $images['small'] }}" alt=""
                                                                style="width: 244px; height: auto;">
                                                        @endif
                                                    </div>
                                                    <div class="col-lg-4">
                                                        <h2>{{ $orderItem->product_name }}</h2>
                                                        <p class="mb-0">Order ID: <b>{{ $orderItem->refno }}</b></p>
                                                        <p class="mb-0">Brand: <b>{{ $orderItem->brandName }}</b></p>
                                                        <p class="mb-0">Product SKU: <b>{{ $orderItem->sku }}</b></p>



                                                    </div>
                                                    <div class="col-lg-4 ml-auto text-lg-right mt-3 mt-lg-0">
                                                        <h2>
                                                            <span
                                                                class="{{ $orderItem->order_status == 'COMPLETE' ? 'text-success' : 'text-danger' }} font-weight-bold">
                                                                Order {{ ucfirst(strtolower($orderItem->order_status)) }}
                                                            </span>
                                                        </h2>
                                                        <p class="mb-0">Denomination:
                                                            <b>{{ $orderItem->denomination }}</b></p>
                                                            <p class="mb-0">Quantity: <b>{{ $orderItem->quantity }}</b></p>
                                                        <p class="mb-0">Discount:
                                                            <b>{{ $orderItem->discounted_amount_value }}</b></p>
                                                        <p class="mb-0">Total Amount Paid:
                                                            <b>{{ $orderItem->amount_payable_after_discount }}</b></p>
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
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('.order-link').on('click', function(e) {
                    e.preventDefault();

                    // Get the order ID and image detail from data attributes
                    var orderId = $(this).data('order-id');
                    var imageDetail = $(this).data('image');

                    // Set the order ID and image detail in the hidden input fields
                    $('#orderIdInput').val(orderId);
                    $('#imageDetail').val(imageDetail);

                    // Submit the form
                    $('#orderForm').submit();
                });
            });
        </script>
    @endpush
@endsection

<style>
    .disabled-link {
        background-color: #f0f0f0 !important;
        opacity: 0.6;
    }
</style>
