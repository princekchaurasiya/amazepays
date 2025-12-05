<?php
/**
 * Test script for stdClass error fixes
 * Run this in Laravel Tinker: php artisan tinker < test_stdclass_fix.php
 * Or copy-paste the code below into tinker
 */

echo "=== Testing stdClass Error Fixes ===\n\n";

// Test 1: Simulate API response as stdClass (what Woohoo API returns)
echo "Test 1: Simulating Woohoo API response as stdClass\n";
$woohooResponse = (object) [
    'refno' => 'Amzr123',
    'orderId' => 'WOOHOO456',
    'status' => 'COMPLETE',
    'cardnumber' => '1234567890',
    'cardpin' => '1234',
    'amount' => 1000,
    'activation_code' => 'ACT123',
    'activation_url' => 'https://example.com/activate',
    'validity' => '2025-12-31',
];

echo "Original type: " . gettype($woohooResponse) . "\n";
echo "Has cardnumber: " . (isset($woohooResponse->cardnumber) ? 'Yes' : 'No') . "\n";

// This would cause error: Cannot use object of type stdClass as array
try {
    $refno = $woohooResponse["refno"]; // This will fail
    echo "ERROR: Should not reach here!\n";
} catch (\Error $e) {
    echo "✓ Correctly caught error: " . $e->getMessage() . "\n";
}

// Test 2: Normalize stdClass to array (the fix)
echo "\nTest 2: Normalizing stdClass to array (the fix)\n";
if (is_object($woohooResponse)) {
    $woohooResponseArray = json_decode(json_encode($woohooResponse), true);
}
echo "Normalized type: " . gettype($woohooResponseArray) . "\n";
echo "Can access as array: " . (isset($woohooResponseArray["refno"]) ? 'Yes' : 'No') . "\n";
echo "Refno: " . $woohooResponseArray["refno"] . "\n";
echo "Cardnumber: " . ($woohooResponseArray["cardnumber"] ?? 'N/A') . "\n";

// Test 3: Simulate cards extraction from flat fields
echo "\nTest 3: Extracting cards from flat fields\n";
$cardsPayload = [];
if (isset($woohooResponseArray['cards']) && is_array($woohooResponseArray['cards'])) {
    $cardsPayload = $woohooResponseArray['cards'];
    echo "Found cards array\n";
} else {
    // Extract from flat fields
    $singleCard = [
        'cardNumber'      => $woohooResponseArray['cardnumber'] ?? $woohooResponseArray['cardNumber'] ?? null,
        'cardPin'         => $woohooResponseArray['cardpin'] ?? $woohooResponseArray['cardPin'] ?? null,
        'amount'          => $woohooResponseArray['amount'] ?? null,
        'activationCode'  => $woohooResponseArray['activation_code'] ?? $woohooResponseArray['activationCode'] ?? null,
        'activationUrl'   => $woohooResponseArray['activation_url'] ?? $woohooResponseArray['activationUrl'] ?? null,
        'validity'        => $woohooResponseArray['validity'] ?? null,
    ];
    
    if (!empty($singleCard['cardNumber']) || !empty($singleCard['cardPin'])) {
        $cardsPayload[] = $singleCard;
        echo "✓ Successfully extracted card from flat fields\n";
        echo "Card Number: " . $singleCard['cardNumber'] . "\n";
        echo "Card Pin: " . $singleCard['cardPin'] . "\n";
    }
}

// Test 4: Simulate callCardActivation response
echo "\nTest 4: Simulating callCardActivation response\n";
$apiResponse = (object) [
    'cards' => [
        [
            'cardNumber' => '9876543210',
            'cardPin' => '5678',
            'amount' => 2000,
        ]
    ]
];

echo "API response type: " . gettype($apiResponse) . "\n";

// Normalize before accessing
if (is_object($apiResponse)) {
    $apiResponseArray = json_decode(json_encode($apiResponse), true);
}

if (is_array($apiResponseArray) && isset($apiResponseArray["cards"]) && is_array($apiResponseArray["cards"])) {
    echo "✓ Successfully extracted cards from normalized response\n";
    echo "Cards count: " . count($apiResponseArray["cards"]) . "\n";
}

// Test 5: Test with Eloquent model (simulating $order)
echo "\nTest 5: Testing with Eloquent model access\n";
try {
    // Simulate getting an order
    $order = \App\Models\QsOrder::first();
    if ($order) {
        echo "Order found: ID " . $order->id . "\n";
        
        // This should work (object property access)
        $woohooOrderId = $order->woohoo_order_id ?? null;
        echo "woohoo_order_id (object access): " . ($woohooOrderId ?? 'null') . "\n";
        
        // This would fail if $order is stdClass (but Eloquent models work with both)
        try {
            $woohooOrderIdArray = $order["woohoo_order_id"] ?? null;
            echo "woohoo_order_id (array access): " . ($woohooOrderIdArray ?? 'null') . "\n";
        } catch (\Error $e) {
            echo "✗ Array access failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "No orders found in database\n";
    }
} catch (\Exception $e) {
    echo "Could not test with Eloquent model: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";


