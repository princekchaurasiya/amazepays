<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Successful - Processing Order</title>
<style>
    body { font-family: 'Segoe UI', sans-serif; background: #f5f6fa; margin:0; display:flex; justify-content:center; align-items:center; min-height:100vh;}
    .container { background:white; border-radius:15px; padding:40px; text-align:center; box-shadow:0 15px 35px rgba(0,0,0,0.1); max-width:500px; width:90%; }
    .success-icon { width:80px; height:80px; background:#4CAF50; border-radius:50%; display:flex; justify-content:center; align-items:center; margin:0 auto 20px; font-size:40px; color:white; }
    .processing-icon { width:50px; height:50px; border:5px solid #f3f3f3; border-top:5px solid #3498db; border-radius:50%; margin:20px auto; animation:spin 1s linear infinite;}
    @keyframes spin { 0%{transform:rotate(0deg);}100%{transform:rotate(360deg);} }
    h1 { color:#2c3e50; margin-bottom:10px; }
    .subtitle { color:#7f8c8d; margin-bottom:20px; }
    .status-card { background:#f8f9fa; padding:20px; border-radius:10px; margin-bottom:20px; border-left:5px solid #4CAF50; text-align:left; }
    .status-item { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #e0e0e0; }
    .status-item:last-child { border-bottom:none; }
    .status-label { font-weight:600; color:#2c3e50; }
    .status-value { color:#7f8c8d; }
    .processing-text { color:#3498db; font-weight:600; margin-top:10px; }
</style>
</head>
<body>
<div class="container">
    <div class="success-icon">✓</div>
    <h1>Payment Successful!</h1>
    <p class="subtitle">Your payment has been processed successfully.</p>

    <div class="status-card">
        <div class="status-item"><span class="status-label">Payment ID:</span><span class="status-value">{{ $payment_id }}</span></div>
        <div class="status-item"><span class="status-label">Order ID:</span><span class="status-value">{{ $order_id }}</span></div>
        <div class="status-item"><span class="status-label">Amount:</span><span class="status-value">₹{{ number_format($amount,2) }}</span></div>
        <div class="status-item"><span class="status-label">Status:</span><span class="status-value" style="color:#4CAF50;font-weight:600">{{ $status }}</span></div>
    </div>

    <div class="processing-icon"></div>
    <p class="processing-text">Processing your order...</p>
</div>

<script>
    setTimeout(function(){
        var merchantOrderId = "{{ $merchant_order_id ?? session('merchant_order_id', '') }}";
        if (merchantOrderId) {
            window.location.href = "{{ route('woohoo.process') }}?merchant_order_id=" + merchantOrderId;
        } else {
            console.error('Merchant order ID not found');
            window.location.href = "{{ route('my-order') }}";
        }
    }, 3000);
</script>
</body>
</html>
