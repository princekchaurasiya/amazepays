# AmazePays Voucher Providers

> **Version:** 2.0  
> **Last Updated:** April 2026

---

## Table of Contents

1. [Overview](#1-overview)
2. [Provider Abstraction Layer](#2-provider-abstraction-layer)
3. [Woohoo Integration](#3-woohoo-integration)
4. [EZ Pin Integration](#4-ez-pin-integration)
5. [Gyftrr Integration](#5-gyftrr-integration)
6. [KGen Integration](#6-kgen-integration)
7. [Value Design Integration](#7-value-design-integration)
8. [Lysto / Athena Integration](#8-lysto--athena-integration)
9. [Catalog Sync Pipeline](#9-catalog-sync-pipeline)
10. [Provider Credential Management](#10-provider-credential-management)
11. [Adding a New Provider](#11-adding-a-new-provider)

---

## 1. Overview

AmazePays aggregates gift card/voucher inventory from multiple providers into a unified product catalog. Each provider has different APIs, authentication methods, and fulfillment flows.

### Provider Comparison

| Provider | Auth Method | Catalog API | Order API | Status Check | Wallet | Encryption |
|----------|-----------|-------------|-----------|-------------|--------|------------|
| Woohoo | OAuth2 (client credentials) | REST v3 | REST v3 | Polling | Yes | AES for card data |
| EZ Pin | API Key + Secret | REST | REST | Callback | Yes | -- |
| Gyftrr | API Key | REST | REST | Callback | Yes | -- |
| KGen | API Key | REST | REST | Polling | Yes | -- |
| Value Design | API Key + Partner ID | REST | REST | EVC status | Yes | -- |
| Lysto/Athena | Bearer Token + Partner ID | REST v1 | REST v1 | By order ID | Yes | AES-256-GCM |

### Current Integration Status

| Provider | Code Exists | Status | Priority |
|----------|------------|--------|----------|
| Woohoo | Yes (deep) | Active | 1 |
| KGen | Yes | Active | 2 |
| Value Design | Yes | Active | 3 |
| Lysto/Athena | Yes | Active | 4 |
| EZ Pin | No | **Planned** | 5 |
| Gyftrr | No | **Planned** | 6 |

---

## 2. Provider Abstraction Layer

### Interface

```php
// app/Contracts/VoucherProviderInterface.php

namespace App\Contracts;

interface VoucherProviderInterface
{
    public function getIdentifier(): string;

    public function getRequiredCredentials(): array;

    /**
     * Fetch product catalog from provider.
     * Returns normalized product collection.
     */
    public function fetchCatalog(array $filters = []): Collection;

    /**
     * Place an order for a voucher.
     */
    public function placeOrder(VoucherOrderRequest $request): VoucherOrderResponse;

    /**
     * Check order status at provider.
     */
    public function checkOrderStatus(string $providerOrderId): VoucherOrderStatus;

    /**
     * Get voucher/card details for a completed order.
     */
    public function getVoucherDetails(string $providerOrderId): VoucherDetails;

    /**
     * Get provider wallet/credit balance.
     */
    public function getWalletBalance(): WalletBalance;

    /**
     * Cancel an order (if supported).
     */
    public function cancelOrder(string $providerOrderId): CancelResponse;
}
```

### Factory

```php
// app/Services/Voucher/VoucherProviderFactory.php

class VoucherProviderFactory
{
    private array $providers = [
        'woohoo'       => WoohooProvider::class,
        'ezpin'        => EZPinProvider::class,
        'gyftrr'       => GyftrProvider::class,
        'kgen'         => KGenProvider::class,
        'value_design' => ValueDesignProvider::class,
        'lysto'        => LystoProvider::class,
    ];

    public function resolve(string $provider, ?int $tenantId = null): VoucherProviderInterface
    {
        $class = $this->providers[$provider]
            ?? throw new \InvalidArgumentException("Unknown provider: {$provider}");

        $credentials = $this->loadCredentials($provider, $tenantId);
        return new $class($credentials);
    }

    /**
     * Resolve provider for a specific product based on its source_provider field.
     */
    public function resolveForProduct(Product $product, ?int $tenantId = null): VoucherProviderInterface
    {
        return $this->resolve($product->source_provider, $tenantId);
    }
}
```

### Normalized Data Models

```php
// Normalized product from any provider
class NormalizedProduct
{
    public string $providerSku;
    public string $provider;        // woohoo, ezpin, etc.
    public string $name;
    public string $description;
    public string $brandName;
    public string $categoryName;
    public array $images;
    public string $priceType;       // FIXED, RANGE, SLAB
    public ?float $minPrice;
    public ?float $maxPrice;
    public ?array $denominations;   // For SLAB pricing
    public string $currency;
    public bool $inStock;
    public array $rawProviderData;  // Full provider response preserved
}
```

---

## 3. Woohoo Integration

### Existing Implementation

- **Controller:** `app/Http/Controllers/GiftCardOrderController.php` (class: `WoohooOrderController`)
- **Commands:** `FetchCategoryData`, `FetchProductList`, `TestWoohooCatalog`
- **Jobs:** `ProcessWoohooOrder`, `StatusCheckJob`
- **Config:** Settings stored via Voyager settings table

### Authentication

```
1. POST {woohoo_url}/rest/v3/auth/token
   Body: { "clientId": "...", "clientSecret": "...", "grantType": "client_credentials" }
2. Response: { "token": "...", "expiresIn": 3600 }
3. Use token in Authorization header for subsequent calls
```

### API Endpoints

| Operation | Method | Endpoint | Description |
|-----------|--------|----------|-------------|
| Auth | POST | `/rest/v3/auth/token` | Get OAuth token |
| Categories | GET | `/rest/v3/catalog/categories` | List categories |
| Products | GET | `/rest/v3/catalog/products` | List products |
| Product Detail | GET | `/rest/v3/catalog/products/{sku}` | Product details |
| Create Order | POST | `/rest/v3/orders` | Place order |
| Order Status | GET | `/rest/v3/orders/{orderId}` | Check status |
| Order Cards | GET | `/rest/v3/orders/{orderId}/cards` | Get card details |

### Credential Structure

```json
{
  "url": "https://sandbox.woohoo.in",
  "client_id": "xxxxx",
  "client_secret": "xxxxx",
  "auth_key": "xxxxx"
}
```

### Catalog Sync

- **Frequency:** Every 4 hours via scheduled command
- **Process:** Fetch all products → upsert into `products` with `source_provider = 'woohoo'`
- **Local overrides preserved:** `custom_description`, `custom_image`, `show_product`, `priority`

---

## 4. EZ Pin Integration

### Status: **Planned**

### Authentication

- API Key + Secret in request headers
- HMAC signature on request body

### Expected API Endpoints

| Operation | Method | Endpoint | Description |
|-----------|--------|----------|-------------|
| Catalog | GET | `/api/v1/products` | List products |
| Product Detail | GET | `/api/v1/products/{id}` | Product details |
| Create Order | POST | `/api/v1/orders` | Place order |
| Order Status | GET | `/api/v1/orders/{id}` | Check status |
| Balance | GET | `/api/v1/wallet/balance` | Wallet balance |

### Credential Structure

```json
{
  "api_url": "https://api.ezpin.com",
  "api_key": "xxxxx",
  "api_secret": "xxxxx",
  "partner_id": "xxxxx"
}
```

### Implementation Notes

- EZ Pin provides digital gift cards and prepaid products
- API documentation to be obtained from EZ Pin
- Sandbox environment available for testing
- Product catalog includes denominations and availability

### Open Questions

- [ ] API documentation URL and access
- [ ] Sandbox credentials
- [ ] Webhook callback format for order status
- [ ] Catalog sync delta support (changed-since)
- [ ] Rate limiting details

---

## 5. Gyftrr Integration

### Status: **Planned**

### Authentication

- API Key in header

### Expected API Endpoints

| Operation | Method | Endpoint | Description |
|-----------|--------|----------|-------------|
| Catalog | GET | `/api/products` | List products |
| Create Order | POST | `/api/orders` | Place order |
| Order Status | GET | `/api/orders/{id}` | Check status |
| Balance | GET | `/api/balance` | Wallet balance |

### Credential Structure

```json
{
  "api_url": "https://api.gyftrr.com",
  "api_key": "xxxxx",
  "merchant_id": "xxxxx"
}
```

### Open Questions

- [ ] API documentation URL and access
- [ ] Sandbox credentials
- [ ] Product catalog format
- [ ] Order fulfillment flow (sync vs async)
- [ ] Voucher code encryption method

---

## 6. KGen Integration

### Existing Implementation

- **Controller:** `app/Http/Controllers/KGenOrderController.php`
- **Commands:** `FetchKGenWalletBalance`
- **Model:** `KGenOrder`, `KgenProduct`, `KGenWalletBalance`
- **Job:** `MonitorOrderStatus`

### Authentication

API key in request header.

### Key Endpoints

| Operation | Method | Endpoint | Description |
|-----------|--------|----------|-------------|
| Products | GET | `/api/products` | List products |
| Create Order | POST | `/api/orders` | Place order |
| Order Status | GET | `/api/orders/{ref}` | Check status |
| Wallet | GET | `/api/wallet/balance` | Wallet balance |

---

## 7. Value Design Integration

### Existing Implementation

- **Service:** `VDWebApiService`, `VDHomeService`
- **Controller:** `VDWebController`, `VDPaymentController`
- **Commands:** Store sync, wallet balance check
- **Related tables:** `brands`, `store_details`, `evc_requests`, `evc_statuses`, `evc_card_items`

---

## 8. Lysto / Athena Integration

### Existing Implementation

- **Service:** `app/Http/Services/AthenaGiftCardService.php`
- **Controller:** `AthenaGiftCardController`
- **Base URL:** `https://stagedistapi.lysto.io/api/v1`

### Authentication

Bearer token + Partner ID in headers:
```
Authorization: Bearer {api_key}
partnerid: {partner_id}
```

### Key Endpoints

| Operation | Method | Endpoint | Description |
|-----------|--------|----------|-------------|
| List Cards | GET | `/giftcards` | Browse catalog |
| SKUs | GET | `/giftcards/{id}/skus` | Get SKUs for card |
| Purchase | POST | `/giftcard/purchase` | Buy gift card |
| Order Details | GET | `/orders?order_id={id}` | Get order + codes |
| Wallet | GET | `/wallet-balance` | Check balance |

### Voucher Code Encryption

Lysto returns encrypted voucher codes using AES-256-GCM:

```php
// Decrypt flow (existing in AthenaGiftCardService)
$ciphertext = hex2bin($data['ciphertext']);
$iv = hex2bin($data['iv']);
$tag = hex2bin($data['tag']);
$key = hash('sha256', $secret, true);

$decrypted = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
```

### Credential Structure

```json
{
  "api_url": "https://stagedistapi.lysto.io/api/v1",
  "api_key": "xxxxx",
  "partner_id": "xxxxx",
  "encryption_secret": "xxxxx"
}
```

---

## 9. Catalog Sync Pipeline

### Sync Architecture

```
Scheduler (cron)
    │
    ├── Every 4h: SyncWoohooCatalogJob
    ├── Every 6h: SyncEZPinCatalogJob
    ├── Every 6h: SyncGyftrCatalogJob
    ├── Daily:    SyncKGenCatalogJob
    ├── Daily:    SyncValueDesignCatalogJob
    └── Daily:    SyncLystoCatalogJob
         │
         ▼
    VoucherProviderInterface::fetchCatalog()
         │
         ▼
    NormalizedProduct collection
         │
         ▼
    Upsert Logic:
    ┌─────────────────────────────────────┐
    │ For each NormalizedProduct:          │
    │                                     │
    │ 1. Find by SKU + source_provider    │
    │ 2. If new:                          │
    │    - Create product                 │
    │    - show_product = false (hidden)  │
    │    - Admin reviews and publishes    │
    │ 3. If existing:                     │
    │    - Update: price, stock, images   │
    │    - Preserve: custom description,  │
    │      custom_image, show_product,    │
    │      priority, category, brand      │
    │ 4. If missing from provider:        │
    │    - Set out_of_stock = true        │
    │    - Do NOT delete                  │
    │ 5. Update last_synced_at            │
    └─────────────────────────────────────┘
```

### Sync Monitoring

| Metric | Alert Threshold |
|--------|----------------|
| Sync duration | > 30 minutes |
| Products added | > 100 in single sync (unusual) |
| Products marked out-of-stock | > 50% of catalog |
| Sync failure | Any failure alerts admin |
| Provider API errors | 3+ consecutive failures |

---

## 10. Provider Credential Management

Same pattern as payment gateways. Credentials stored encrypted in `tenant_voucher_providers` table.

### Admin UI

```
Provider Configuration Form:
┌──────────────────────────────────────────┐
│ Provider: [ Woohoo ▼ ]                   │
│ API URL:  [_________________________]     │
│ Client ID: [_________________________]    │
│ Client Secret: [_________________________]│
│ Priority: [1] (lower = preferred)         │
│ Environment: [ Production ▼ ]             │
│ [Test Connection] [Save]                  │
└──────────────────────────────────────────┘
```

---

## 11. Adding a New Provider

1. Create `app/Services/Voucher/NewProvider.php` implementing `VoucherProviderInterface`
2. Add provider identifier to `tenant_voucher_providers.provider` ENUM
3. Register in `VoucherProviderFactory::$providers`
4. Create catalog sync job under `app/Jobs/`
5. Register sync schedule in `app/Console/Kernel.php`
6. Add credential form fields to admin UI
7. Add provider test method for credential validation
8. Update this documentation

---

## 12. Vouchagram (Communication Engine)

| Aspect | Detail |
|--------|--------|
| **Drivers** | `vouchagram` / `vouchagram_send` (B2C Send Voucher), `vouchagram_pull` (B2B Pull Voucher) |
| **Config** | `config/vouchagram.php` — env: `VOUCHAGRAM_SEND_*`, `VOUCHAGRAM_PULL_*` |
| **Encryption** | AES-256-CBC on payloads; JWT from `GET /gettoken` (cached ~25 min) |
| **Service** | `App\Services\Voucher\VouchagramService` |
| **Fulfillment** | `App\Services\Order\VouchagramOrderFulfillmentService` — B2C (`tenant_id` null) uses Send flow; B2B (`tenant_id` set) uses Pull flow |
| **Catalog sync** | `php artisan vouchagram:sync-catalog` or admin **Panel → Vouchagram → Catalog sync**; `syncProvider('vouchagram')` stores `source_provider = vouchagram` |
| **Admin UI** | `/panel/vouchagram` (Inertia) — brands, stock, send/pull, status, stores, sync |
| **Vendor API reference** | [VOUCHAGRAM_SEND_API.md](./VOUCHAGRAM_SEND_API.md) (B2C Send), [VOUCHAGRAM_PULL_API.md](./VOUCHAGRAM_PULL_API.md) (B2B Pull) |

---

## 13. Provider response code mapping

Normalized error handling for voucher APIs:

| Layer | Role |
|-------|------|
| `App\Enums\ResponseCode` | Canonical success/error codes for the app and JSON API envelope |
| `App\Enums\ProviderErrorCode` | Maps provider-specific identifiers (e.g. Woohoo `5310`, Vouchagram `0000`) to `ResponseCode` |
| `App\Support\ProviderResponseTranslator` | Builds `{ response_code, message, provider_error }` from raw provider payloads |
| `resources/lang/en/provider_errors.php` | Localized provider messages |
| `resources/lang/en/responses.php` | Localized `ResponseCode` messages |

`WoohooProvider`, `VouchagramService` (envelope `code`), and `VouchagramPullProvider` use the translator for user-facing error strings where applicable.

---

## Related Documents

- [CODE_STANDARDS.md](CODE_STANDARDS.md) -- Webhook/callback input handling patterns
- [PAYMENT_GATEWAYS.md](PAYMENT_GATEWAYS.md) -- Payment gateway integration
- [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) -- Product and order tables
- [SYSTEM_DESIGN.md](SYSTEM_DESIGN.md) -- Catalog sync data flow
- [REQUIREMENT_QUESTIONS.md](REQUIREMENT_QUESTIONS.md) -- Open questions for EZ Pin and Gyftrr
