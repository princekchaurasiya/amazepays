<!-- resources/views/dashboard.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Value Design API Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="mb-4 text-center">Value Design API Dashboard</h1>

        <div class="d-grid gap-3 col-6 mx-auto">
            <a href="{{ url('/fetchbrands') }}" class="btn btn-primary btn-lg">1. Fetch Brands</a>
            <a href="{{ url('/brands/select') }}" class="btn btn-secondary btn-lg">2. Select Brand</a>
            <a href="{{ url('/stores/select') }}" class="btn btn-success btn-lg">3. Select Store</a>
            <a href="{{ url('/stores/filter') }}" class="btn btn-info btn-lg text-white">4. Filter Stores</a>
            <a href="{{ url('/evc/request') }}" class="btn btn-info btn-lg text-white">5. Get EVC Request</a>
            <a href="{{ url('/evc/form') }}" class="btn btn-info btn-lg text-white">6. Get EVC Status</a>
        </div>
    </div>
</body>
</html>
