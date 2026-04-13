# AmazePays System Design

> **Version:** 2.0  
> **Last Updated:** April 2026  
> **Status:** Planning / Pre-Implementation

---

## Table of Contents

1. [System Context](#1-system-context)
2. [Component Design](#2-component-design)
3. [Data Flow Diagrams](#3-data-flow-diagrams)
4. [Order Lifecycle](#4-order-lifecycle)
5. [Payment Processing Flow](#5-payment-processing-flow)
6. [Voucher Fulfillment Flow](#6-voucher-fulfillment-flow)
7. [Wallet Transaction Flow](#7-wallet-transaction-flow)
8. [B2B Tenant Isolation](#8-b2b-tenant-isolation)
9. [Offer/Promotion Engine](#9-offerpromotion-engine)
10. [API Design for External Consumers](#10-api-design-for-external-consumers)
11. [Notification System](#11-notification-system)
12. [Catalog Sync Pipeline](#12-catalog-sync-pipeline)
13. [Failure Handling and Retry Strategy](#13-failure-handling-and-retry-strategy)
14. [Scalability Considerations](#14-scalability-considerations)

---

## 1. System Context

### Actors

| Actor | Description | Interface |
|-------|-------------|-----------|
| Super Admin | Platform owner, full access | Admin Panel (Inertia + React) |
| Admin | Platform operator | Admin Panel |
| Finance | Reconciliation, settlements | Admin Panel (restricted) |
| B2B Client | Business customer (distributor, reseller) | B2B Portal + API |
| B2B Operator | Sub-user of B2B client | B2B Portal |
| B2C Consumer | End consumer buying vouchers | Storefront + Mobile App |
| Reseller (API) | Third-party system consuming API | REST API v1 |
| Loyalty Program | External loyalty system | REST API v1 |

### External Systems

```
┌─────────────────────────────────────────────────────────┐
│                    AmazePays Platform                    │
│                                                         │
│  Receives from:              Sends to:                  │
│  ─────────────               ─────────                  │
│  Woohoo (catalog, orders)    Email (SMTP)               │
│  EZ Pin (catalog, orders)    SMS (provider TBD)         │
│  Gyftrr (catalog, orders)    Push (FCM)                 │
│  KGen (catalog, orders)      Bank APIs (verification)   │
│  Value Design (catalog)                                 │
│  Lysto/Athena (catalog)                                 │
│                                                         │
│  CCAvenue (payments)                                    │
│  Razorpay (payments)                                    │
│  Unlimit (payments)                                     │
│  IPHub (VPN detection)                                  │
└─────────────────────────────────────────────────────────┘
```

---

## 2. Component Design

### 2.1 Authentication & Authorization Component

```
┌─────────────────────────────────────────────────┐
│              Auth Component                      │
│                                                  │
│  ┌──────────────┐    ┌────────────────────────┐ │
│  │ Sanctum      │    │ Spatie Permission       │ │
│  │ (Token Auth) │    │ (Roles + Permissions)   │ │
│  │              │    │                          │ │
│  │ - Web: Cookie│    │ Roles:                   │ │
│  │ - API: Token │    │  super-admin             │ │
│  │ - HMAC: Key  │    │  admin                   │ │
│  └──────────────┘    │  finance                 │ │
│                      │  b2b-manager             │ │
│  ┌──────────────┐    │  b2b-client              │ │
│  │ 2FA (TOTP)   │    │  b2b-operator            │ │
│  │ Google Auth   │    │  b2c-user                │ │
│  │ Required for: │    │  reseller                │ │
│  │ - Admins     │    │                          │ │
│  │ - B2B owners │    │ Permissions:              │ │
│  └──────────────┘    │  products.view/create/    │ │
│                      │    edit/delete             │ │
│  ┌──────────────┐    │  orders.view/create/      │ │
│  │ IP Whitelist  │    │    cancel/export          │ │
│  │ Per-tenant    │    │  wallets.view/credit/     │ │
│  │ configurable  │    │    debit/approve          │ │
│  └──────────────┘    │  tenants.manage            │ │
│                      │  reports.view/export       │ │
│                      │  settings.manage           │ │
│                      │  api-keys.manage           │ │
│                      └────────────────────────┘ │
└─────────────────────────────────────────────────┘
```

### 2.2 Payment Gateway Component

```
┌──────────────────────────────────────────────────────────────┐
│                  Payment Gateway Component                    │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐  │
│  │              PaymentGatewayInterface                    │  │
│  │                                                        │  │
│  │  + initiate(PaymentRequest): PaymentResponse           │  │
│  │  + verify(transactionId): PaymentStatus                │  │
│  │  + refund(transactionId, amount): RefundResponse       │  │
│  │  + handleWebhook(Request): WebhookResult               │  │
│  │  + getRequiredCredentials(): array                      │  │
│  └────────────────────────────────────────────────────────┘  │
│         ▲                ▲                 ▲                  │
│         │                │                 │                  │
│  ┌──────┴──────┐  ┌─────┴──────┐  ┌──────┴───────┐         │
│  │  CCAvenue   │  │  Razorpay  │  │   Unlimit    │         │
│  │  Gateway    │  │  Gateway   │  │   Gateway    │         │
│  │             │  │            │  │              │         │
│  │ Redirect    │  │ Checkout   │  │ API-based    │         │
│  │ based flow  │  │ JS + API   │  │ + Webhooks   │         │
│  └─────────────┘  └────────────┘  └──────────────┘         │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐  │
│  │              PaymentGatewayFactory                      │  │
│  │                                                        │  │
│  │  resolve(tenantId, gateway): PaymentGatewayInterface   │  │
│  │                                                        │  │
│  │  1. Load tenant_payment_gateways record                │  │
│  │  2. Decrypt credentials from DB                        │  │
│  │  3. Instantiate correct gateway class                  │  │
│  │  4. Inject decrypted credentials                       │  │
│  │  5. Return ready-to-use gateway instance               │  │
│  └────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────┘
```

### 2.3 Voucher Provider Component

```
┌──────────────────────────────────────────────────────────────┐
│                 Voucher Provider Component                     │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐  │
│  │             VoucherProviderInterface                    │  │
│  │                                                        │  │
│  │  + fetchCatalog(filters): Collection                   │  │
│  │  + placeOrder(OrderRequest): OrderResponse             │  │
│  │  + checkOrderStatus(orderId): OrderStatus              │  │
│  │  + getVoucherDetails(voucherId): VoucherDetails        │  │
│  │  + getWalletBalance(): WalletBalance                   │  │
│  │  + cancelOrder(orderId): CancelResponse                │  │
│  └────────────────────────────────────────────────────────┘  │
│       ▲         ▲         ▲         ▲          ▲             │
│       │         │         │         │          │             │
│  ┌────┴───┐┌────┴───┐┌───┴────┐┌───┴────┐┌────┴──────┐     │
│  │ Woohoo ││ EZ Pin ││ Gyftrr ││  KGen  ││Value     │     │
│  │Provider││Provider││Provider││Provider││Design   │     │
│  └────────┘└────────┘└────────┘└────────┘└──────────┘     │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐  │
│  │            VoucherProviderFactory                       │  │
│  │                                                        │  │
│  │  resolve(provider, tenantId): VoucherProviderInterface │  │
│  │  resolveAll(tenantId): Collection<VoucherProvider>     │  │
│  │  resolveByPriority(tenantId): ordered providers        │  │
│  └────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────┘
```

### 2.4 Tenant Isolation Component

```
┌──────────────────────────────────────────────────────────────┐
│                  Tenant Isolation Component                    │
│                                                              │
│  ┌────────────────┐    ┌──────────────────────────────────┐ │
│  │ TenantScope    │    │ TenantMiddleware                  │ │
│  │ (Global Scope) │    │                                  │ │
│  │                │    │ 1. Resolve tenant from auth user  │ │
│  │ Auto-applied   │    │ 2. Set tenant in container        │ │
│  │ to all tenant- │    │ 3. Apply TenantScope              │ │
│  │ aware models   │    │ 4. Super Admin: bypass scope      │ │
│  └────────────────┘    └──────────────────────────────────┘ │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐  │
│  │ Tenant-Aware Models (have tenant_id column):           │  │
│  │                                                        │  │
│  │  Order, WalletLoadRequest, Payment,                  │  │
│  │  TenantProduct, TenantPaymentGateway,                  │  │
│  │  TenantVoucherProvider, ApiKey, AuditLog               │  │
│  └────────────────────────────────────────────────────────┘  │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐  │
│  │ Global Models (no tenant_id, shared across platform):  │  │
│  │                                                        │  │
│  │  User, Product, ProductCategory, Brand, Category,       │  │
│  │  HomepageSection, Role, Permission                     │  │
│  └────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────┘
```

---

## 3. Data Flow Diagrams

### 3.1 B2C Purchase Flow (Consumer buys a voucher)

```
Consumer          Storefront        OrderService      PaymentGateway     VoucherProvider
   │                  │                  │                  │                  │
   │  Browse catalog  │                  │                  │                  │
   │─────────────────>│                  │                  │                  │
   │  Select product  │                  │                  │                  │
   │─────────────────>│                  │                  │                  │
   │                  │  Create order    │                  │                  │
   │                  │─────────────────>│                  │                  │
   │                  │                  │ Validate stock   │                  │
   │                  │                  │ Apply offers     │                  │
   │                  │                  │ Calculate total  │                  │
   │                  │  Order created   │                  │                  │
   │                  │<─────────────────│                  │                  │
   │  Checkout page   │                  │                  │                  │
   │<─────────────────│                  │                  │                  │
   │                  │                  │                  │                  │
   │  Pay (gateway)   │                  │                  │                  │
   │─────────────────>│                  │                  │                  │
   │                  │  Initiate payment│                  │                  │
   │                  │─────────────────────────────────────>│                  │
   │                  │                  │  Redirect / JS   │                  │
   │<────────────────────────────────────────────────────────│                  │
   │  Complete payment│                  │                  │                  │
   │─────────────────────────────────────────────────────────>│                  │
   │                  │                  │  Webhook/Return  │                  │
   │                  │                  │<─────────────────│                  │
   │                  │                  │ Verify payment   │                  │
   │                  │                  │─────────────────>│                  │
   │                  │                  │ Payment confirmed│                  │
   │                  │                  │<─────────────────│                  │
   │                  │                  │                  │                  │
   │                  │                  │  Place voucher order               │
   │                  │                  │─────────────────────────────────────>│
   │                  │                  │  Voucher codes returned            │
   │                  │                  │<─────────────────────────────────────│
   │                  │                  │                  │                  │
   │  Voucher details │                  │                  │                  │
   │<─────────────────│                  │                  │                  │
   │  (email + app)   │                  │                  │                  │
```

### 3.2 B2B Bulk Order Flow

```
B2B Client       B2B Portal       TenantScope       OrderService      WalletService
   │                 │                 │                  │                 │
   │  Place bulk     │                 │                  │                 │
   │  order          │                 │                  │                 │
   │────────────────>│                 │                  │                 │
   │                 │  Validate       │                  │                 │
   │                 │  tenant access  │                  │                 │
   │                 │────────────────>│                  │                 │
   │                 │  Scoped products│                  │                 │
   │                 │<────────────────│                  │                 │
   │                 │                 │                  │                 │
   │                 │  Create order (wallet payment)     │                 │
   │                 │───────────────────────────────────>│                 │
   │                 │                 │                  │  Check balance  │
   │                 │                 │                  │────────────────>│
   │                 │                 │                  │  Balance OK     │
   │                 │                 │                  │<────────────────│
   │                 │                 │                  │  Debit wallet   │
   │                 │                 │                  │────────────────>│
   │                 │                 │                  │  Debited        │
   │                 │                 │                  │<────────────────│
   │                 │                 │                  │                 │
   │                 │                 │  Dispatch to queue (async)        │
   │                 │                 │  PlaceVoucherOrderJob             │
   │                 │                 │                  │                 │
   │  Order accepted │                 │                  │                 │
   │<────────────────│                 │                  │                 │
   │                 │                 │                  │                 │
   │  (Async) Voucher codes delivered via webhook/email   │                 │
```

### 3.3 Wallet Bank Load Flow

```
B2B Client       B2B Portal       WalletService      Admin Panel       BankVerification
   │                 │                  │                 │                  │
   │  Request load   │                  │                 │                  │
   │  (amount + UTR) │                  │                 │                  │
   │────────────────>│                  │                 │                  │
   │                 │  Create request  │                 │                  │
   │                 │─────────────────>│                 │                  │
   │                 │                  │ Save pending     │                  │
   │                 │                  │ wallet_load_req  │                  │
   │                 │  Request created │                 │                  │
   │                 │<─────────────────│                 │                  │
   │  Pending status │                  │                 │                  │
   │<────────────────│                  │                 │                  │
   │                 │                  │                 │                  │
   │                 │                  │  Notify admin   │                  │
   │                 │                  │────────────────>│                  │
   │                 │                  │                 │  Review request  │
   │                 │                  │                 │  Verify UTR      │
   │                 │                  │                 │─────────────────>│
   │                 │                  │                 │  UTR Confirmed   │
   │                 │                  │                 │<─────────────────│
   │                 │                  │                 │                  │
   │                 │                  │  Approve load   │                  │
   │                 │                  │<────────────────│                  │
   │                 │                  │ Credit wallet   │                  │
   │                 │                  │ Log transaction │                  │
   │                 │                  │ Log audit trail │                  │
   │  Wallet credited│                  │                 │                  │
   │<────────────────────────────────────                 │                  │
   │  (email + push) │                  │                 │                  │
```

---

## 4. Order Lifecycle

### State Machine

```
                    ┌──────────┐
                    │ CREATED  │
                    └────┬─────┘
                         │
                    ┌────▼─────┐
               ┌────│ PENDING  │────┐
               │    │ PAYMENT  │    │
               │    └────┬─────┘    │
               │         │         │
          (timeout)  (success)  (failed)
               │         │         │
               ▼         ▼         ▼
        ┌──────────┐ ┌────────┐ ┌──────────┐
        │ EXPIRED  │ │ PAID   │ │ PAYMENT  │
        └──────────┘ └───┬────┘ │ FAILED   │
                         │      └──────────┘
                    ┌────▼─────┐
               ┌────│PROCESSING│────┐
               │    └────┬─────┘    │
               │         │         │
         (provider   (success)  (provider
          error)         │      out of stock)
               │         │         │
               ▼         ▼         ▼
        ┌──────────┐ ┌────────┐ ┌──────────┐
        │ PROVIDER │ │COMPLETE│ │OUT OF    │
        │ ERROR    │ └────────┘ │STOCK     │
        └────┬─────┘            └────┬─────┘
             │                       │
             └───────┐   ┌───────────┘
                     ▼   ▼
                ┌──────────┐
                │ REFUNDED │
                └──────────┘
```

### Order Events

| Event | Trigger | Side Effects |
|-------|---------|-------------|
| `OrderCreated` | User submits order | Reserve stock, start payment timer |
| `PaymentReceived` | Gateway callback confirms | Mark paid, dispatch fulfillment job |
| `PaymentFailed` | Gateway callback/timeout | Release stock, notify user |
| `OrderFulfilled` | Provider returns voucher codes | Store codes, notify user, mark complete |
| `OrderFailed` | Provider error or out-of-stock | Initiate refund, notify user and admin |
| `OrderRefunded` | Refund processed | Credit wallet or reverse gateway payment |

---

## 5. Payment Processing Flow

### Gateway Selection Logic

```
1. User reaches checkout
2. System determines available gateways:
   a. If B2B client → check tenant_payment_gateways (tenant-specific)
   b. If B2C consumer → use platform default gateways
   c. If wallet payment → debit directly (no gateway)
3. User selects payment method
4. PaymentGatewayFactory resolves the correct gateway
5. Gateway.initiate() creates the payment session
6. User completes payment on gateway side
7. Webhook/return URL triggers verification (handlers pass **whitelisted** request keys to the payment service—no full `$request->all()`; see [PAYMENT_GATEWAYS.md](PAYMENT_GATEWAYS.md#payload-whitelisting-and-application-logs))
8. Payment marked as success/failure
```

### Idempotency

- Every payment initiation generates a unique `merchant_order_id` (UUID)
- Webhooks are idempotent: duplicate callbacks for same `merchant_order_id` are ignored
- Payment status transitions are one-way (cannot go from SUCCESS back to PENDING)

---

## 6. Voucher Fulfillment Flow

### Provider Selection

```
1. Order is paid → dispatch PlaceVoucherOrderJob
2. VoucherProviderFactory resolves provider based on product's source
3. Provider.placeOrder() sends request to external API
4. If success → store voucher codes (encrypted), mark COMPLETE
5. If failure → retry up to 3 times with exponential backoff
6. If all retries fail → mark PROVIDER_ERROR, alert admin
7. Async: StatusCheckJob polls provider for order status (Woohoo pattern)
```

### Voucher Code Storage

Voucher codes (card numbers, PINs) are stored encrypted:

```php
// Order model
protected $casts = [
    'cards' => 'encrypted:array',
];
```

Decrypted only when:
- User views their order (authenticated, owns the order)
- Admin views order details (authorized via policy)
- API response to reseller (authenticated, HMAC-verified)

---

## 7. Wallet Transaction Flow

### Transaction Types

| Type | Source | Description |
|------|--------|-------------|
| `credit:bank-load` | Admin approval | Bank transfer verified and credited |
| `credit:refund` | System | Failed order refunded to wallet |
| `credit:adjustment` | Admin | Manual adjustment with reason |
| `debit:purchase` | User/API | Voucher purchase deducted |
| `debit:adjustment` | Admin | Manual adjustment with reason |

### Concurrency Control

```php
// WalletService uses DB transactions with row-level locking
DB::transaction(function () use ($wallet, $amount) {
    $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();

    if ($wallet->balance < $amount) {
        throw new InsufficientBalanceException();
    }

    $wallet->decrement('balance', $amount);
    // ... create transaction record
});
```

---

## 8. B2B Tenant Isolation

### Data Access Matrix

| Data | Super Admin | Admin | B2B Client | B2C User |
|------|------------|-------|------------|----------|
| All tenants | Read/Write | Read | Own only | N/A |
| All users | Read/Write | Read/Write | Own tenant users | Own profile |
| All products | Read/Write | Read/Write | Assigned products | Published products |
| All orders | Read/Write | Read/Write | Own tenant orders | Own orders |
| All wallets | Read/Write | Read/Write | Own tenant wallets | Own wallet |
| Gateway credentials | Read/Write | Read | Own tenant | N/A |
| Audit logs | Read | Read | Own tenant | N/A |
| System settings | Read/Write | Read | N/A | N/A |

### Tenant Resolution

```
Request arrives
    │
    ▼
Is API request with API key?
    │
    ├── Yes → Resolve tenant from api_keys table
    │
    └── No → Is authenticated user?
              │
              ├── Yes → user.currentTenant (from tenant_users pivot)
              │         │
              │         ├── Has role super-admin? → No tenant scope (see all)
              │         └── Has tenant? → Apply TenantScope
              │
              └── No → Public route (no tenant scope needed)
```

---

## 9. Offer/Promotion Engine

### Offer Types

| Type | Description | Example |
|------|-------------|---------|
| `percentage_discount` | % off on voucher face value | 5% off on Amazon gift cards |
| `flat_discount` | Fixed amount off | Rs 50 off on orders above Rs 500 |
| `cashback_wallet` | Cashback credited to wallet | 10% cashback (max Rs 100) |
| `buy_x_get_y` | Bundle offers | Buy 2 Swiggy cards, get 1 free |
| `first_order` | New user discount | 15% off on first purchase |

### Offer Data Model

```
offers
├── id
├── tenant_id (nullable -- null = platform-wide)
├── name
├── description
├── type (percentage_discount / flat_discount / cashback_wallet / buy_x_get_y / first_order)
├── value (discount amount or percentage)
├── max_discount (cap for percentage discounts)
├── min_order_amount
├── max_uses_total
├── max_uses_per_user
├── current_uses
├── applicable_products (JSON array of product IDs, null = all)
├── applicable_categories (JSON array of category IDs, null = all)
├── applicable_brands (JSON array of brand IDs, null = all)
├── start_date
├── end_date
├── is_active
├── promo_code (nullable, for code-based offers)
├── auto_apply (boolean, for automatic offers)
├── priority (for stacking rules)
├── created_at
└── updated_at
```

### Offer Application Logic

```
1. User adds product to cart
2. OfferService.getApplicableOffers(cart, user)
3. Filter by: date range, product/category/brand match, usage limits, min order
4. Sort by priority
5. Apply best offer (or allow stacking if configured)
6. Show discounted price to user
7. On order creation: validate offer still valid, lock usage count
8. On payment failure: release usage count
```

### Admin CRUD for Offers

The admin panel provides full CRUD for offers:

- **Create:** Form with all offer fields, product/category/brand selectors, date pickers
- **Read:** Searchable/filterable table with status (active/expired/scheduled), usage stats
- **Update:** Edit any field, preview impact on existing orders
- **Delete:** Soft delete with cascade handling

---

## 10. API Design for External Consumers

### Reseller API Authentication

```
Request Flow:
1. Reseller includes API key in header: X-Api-Key: <key>
2. Reseller signs request body with HMAC-SHA256:
   signature = HMAC-SHA256(request_body + timestamp, api_secret)
3. Include in headers:
   X-Signature: <signature>
   X-Timestamp: <unix_timestamp>
4. Server verifies:
   a. API key exists and is active
   b. Timestamp within 5-minute window (prevent replay)
   c. HMAC signature matches
   d. Rate limit not exceeded
```

### API Response Format

```json
{
  "success": true,
  "data": { },
  "meta": {
    "request_id": "uuid",
    "timestamp": "2026-04-07T12:00:00Z"
  },
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8
  }
}
```

### Error Response Format

```json
{
  "success": false,
  "error": {
    "code": "INSUFFICIENT_BALANCE",
    "message": "Wallet balance is insufficient for this order",
    "details": {
      "required": 5000.00,
      "available": 3200.50
    }
  },
  "meta": {
    "request_id": "uuid",
    "timestamp": "2026-04-07T12:00:00Z"
  }
}
```

---

## 11. Notification System

### Channels

| Channel | Use Case | Provider |
|---------|----------|----------|
| Email | Order confirmation, voucher delivery, receipts | SMTP / Mailgun / SES |
| SMS | OTP, order status updates | Twilio / MSG91 (TBD) |
| Push | Order updates, offers, wallet credits | Firebase Cloud Messaging |
| In-App | All notifications stored in DB for in-app feed | Laravel Notifications |
| Webhook | Reseller order status callbacks | Custom HTTP client |

### Notification Events

| Event | Email | SMS | Push | In-App | Webhook |
|-------|-------|-----|------|--------|---------|
| OTP Sent | -- | Yes | -- | -- | -- |
| Order Created | Yes | -- | Yes | Yes | Yes (reseller) |
| Payment Confirmed | Yes | Yes | Yes | Yes | Yes (reseller) |
| Voucher Delivered | Yes | Yes | Yes | Yes | Yes (reseller) |
| Order Failed | Yes | Yes | Yes | Yes | Yes (reseller) |
| Wallet Credited | Yes | -- | Yes | Yes | -- |
| Wallet Load Approved | Yes | Yes | Yes | Yes | -- |
| Wallet Load Rejected | Yes | -- | Yes | Yes | -- |
| New Offer Available | Yes | -- | Yes | Yes | -- |

---

## 12. Catalog Sync Pipeline

### Sync Architecture

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│  Scheduler   │     │  Sync Jobs   │     │  Providers   │
│  (Cron)      │────>│  (Queue)     │────>│  (External)  │
│              │     │              │     │              │
│  Frequency:  │     │  Per-provider│     │  Woohoo      │
│  - Woohoo:   │     │  job that    │     │  EZ Pin      │
│    every 4h  │     │  fetches and │     │  Gyftrr      │
│  - EZ Pin:   │     │  upserts     │     │  KGen        │
│    every 6h  │     │  products    │     │  Value Design│
│  - Others:   │     │              │     │              │
│    daily     │     │              │     │              │
└──────────────┘     └──────┬───────┘     └──────────────┘
                            │
                    ┌───────▼───────┐
                    │  products  │
                    │  (Unified     │
                    │   Catalog)    │
                    │               │
                    │  source_provider
                    │  last_synced_at
                    │  sync_status  │
                    └───────────────┘
```

### Sync Rules

- New products from provider → auto-create as hidden (`show_product = false`)
- Existing products → update price, stock, metadata; preserve local overrides (descriptions, images, visibility)
- Deleted from provider → mark `out_of_stock = true`, do not hard-delete
- Admin manually publishes products after review

---

## 13. Failure Handling and Retry Strategy

### Payment Failures

| Failure Type | Retry | Action |
|-------------|-------|--------|
| Gateway timeout | Yes (2 retries, 5s delay) | Show retry option to user |
| Invalid card | No | Show error, ask user to retry with different card |
| Webhook not received | Yes (poll every 30s, 5 min max) | Auto-verify via gateway API |
| Duplicate webhook | No | Idempotent check, ignore duplicate |

### Voucher Provider Failures

| Failure Type | Retry | Action |
|-------------|-------|--------|
| Provider API timeout | Yes (3 retries, exponential backoff) | Queue retry |
| Out of stock | No | Refund, notify user, notify admin |
| Invalid credentials | No | Alert admin, suspend provider |
| Rate limited | Yes (after cooldown) | Respect Retry-After header |

### Queue Job Failures

- All jobs implement `$tries = 3` and `$backoff = [10, 60, 300]` (seconds)
- Failed jobs logged to `failed_jobs` table
- Admin dashboard shows failed job count with retry/delete actions
- Critical failures (payment, order) trigger admin email alert

---

## 14. Scalability Considerations

### Current Scale (estimated)

- ~1,000 orders/day
- ~5,000 registered users
- ~500 products in catalog
- ~10 B2B clients

### Target Scale

- ~50,000 orders/day
- ~500,000 registered users
- ~10,000 products in catalog
- ~200 B2B clients

### Scaling Strategy

| Concern | Strategy |
|---------|----------|
| Database reads | Redis caching, read replicas |
| Database writes | Queue-based writes, batch inserts for bulk orders |
| API throughput | Per-tenant rate limiting, horizontal PHP-FPM scaling |
| Catalog sync | Parallel provider jobs, delta sync (changed-since) |
| File storage | S3-compatible object storage |
| Session management | Redis sessions (stateless PHP) |
| Search | Meilisearch / Algolia for product search |

---

## 14. Security Middleware Pipeline

Every request passes through a layered security pipeline before reaching application logic:

```
Request
  │
  ▼
┌──────────────────────────────────┐
│  1. SecurityHeaders              │ X-Frame-Options, HSTS, CSP
├──────────────────────────────────┤
│  2. ThreatDetection              │ IP blocklist check, attack payload scan
├──────────────────────────────────┤
│  3. DetectVpnProxy               │ Multi-source VPN/proxy/Tor detection
├──────────────────────────────────┤
│  4. Rate Limiting                │ Per-IP and per-user request throttling
├──────────────────────────────────┤
│  5. Authentication               │ Session / Sanctum / API Key + HMAC
├──────────────────────────────────┤
│  6. VerifyIpWhitelist            │ Per-tenant API IP whitelist
├──────────────────────────────────┤
│  7. ResolveTenant + TenantScope  │ Multi-tenant isolation
├──────────────────────────────────┤
│  8. Authorization (RBAC)         │ Spatie permissions + policies
├──────────────────────────────────┤
│  9. WalletFraudDetector          │ On wallet operations only
├──────────────────────────────────┤
│  10. Controller / Business Logic │
└──────────────────────────────────┘
```

**VPN/Proxy behavior by route group:**

| Route Group | VPN Action | Additional |
|-------------|-----------|------------|
| `api/v1/catalog/*` | Log only | Low-risk browsing |
| `api/v1/orders/*` | Block + log | Purchase fraud prevention |
| `api/v1/wallet/*` | Block + log + alert | Financial fraud prevention |
| `api/v1/voucher-codes/*` | Block + log + alert | Code theft prevention |
| `admin/*` | Flag + require 2FA | Admins may use VPN remotely |
| `api/v1/reseller/*` | Log only | IP whitelisting handles B2B |

See [SECURITY.md](SECURITY.md) for full implementation details.

---

## Related Documents

- [ARCHITECTURE.md](ARCHITECTURE.md) -- High-level architecture and tech stack
- [CODE_STANDARDS.md](CODE_STANDARDS.md) -- Request validation and payment webhook handling
- [DATABASE_DICTIONARY.md](DATABASE_DICTIONARY.md) -- Column-level data dictionary
- [PAYMENT_GATEWAYS.md](PAYMENT_GATEWAYS.md) -- Payment gateway integration details
- [SECURITY.md](SECURITY.md) -- VPN detection, fraud prevention, threat blocking, security event logging
