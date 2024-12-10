@extends('layouts.app')

@section('title', 'Check Card Balance')

@section('content')
<div class="container mt-5">
    <div class="row">
        <div class="col-lg-4 align-self-center">
            <img src="{{ asset('images/bal_enuiry.png') }}" alt="" class="img-fluid">
        </div>
        <div class="col-lg-8">
            <h2>Check Your Card Balance</h2>

            {{-- Success Message --}}
            @if(session('response'))
                <div class="alert alert-success mt-3">
                    <h4>Balance Information</h4>
                    <p><strong>Card Number:</strong> {{ session('response.cardNumber') }}</p>
                    <p><strong>Balance:</strong> ₹{{ session('response.balance') }}</p>
                    <p><strong>Expiry:</strong> {{ session('response.expiry') }}</p>
                    <p><strong>Status:</strong> {{ session('response.status') }}</p>
                </div>
            @endif

            {{-- Error Message --}}
            @if($errors->any())
                <div class="alert alert-danger mt-3">

                    <p>{{ $errors->first('error') }}</p>
                </div>
            @endif

            <div class="card p-4 mb-5">
                <form id="checkBalanceForm" action="{{ route('checkCardBalance') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="cardNumber">Card Number*</label>
                        <input type="text" class="form-control @error('cardNumber') is-invalid @enderror" id="cardNumber" name="cardNumber" value="{{ old('cardNumber') }}" required maxlength="16">
                        @error('cardNumber')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="pin">Card PIN*</label>
                        <input type="password" class="form-control @error('pin') is-invalid @enderror" id="pin" name="pin"  maxlength="6">
                        @error('pin')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="sku">SKU (Optional)</label>
                        <input type="text" class="form-control @error('sku') is-invalid @enderror" id="sku" name="sku" value="{{ old('sku') }}" maxlength="30">
                        @error('sku')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary mt-3 btn-blue-background-color">Check Balance</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
