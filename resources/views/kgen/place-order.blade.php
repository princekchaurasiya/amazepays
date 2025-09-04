@extends('layouts.app')

@section('content')
<div class="container">
      <br/>
    <h1>Place Order</h1>

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('place-order.submit') }}">
        @csrf
      
        <div class="mb-3">
            <label>Variant ID</label>
            <input type="text" name="variantId" class="form-control" value="{{ $variantId }}" readonly>
        </div>

        <div class="mb-3">
            <label>MRP</label>
            <input type="number" step="0.01" name="mrp" class="form-control" value="{{ $mrp }}" required>
        </div>

        <button type="submit" class="btn btn-primary">Confirm Order</button>
        <a href="{{ route('products') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
