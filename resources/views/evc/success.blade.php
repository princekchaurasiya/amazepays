@extends('layouts.app')

@section('content')
<br/>
<div class="max-w-4xl mx-auto p-6 bg-white shadow-md rounded-xl mt-8">

    <h2 class="text-2xl font-bold mb-4 text-green-600">EVC Request Approved</h2>

    <div class="mb-4">
        <p><strong>Order ID:</strong> {{ $orderId }}</p>
        <p><strong>Request Ref No:</strong> {{ $requestRefNo }}</p>
    </div>

    @if(!empty($items))
        <h3 class="text-xl font-semibold mt-6 mb-4">Card Details:</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse border border-gray-300 text-sm">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="border border-gray-300 px-4 py-2">Card No</th>
                        <th class="border border-gray-300 px-4 py-2">Card Pin</th>
                        <th class="border border-gray-300 px-4 py-2">Expiry Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td class="border border-gray-300 px-4 py-2">{{ $item['getCardNo'] ?? 'N/A' }}</td>
                            <td class="border border-gray-300 px-4 py-2">{{ $item['getCardPin'] ?? 'N/A' }}</td>
                            <td class="border border-gray-300 px-4 py-2">{{ $item['getExpiryDate'] ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
            <p>No card details available.</p>
        </div>
    @endif

</div>
@endsection
