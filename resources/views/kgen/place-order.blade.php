@extends('layouts.app')

@section('content')
@auth
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
@else
<div class="container py-5 text-center">
    <h2 class="mb-3">Please log in to place an order</h2>
    <p class="text-muted mb-4">An active account is required to submit orders.</p>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#Modallogin">
        Launch Login
    </button>
</div>
@endauth
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    @guest
    if (typeof $ !== 'undefined') {
        const loginModal = $('#Modallogin');
        if (loginModal.length) {
            loginModal.modal({ backdrop: 'static', keyboard: false });
            loginModal.modal('show');
            loginModal.on('hide.bs.modal', function (event) {
                if (!window.kgenLoginCompleted) {
                    event.preventDefault();
                }
            });
        }
    }
    @endguest
});
</script>
@endpush
@endsection
