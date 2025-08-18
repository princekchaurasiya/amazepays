@extends('layouts.app')

@section('content')
<br/>
<div class="max-w-2xl mx-auto p-6 bg-white shadow-md rounded-xl mt-8">
    <div class="mb-4">
        <p><strong>Order ID:</strong> {{ $orderId }}</p>
        <p><strong>Request Ref No:</strong> {{ $requestRefNo }}</p>
    </div>

    <h2 class="text-2xl font-bold mb-4 text-blue-600">EVC Card Details</h2>

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
                    <td class="border border-gray-300 px-4 py-2">{{ $item['getCardNo'] }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $item['getCardPin'] }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $item['getExpiryDate'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</div>
@endsection
