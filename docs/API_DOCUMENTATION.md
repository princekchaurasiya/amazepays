# AmazePays REST API v1 Documentation

> **Version:** 1.0  
> **Last Updated:** April 2026  
> **Base URL:** `https://api.amazepays.com/api/v1`

---

## Table of Contents

1. [Overview](#1-overview)
2. [Authentication](#2-authentication)
3. [Common Response Format](#3-common-response-format)
4. [Error Codes](#4-error-codes)
5. [Rate Limiting](#5-rate-limiting)
6. [Consumer API (Mobile App)](#6-consumer-api-mobile-app)
7. [Reseller API](#7-reseller-api)
8. [Loyalty API](#8-loyalty-api)
9. [Webhook Callbacks](#9-webhook-callbacks)
10. [SDK Guidelines](#10-sdk-guidelines)

---

## 1. Overview

AmazePays exposes three API surfaces:

| API | Audience | Auth Method | Base Path |
|-----|----------|------------|-----------|
| Consumer API | Mobile app (B2C) | Sanctum Token | `/api/v1/` |
| Reseller API | Third-party B2B systems | API Key + HMAC | `/api/v1/reseller/` |
| Loyalty API | Loyalty program integrations | OAuth2 Client Credentials | `/api/v1/loyalty/` |

All APIs return JSON. All requests must include `Accept: application/json`.

### API v1 canonical routes (source of truth)

The canonical list of v1 routes is defined in `routes/api.php` under the `/api/v1` prefix.

**Public (no auth)**

| Method | Path | Notes |
|---|---|---|
| GET | `/health` | Minimal health check |
| GET | `/homepage` | Canonical homepage document (web + RN) |
| GET | `/home` | Legacy alias for `/homepage` |
| GET | `/catalog` | Product list (paginated) |
| GET | `/catalog/categories` | Navigation categories |
| GET | `/catalog/{product}` | Product details |
| GET | `/products/{sku}/pricing` | Price breakdown envelope |
| POST | `/auth/otp/send` | Throttled |
| POST | `/auth/otp/verify` | Throttled |
| POST | `/auth/complete-profile` | Throttled |

**Authenticated Consumer (Sanctum)**

| Method | Path | Notes |
|---|---|---|
| GET | `/auth/me` | Current user |
| POST | `/auth/logout` | Logout current token |
| POST | `/auth/2fa/verify` | Requires a `2fa-pending` token |
| GET | `/customers/{customer}` | Self lookup only |
| POST | `/checkout/sessions` | Draft order session (idempotent) |
| POST | `/payments/sessions` | Initiate payment (idempotent) |
| GET | `/payments/sessions/{payment}` | Payment session status |
| POST | `/payments/sessions/{payment}/verify` | Query gateway status (idempotent) |
| GET | `/wallet/balance` | Wallet summary |
| GET | `/wallet/transactions` | Wallet tx list (paginated) |
| POST | `/wallet/load-request` | Wallet load request (step-up + PIN + idempotent) |
| GET | `/wallet/load-request/{loadRequest}` | Wallet load request status |
| GET | `/orders` | Order history (paginated) |
| GET | `/orders/{order}` | Order details |
| POST | `/orders` | Place order (idempotent + limits + VPN check) |
| POST | `/orders/{order}/refund` | Refund request (idempotent) |
| GET | `/orders/{order}/voucher-code` | Protected by step-up + transaction PIN |
| POST | `/transaction-pin/set` | Set PIN |
| POST | `/transaction-pin/change` | Change PIN |
| POST | `/transaction-pin/verify` | Verify PIN |

### Server-side validation (clients cannot be trusted)

The API validates every write with explicit rules. Extra JSON fields sent by the client are **ignored** for persistence unless they are part of validated input—do not rely on undocumented keys. Webhook endpoints under `/api/v1/webhooks/*` verify gateway signatures (where configured) and pass only whitelisted payload fragments to the payment service (see [PAYMENT_GATEWAYS.md](PAYMENT_GATEWAYS.md#payload-whitelisting-and-application-logs) and [CODE_STANDARDS.md](CODE_STANDARDS.md#http-request-input--logging)).

### Storefront checkout anti-tampering notes

For gift-card checkout (`/checkout/{slug}` in web storefront), backend validation is authoritative:

- Client-provided totals are not trusted; payable amounts are recomputed server-side from locked product pricing.
- `gift_send_option` is enforced against product policy (`both`, `self_only`, `gift_only`).
- For `send_as_gift`, required fields are enforced server-side (`receiver_*`, `gift_message_title`, `sender_first_name`, `gift_theme_id`).
- `gift_theme_id` must reference an active row in `gift_card_themes`; inactive/forged values are rejected.
- Invalid denomination/range/quantity combinations are rejected even if client UI is bypassed via DevTools.

### Provider-aware billing requirements (checkout gate)

Billing field requirements are resolved server-side from payment method + provider context.
Do not hardcode assumptions in client apps.

| Context | Required Billing Fields |
|---|---|
| `upi + woohoo` | `billing_name`, `billing_email`, `billing_address`, `billing_city`, `billing_state`, `billing_zip`, `billing_country` |
| `ccavenue` | `billing_name`, `billing_email`, `billing_tel`, `billing_address`, `billing_address_two`, `billing_city`, `billing_state`, `billing_zip`, `billing_country` |
| `wallet/internal` | `billing_name`, `billing_email` |

Implementation source of truth:
- `App\\Support\\BillingRequirementResolver`
- Checkout readiness checks in `ProductPageController` and `UPIPaymentController`

### Checkout summary and payable authority

- Storefront checkout order summary is display-only and intentionally minimal (product + payable).
- Denomination/quantity are product-selection concerns and are not used as authority values during payment initiation.
- Payable amount is always resolved from persisted order records on the server (`amount_payable_after_discount` fallback `grand_payable_amount`).
- Payment initiation endpoints reject unexpected fields and ignore client-side total tampering.

### Storefront product card theme payload

For `/product/{slug}` storefront render payload, card appearance is resolved server-side with this precedence:

1. active row from `brand_card_themes` (product/brand/name match),
2. fallback fields on `products` (`card_logo_url`, `card_bg_color`, `card_text_color`, `card_accent_color`),
3. neutral default palette.

The UI receives:

- `productDetails.card_theme.logo_url`
- `productDetails.card_theme.bg_color`
- `productDetails.card_theme.text_color`
- `productDetails.card_theme.accent_color`
- `productDetails.default_card_value` (initial card value before user changes denomination)

---

## 2. Authentication

### 2.1 Consumer API (Sanctum Token) — Mobile-first OTP (no password)

B2C consumers authenticate like the web storefront: **10-digit Indian mobile → SMS OTP → token**. There is **no** email/password login on this API.

#### Identity table (new)

OTP authentication is backed by `user_identities`:

- A `user_identities` row is created/ensured when OTP is sent/verified.
- A mobile identity can exist **before** a `users` row (OTP pre-registration).
- Once registration completes, the identity is attached to the created user and marked `is_primary = 1`, `verified_at = now()`.

#### Step 1 — Send OTP

```
POST /api/v1/auth/otp/send
Content-Type: application/json

{
  "mobile": "9876543210",
  "type": "login"
}
```

- `mobile` is normalized to 10 digits (`^[6-9]\d{9}$`).
- `type` is optional (default `login`).

Response (success):

```json
{
  "success": true,
  "code": "OK",
  "message_key": "auth.otp.sent",
  "message": "OTP sent successfully.",
  "data": {
    "expires_in": 300,
    "resend_available": 1712505660,
    "identity_id": 123
  },
  "details": {},
  "meta": {}
}
```

#### Step 2 — Verify OTP

```
POST /api/v1/auth/otp/verify
Content-Type: application/json

{
  "mobile": "9876543210",
  "otp": "123456"
}
```

**Existing user** — HTTP 200:

```json
{
  "success": true,
  "code": "OK",
  "message_key": "auth.login.success",
  "message": "Login successful.",
  "data": {
    "action": "logged_in",
    "token": "1|plaintext...",
    "user": {
      "id": 42,
      "name": "John Doe",
      "email": "john@example.com",
      "mobile": "9876543210",
      "two_factor_enabled": false,
      "transaction_pin_set": false,
      "roles": ["b2c-user"]
    },
    "new_device": false,
    "identity_id": 123
  },
  "details": {},
  "meta": {}
}
```

If the user has **2FA enabled**, HTTP **202**:

```json
{
  "success": true,
  "code": "OK",
  "message_key": "auth.two_factor.required",
  "message": "Please complete 2FA verification.",
  "data": {
    "action": "2fa_required",
    "temp_token": "…",
    "method": "totp",
    "new_device": false
  },
  "details": {},
  "meta": {}
}
```

Then:

```
POST /api/v1/auth/2fa/verify
Authorization: Bearer {temp_token}
Content-Type: application/json

{ "code": "123456" }
```

**New mobile** (no account yet) — HTTP 200:

```json
{
  "success": true,
  "code": "OK",
  "message_key": "auth.profile.required",
  "message": "Profile required.",
  "data": {
    "action": "needs_profile",
    "temp_token": "opaque_hex_string",
    "phone": "9876543210",
    "identity_id": 123
  },
  "details": {},
  "meta": {}
}
```

`temp_token` is valid for **15 minutes** (server cache). Use it in step 3.

#### Step 3 — Complete profile (new users only)

```
POST /api/v1/auth/complete-profile
Content-Type: application/json

{
  "temp_token": "opaque_hex_string",
  "name": "Jane Doe",
  "email": "jane@example.com",
  "referral_code": "OPTIONAL"
}
```

- `name` required (letters and spaces only).
- `email` and `referral_code` optional; empty email is stored as `null`.
- User is created with **`password = null`** and role **`b2c-user`**.

Response — HTTP **201**:

```json
{
  "success": true,
  "code": "CREATED",
  "message_key": "auth.profile.completed",
  "message": "Registration successful.",
  "data": {
    "action": "registered",
    "token": "2|plaintext...",
    "user": { "...": "same shape as logged_in" },
    "identity_id": 123
  },
  "details": {},
  "meta": {}
}
```

#### Logout / current user

```
POST /api/v1/auth/logout
Authorization: Bearer {token}

GET /api/v1/auth/me
Authorization: Bearer {token}
```

**Authenticated requests:**

```
Authorization: Bearer 1|abc123...
```

### 2.2 Reseller API (API Key + HMAC)

Every request must include:

| Header | Description |
|--------|-------------|
| `X-Api-Key` | Public API key from `api_keys.key` |
| `X-Timestamp` | Unix timestamp (must be within 5 min of server time) |
| `X-Signature` | HMAC-SHA256 signature |

**Signature computation:**

```
payload = request_body + X-Timestamp
signature = HMAC-SHA256(payload, api_secret)
```

Example:
```
X-Api-Key: ak_live_abc123def456
X-Timestamp: 1712505600
X-Signature: e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855
```

### 2.3 Loyalty API (OAuth2 Client Credentials)

```
POST /oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=client_credentials
&client_id=your_client_id
&client_secret=your_client_secret
&scope=loyalty:read loyalty:redeem
```

Response:
```json
{
  "access_token": "eyJ...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

---

## 3. Common Response Format

Most v1 endpoints return a unified envelope produced by `App\Support\Http\ResponseFormatter`:

```json
{
  "success": true,
  "code": "OK",
  "message_key": "response.ok",
  "message": "OK",
  "data": {},
  "details": {},
  "meta": {}
}
```

Notes:

- `data` is always an object. If the controller returns a list, it is normalized to `{ "items": [ ... ] }`.
- `details` is present on every response; on success it is always `{}`.
- Not every endpoint uses this envelope yet. Notably, `/homepage` currently returns `{ success, message, data }` (no `code/message_key/details/meta`).

---

## 4. Error Codes

The canonical list of API response codes is defined in `App\Enums\ResponseCode`.

| Code | Typical HTTP Status | Description |
|------|----------------------|-------------|
| `OK` | 200 | Success |
| `CREATED` | 201 | Resource created |
| `VALIDATION_FAILED` | 422 | Request validation failed |
| `UNAUTHENTICATED` | 401 | Missing/invalid auth |
| `FORBIDDEN` | 403 | Insufficient permissions |
| `NOT_FOUND` | 404 | Resource not found |
| `RATE_LIMITED` / `TOO_MANY_REQUESTS` | 429 | Too many requests |
| `INVALID_OTP` / `OTP_EXPIRED` / `OTP_REPLAYED` | 422 | OTP problems |
| `INSUFFICIENT_BALANCE` | 422 | Wallet balance too low |
| `INVALID_DENOMINATION` | 422 | Invalid denomination |
| `PURCHASE_LIMIT_EXCEEDED` | 422 | Purchase limits exceeded |
| `PRODUCT_UNAVAILABLE` | 422 | Product unavailable |
| `DUPLICATE_ORDER` | 422 | Duplicate request / idempotency protection |
| `KYC_REQUIRED` | 422 | KYC gate failed |
| `VOUCHER_FULFILLMENT_FAILED` | 502 | Provider fulfillment failed |
| `PROVIDER_TIMEOUT` / `PROVIDER_UNAVAILABLE` | 502 | Provider issues |
| `PAYMENT_FAILED` / `PAYMENT_GATEWAY_ERROR` | 502 | Payment issues |
| `INTERNAL_ERROR` / `UNKNOWN_ERROR` | 500 | Server error |

---

## 5. Rate Limiting

| API | Limit | Window | Key |
|-----|-------|--------|-----|
| Consumer | 60 requests | Per minute | Per user token |
| Reseller | 120 requests | Per minute | Per API key |
| Loyalty | 30 requests | Per minute | Per client |

Rate limit headers on every response:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1712506000
```

---

## 6. Consumer API (Mobile App)

### 6.1 Homepage (public)

```
GET /api/v1/homepage?platform=mobile&surface=storefront_home
```

Notes:

- `platform` defaults to `mobile`
- `surface` defaults to `storefront_home`
- Response is currently **not** the standard envelope (see §3).

### 6.2 Catalog (public)

**List products**

```
GET /api/v1/catalog?search=amazon&category_id=5&brand_id=3&source_provider=woohoo&page=1&per_page=20
```

**Product detail**

```
GET /api/v1/catalog/{product}
```

**Categories**

```
GET /api/v1/catalog/categories
```

### 6.3 Pricing (public; optionally authenticated)

```
GET /api/v1/products/{sku}/pricing?quantity=1&denomination=500&offer_code=FIRST15
```

### 6.4 Customers (authenticated)

Self-lookup only:

```
GET /api/v1/customers/{customer}
Authorization: Bearer {token}
```

### 6.5 Checkout + Payments (authenticated)

**Create checkout session** (draft order, idempotent)

```
POST /api/v1/checkout/sessions
Authorization: Bearer {token}
Idempotency-Key: <client-generated-unique-key>
Content-Type: application/json
```

**Create payment session** (idempotent)

```
POST /api/v1/payments/sessions
Authorization: Bearer {token}
Idempotency-Key: <client-generated-unique-key>
Content-Type: application/json
```

**Fetch payment session**

```
GET /api/v1/payments/sessions/{payment}
Authorization: Bearer {token}
```

**Verify/query payment status** (idempotent)

```
POST /api/v1/payments/sessions/{payment}/verify
Authorization: Bearer {token}
Idempotency-Key: <client-generated-unique-key>
Content-Type: application/json
```

### 6.6 Orders (authenticated)

```
GET /api/v1/orders
Authorization: Bearer {token}

GET /api/v1/orders/{order}
Authorization: Bearer {token}
```

**Voucher code (completed orders only)** (protected by step-up + transaction PIN middleware)

```
GET /api/v1/orders/{order}/voucher-code
Authorization: Bearer {token}
```

### 6.7 Wallet (authenticated)

```
GET /api/v1/wallet/balance
Authorization: Bearer {token}

GET /api/v1/wallet/transactions
Authorization: Bearer {token}

POST /api/v1/wallet/load-request
Authorization: Bearer {token}
Idempotency-Key: <client-generated-unique-key>
Content-Type: application/json

GET /api/v1/wallet/load-request/{loadRequest}
Authorization: Bearer {token}
```

### 6.8 Transaction PIN (authenticated)

```
POST /api/v1/transaction-pin/set
POST /api/v1/transaction-pin/change
POST /api/v1/transaction-pin/verify
Authorization: Bearer {token}
Content-Type: application/json
```

---

## 7. Reseller API

### 7.1 Catalog

**List Available Products**

```
GET /api/v1/reseller/catalog?page=1&per_page=50
X-Api-Key: ak_live_xxxxx
X-Timestamp: 1712505600
X-Signature: xxxxx
```

Products returned are scoped to the reseller's tenant (only assigned products visible).

### 7.2 Orders

**Place Order**

```
POST /api/v1/reseller/orders
X-Api-Key: ak_live_xxxxx
X-Timestamp: 1712505600
X-Signature: xxxxx
Content-Type: application/json

{
  "client_order_id": "MY-ORD-001",
  "items": [
    {
      "sku": "AMZ-GC-001",
      "denomination": 500,
      "quantity": 10,
      "receiver_email": "bulk@company.com"
    }
  ],
  "payment_method": "wallet",
  "webhook_url": "https://reseller.com/webhooks/amazepays"
}
```

Response:
```json
{
  "success": true,
  "data": {
    "order_id": "ORD-2026-00043",
    "client_order_id": "MY-ORD-001",
    "status": "PROCESSING",
    "items": [
      {
        "sku": "AMZ-GC-001",
        "quantity": 10,
        "unit_price": 487.50,
        "total": 4875.00
      }
    ],
    "total_amount": 4875.00,
    "wallet_debited": 4875.00,
    "remaining_balance": 95125.00
  }
}
```

**Bulk Order**

```
POST /api/v1/reseller/orders/bulk
```

Accepts up to 100 items per request.

**Order Status**

```
GET /api/v1/reseller/orders/{order_id}
```

**Order Cards** (completed orders)

```
GET /api/v1/reseller/orders/{order_id}/cards
```

Returns voucher codes for fulfilled orders.

### 7.3 Balance

```
GET /api/v1/reseller/balance
```

### 7.4 Idempotency

Resellers should include `Idempotency-Key` header on order creation:

```
Idempotency-Key: MY-ORD-001-attempt-1
```

Duplicate keys within 24 hours return the original response without re-processing.

---

## 8. Loyalty API

### 8.1 Eligible Products

```
GET /api/v1/loyalty/catalog?points_min=100&points_max=5000
Authorization: Bearer {access_token}
```

### 8.2 Redeem Points

```
POST /api/v1/loyalty/redeem
Authorization: Bearer {access_token}
Content-Type: application/json

{
  "member_id": "LOYALTY-12345",
  "product_sku": "AMZ-GC-001",
  "denomination": 500,
  "points_to_redeem": 5000,
  "receiver_email": "member@example.com"
}
```

### 8.3 Balance

```
GET /api/v1/loyalty/balance?member_id=LOYALTY-12345
Authorization: Bearer {access_token}
```

---

## 9. Webhook Callbacks

### Reseller Order Status Webhook

When an order status changes, AmazePays sends a POST to the reseller's `webhook_url`:

```
POST https://reseller.com/webhooks/amazepays
Content-Type: application/json
X-Webhook-Signature: HMAC-SHA256 of body with api_secret

{
  "event": "order.status_changed",
  "data": {
    "order_id": "ORD-2026-00043",
    "client_order_id": "MY-ORD-001",
    "status": "COMPLETE",
    "items": [
      {
        "sku": "AMZ-GC-001",
        "cards": [
          { "card_number": "xxxx-xxxx-xxxx-1234", "pin": "****", "expiry": "2027-04-07" }
        ]
      }
    ],
    "timestamp": "2026-04-07T12:30:00Z"
  }
}
```

### Webhook Retry Policy

| Attempt | Delay |
|---------|-------|
| 1 | Immediate |
| 2 | 5 minutes |
| 3 | 30 minutes |
| 4 | 2 hours |
| 5 | 12 hours |

After 5 failures, webhook is marked as failed and admin is notified.

---

## 10. SDK Guidelines

### React Native (Mobile App)

```typescript
// api/client.ts
import axios from 'axios';
import { getToken } from './auth';

const api = axios.create({
  baseURL: 'https://api.amazepays.com/api/v1',
  headers: { 'Accept': 'application/json' },
});

api.interceptors.request.use(async (config) => {
  const token = await getToken();
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

api.interceptors.response.use(
  (response) => response.data,
  (error) => {
    if (error.response?.status === 401) {
      // Token expired, redirect to login
    }
    return Promise.reject(error.response?.data?.error || error);
  }
);

export default api;
```

### Reseller (PHP Example)

```php
function makeRequest(string $method, string $url, array $body = []): array
{
    $timestamp = time();
    $bodyJson = json_encode($body);
    $signature = hash_hmac('sha256', $bodyJson . $timestamp, $apiSecret);

    $response = Http::withHeaders([
        'X-Api-Key' => $apiKey,
        'X-Timestamp' => $timestamp,
        'X-Signature' => $signature,
        'Accept' => 'application/json',
    ])->$method($baseUrl . $url, $body);

    return $response->json();
}
```

---

## Related Documents

- [CODE_STANDARDS.md](CODE_STANDARDS.md) -- Validation, webhooks, logging for API controllers
- [SECURITY.md](SECURITY.md) -- API authentication security details
- [MOBILE_APP.md](MOBILE_APP.md) -- Mobile app API consumption
- [B2B_TENANCY.md](B2B_TENANCY.md) -- Tenant scoping for API responses
