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

            <p class="mt-4"><a href="/" class="btn btn-primary">Back to HomePage</a></p>

        </div>
    </div>
@endsection
