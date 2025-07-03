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

    <!-- Dropdown to select gift card -->
    <label>Select Gift Card:</label>
    <select id="giftcardSelect" onchange="getSkusByGiftCard()">
        <option value="">-- Select --</option>
    </select><br>

    <!-- SKUs will be shown here -->
    <div id="skuList" class="result-box"></div>

    <!-- Navigate to purchase form -->
    <a href="{{ url('/purchase-form') }}">
        <button>Purchase a Gift Card</button>
    </a>

    <!-- Check Order Status -->
    <div>
        <label>Check Order Status:</label><br>
        <input type="text" id="orderId" placeholder="Enter order_id">
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
            const res = await fetch('/api/giftcards');
            const data = await res.json();

            let giftcardSelect = document.getElementById('giftcardSelect');
            giftcardSelect.innerHTML = '<option value="">-- Select --</option>';

            data.giftcards?.forEach(card => {
                let opt = document.createElement('option');
                opt.value = card.id;
                opt.innerText = `${card.name} (${card.brand})`;
                giftcardSelect.appendChild(opt);
            });

            document.getElementById('giftcardResult').innerText = JSON.stringify(data, null, 2);
        }

        async function getSkusByGiftCard() {
            const id = document.getElementById('giftcardSelect').value;
            if (!id) return;

            const res = await fetch(`/api/giftcards/${id}/skus`);
            const data = await res.json();

            let output = "<strong>SKUs:</strong><br>";
            data.skus?.forEach(sku => {
                output += `SKU ID: ${sku.sku_id}, ₹${sku.denomination}, Qty: ${sku.quantity}, Discount: ${sku.discount}<br>`;
            });

            document.getElementById('skuList').innerHTML = output;
        }

        async function checkOrder() {
            const orderId = document.getElementById('orderId').value;
            if (!orderId) return;

            const res = await fetch(`/api/orders/${orderId}`);
            const data = await res.json();

            document.getElementById('orderResult').innerText = JSON.stringify(data, null, 2);
        }

        async function getWalletBalance() {
            const res = await fetch('/api/wallet-balance');
            const data = await res.json();

            document.getElementById('walletResult').innerText = `Wallet Balance: ₹${data.balance}`;
        }
    </script>
</body>
</html>
