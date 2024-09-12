@extends('layouts.app')
@section('title')
    Amazepay | Something Went Wrong
@endsection
@section('content')
    <div class="row paymentSuccess" id="paymentSuccess">
        <div class="col-12 text-center allsection">
            <p>
                <span class="fa-regular fa-face-frown sad-icon"></span>
            </p>

            {{-- Check if APP_DEBUG is true in the .env file --}}
            @if (env('APP_DEBUG') == true)
                <strong>{{ $errorMessage ?? 'Something Went Wrong' }}</strong><br>
            @else
                <strong>Something Went Wrong, please place a fresh new order.</strong><br>
            @endif

            <p class="mt-4">
                <a href="/" class="btn btn-primary">Back to HomePage</a>
            </p>
        </div>
    </div>
@endsection
