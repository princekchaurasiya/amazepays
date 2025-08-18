<!-- resources/views/get_evc_requests/create.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        form {
            background: #fff;
            padding: 20px;
            max-width: 700px;
            margin: auto;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
            color: #444;
        }

        input, select {
            width: 100%;
            padding: 10px;
            border: 2px solid orange; /* Default border */
            border-radius: 5px;
            outline: none;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }

        input:focus, select:focus {
            border-color: blue; /* Focus border */
            box-shadow: 0 0 5px rgba(0, 0, 255, 0.3);
        }

        button {
            background: orange;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: darkorange;
        }

        .error {
            color: red;
            margin-bottom: 10px;
        }

        .success {
            color: green;
            margin-bottom: 10px;
        }

        .gifting-details {
            display: none;
        }
    </style>
</head>
<body>

<h1>Checkout</h1>

@if(session('success'))
    <div class="success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="error">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('request.evc') }}" method="POST" id="evcForm">
    @csrf
    
    <!-- Hidden fields for brand data -->
    <input type="hidden" name="vd_discount" value="{{ request('vd_discount') }}">
    <input type="hidden" name="vd_brand_code" value="{{ request('vd_brand_code') }}">

    <!-- Gift card details -->
    <label>Denomination</label>
    <input type="number" name="denomination" value="{{ request('denomination') }}" min="100" max="10000" required>

    <label>Quantity</label>
    <input type="number" name="quantity" value="{{ request('quantity') }}" min="1" max="10" required>

    <label>Gift Send Option</label>
    <select name="gift_send_option" id="sendAsGiftRadio" onchange="toggleReceiverFields()">
        <option value="Send as Gift" {{ request('gift_send_option') == 'Send as Gift' ? 'selected' : '' }}>Send as Gift</option>
        <option value="Buy for Self" {{ request('gift_send_option') == 'Buy for Self' ? 'selected' : '' }}>Buy for Self</option>
    </select>

    <input type="hidden" name="delivery_mode" value="both">

    <!-- Gifting details -->
    <div class="gifting-details" id="giftingDetails">
        <label>Receiver Name</label>
        <input type="text" name="receiver_name" value="{{ request('receiver_name') }}" placeholder="Receiver Name">

        <label>Receiver Email</label>
        <input type="email" name="receiver_email" value="{{ request('receiver_email') }}" placeholder="Receiver Email">

        <label>Receiver Mobile</label>
        <input type="text" name="receiver_mobile" value="{{ request('receiver_mobile') }}" placeholder="Receiver Mobile">

        <label>Message for Receiver</label>
        <input type="text" name="receiver_msg" value="{{ request('receiver_msg') }}" placeholder="Message for Receiver">
    </div>

    <!-- Original form fields -->
    @foreach ([
         'no_of_card', 'amount','firstname', 'lastname', 'email', 'mobile_no', 
        'address', 'city', 'state', 'country', 'pincode', 'curr'
    ] as $field)
        <label>{{ ucfirst(str_replace('_', ' ', $field)) }}</label>
        <input 
            type="{{ in_array($field, ['amount', 'no_of_card']) ? 'number' : ($field == 'email' ? 'email' : 'text') }}" 
            name="{{ $field }}" 
            value="{{ old($field) }}" 
            required>
    @endforeach

    <button type="submit">Submit to EVC</button>
</form>

<script>
function toggleReceiverFields() {
    const selectedOption = document.getElementById('sendAsGiftRadio').value;
    const giftingDetails = document.getElementById('giftingDetails');

    if (selectedOption === 'Send as Gift') {
        giftingDetails.style.display = 'block';
    } else {
        giftingDetails.style.display = 'none';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleReceiverFields();
});
</script>

</body>
</html>
