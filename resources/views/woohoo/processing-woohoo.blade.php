<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - Processing Order</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: pulse 2s infinite;
        }
        .success-icon::before {
            content: "✓";
            color: white;
            font-size: 40px;
            font-weight: bold;
        }
        .processing-icon {
            width: 60px;
            height: 60px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #7f8c8d;
            font-size: 18px;
            margin-bottom: 30px;
        }
        .status-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #4CAF50;
        }
        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        .status-item:last-child {
            border-bottom: none;
        }
        .status-label {
            font-weight: 600;
            color: #2c3e50;
        }
        .status-value {
            color: #7f8c8d;
        }
        .processing-text {
            color: #3498db;
            font-weight: 600;
            margin-top: 20px;
        }
        .note {
            background: #e8f4fd;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            color: #0c5460;
            font-size: 14px;
        }
        .loading-dots {
            display: inline-block;
        }
        .loading-dots::after {
            content: '';
            animation: dots 1.5s infinite;
        }
        @keyframes dots {
            0%, 20% { content: ''; }
            40% { content: '.'; }
            60% { content: '..'; }
            80%, 100% { content: '...'; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon"></div>
        
        <h1>Payment Successful!</h1>
        <p class="subtitle">Your payment has been processed successfully</p>
        
        <div class="status-card">
            <div class="status-item">
                <span class="status-label">Payment ID:</span>
                <span class="status-value">{{ $payment_id ?? 'N/A' }}</span>
            </div>
            <div class="status-item">
                <span class="status-label">Order ID:</span>
                <span class="status-value">{{ $order_id ?? 'N/A' }}</span>
            </div>
            <div class="status-item">
                <span class="status-label">Amount:</span>
                <span class="status-value">₹{{ number_format($amount ?? 0, 2) }}</span>
            </div>
            <div class="status-item">
                <span class="status-label">Status:</span>
                <span class="status-value" style="color: #4CAF50; font-weight: 600;">{{ $status ?? 'Success' }}</span>
            </div>
        </div>
        
        <div class="processing-icon"></div>
        <p class="processing-text">
            Processing your order<span class="loading-dots"></span>
        </p>
        
        <div class="note">
            <strong>What's happening now:</strong><br>
            • Your payment has been confirmed<br>
            • We're preparing your gift card order<br>
            • You'll receive an email confirmation shortly<br>
            • Please don't close this page
        </div>
    </div>

    <script>
        // Auto-redirect to processing after 3 seconds
        setTimeout(function() {
            window.location.href = "{{ route('woohoo.processing.createOrder') }}";
        }, 3000);
        
        // Show countdown
        let countdown = 3;
        const countdownElement = document.createElement('p');
        countdownElement.style.color = '#7f8c8d';
        countdownElement.style.marginTop = '10px';
        document.querySelector('.processing-text').appendChild(countdownElement);
        
        const timer = setInterval(function() {
            countdownElement.textContent = `Redirecting in ${countdown} seconds...`;
            countdown--;
            
            if (countdown < 0) {
                clearInterval(timer);
                countdownElement.textContent = 'Redirecting now...';
            }
        }, 1000);
    </script>
</body>
</html>
