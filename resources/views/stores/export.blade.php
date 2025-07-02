<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store details</title>
</head>
<body>
    <table>
    <thead>
        <tr>
            <th>Store Name</th>
            <th>Price</th>
            <th>Country</th>
            <th>Brand Code</th>
        </tr>
    </thead>
    <tbody>
        @foreach($stores as $store)
        <tr>
            <td>{{ $store->store_name }}</td>
            <td>{{ $store->price }}</td>
            <td>{{ $store->country }}</td>
            <td>{{ $store->brand_code }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
