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
            <a href="{{ route('admin.api.index') }}" class="btn btn-primary btn-lg">1. API Management (Fetch Brands, Sync Stores, Wallet Balance)</a>
            <a href="{{ route('admin.vd.brands') }}" class="btn btn-secondary btn-lg">2. Show Brands</a>
            <a href="{{ route('admin.stores.filter') }}" class="btn btn-info btn-lg text-white">3. Filter Stores</a>
            <a href="{{ route('admin.evc.request') }}" class="btn btn-info btn-lg text-white">4. Get EVC Request</a>
            <a href="{{ route('admin.evc.form') }}" class="btn btn-info btn-lg text-white">5. Get EVC Status</a>
            
        </div>
    </div>
</body>
</html>
