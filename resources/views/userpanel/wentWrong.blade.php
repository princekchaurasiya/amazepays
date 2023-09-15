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
                <h1>Something Went Wrong</h1>
                
                <br>
    <p>We apologize, but something went wrong while processing your request.
    <br>
    Please try again later or contact our support team for assistance.</p>
    <p><a href="/">Back to Home</a></p>
            </div>
        </div>
    
@endsection
