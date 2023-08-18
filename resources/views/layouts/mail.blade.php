<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Template</title>
</head>
<body>
    <table style="width: 100%; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ccc; font-family: Arial, sans-serif;">
        <tr>
            <td style="text-align: center;">
                {{-- <img src="http://amazepay.test/images/logo.png" alt="Logo" style="max-width: 200px;" type="image/png"> --}}
                <h2>Dear {{ $name }},</h2>
                <p>Thank you for your order from http://amazepay.test</p>
                <p>For your convenience, we have included a copy of your order below. The charge will appear on your credit card / Account Statement as 'sdf'</p>
            </td>
        </tr>
        <tr>
            <td style="padding-top: 20px;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 50%;"><strong>Order No </strong></td>
                        <td style="width: 50%;">{{ $order_id }}</td>
                    </tr>
                    
                    <tr>
                        <td style="width: 50%;"><strong>CCAvenue Reference </strong></td>
                        <td style="width: 50%;">{{ $reference_id }}</td>
                    </tr>
                    <tr>
                        <td style="width: 50%;"><strong>Order Date</strong></td>
                        <td style="width: 50%;">{{ $order_date }}</td>
                    </tr>
                    <tr>
                        <td><img src="{{ $smallImageUrl }}" alt="Logo" style="max-width: 200px;" type="image/png"></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding-top: 20px;">
                <h3>Billing Details</h3>
                <p>Customer: {{ $billing_name }} | {{ $billing_email }} | {{ $billing_tel }}</p>
                <p>Address: {{ $billing_address }}</p>
                <p>Pay Mode: {{ $payment_mode }}</p>
                <p>Bank Ref #: {{ $bank_ref_no }}</p>
                <p>Instructions: </p>
                <p>Order Amount: {{ $order_amount }}</p>
                <p>Net Payable: {{ $net_payable }}</p>
            </td>
        </tr>
        <tr>
            <td style="padding-top: 20px;">
                <h3>Shipping Details</h3>
                <p>Contact Person: {{ $contact_person }}</p>
                <p>Address: {{ $shipping_address }}</p>
            </td>
        </tr>
        <tr>
            <td style="text-align: center; padding-top: 20px;">
                <p>CUSTOMER CARE</p>
                <p><a href="http://amazepay.toutle.in">http://amazepay.toutle.in</a></p>
                <p>Email: samsher@amazepays.in</p>
                <p>Contact Info: 9988776655 </p>
            </td>
        </tr>
    </table>
</body>
</html>
