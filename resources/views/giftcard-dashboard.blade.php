<!DOCTYPE html>
<html>
<head>
    <title>Gift Card Dashboard</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: Arial; padding: 20px; }
        button, select, input { margin: 10px 0; padding: 8px; }
        .result-box { margin-top: 20px; background: #f9f9f9; padding: 15px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h2>Gift Card Dashboard</h2>

    <!-- Button to fetch available gift cards -->
    <button onclick="getGiftCards()">List Available Gift Cards</button><br>

    <!-- Check Order Status -->
    <div>
    <label>Check Order Status:</label><br>
    <input type="text" id="orderId" placeholder="Enter order_id (optional)">
    <input type="text" id="merchantId" placeholder="Enter merchant_order_request_id (optional)">
    <button onclick="checkOrder()">Check Order</button>
</div>
<div id="orderResult" class="result-box"></div>

    <!-- Wallet Balance -->
    <div>
        <button onclick="getWalletBalance()">Get Wallet Balance</button>
        <div id="walletResult" class="result-box"></div>
    </div>

    <!-- Results -->
    <div id="giftcardResult" class="result-box"></div>

    <script>
        async function getGiftCards() {
            const res = await fetch('/giftcards');
            const data = await res.json();

            let giftcardSelect = document.getElementById('giftcardSelect');
            giftcardSelect.innerHTML = '<option value="">-- Select --</option>';

            data.giftcards?.forEach(card => {
                let opt = document.createElement('option');
                opt.value = card.id;
                opt.innerText = `${card.name} (${card.brand})`;
                giftcardSelect.appendChild(opt);
                console.log('Gift cards:', data.giftcards);
            });

            document.getElementById('giftcardResult').innerText = JSON.stringify(data, null, 2);
        }

        async function getSkusByGiftCard() {
            const id = document.getElementById('giftcardSelect').value;
            if (!id) return;

            try 
            {
            const res = await fetch(`/giftcards/${id}/skus`);
            if (!res.ok) {
            const errorText = await res.text(); // read once
            console.error('Error response:', errorText);
            document.getElementById('skuList').innerHTML = `Error: ${res.status}`;
            return;
                }
            const contentType = res.headers.get("content-type") || "";

        if (res.ok && contentType.includes("application/json")) {
            const data = await res.json();

            let output = "<strong>SKUs:</strong><br>";
            data.skus?.forEach(sku => {
                output += `SKU ID: ${sku.sku_id}, ₹${sku.denomination}, Qty: ${sku.quantity}, Discount: ${sku.discount}<br>`;
            });

            document.getElementById('skuList').innerHTML = output;
        }
        else {
            // Treat as HTML (probably an error page)
            const html = await res.text();
            document.getElementById('skuList').innerHTML = html;
        }
    }
        catch (err) {
        console.error('Request failed:', err);
        document.getElementById('skuList').innerHTML = 'An unexpected error occurred.';
    }
}

    async function checkOrder() {
    const orderId = document.getElementById('orderId').value.trim();
    const merchantId = document.getElementById('merchantId').value.trim();

    if (!orderId && !merchantId) {
        document.getElementById('orderResult').innerText = 'Please enter at least one ID.';
        return;
    }

    const params = new URLSearchParams();
    if (orderId) params.append('order_id', orderId);
    if (merchantId) params.append('merchant_order_request_id', merchantId);

    try {
        const res = await fetch(`/orders?${params.toString()}`);
        const data = await res.json();

        if (res.ok) {
            document.getElementById('orderResult').innerText = JSON.stringify(data, null, 2);
        } else {
            document.getElementById('orderResult').innerText = `Error: ${data.error || 'Something went wrong'}`;
        }
    } catch (err) {
        document.getElementById('orderResult').innerText = 'Failed to fetch order status.';
        console.error(err);
    }
}


        async function getWalletBalance() {
            const res = await fetch('/wallet-balance');
            const data = await res.json();

            document.getElementById('walletResult').innerText = `Wallet Balance: ₹${data.balance}`;
        }
    </script>
</body>
</html>
