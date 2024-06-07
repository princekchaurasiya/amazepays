@extends('layouts.app')
@section('title')
    Amazepay | About
@endsection
@section('content')
    <div class="row paymentSuccess" id="paymentSuccess">
        <div class="col-12 text-center allsection">
            <p>
                <span class="fa-regular fa-face-frown sad-icon"></span>
            </p>
            <strong>{{ $errorMessage ?? 'Something Went Wrong' }}</strong><br>
            @if (session('error-message'))
                <div class="alert alert-danger" role="alert">

                    {{ session('error-message') }}
                </div>
                <p><a href="/" class="btn btn-danger">Back to Home</a></p>
            @endif

            @if (session('success_message'))
                <div class="alert alert-success" role="alert">
                    {{ session('success_message') }}
                </div>
                <p><a href="/" class="btn btn-success">Back to Home</a></p>
            @endif
        </div>
    </div>
@endsection
