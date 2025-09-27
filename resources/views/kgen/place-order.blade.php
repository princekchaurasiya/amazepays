@extends('layouts.app')

@section('content')
<div class="container">
      <br/>
      @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif
    <h1>Place Order</h1>

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('place-order.submit') }}">
        @csrf
      
        <div class="mb-3">
           <!-- <label>Variant ID</label>-->
            <input type="hidden" name="variantId" class="form-control" value="{{ $variantId }}" readonly>
        </div>

        <div class="mb-3">
            <label>MRP</label>
            <input type="number" step="0.01" name="mrp" class="form-control" value="{{ $mrp }}" required>
        </div>

        <button type="submit" class="btn btn-primary">Confirm Order</button>
        <a href="{{ route('products') }}" class="btn btn-secondary">Cancel</a>
    </form>
    @if(session('vouchers'))
    <div class="card mt-3">
        <div class="card-header">Voucher Details</div>
        <div class="card-body">
            @foreach(session('vouchers') as $voucher)
                <p><strong>Code:</strong> {{ $voucher['voucherCode'] }}</p>
                <p><strong>PIN:</strong> {{ $voucher['voucherPin'] }}</p>
                <p><strong>Expires on:</strong> {{ \Carbon\Carbon::parse($voucher['expirationDate'])->toDateString() }}</p>
                <hr>
            @endforeach
        </div>
    </div>
@endif
</div>
@endsection
