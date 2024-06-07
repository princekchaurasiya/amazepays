<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gift Email Template</title>
</head>

<body>
    <table
        style="width: 100%; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ccc; font-family: Arial, sans-serif;">
        {{-- {{ dd($cardsArray) }} --}}
        <tr>
            <td style="text-align: left; width:70%;">
                <p style="font-weight: 600; font-size: 13px">Dear <span
                        style="font-size: 15px; font-weight: 600">{{ ucfirst
                        ($prepareMailDetails['shipToName']) }}</span>, you've
                    received {{ count($cardsArray) }} Amazon Pay E-Gift Card's! Worth ₹ {{ $cardsArray[0]['amount'] }}
                    each.</p>
            </td>
            </td>
            <td style="text-align: center; width:30%">
                <img src="https://amazepays.in/images/logo.png" alt="Logo" style="max-width: 100px;"
                    type="image/png">
            </td>
        </tr>
    </table>
    <table
        style="width: 100%; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ccc; font-family: Arial, sans-serif;">
        @foreach ($cardsArray as $card)
            <tr>
                <td style="width: 50%; font-size: 16px; vertical-align: middle; padding: 10px; padding-left: 10%; ">
                    <img src="{{ $prepareMailDetails['smallImageUrl'] }}" alt="Logo" style="max-width: 500px; width: 100%; height: auto;" type="image/png">
                    <p style="font-size: 12px;">
                        <span style="font-weight: 600; font-size: 14px;">Gift Card Id</span>
                        <br>
                        {{ $card['cardNumber'] }}
                    </p>
                    <p style="font-size: 12px;">
                        <span style="font-weight: 600; font-size: 14px;">Card Pin</span>
                        <br>
                        {{ $card['cardPin'] }}
                    </p>
                </td>
                <td style="width: 50%; vertical-align: top;  padding-left: 10%;">
                    <p style="font-size: 20px; font-weight: 600;">
                        ₹ {{ $card['amount'] }}
                        <br>
                        <span style="font-size: 12px; font-weight: 600;">Validity:</span>
                        <span
                            style="font-size: 12px; font-weight: 400;">{{ date('Y/m/d', strtotime($card['validity'])) }}</span>
                        <br>
                        <span style="font-size: 12px; font-weight: 600;">Activation Code:</span>
                        <span style="font-size: 12px; font-weight: 400;">{{ $card['activationCode'] }}</span>
                        <br>
                        <span style="font-size: 12px; font-weight: 600;">Activation URL:</span>
                        <span style="font-size: 12px; font-weight: 400;">{{ $card['activationUrl'] }}</span>
                    </p>
                </td>

            </tr>
        @endforeach
    </table>
    <table
        style="width: 100%; max-width: 600px; margin: 0 auto; border: 1px solid #ccc; font-family: Arial, sans-serif; padding: 20px">
        <tr>
            <td style="width: 50%; padding-left: 20px">
                Order Number : {{ $prepareMailDetails['order_id'] }}
            </td>
        </tr>
    </table>
</body>

</html>
