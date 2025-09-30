<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transaction Mail</title>
</head>
<body>
    <h2>Hello {{ $prepareMailDetails['billing_name'] ?? 'Customer' }},</h2>
    <p>Thank you for your purchase! Please find your product details below:</p>

    @if(!empty($prepareMailDetails['products']))
        <h3>Products:</h3>
        <ul>
            @foreach($prepareMailDetails['products'] as $product)
                <li>
                    <strong>{{ $product['productName'] }}</strong><br>
                    <em>Redemption Instructions:</em><br>
                    <p>{!! nl2br(e($product['redemptionInstructions'])) !!}</p>
                </li>
            @endforeach
        </ul>
    @else
        <p>No product details available.</p>
    @endif

    <p>Best regards,</p>
    <p>{{ config('companyDefaultValues.company_name') }}</p>
</body>
</html>
