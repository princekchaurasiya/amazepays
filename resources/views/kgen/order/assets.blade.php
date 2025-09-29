@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <br/>

    <h1 class="text-2xl font-bold mb-4">Order Assets for Order #{{ $order->id }}</h1>

    @if(session('error'))
        <div class="bg-red-100 text-red-800 p-2 mb-4 rounded">{{ session('error') }}</div>
    @endif

    @if(isset($message))
        <div class="bg-yellow-100 text-yellow-800 p-2 mb-4 rounded">{{ $message }}</div>
    @endif

    @if(isset($assetData))
        <div class="mb-4">
            <p><strong>Downloaded At:</strong> {{ $assetData['downloaded_at'] }}</p>
            <p><strong>Password:</strong> {{ $assetData['password'] ?? 'N/A' }}</p>
            <a href="{{ route('order.download', $order) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Download Asset
            </a>
        </div>
    @endif

    @if(isset($vouchers) && count($vouchers) > 0)
        <h2 class="text-xl font-semibold mt-6 mb-2">Vouchers</h2>
        <ul class="list-disc list-inside">
            @foreach($vouchers as $voucher)
                <li>{{ $voucher }}</li>
            @endforeach
        </ul>
    @else
        <p>No vouchers available for this order.</p>
    @endif

</div>
@endsection
