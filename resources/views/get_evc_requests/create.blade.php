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

        input {
            width: 100%;
            padding: 10px;
            border: 2px solid orange; /* Default border */
            border-radius: 5px;
            outline: none;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }

        input:focus {
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

<form action="{{ url('/get-evc-request') }}" method="POST">
    @csrf

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

    <button type="submit">Submit</button>
</form>

</body>
</html>
