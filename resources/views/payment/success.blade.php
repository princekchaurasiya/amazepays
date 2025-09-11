@extends('layouts.app')

@section('content')
    <div style="color: green; text-align: center; margin-top: 50px;">
        <h2>Payment Successful</h2>

        <button 
            onclick="redirectToEvcDetails()"
            style="margin-top: 20px; padding: 10px 20px; border: none; border-radius: 5px; background-color: #007bff; color: white; cursor: pointer;"
        >
            Get Order Details
        </button>
    </div>

    <script>
        function generateOrderId() {
            const letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            let prefix = "";
            for (let i = 0; i < 5; i++) {
                prefix += letters.charAt(Math.floor(Math.random() * letters.length));
            }

            let numbers = "";
            for (let i = 0; i < 9; i++) {
                numbers += Math.floor(Math.random() * 10);
            }

            return prefix + numbers; // total length = 14
        }

        function generateRequestRefNo() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');

            return `${year}${month}${day}${hours}${minutes}${seconds}`;
        }

        function redirectToEvcDetails() {
            @if(isset($orderId) && isset($requestRefNo))
                // Use server-side values when available
                window.location.href = '{{ route('evc.details', ['orderId' => $orderId, 'requestRefNo' => $requestRefNo]) }}';
            @else
                // Generate values when server-side values are not available
                const orderId = generateOrderId();
                const requestRefNo = generateRequestRefNo();
                const url = `/evc-details/${orderId}/${requestRefNo}`;
                window.location.href = url;
            @endif
        }
    </script>
@endsection
