@extends('layouts.app')

@section('content')
    <div style="color: green; text-align: center; margin-top: 50px;">
        <h2>Payment Successful</h2>

        <button 
    onclick="window.location.href='{{ route('evc.details', ['orderId' => $orderId, 'requestRefNo' => $requestRefNo]) }}'" 
    style="margin-top: 20px; padding: 10px 20px; border: none; border-radius: 5px; background-color: #007bff; color: white; cursor: pointer;"
>
    Get Order Details
</button>
    </div>
@endsection
