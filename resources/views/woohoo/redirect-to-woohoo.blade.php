<!DOCTYPE html>
<html>
<head>
    <title>Redirecting...</title>
    <style>
        /* Basic loader styles */
        .loader {
            border: 16px solid #f3f3f3;
            border-radius: 50%;
            border-top: 16px solid #3498db;
            width: 120px;
            height: 120px;
            animation: spin 2s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Centering the loader */
        .loader-container {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            flex-direction: column;
        }

        /* Message styles */
        .message {
            margin-top: 20px;
            font-size: 18px;
            color: #333;
            text-align: center;
        }

        /* Digital clock styles */
        .clock {
            font-size: 24px;
            color: #333;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="loader-container">
        <div class="loader"></div>
        <div class="message">Please do not refresh the page or press the back button. We are creating your order...</div>
        <div id="clock" class="clock"></div>
    </div>

    <form id="redirectForm" method="POST" action="{{ route('woohoo.createOrder') }}" style="display: none;">
        @csrf
        <!-- Hidden inputs can be added here if needed to pass additional data -->
    </form>

    <script type="text/javascript">
        let secondsElapsed = 0;

        function updateClock() {
            secondsElapsed++;
            document.getElementById('clock').textContent = secondsElapsed;

            // Check if elapsed time is 180 seconds
            if (secondsElapsed === 180) {
                // Submit the form after 180 seconds
                document.getElementById('redirectForm').submit();
            }
        }

        setInterval(updateClock, 1000);
        updateClock(); // Initial call to start the timer immediately

        // Remove immediate form submission - let the timer handle it
        // document.getElementById('redirectForm').submit();
    </script>
</body>
</html>
