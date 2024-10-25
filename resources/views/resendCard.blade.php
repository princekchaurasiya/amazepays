@extends('layouts.app')
@section('title')
   Resend Order ID
@endsection

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 p-5">
                <div class="card">
                    <div class="card-header text-center">
                        <h2 class="mb-0">Resend Order Details</h2>
                    </div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success" role="alert">
                                {{ session('success') }}
                            </div>
                        @elseif(session('error'))
                            <div class="alert alert-danger" role="alert">
                                {{ session('error') }}
                            </div>
                        @endif
                        <form action="{{ route('resend.order') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="order_id">Order ID</label>
                                <input type="text" class="form-control" id="order_id" name="order_id" placeholder="Enter Order ID" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Resend Order</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
