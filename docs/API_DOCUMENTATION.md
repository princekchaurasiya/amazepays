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
  "message": "OTP sent successfully.",
  "expires_in": 300,
  "resend_available": 1712505660
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
  "new_device": false
}
```

If the user has **2FA enabled**, HTTP **202**:

```json
{
  "action": "2fa_required",
  "message": "Please complete 2FA verification.",
  "temp_token": "…",
  "method": "totp",
  "new_device": false
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
  "action": "needs_profile",
  "temp_token": "opaque_hex_string",
  "phone": "9876543210"
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
  "message": "Registration successful.",
  "action": "registered",
  "token": "2|plaintext...",
  "user": { "...": "same shape as logged_in" }
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

### Success

```json
{
  "success": true,
  "data": { },
  "meta": {
    "request_id": "req_abc123",
    "timestamp": "2026-04-07T12:00:00Z"
  }
}
```

### Success with Pagination

```json
{
  "success": true,
  "data": [ ],
  "meta": {
    "request_id": "req_abc123",
    "timestamp": "2026-04-07T12:00:00Z"
  },
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 250,
    "last_page": 13,
    "has_more": true
  }
}
```

### Error

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The amount field is required.",
    "details": {
      "amount": ["The amount field is required."]
    }
  },
  "meta": {
    "request_id": "req_abc123",
    "timestamp": "2026-04-07T12:00:00Z"
  }
}
```

---

## 4. Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `VALIDATION_ERROR` | 422 | Request validation failed |
| `UNAUTHORIZED` | 401 | Invalid or missing authentication |
| `FORBIDDEN` | 403 | Insufficient permissions |
| `NOT_FOUND` | 404 | Resource not found |
| `RATE_LIMITED` | 429 | Too many requests |
| `INSUFFICIENT_BALANCE` | 400 | Wallet balance too low |
| `ORDER_FAILED` | 400 | Order could not be placed |
| `PAYMENT_FAILED` | 400 | Payment processing failed |
| `PRODUCT_OUT_OF_STOCK` | 400 | Product unavailable |
| `INVALID_PROMO_CODE` | 400 | Promo code invalid or expired |
| `DUPLICATE_ORDER` | 409 | Order already exists (idempotency) |
| `PROVIDER_ERROR` | 502 | Upstream voucher provider error |
| `SERVER_ERROR` | 500 | Internal server error |

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

### 6.1 Products

**List Products**

```
GET /api/v1/products?category_id=5&brand_id=3&search=amazon&page=1&per_page=20
```

Response:
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "name": "Amazon Gift Card",
      "sku": "AMZ-GC-001",
      "slug": "amazon-gift-card",
      "brand": { "id": 3, "name": "Amazon", "logo": "..." },
      "category": { "id": 5, "name": "Shopping" },
      "images": ["https://..."],
      "price_type": "RANGE",
      "min_price": 100.00,
      "max_price": 10000.00,
      "denominations": null,
      "currency": "INR",
      "discount_percentage": 2.50,
      "in_stock": true,
      "how_to_redeem": "...",
      "terms_and_conditions": "..."
    }
  ],
  "pagination": { "current_page": 1, "total": 85, "per_page": 20, "last_page": 5 }
}
```

**Product Detail**

```
GET /api/v1/products/{id}
```

### 6.2 Categories

```
GET /api/v1/categories
GET /api/v1/categories/{id}/products
```

### 6.3 Brands

```
GET /api/v1/brands
GET /api/v1/brands/{id}/products
```

### 6.4 Orders

**Create Order** (requires auth)

```
POST /api/v1/orders
Authorization: Bearer {token}
Content-Type: application/json

{
  "sku": "AMZ-GC-001",
  "denomination": 500,
  "quantity": 2,
  "receiver": {
    "name": "Jane Doe",
    "email": "jane@example.com",
    "mobile": "+919876543210",
    "message": "Happy Birthday!"
  },
  "payment_method": "wallet",
  "promo_code": "FIRST15"
}
```

Response:
```json
{
  "success": true,
  "data": {
    "order_id": "ORD-2026-00042",
    "merchant_order_id": "f47ac10b-58cc-4372-a567-0e02b2c3d479",
    "status": "PENDING_PAYMENT",
    "amount": 1000.00,
    "discount": 150.00,
    "payable_amount": 850.00,
    "payment_method": "wallet",
    "payment_url": null
  }
}
```

For gateway payments, `payment_url` or `checkout_token` is returned instead.

**Order History**

```
GET /api/v1/orders?status=COMPLETE&page=1
Authorization: Bearer {token}
```

**Order Detail**

```
GET /api/v1/orders/{merchant_order_id}
Authorization: Bearer {token}
```

Response includes voucher details (card number, pin) for completed orders.

### 6.5 Wallet

**Get Balance**

```
GET /api/v1/wallet/balance
Authorization: Bearer {token}
```

Response:
```json
{
  "success": true,
  "data": {
    "balance": 5250.00,
    "currency": "INR"
  }
}
```

**Transaction History**

```
GET /api/v1/wallet/transactions?type=credit&page=1
Authorization: Bearer {token}
```

**Request Wallet Load** (B2B clients)

```
POST /api/v1/wallet/load-request
Authorization: Bearer {token}
Content-Type: application/json

{
  "amount": 50000.00,
  "bank_reference": "UTR123456789",
  "bank_name": "HDFC Bank",
  "notes": "Wire transfer on April 7"
}
```

### 6.6 Offers

**List Active Offers**

```
GET /api/v1/offers
```

**Apply Promo Code**

```
POST /api/v1/offers/apply
Authorization: Bearer {token}
Content-Type: application/json

{
  "promo_code": "FIRST15",
  "order_amount": 1000.00,
  "product_id": 123
}
```

Response:
```json
{
  "success": true,
  "data": {
    "offer_id": 7,
    "discount_type": "percentage_discount",
    "discount_value": 15,
    "discount_amount": 150.00,
    "final_amount": 850.00
  }
}
```

### 6.7 Profile

```
GET /api/v1/profile
PUT /api/v1/profile
POST /api/v1/profile/update-email
```

### 6.8 Home (hero carousel)

```
GET /api/v1/home
```

Public. Returns the standard success envelope with **`data.slides`**: an array of homepage hero slides (same source as the web storefront carousel). Active slides are those with `status = 1` and `display_on_page = homepage` (see `SlidePresentationService`).

Each slide object includes (among others):

| Field | Description |
|-------|-------------|
| `id` | Slide id |
| `desktop_image` | Absolute URL for wide / desktop hero |
| `image_mobile` | Absolute URL for mobile hero |
| `slug` | Resolved target slug when linked to product/category/brand |
| `product_id`, `category_id`, `brand_id` | Optional deep-link targets |
| `is_linked` | Whether the slide should be tappable |
| `img_alt_tag` | Accessibility |
| `cta_link`, `custom_url`, `link_type` | Optional URLs / metadata |

**Admin:** Manage slides in the panel at **Settings → Hero carousel** (`/panel/settings/hero-slides`). Legacy URL `/panel/slides` redirects there.

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
