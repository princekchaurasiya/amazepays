<!DOCTYPE html>
<html>
<head>
    <title>Purchase Gift Card</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <h2>Purchase Gift Card</h2>

    <form method="POST" action="{{ route('giftcard.purchase') }}">
        @csrf

        {{-- Auto-generated Order ID --}}
        <input type="hidden" name="order_request_id" id="order_request_id" value="{{ (string) Str::uuid() }}">

        {{-- Giftcard ID (Read-only display or hidden) --}}
        <label for="giftcard_id">Giftcard ID:</label><br>
        <input type="text" name="brand_id" id="giftcard_id" value="{{ $giftcardId }}" readonly><br><br>

        {{-- SKU ID --}}
        <label for="sku_id">SKU ID:</label><br>
        <input type="text" name="sku_id" id="sku_id" value="{{ $skuId }}" readonly><br><br>

        {{-- Quantity --}}
        <label for="quantity">Quantity:</label><br>
        <input type="number" name="quantity" id="quantity" value="1" min="1" required><br><br>

        {{-- Currency --}}
        <label for="currency">Currency:</label><br>
        <input type="text" name="currency" id="currency" value="INR" readonly><br><br>

        <button type="submit">Purchase</button>
    </form>
</body>
@if($errors->any())
    <div style="margin-top: 20px; padding: 10px; background-color: #ffe6e6; border: 1px solid #ff4d4f;">
        <strong>Error:</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
</html>
