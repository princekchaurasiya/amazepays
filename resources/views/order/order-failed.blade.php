@extends('layouts.app')

@section('title', 'Amazepay | About')

@section('content')
    <div class="container-fluid pt-4">
        <div class="row tyfsrow">
            <div class="col-12 text-center p-0">
                <p>
                    <i class="fa-regular fa-circle-xmark cross-icon"></i>
                </p>
                <h2 class="section-subtext black-header pb-3">
                    Your Order is Failed any Money Deducted will be refunded in 1 hour.
                </h2>
                <p class="pb-3">
                    Please try again.
                </p>
                <p>
                    <a href="{{ route('home') }}">Go to Home</a>
                </p>
                @if(session('error'))
                    <div class="alert alert-danger" role="alert">
                        {{ session('error') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
