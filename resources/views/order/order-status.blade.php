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
                    
                    @if(isset($cardsArray) && !empty($cardsArray) && is_array($cardsArray))
                        <div class="voucher-details mt-4 mb-4" style="max-width: 800px; margin: 0 auto;">
                            <h3 class="mb-3" style="color: #333; font-size: 1.5rem;">Your Voucher Details</h3>
                            <div class="row">
                                @foreach ($cardsArray as $index => $card)
                                    <div class="col-md-6 mb-3">
                                        <div class="card" style="border: 2px solid #4CAF50; border-radius: 8px; padding: 20px; background: #f9f9f9;">
                                            <h4 style="color: #4CAF50; margin-bottom: 15px;">Voucher {{ $index + 1 }}</h4>
                                            <div class="voucher-info" style="text-align: left;">
                                                @if(isset($card['cardNumber']))
                                                    <p style="margin-bottom: 10px;"><strong>Card Number:</strong> <span style="font-family: monospace; font-size: 1.1em;">{{ $card['cardNumber'] }}</span></p>
                                                @endif
                                                @if(isset($card['cardPin']))
                                                    <p style="margin-bottom: 10px;"><strong>Card PIN:</strong> <span style="font-family: monospace; font-size: 1.1em;">{{ $card['cardPin'] }}</span></p>
                                                @endif
                                                @if(isset($card['amount']))
                                                    <p style="margin-bottom: 10px;"><strong>Amount:</strong> ₹{{ number_format($card['amount'], 2) }}</p>
                                                @endif
                                                @if(isset($card['activationCode']))
                                                    <p style="margin-bottom: 10px;"><strong>Activation Code:</strong> <span style="font-family: monospace;">{{ $card['activationCode'] }}</span></p>
                                                @endif
                                                @if(isset($card['activationUrl']))
                                                    <p style="margin-bottom: 10px;"><strong>Activation URL:</strong> <a href="{{ $card['activationUrl'] }}" target="_blank" style="color: #4CAF50; word-break: break-all;">{{ $card['activationUrl'] }}</a></p>
                                                @endif
                                                @if(isset($card['validity']))
                                                    <p style="margin-bottom: 10px;"><strong>Validity:</strong> {{ \Carbon\Carbon::parse($card['validity'])->format('d M Y') }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="alert alert-info mt-3" style="background-color: #e3f2fd; border-left: 4px solid #2196F3; padding: 15px;">
                                <strong>Note:</strong> Please save these voucher details. You will also receive them via email and SMS.
                            </div>
                        </div>
                    @endif
                    
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
