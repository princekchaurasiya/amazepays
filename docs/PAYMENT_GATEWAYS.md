# AmazePays Payment Gateways

> **Version:** 2.1  
> **Last Updated:** April 2026

---

## Table of Contents

1. [Overview](#1-overview)
2. [Gateway Abstraction Layer](#2-gateway-abstraction-layer)
3. [CCAvenue Integration](#3-ccavenue-integration)
4. [Razorpay Integration](#4-razorpay-integration)
5. [Unlimit Integration](#5-unlimit-integration)
6. [Credential Vault](#6-credential-vault)
7. [Webhook Handling](#7-webhook-handling)
8. [Payment Flow](#8-payment-flow)
9. [Refund Handling](#9-refund-handling)
10. [Reconciliation](#10-reconciliation)
11. [Adding a New Gateway](#11-adding-a-new-gateway)

---

## 1. Overview

AmazePays supports multiple payment gateways that can be configured per tenant. The system uses a **Strategy Pattern** to abstract gateway differences behind a common interface.

### Supported Gateways

| Gateway | Status | Type | Currencies | Use Case |
|---------|--------|------|-----------|----------|
| CCAvenue | Active | Redirect-based | INR | Primary for B2C |
| Unlimit | Active | API + Webhook | INR, USD, EUR | International + B2B |
| Razorpay | Planned | Checkout.js + API | INR | Alternative B2C |

### Gateway Selection Priority

1. **B2B (wallet):** Debit from wallet directly -- no gateway needed
2. **B2B (gateway):** Use tenant-specific gateway from `tenant_payment_gateways`
3. **B2C:** Use platform default gateways (configured in system settings)
4. **Fallback:** If primary gateway fails, offer alternative gateways

---

## 2. Gateway Abstraction Layer

### Interface Definition

```php
// app/Contracts/PaymentGatewayInterface.php

namespace App\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Get the gateway identifier.
     */
    public function getIdentifier(): string;

    /**
     * Get the list of required credential keys.
     * Used by admin UI to render the credential form.
     */
    public function getRequiredCredentials(): array;

    /**
     * Initiate a payment session.
     *
     * @param PaymentRequest $request
     * @return PaymentResponse (contains redirect URL or checkout data)
     */
    public function initiate(PaymentRequest $request): PaymentResponse;

    /**
     * Verify a payment by transaction ID.
     *
     * @param string $transactionId
     * @return PaymentStatus
     */
    public function verify(string $transactionId): PaymentStatus;

    /**
     * Process a refund.
     *
     * @param string $transactionId
     * @param float $amount (partial or full)
     * @return RefundResponse
     */
    public function refund(string $transactionId, float $amount): RefundResponse;

    /**
     * Handle an incoming webhook/callback.
     *
     * @param Request $request
     * @return WebhookResult
     */
    public function handleWebhook(Request $request): WebhookResult;
}
```

### Factory Pattern

```php
// app/Services/Payment/PaymentGatewayFactory.php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\TenantPaymentGateway;
use Illuminate\Support\Facades\Crypt;

class PaymentGatewayFactory
{
    private array $gateways = [
        'ccavenue' => CCAvenueGateway::class,
        'razorpay' => RazorpayGateway::class,
        'unlimit'  => UnlimitGateway::class,
    ];

    public function resolve(string $gateway, ?int $tenantId = null): PaymentGatewayInterface
    {
        $class = $this->gateways[$gateway]
            ?? throw new \InvalidArgumentException("Unknown gateway: {$gateway}");

        $credentials = $this->loadCredentials($gateway, $tenantId);

        return new $class($credentials);
    }

    public function getAvailableGateways(?int $tenantId = null): array
    {
        if ($tenantId) {
            return TenantPaymentGateway::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->pluck('gateway')
                ->toArray();
        }

        // Platform defaults
        return array_keys($this->gateways);
    }

    private function loadCredentials(string $gateway, ?int $tenantId): array
    {
        if ($tenantId) {
            $config = TenantPaymentGateway::where('tenant_id', $tenantId)
                ->where('gateway', $gateway)
                ->where('is_active', true)
                ->firstOrFail();

            return json_decode(Crypt::decryptString($config->credentials), true);
        }

        // Fall back to .env / config for platform-level
        return match ($gateway) {
            'ccavenue' => config('paymentconfig'),
            'unlimit'  => config('unlimit'),
            'razorpay' => config('razorpay'),
        };
    }
}
```

### Data Transfer Objects

```php
// PaymentRequest -- passed to gateway.initiate()
class PaymentRequest
{
    public string $merchantOrderId;   // UUID
    public float $amount;
    public string $currency;          // INR, USD
    public string $customerName;
    public string $customerEmail;
    public string $customerPhone;
    public string $returnUrl;
    public string $cancelUrl;
    public string $webhookUrl;
    public array $billingAddress;
    public array $metadata;           // merchant_param1-5, etc.
}

// PaymentResponse -- returned from gateway.initiate()
class PaymentResponse
{
    public bool $success;
    public string $transactionId;
    public ?string $redirectUrl;      // For redirect-based (CCAvenue)
    public ?string $checkoutToken;    // For JS-based (Razorpay)
    public array $rawResponse;
}

// PaymentStatus -- returned from gateway.verify()
class PaymentStatus
{
    public string $transactionId;
    public string $status;            // success, failed, pending
    public float $amount;
    public string $currency;
    public ?string $bankRefNo;
    public ?string $paymentMode;      // card, netbanking, upi, wallet
    public array $rawResponse;
}
```

---

## 3. CCAvenue Integration

### Current State

CCAvenue is integrated via `app/Http/Controllers/CCAvenueController.php` with encryption helpers in `CryptoController.php`.

### Configuration

**Current (`.env`):**
```
CCAVENUE_MERCHANT_ID=xxxxx
CCAVENUE_ACCESS_CODE=xxxxx
CCAVENUE_WORKING_KEY=xxxxx
CCAVENUE_LINK=https://secure.ccavenue.com/transaction/transaction.do
```

**Target (per-tenant from DB):**
```json
{
  "merchant_id": "xxxxx",
  "access_code": "xxxxx",
  "working_key": "xxxxx",
  "api_endpoint": "https://secure.ccavenue.com/transaction/transaction.do"
}
```

### Payment Flow

```
1. User clicks "Pay with CCAvenue"
2. CCAvenueGateway.initiate():
   a. Build form data (order_id, amount, billing details)
   b. Encrypt with AES-128-CBC using working_key
   c. Return redirect URL + encrypted data
3. User redirected to CCAvenue payment page
4. User completes payment on CCAvenue
5. CCAvenue POSTs to return URL with encrypted response
6. CCAvenueGateway.handleWebhook():
   a. Decrypt response with working_key
   b. Parse order_status (Success/Failure/Aborted)
   c. Return WebhookResult
7. Order updated based on result
```

### Required Credentials

| Key | Description | Example |
|-----|-------------|---------|
| `merchant_id` | CCAvenue merchant ID | `12345` |
| `access_code` | API access code | `AVAB12CD34` |
| `working_key` | Encryption working key | `A1B2C3D4E5F6G7H8` |
| `api_endpoint` | CCAvenue URL | `https://secure.ccavenue.com/...` |

---

## 4. Razorpay Integration

### Configuration

```json
{
  "key_id": "rzp_live_xxxxx",
  "key_secret": "xxxxx",
  "webhook_secret": "xxxxx"
}
```

### Payment Flow

```
1. User clicks "Pay with Razorpay"
2. RazorpayGateway.initiate():
   a. Create Razorpay Order via API
   b. Return order_id + checkout token
3. Frontend opens Razorpay Checkout.js modal
4. User completes payment
5. Frontend receives payment_id, sends to verify endpoint
6. RazorpayGateway.verify():
   a. Verify signature: SHA256(order_id|payment_id, key_secret)
   b. Fetch payment details via API
   c. Return PaymentStatus
7. Webhook handler also receives async notification
```

### Required Credentials

| Key | Description | Example |
|-----|-------------|---------|
| `key_id` | Razorpay API key | `rzp_live_xxxxx` |
| `key_secret` | API secret | `xxxxx` |
| `webhook_secret` | Webhook signature secret | `xxxxx` |

### Razorpay Checkout.js (Frontend)

```javascript
const options = {
  key: razorpayKeyId,         // Public key (safe to expose)
  amount: order.amount * 100, // Razorpay expects paise
  currency: "INR",
  name: "AmazePays",
  order_id: order.razorpay_order_id,
  handler: function (response) {
    // Send to backend for verification
    verifyPayment({
      razorpay_payment_id: response.razorpay_payment_id,
      razorpay_order_id: response.razorpay_order_id,
      razorpay_signature: response.razorpay_signature,
    });
  },
};
const rzp = new Razorpay(options);
rzp.open();
```

---

## 5. Unlimit Integration

### Current State

Unlimit is deeply integrated with:
- `config/unlimit.php` -- API URLs, timeouts, retries
- `UnlimitPaymentController.php` -- Payment initiation
- `UnlimitCallbackController.php` -- Webhook handler
- `VerifyUnlimitSignature.php` -- HMAC-SHA512 signature verification

### Configuration

**Current (`.env`):**
```
UNLIMIT_BASE_URL=https://psp.in.unlimit.com/ma-new
UNLIMIT_API_LOGIN=xxxxx
UNLIMIT_API_PASSWORD=xxxxx
UNLIMIT_CALLBACK_SECRET=xxxxx
```

**Target (per-tenant from DB):**
```json
{
  "base_url": "https://psp.in.unlimit.com/ma-new",
  "api_base_url": "https://psp.in.unlimit.com",
  "login": "xxxxx",
  "password": "xxxxx",
  "callback_secret": "xxxxx"
}
```

### Payment Flow

```
1. User initiates payment
2. UnlimitGateway.initiate():
   a. Get auth token via POST /api/auth/token
   b. Create payment via POST /api/payments
   c. Return redirect URL from response
3. User completes payment on Unlimit page
4. Unlimit sends POST to /api/unlimit/callback
5. VerifyUnlimitSignature middleware:
   a. Extract Signature header
   b. Compute: hash('sha512', rawBody + callbackSecret)
   c. Compare with hash_equals()
6. UnlimitGateway.handleWebhook():
   a. Parse callback payload
   b. Update payment + order status
   c. Return WebhookResult
```

### Webhook Signature Verification

```php
// Existing implementation in VerifyUnlimitSignature middleware
$stringToSign = $rawBody . $callbackSecret;
$expectedSignature = hash('sha512', $stringToSign);
hash_equals($expectedSignature, $signatureHeader);
```

### Required Credentials

| Key | Description | Example |
|-----|-------------|---------|
| `base_url` | Payment redirect base URL | `https://psp.in.unlimit.com/ma-new` |
| `api_base_url` | API base URL | `https://psp.in.unlimit.com` |
| `login` | API login | `xxxxx` |
| `password` | API password | `xxxxx` |
| `callback_secret` | Webhook HMAC secret | `xxxxx` |

---

## 6. Credential Vault

### Storage Architecture

```
┌──────────────────────────────────────────────────┐
│              Admin Panel (React)                  │
│                                                  │
│  Gateway Credential Form:                        │
│  ┌──────────────────────────────────────┐        │
│  │ Gateway: [ CCAvenue ▼ ]              │        │
│  │ Merchant ID: [_____________]          │        │
│  │ Access Code: [_____________]          │        │
│  │ Working Key: [_____________]          │        │
│  │ Environment: [ Production ▼ ]         │        │
│  │ [Save Credentials]                    │        │
│  └──────────────────────────────────────┘        │
└──────────────┬───────────────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────────────┐
│         TenantPaymentGatewayController            │
│                                                  │
│  1. Validate credentials format                  │
│  2. Test connection (optional sandbox call)       │
│  3. JSON encode credentials                      │
│  4. Crypt::encryptString(json)                   │
│  5. Store in tenant_payment_gateways             │
│  6. Log audit trail                              │
└──────────────┬───────────────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────────────┐
│         tenant_payment_gateways table             │
│                                                  │
│  credentials column = AES-256-CBC encrypted blob │
│  Only decryptable with APP_KEY                   │
│  Never exposed in API responses or logs          │
└──────────────────────────────────────────────────┘
```

### Security Rules

1. Credentials are **never** returned to the frontend after saving (write-only)
2. Admin UI shows `••••••••` for existing credentials
3. To update: enter new values (blank fields = keep existing)
4. All credential operations logged in `audit_logs`
5. `APP_KEY` rotation requires re-encrypting all credentials (migration command)
6. Credential fields are **excluded** from model `$visible` and `toArray()`

### Credential Validation

Before saving, the system can optionally test the credentials:

| Gateway | Test Method |
|---------|-------------|
| CCAvenue | Encrypt/decrypt test with working_key |
| Razorpay | `GET /v1/payments?count=1` with key_id/key_secret |
| Unlimit | `POST /api/auth/token` with login/password |

---

## 7. Webhook Handling

### Webhook Routes

```php
// routes/api.php (or routes/webhooks.php)

Route::prefix('webhooks')->group(function () {
    Route::post('/ccavenue/callback', [WebhookController::class, 'ccavenue'])
        ->middleware(['throttle:payment-callbacks']);

    Route::post('/razorpay/callback', [WebhookController::class, 'razorpay'])
        ->middleware(['throttle:payment-callbacks', 'verify.razorpay.signature']);

    Route::post('/unlimit/callback', [WebhookController::class, 'unlimit'])
        ->middleware(['throttle:payment-callbacks', 'verify.unlimit.signature']);
});
```

### Payload whitelisting and application logs

Implementation guidelines (aligned with `App\Services\Payment\PaymentService`):

| Gateway | Typical keys passed to `handleGatewayCallback()` | Notes |
|---------|--------------------------------------------------|--------|
| CCAvenue | `encResp` only | Encrypted blob is decrypted inside the gateway class. |
| Razorpay | Webhook: `event`, `payload` — Redirect: `razorpay_signature`, `razorpay_payment_id`, `razorpay_order_id` | Mode is detected by presence of `event`. |
| Unlimit | `payment_data`, `merchant_order` | Nested structures; do not merge arbitrary top-level client keys. |

- Do **not** log full webhook bodies or payment return query parameters in application logs.
- Persist outcomes on `Order` / `UnlimitPayment` (or equivalent) rows for reconciliation and support.

Controllers: `App\Http\Controllers\Payment\CCAvenuCallbackController`, `RazorpayCallbackController`, `UnlimitCallbackController`; legacy HTML flows may use `CCAvenueController`, `UnlimitController`, etc., with billing fields limited to `$request->only([...])` when building gateway posts.

### Idempotency

```php
// WebhookController base logic
public function processWebhook(string $gateway, Request $request): Response
{
    $merchantOrderId = $this->extractMerchantOrderId($gateway, $request);

    // Idempotency check
    $order = Order::where('merchant_order_id', $merchantOrderId)->first();
    if (!$order || $order->order_status === 'COMPLETE') {
        return response()->json(['status' => 'ignored'], 200);
    }

    // Process
    $gatewayInstance = $this->factory->resolve($gateway, $order->tenant_id);
    $result = $gatewayInstance->handleWebhook($request);

    // Update order atomically
    DB::transaction(function () use ($order, $result) {
        $order->update(['order_status' => $result->status]);
        $order->orderSummary->update([
            'payment_status' => $result->status,
            'payment_gateway' => $result->gateway,
        ]);
    });

    // Dispatch fulfillment if paid
    if ($result->status === 'PAID') {
        PlaceVoucherOrderJob::dispatch($order);
    }

    return response()->json(['status' => 'processed'], 200);
}
```

### Webhook Retry Handling

| Scenario | Response | Gateway Behavior |
|----------|----------|-----------------|
| Processed successfully | HTTP 200 | No retry |
| Already processed (idempotent) | HTTP 200 | No retry |
| Server error | HTTP 500 | Gateway retries (varies) |
| Signature invalid | HTTP 403 | No retry |
| Rate limited | HTTP 429 | Gateway retries with backoff |

---

## 8. Payment Flow

### Unified Checkout Sequence

```
1. User on checkout page
2. Frontend: GET /api/v1/checkout/gateways
   → Returns available gateways for this tenant/user
3. User selects gateway
4. Frontend: POST /api/v1/checkout/initiate
   Body: { merchant_order_id, gateway, amount, billing }
5. Backend:
   a. Validate order exists and is PENDING_PAYMENT
   b. Resolve gateway via factory
   c. Call gateway.initiate()
   d. Save payment record
   e. Return redirect_url or checkout_token
6. Frontend:
   a. CCAvenue: Redirect to payment page
   b. Razorpay: Open Checkout.js modal
   c. Unlimit: Redirect to payment page
7. Payment completion → webhook → order fulfillment
```

---

## 9. Refund Handling

### Refund Flow

```
Admin initiates refund
    │
    ▼
Validate: order is COMPLETE or PROVIDER_ERROR
    │
    ▼
Determine refund method:
    ├── Wallet payment → Credit back to wallet
    └── Gateway payment → Call gateway.refund()
         │
         ├── Full refund: refund entire amount
         └── Partial refund: refund specified amount
              │
              ▼
         Update order status to REFUNDED
         Log in audit_logs
         Notify customer
```

---

## 10. Reconciliation

### Daily Reconciliation Process

1. **Scheduled job** runs daily at 2:00 AM IST
2. Fetch all orders in PENDING status older than 30 minutes
3. For each: call `gateway.verify(transactionId)` to get actual status
4. Update order status based on gateway response
5. Generate reconciliation report
6. Alert admin for any mismatches

### Reports

| Report | Frequency | Content |
|--------|-----------|---------|
| Daily settlement | Daily | Orders settled, amount by gateway |
| Weekly reconciliation | Weekly | Mismatches, pending orders, failed orders |
| Monthly gateway summary | Monthly | Volume, success rate, avg time per gateway |

---

## 11. Adding a New Gateway

To add a new payment gateway:

1. Create `app/Services/Payment/NewGateway.php` implementing `PaymentGatewayInterface`
2. Add gateway identifier to `tenant_payment_gateways.gateway` ENUM
3. Register in `PaymentGatewayFactory::$gateways` array
4. Add webhook route with signature verification middleware
5. Create signature verification middleware if needed
6. Add credential form fields to admin UI
7. Add gateway test method for credential validation
8. Update this documentation

---

## Related Documents

- [CODE_STANDARDS.md](CODE_STANDARDS.md) -- Payload whitelisting and log policy
- [SECURITY.md](SECURITY.md) -- Webhook signature verification details
- [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) -- Payment tables
- [API_DOCUMENTATION.md](API_DOCUMENTATION.md) -- Checkout API endpoints
