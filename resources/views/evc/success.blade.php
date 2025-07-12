@extends('layouts.app')

@section('content')
<br/>
<div class="max-w-2xl mx-auto p-6 bg-white shadow-md rounded-xl mt-8">

    <h2 class="text-2xl font-bold mb-4 text-green-600"> EVC Request Approved</h2>

    <div class="mb-4">
        <p><strong>Order ID:</strong> {{ $orderId }}</p>
        <p><strong>Request Ref No:</strong> {{ $requestRefNo }}</p>
    </div>

    <h3 class="text-xl font-semibold mt-6 mb-2">🔐 Decrypted EVC Data:</h3>
    <div class="bg-gray-100 p-4 rounded">
       <pre class="whitespace-pre-wrap break-words text-sm text-gray-800">
        {{ $evcData }}
    </pre>
    </div>

    @foreach($evcData as $key => $value)
        <li>
            <span class="font-medium capitalize">{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
            <span>{{ $value }}</span>
        </li>
    @endforeach

</div>
@endsection
