@extends('layouts.app')

@section('title')
    Amazepay | Success
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row tyfsrow">
            <div class="col-12 text-center p-0">


                @if ($isSuccessful)
                    <p>
                        <span class="icon-tick tyicon"></span>
                    </p>
                    <p>
                        <i class="fa-regular fa-circle-check tyicon"></i>
                    </p>
                    <h2 class="section-subtext black-header pb-3">
                        {{ $transactionStatusMessage }}
                    </h2>
                    <p>
                        Thank you for your purchase! We have received your order.
                    </p>
                    <p><a href="{{ route('my-order') }}" class="Order-status-link">View your recent order.</a></p>
                @else
                    <p>
                        <span class="icon-cross cross-icon"></span>
                    </p>
                    <p>
                        <i class="fa-regular fa-circle-xmark cross-icon"></i>
                    </p>
                    <h2 class="section-subtext black-header pb-3">

                        {{ $transactionStatusMessage }}
                    </h2>
                    <p>
                        Unfortunately, we were unable to process your order. Please go to the <a href="{{ route('home') }}">homepage</a> and place a fresh new order.
                    </p>
                    {{-- <p><a href="{{ route('home') }}" class="btn btn-primary">Back to Homepage</a></p> --}}
                @endif
            </div>
        </div>
    </div>
@endsection
