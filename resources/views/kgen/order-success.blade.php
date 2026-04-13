<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - KGen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .success-icon { font-size: 4rem; color: #28a745; }
        .voucher-card { border-left: 5px solid #28a745; }
        .voucher-code { font-family: monospace; font-size: 1.2rem; font-weight: bold; }
        .details-table th { width: 150px; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Success Header -->
                <div class="text-center mb-5">
                    <div class="success-icon mb-4">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h1 class="display-4 fw-bold text-success mb-3">Order Confirmed!</h1>
                    <p class="lead text-muted">Your payment was successful and order has been placed.</p>
                </div>

                <!-- Order Summary Card -->
                <div class="card shadow-lg border-0 mb-5">
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-receipt me-2"></i>
                            Order #{{ $order->id }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <th>Order Date:</th>
                                        <td>{{ $order->created_at->format('M d, Y h:i A') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i>
                                                {{ ucfirst($order->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Payment Status:</th>
                                        <td>
                                            <span class="badge bg-success">
                                                <i class="fas fa-credit-card me-1"></i>
                                                {{ $order->payment_status ?? 'Completed' }}
                                            </span>
                                        </td>
                                    </tr>
                                    @if($order->user)
                                    <tr>
                                        <th>Customer:</th>
                                        <td>{{ $order->user->name ?? 'Guest' }}</td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <th>MRP:</th>
                                        <td><strong>₹{{ number_format($order->mrp, 2) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Paid Amount:</th>
                                        <td><strong class="text-success">₹{{ number_format($order->payable_amount, 2) }}</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

<!-- ✅ FORCE VOUCHER DISPLAY - Works with your exact JSON -->
<div class="card voucher-card shadow mb-4">
    <div class="card-header bg-success text-white">
        <h4 class="mb-0">
            <i class="fas fa-gift-card me-2"></i>
            Voucher Details
        </h4>
    </div>
    <div class="card-body">
        @if($order->api_response)
            <div class="row mb-4 p-4 border rounded bg-light">
                <div class="col-md-3 text-center">
                    <i class="fas fa-ticket-alt fa-4x text-success mb-3"></i>
                    <div class="voucher-code fs-3 fw-bold text-primary mb-2">
                        {{ $order->voucher_code }}
                    </div>
                    <small class="text-muted">Voucher Code</small>
                </div>
                <div class="col-md-9">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="card border-danger h-100">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-key fa-2x text-danger mb-2"></i>
                                    <h6 class="text-danger">PIN</h6>
                                    <div class="fs-5 fw-bold text-dark">{{ $order->voucher_pin }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-calendar fa-2x text-warning mb-2"></i>
                                    <h6>Expires</h6>
                                    <div class="fs-6">{{ $order->voucher_expiration_date }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white h-100">
                                <div class="card-body text-center p-3">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                                    <h6>Status</h6>
                                    <span>FULFILLED</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-warning">
                <i class="fas fa-clock me-2"></i>
                No API response yet. Order still processing...
            </div>
        @endif
    </div>
</div>

                <!-- Action Buttons -->
                <div class="text-center mt-5">
                    <a href="{{ route('orders.list') }}" class="btn btn-primary btn-lg me-3">
                        <i class="fas fa-list me-2"></i>
                        View Orders
                    </a>
                    <a href="{{ route('products') }}" class="btn btn-outline-success btn-lg">
                        <i class="fas fa-plus me-2"></i>
                        Place New Order
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>