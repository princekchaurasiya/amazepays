@extends('layouts.app')

@section('title')
    Amazepay | Transaction Status
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row tyfsrow justify-content-center mt-5 mb-5">
            <div class="col-10 text-center p-0">

                @if ($isSuccessful)
                    <p class="success-icon">
                        <span class="icon-tick tyicon"></span>
                    </p>
                    <p class="success-icon">
                        <i class="fa-regular fa-circle-check tyicon"></i>
                    </p>
                    <h2 class="section-subtext black-header pb-3 success-text">
                        {{ $transactionStatusMessage }}
                    </h2>
                    <p class="success-message">
                        Thank you for your purchase! We have received your order.
                    </p>
                    <p><a href="{{ route('my-order') }}" class="Order-status-link">View your recent order.</a></p>

                @else
                    <p class="error-icon">
                        <span class="icon-cross cross-icon"></span>
                    </p>
                    <p class="error-icon">
                        <i class="fa-regular fa-circle-xmark cross-icon"></i>
                    </p>
                    <h2 class="section-subtext black-header pb-3 error-text">
                        {{ $transactionStatusMessage }}
                    </h2>
                    <p class="error-message">
                        Unfortunately, we were unable to process your order. Please go to the <a href="{{ route('home') }}" class="error-link">homepage</a> and place a fresh new order.
                    </p>
                    <p class="refund-info">If any money was deducted, it will either be refunded or we will resend your card details via email or SMS within 1 or 2 working days.</p>
                @endif
            </div>
        </div>
    </div>
@endsection
