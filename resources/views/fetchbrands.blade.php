<!DOCTYPE html>
<html>
<head>
    <title>Fetch Brands</title>
</head>
<body>
    <h1>Fetch Brands Test Page</h1>
    <form method="POST" action="/fetchbrands">
        @csrf
        <button type="submit">Fetch Brands</button>
    </form>
</body>
</html>
