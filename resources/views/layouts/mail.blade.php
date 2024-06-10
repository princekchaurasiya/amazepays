<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Template</title>
</head>

<body>



    <table
        style="width: 100%; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ccc; font-family: Arial, sans-serif;">

        <tr>
            <td style="text-align: left;">
                {{-- <img src="http://amazepay.test/images/logo.png" alt="Logo" style="max-width: 200px;" type="image/png"> --}}
                <h2>Dear {{ $prepareMailDetails['name'] }},</h2>
                <p>Thank you for your order from AMAZEPAYS</p>
            </td>
        </tr>
        <tr>
            <td style="padding-top: 20px;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 50%;"><strong>Order No </strong></td>
                        <td style="width: 50%;">{{ $prepareMailDetails['order_id'] }}</td>
                    </tr>

                    <tr>
                        <td style="width: 50%;"><strong>Bank Reference No</strong></td>
                        <td style="width: 50%;">{{ $prepareMailDetails['bank_ref_no'] }}</td>
                    </tr>
                    <tr>
                        <td style="width: 50%;"><strong>Order Date</strong></td>
                        <td style="width: 50%;">{{ $prepareMailDetails['invoice_date'] }}</td>
                    </tr>
                    <tr>
                        <td style="padding-top:20px">
                            <img src="https://amazepays.in/images/logo.png" alt="Logo" style="max-width: 200px;"
                                type="image/png">
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding-top: 20px;">
                <h3>Billing Details</h3>
                <p>Customer: {{ $prepareMailDetails['billing_name'] }} | {{ $prepareMailDetails['billing_email'] }} |
                    {{ $prepareMailDetails['billing_tel'] }}</p>
                <p>Address: {{ $prepareMailDetails['billing_address'] }}</p>
                <p>Pay Mode: {{ $prepareMailDetails['payment_mode'] }}</p>
                <p>Order Amount: {{ $prepareMailDetails['amount_payable_after_discount'] }}</p>
            </td>
        </tr>
        <tr>
            <td style="padding-top: 20px;">
                <h3>Shipping Details</h3>
                <p>{{ $prepareMailDetails['shipToName'] }}</p>
                <p>{{ $prepareMailDetails['shipToEmail'] }}</p>
                <p> {{ $prepareMailDetails['shipToContactNo'] }}</p>
            </td>
        </tr>
        <tr>
            <td style="text-align: left; padding-top: 20px;">
                <p>CUSTOMER CARE</p>
                <p><a
                        href="{{ config('companyDefaultValues.company_website') }}">{{ config('companyDefaultValues.company_website') }}</a>
                </p>
                <p>Email: {{ config('companyDefaultValues.company_email') }}</p>
                <p>Contact Info: {{ config('companyDefaultValues.company_contact_no') }}</p>


            </td>
        </tr>
    </table>
</body>

</html>
