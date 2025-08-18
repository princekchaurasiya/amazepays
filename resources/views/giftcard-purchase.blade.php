<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Gift Card</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Purchase Gift Card</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('giftcard.purchase') }}">
                    @csrf

                    <!-- Auto-generated Order ID -->
                    <input type="hidden" name="order_request_id" id="order_request_id" value="{{ (string) Str::uuid() }}">

                    <!-- Giftcard ID -->
                    <div class="mb-3">
                        <label for="giftcard_id" class="form-label">Giftcard ID</label>
                        <input type="text" name="brand_id" id="giftcard_id" class="form-control" value="{{ $giftcardId }}" readonly>
                    </div>

                    <!-- SKU ID -->
                    <div class="mb-3">
                        <label for="sku_id" class="form-label">SKU ID</label>
                        <input type="text" name="sku_id" id="sku_id" class="form-control" value="{{ $skuId }}" readonly>
                    </div>

                    <!-- Quantity -->
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" name="quantity" id="quantity" class="form-control" value="1" min="1" required>
                    </div>

                    <!-- Currency -->
                    <div class="mb-3">
                        <label for="currency" class="form-label">Currency</label>
                        <input type="text" name="currency" id="currency" class="form-control" value="INR" readonly>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn btn-success">Purchase</button>
                </form>

                @if($errors->any())
                    <div class="alert alert-danger mt-4">
                        <strong>Error:</strong>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (Optional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
