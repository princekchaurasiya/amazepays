@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Check EVC Status</h2>

    <form method="POST" action="{{ route('evc.status') }}">
        @csrf
        <div class="form-group">
            <label>Order ID</label>
            <input type="text" name="order_id" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Request Ref No</label>
            <input type="text" name="request_ref_no" class="form-control" required>
        </div>

        <button class="btn btn-primary mt-3">Check Status</button>
    </form>
    <form method="POST" action="{{ route('evc.activated') }}">
        @csrf
        <div class="form-group">
            <label>Order ID</label>
            <input type="text" name="order_id" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Request Ref No</label>
            <input type="text" name="request_ref_no" class="form-control" required>
        </div>

        <button class="btn btn-primary mt-3">Check Activated Status</button>
    </form>
</div>
@endsection
