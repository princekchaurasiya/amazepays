@extends('layouts.app')

@section('title')
    Amazepay | My Order
@endsection

@section('content')
@php
    use App\Helpers\CommonHelper;
@endphp
    <div class="dashboard-wrapper bg-greylight">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 d-none d-lg-block">
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
                                $isClickable = $orderItem->order_status == 'COMPLETE' && !empty($orderItem->woohoo_order_id);
                                $linkAttributes = $isClickable ? 'href="' . route('view-card-details', ['orderId' => $orderItem->woohoo_order_id]) . '"' : '';
                                ?>

                                <a {!! $linkAttributes !!} 
                                    class="order-link {{ !$isClickable ? 'disabled-link' : '' }}"
                                    style="{{ !$isClickable ? 'pointer-events: none;' : '' }}">
                                    <div class="outer-order-wrapper-div">
                                        <div class="card product-card">
                                            <div class="card-body my-order-card-body">
                                                <div class="row">
                                                    <div class="col-lg-4">
                                                        @if($orderItem->display_image)
                                                            <img class="my-order-image-div img-fluid mb-3 mb-lg-0"
                                                                src="{{ $orderItem->display_image }}" 
                                                                alt="{{ $orderItem->product_name }}"
                                                                style="width: 244px; height: auto;">
                                                        @endif
                                                    </div>
                                                    <div class="col-lg-4">
                                                        <h2>{{ $orderItem->product_name }}</h2>
                                                        <p class="mb-0">Order ID: <b>{{ $orderItem->refno }}</b></p>
                                                        <p class="mb-0">Brand: <b>{{ $orderItem->brandName ?? $orderItem->brand_name }}</b></p>
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
            // Remove the old script since we're not using forms anymore
        </script>
    @endpush
@endsection

<style>
    .disabled-link {
        background-color: #f0f0f0 !important;
        opacity: 0.6;
    }
</style>
