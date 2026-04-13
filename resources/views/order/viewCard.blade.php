@extends('layouts.app')
@section('title')
    Amazepay | View Card
@endsection
@section('content')
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
                            <!-- <li class="d-block rounded-lg "><a href="payment.html"><i class="ti-credit-card font-sm"></i><span> Payment</span></a></li> -->
                            @include('partials.logout-form-sidebar')
                        </ul>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="row outer-order-wrapper-div  pb-5">
                        <div>
                            <h1 class="font-weight-bold pt-4 pb-2">Card Details</h1>
                        </div>

                        @foreach ($cardArray as $card)

                            <div class="col-lg-6 mb-2">
                                <div class="card product-card">
                                    <div class="card-body my-order-card-body">
                                        @if($productImage)
                                            <p>
                                                <img src="{{ $productImage }}" alt="{{ $order->product->name ?? 'Product Image' }}" class="img-fluid">
                                            </p>
                                        @endif
                                        <p class="mb-0">Card Number: <b>{{ $card['cardNumber'] ?? $card['cardnumber'] ?? 'N/A' }}</b></p>
                                        <p class="mb-0">Card Pin: <b>{{ $card['cardPin'] ?? $card['cardpin'] ?? 'N/A' }}</b></p>
                                        @if(!empty($card['validity']))
                                        <p class="mb-0">Validity:
                                            <b>{{ \Carbon\Carbon::parse($card['validity'])->format('d/m/y') }}</b>
                                        </p>
                                        @endif
                                        @if(!empty($card['activationCode'] ?? $card['activation_code'] ?? null))
                                        <p class="mb-0">Activation Code: <b>{{ $card['activationCode'] ?? $card['activation_code'] }}</b></p>
                                        @endif
                                        @if(!empty($card['amount']))
                                        <p class="mb-0">Amount: <b>₹{{ number_format($card['amount'], 2) }}</b></p>
                                        @endif
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
    @endpush
@endsection
