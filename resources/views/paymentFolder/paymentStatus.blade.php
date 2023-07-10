@extends('layouts.app')
@section('title')
    Amazepay | About
@endsection
@section('content')

    @if ($status === 'success')
        <div class="row paymentSuccess" id="paymentSuccess">
            <div class="col-12 text-center allsection">
                <p>
                    <i class="fa-regular fa-circle-check tyicon"></i>
                </p>
                <h2 class="order-header pb-5">
                    {{ $msg }}
                </h2>
                <p>
                    Thank you for your purchase! We have received your order.
                </p>
                <p><a href="{{ route('myOrder') }}" class="payment-status-link">View your recent order.</a></p>
            </div>
        </div>
    @else
        <div class="row paymentFailed" id="paymentFailed">
            <div class="col-12 text-center allsection">
                <p>
                    <i class="fa-regular fa-circle-xmark cross-icon"></i>
                </p>
                <h2 class="order-header pb-5">
                    {{ $msg }}
                </h2>
                <p><a href="{{ route('home') }}" class="payment-status-link">Go to Homepage</a></p>
            </div>
        </div>
    @endif
@endsection
