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

    <a href="{{ route('evc.details', ['orderId' => $orderId, 'requestRefNo' => $requestRefNo]) }}" 
   class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
    View Card Details
</a>

</div>
@endsection
