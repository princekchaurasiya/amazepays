@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="error-template">
                <h1>Oops!</h1>
                <h2>404 Not Found</h2>
                <div class="error-details my-4">
                    Sorry, the page you requested could not be found.
                </div>
                <div class="error-actions">
                    <a href="{{ route('home') }}" class="btn btn-primary">
                        <i class="fas fa-home"></i> Take Me Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.error-template {
    padding: 40px 15px;
}
.error-template h1 {
    font-size: 3.5rem;
    color: #333;
}
.error-template h2 {
    font-size: 2rem;
    color: #666;
}
.error-details {
    font-size: 1.2rem;
    color: #777;
}
.error-actions {
    margin-top: 30px;
}
</style>
@endsection
