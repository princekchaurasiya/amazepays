@extends('layouts.app')
@section('title')
    Amazepay | Success
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row tyfsrow">
            <div class="col-12 text-center p-0">
                <p>
                    <span class="icon-tick tyicon"></span>
                </p>
                <p>
                    <i class="fa-regular fa-circle-check tyicon"></i>
                </p>
                <h2 class="section-subtext black-header pb-3">
                    Your Order is successfully processed.
                </h2>

                <p>
                    Thank you for your purchase! We have received your order.
                </p>
                <p><a href="{{ route('my-order') }}" class="Order-status-link">View your recent order.</a></p>
            </div>
        </div>
    </div>
@endsection
