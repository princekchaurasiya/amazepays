@extends('app')
@section('title')
    Amazepay | Payment
@endsection
@section('content')
    <h1>CCAvenue Payment Gateway Integration</h1>
    <div id="ccav-payment-form">
        <form name="frmPayment" action="{{ route('process-payment') }}" method="POST">
            @csrf
            <input type="hidden" name="merchant_id" value="{{ config('paymentconfig.merchant_id') }}">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="language" value="EN">
            <input type="hidden" name="amount" value="1">
            <input type="hidden" name="currency" value="INR">
            <input type="hidden" name="redirect_url" value="{{ route('payment-success') }}">
            <input type="hidden" name="cancel_url" value="{{ route('payment-failed') }}">
            <div>
                <input type="text" name="billing_name" value="" class="form-field" placeholder="Billing Name">
                <input type="text" name="billing_address" value="" class="form-field"
                    placeholder="Billing Address">
            </div>
            <div>
                <input type="text" name="billing_state" value="" class="form-field" placeholder="State">
                <input type="text" name="billing_zip" value="" class="form-field" placeholder="Zipcode">
            </div>
            <div>
                <input type="text" name="billing_country" value="" class="form-field" placeholder="Country">
                <input type="text" name="billing_tel" value="" class="form-field" placeholder="Phone">
            </div>
            <div>
                <input type="text" name="billing_email" value="" class="form-field" placeholder="Email">
            </div>
            <div>
                <button class="btn-payment" type="submit">Pay Now</button>
            </div>
        </form>
    </div>
@endsection