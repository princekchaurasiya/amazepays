# AmazePays Database Dictionary

> **Version:** 2.0  
> **Last Updated:** April 2026  
> **Database:** MySQL 8.0

This document maps every table, its columns, data types, constraints, and relationships. Tables are grouped by domain. Each relationship is documented with direction and cardinality.

---

## Table of Contents

1. [Relationship Overview Diagram](#1-relationship-overview-diagram)
2. [Users & Authentication Domain](#2-users--authentication-domain)
3. [Tenancy Domain](#3-tenancy-domain)
4. [Catalog Domain](#4-catalog-domain)
5. [Orders Domain](#5-orders-domain)
6. [Payments Domain](#6-payments-domain)
7. [Wallet Domain](#7-wallet-domain)
8. [Offers & Promotions Domain](#8-offers--promotions-domain)
9. [KGen / EVC Domain](#9-kgen--evc-domain)
10. [Content / CMS Domain](#10-content--cms-domain)
11. [Security & Audit Domain](#11-security--audit-domain)
12. [API & Integration Domain](#12-api--integration-domain)
13. [System Domain](#13-system-domain)
14. [Cross-Domain Relationship Summary](#14-cross-domain-relationship-summary)

---

## 1. Relationship Overview Diagram

```
                            ┌────────────────┐
                            │     users      │
                            └───────┬────────┘
               ┌────────────────────┼────────────────────────────────┐
               │        │           │           │          │         │
               ▼        ▼           ▼           ▼          ▼         ▼
          ┌────────┐┌────────┐┌──────────┐┌────────┐┌────────┐┌─────────┐
          │wallets ││ otps   ││orders ││user_ips││tenant_ ││audit_   │
          └───┬────┘└────────┘└────┬─────┘└────────┘│users   ││logs     │
              │                    │                 └───┬────┘└─────────┘
              ▼                    │                     │
         ┌──────────┐             │                     ▼
         │wallet_   │             │              ┌──────────┐
         │transact- │             │              │ tenants  │
         │ions      │             │              └────┬─────┘
         └──────────┘             │     ┌────────────┼──────────────┐
                                  │     │            │              │
                                  │     ▼            ▼              ▼
                                  │ ┌────────┐ ┌──────────┐ ┌──────────┐
                                  │ │tenant_ │ │tenant_   │ │tenant_   │
                                  │ │products│ │payment_  │ │voucher_  │
                                  │ └───┬────┘ │gateways  │ │providers │
                                  │     │      └──────────┘ └──────────┘
                                  │     │
                                  │     ▼
                                  │ ┌──────────┐      ┌──────────────────┐
                                  │ │products  │──────│category_product  │
                                  │ │          │      │(pivot)           │
                                  │ └────┬─────┘      └────────┬─────────┘
                                  │      │                     │
                                  │      │              ┌──────┴──────┐
                                  │      │              ▼             ▼
                                  │      │      ┌────────────┐┌───────────┐
                                  │      │      │categories  ││brands     │
                                  │      │      │            ││           │
                                  │      │      └────────────┘│           │
                                  │      │                    └───────────┘
                                  ▼      │
                            ┌──────────┐ │
                            │order_    │ │
                            │summary   │ │
                            └──────────┘ │
                                         │
                            ┌────────────┘
                            ▼
                     ┌──────────────┐
                     │ unlimit_     │
                     │ payment      │
                     └──────────────┘
```

---

## 2. Users & Authentication Domain

### `users`

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `name` | VARCHAR(255) | No | -- | -- | Full name |
| `email` | VARCHAR(255) | No | -- | UNIQUE | Login email |
| `email_verified_at` | TIMESTAMP | Yes | NULL | -- | Email verification timestamp |
| `password` | VARCHAR(255) | No | -- | -- | Bcrypt hashed password |
| `mobile` | VARCHAR(20) | Yes | NULL | -- | Phone number |
| `remember_token` | VARCHAR(100) | Yes | NULL | -- | Session remember token |
| `avatar` | VARCHAR(255) | Yes | 'users/default.png' | -- | Profile image path |
| `role_id` | BIGINT UNSIGNED | Yes | NULL | FK → roles.id (Voyager, to be removed) | Voyager role (legacy) |
| `is_admin` | TINYINT(1) | No | 0 | -- | Admin flag (legacy) |
| `is_blocked` | TINYINT(1) | No | 0 | -- | Account blocked |
| `can_transact` | TINYINT(1) | No | 1 | -- | Transaction permission |
| `restricted_features` | JSON | Yes | NULL | -- | Feature restrictions |
| `restriction_reason` | TEXT | Yes | NULL | -- | Reason for restriction |
| `billing_zip` | VARCHAR(20) | Yes | NULL | -- | Default billing ZIP |
| `billing_address` | VARCHAR(255) | Yes | NULL | -- | Default billing address |
| `billing_address_two` | VARCHAR(255) | Yes | NULL | -- | Address line 2 |
| `billing_city` | VARCHAR(100) | Yes | NULL | -- | Default billing city |
| `billing_state` | VARCHAR(100) | Yes | NULL | -- | Default billing state |
| `billing_country` | VARCHAR(100) | Yes | NULL | -- | Default billing country |
| `two_factor_enabled` | TINYINT(1) | No | 0 | -- | **NEW:** 2FA enabled flag |
| `settings` | TEXT | Yes | NULL | -- | Voyager user settings |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created timestamp |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated timestamp |

**Relationships FROM users:**

| Relationship | Target Table | Type | FK Column | Description |
|-------------|-------------|------|-----------|-------------|
| `users → wallets` | wallets | 1:1 | wallets.user_id | Each user has one wallet |
| `users → orders` | orders | 1:N | orders.user_id | User's voucher orders |
| `users → k_gen_orders` | k_gen_orders | 1:N | k_gen_orders.user_id | User's KGen orders |
| `users → unlimit_payments` | unlimit_payments | 1:N | unlimit_payments.user_id | User's Unlimit payments |
| `users → otps` | otps | 1:N | otps.user_id | OTP codes |
| `users → email_verification_codes` | email_verification_codes | 1:N | (by email) | Verification codes |
| `users → user_ips` | user_ips | 1:N | user_ips.user_id | IP tracking |
| `users → personal_access_tokens` | personal_access_tokens | 1:N (morph) | tokenable_id | Sanctum tokens |
| `users → tenant_users` | tenant_users | 1:N | tenant_users.user_id | Tenant memberships |
| `users → api_keys` | api_keys | 1:N | api_keys.user_id | Reseller API keys |
| `users → audit_logs` | audit_logs | 1:N | audit_logs.user_id | Actions performed |
| `users → two_factor_secrets` | two_factor_secrets | 1:1 | two_factor_secrets.user_id | 2FA secret |
| `users → offer_usages` | offer_usages | 1:N | offer_usages.user_id | Offers redeemed |
| `users → wallet_load_requests` | wallet_load_requests | 1:N | wallet_load_requests.user_id | Load requests |

### `personal_access_tokens`

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `tokenable_type` | VARCHAR(255) | No | -- | INDEX (composite) | Polymorphic model class |
| `tokenable_id` | BIGINT UNSIGNED | No | -- | INDEX (composite) | Polymorphic model ID |
| `name` | VARCHAR(255) | No | -- | -- | Token name/label |
| `token` | VARCHAR(64) | No | -- | UNIQUE | SHA-256 hashed token |
| `abilities` | TEXT | Yes | NULL | -- | Token abilities JSON |
| `last_used_at` | TIMESTAMP | Yes | NULL | -- | Last API call |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated |

### `otps`

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `user_id` | BIGINT UNSIGNED | Yes | NULL | -- | User reference |
| `mobile_number` | VARCHAR(20) | Yes | NULL | -- | Phone for OTP |
| `otp` | VARCHAR(10) | No | -- | -- | OTP code |
| `expires_at` | TIMESTAMP | No | -- | -- | Expiry time |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated |

### `email_verification_codes`

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `email` | VARCHAR(255) | No | -- | -- | Email address |
| `code` | VARCHAR(10) | No | -- | -- | Verification code |
| `expires_at` | TIMESTAMP | No | -- | -- | Expiry time |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated |

### `user_ips`

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `user_id` | BIGINT UNSIGNED | Yes | NULL | -- | User reference |
| `ip_address` | VARCHAR(45) | No | -- | -- | IPv4 or IPv6 |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated |

---

## 3. Tenancy Domain

### `tenants` **(NEW)**

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `name` | VARCHAR(255) | No | -- | -- | Organization name |
| `slug` | VARCHAR(255) | No | -- | UNIQUE | URL-safe identifier |
| `logo` | VARCHAR(255) | Yes | NULL | -- | Logo file path |
| `contact_email` | VARCHAR(255) | No | -- | -- | Primary contact email |
| `contact_phone` | VARCHAR(20) | Yes | NULL | -- | Primary contact phone |
| `status` | ENUM | No | 'active' | -- | active/suspended/inactive |
| `settings` | JSON | Yes | NULL | -- | Tenant-specific config |
| `margin_percentage` | DECIMAL(5,2) | No | 0.00 | -- | Default margin on products |
| `credit_limit` | DECIMAL(12,2) | No | 0.00 | -- | Wallet credit limit |
| `website` | VARCHAR(255) | Yes | NULL | -- | Tenant website |
| `gst_number` | VARCHAR(50) | Yes | NULL | -- | GST registration |
| `address` | TEXT | Yes | NULL | -- | Street address |
| `city` | VARCHAR(100) | Yes | NULL | -- | City |
| `state` | VARCHAR(100) | Yes | NULL | -- | State |
| `pincode` | VARCHAR(10) | Yes | NULL | -- | PIN code |
| `country` | VARCHAR(100) | No | 'India' | -- | Country |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated |

**Relationships FROM tenants:**

| Relationship | Target Table | Type | FK Column | Description |
|-------------|-------------|------|-----------|-------------|
| `tenants → tenant_users` | tenant_users | 1:N | tenant_users.tenant_id | Members |
| `tenants → tenant_products` | tenant_products | 1:N | tenant_products.tenant_id | Product access |
| `tenants → tenant_payment_gateways` | tenant_payment_gateways | 1:N | tenant_payment_gateways.tenant_id | Gateway configs |
| `tenants → tenant_voucher_providers` | tenant_voucher_providers | 1:N | tenant_voucher_providers.tenant_id | Provider configs |
| `tenants → api_keys` | api_keys | 1:N | api_keys.tenant_id | Reseller API keys |
| `tenants → offers` | offers | 1:N | offers.tenant_id | Tenant-specific offers |
| `tenants → wallet_load_requests` | wallet_load_requests | 1:N | wallet_load_requests.tenant_id | Load requests |
| `tenants → ip_whitelists` | ip_whitelists | 1:N | ip_whitelists.tenant_id | IP rules |
| `tenants → audit_logs` | audit_logs | 1:N | audit_logs.tenant_id | Tenant audit trail |

### `tenant_users` **(NEW)**

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `tenant_id` | BIGINT UNSIGNED | No | -- | FK → tenants.id CASCADE | Tenant |
| `user_id` | BIGINT UNSIGNED | No | -- | FK → users.id CASCADE | User |
| `role` | ENUM | No | 'operator' | -- | owner/manager/operator |
| `is_active` | TINYINT(1) | No | 1 | -- | Active membership |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated |

**Unique constraint:** `(tenant_id, user_id)` -- a user can belong to a tenant only once.

### `tenant_products` **(NEW)**

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `tenant_id` | BIGINT UNSIGNED | No | -- | FK → tenants.id CASCADE | Tenant |
| `product_id` | BIGINT UNSIGNED | No | -- | FK → products.id CASCADE | Product |
| `custom_price` | DECIMAL(10,2) | Yes | NULL | -- | Override price |
| `margin_override` | DECIMAL(5,2) | Yes | NULL | -- | Override margin % |
| `is_active` | TINYINT(1) | No | 1 | -- | Active for this tenant |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated |

**Unique constraint:** `(tenant_id, product_id)`

### `tenant_payment_gateways` **(NEW)**

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `tenant_id` | BIGINT UNSIGNED | No | -- | FK → tenants.id CASCADE | Tenant |
| `gateway` | ENUM | No | -- | -- | ccavenue/razorpay/unlimit |
| `credentials` | TEXT | No | -- | -- | **ENCRYPTED** JSON (Laravel Crypt) |
| `is_active` | TINYINT(1) | No | 1 | -- | Gateway enabled |
| `environment` | ENUM | No | 'sandbox' | -- | sandbox/production |
| `display_name` | VARCHAR(255) | Yes | NULL | -- | Custom label |
| `notes` | TEXT | Yes | NULL | -- | Internal notes |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated |

**Unique constraint:** `(tenant_id, gateway)` -- one config per gateway per tenant.

**Credential JSON structure (before encryption):**

- CCAvenue: `{"merchant_id": "...", "access_code": "...", "working_key": "..."}`
- Razorpay: `{"key_id": "...", "key_secret": "...", "webhook_secret": "..."}`
- Unlimit: `{"login": "...", "password": "...", "callback_secret": "..."}`

### `tenant_voucher_providers` **(NEW)**

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `tenant_id` | BIGINT UNSIGNED | No | -- | FK → tenants.id CASCADE | Tenant |
| `provider` | ENUM | No | -- | -- | woohoo/ezpin/gyftrr/kgen/value_design/lysto |
| `credentials` | TEXT | No | -- | -- | **ENCRYPTED** JSON |
| `is_active` | TINYINT(1) | No | 1 | -- | Provider enabled |
| `priority` | INT | No | 0 | -- | Failover order (lower = higher priority) |
| `settings` | JSON | Yes | NULL | -- | Provider-specific settings |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated |

**Unique constraint:** `(tenant_id, provider)`

---

## 4. Catalog Domain

### `products`

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `name` | VARCHAR(255) | Yes | NULL | -- | Product display name |
| `sku` | VARCHAR(255) | Yes | NULL | UNIQUE | Stock keeping unit |
| `description` | TEXT | Yes | NULL | -- | Provider description |
| `price` | TEXT | Yes | NULL | -- | JSON price structure from provider |
| `currency` | TEXT | Yes | NULL | -- | JSON currency info |
| `images` | TEXT | Yes | NULL | -- | JSON array of image URLs |
| `minPrice` | VARCHAR(50) | Yes | NULL | -- | Minimum price |
| `maxPrice` | VARCHAR(50) | Yes | NULL | -- | Maximum price |
| `discount_percentage` | DECIMAL(5,2) | No | 0 | -- | Platform discount % |
| `CGST` | DECIMAL(5,2) | No | 0 | -- | Central GST % |
| `SGST` | DECIMAL(5,2) | No | 0 | -- | State GST % |
| `IGST` | DECIMAL(5,2) | No | 0 | -- | Integrated GST % |
| `slug` | VARCHAR(255) | Yes | NULL | -- | URL slug |
| `category_id` | BIGINT UNSIGNED | Yes | NULL | FK → categories.id | Category |
| `brand_id` | BIGINT UNSIGNED | Yes | NULL | FK → brands.id | Brand |
| `how_to_redeem` | TEXT | Yes | NULL | -- | Redemption instructions |
| `terms_and_conditions` | TEXT | Yes | NULL | -- | Terms and conditions |
| `custom_description` | TEXT | Yes | NULL | -- | Custom description |
| `custom_image` | VARCHAR(255) | Yes | NULL | -- | Overridden image |
| `card_logo_url` | VARCHAR(255) | Yes | NULL | -- | Product-level logo fallback for card UI |
| `card_bg_color` | VARCHAR(16) | Yes | NULL | -- | Product-level card background color |
| `card_text_color` | VARCHAR(16) | Yes | NULL | -- | Product-level card text color |
| `card_accent_color` | VARCHAR(16) | Yes | NULL | -- | Product-level accent color for CTA/discount line |
| `show_product` | TINYINT(1) | No | 0 | -- | Visible on storefront |
| `priority` | INT | No | 0 | -- | Display priority |
| `secondary_priority` | INT | No | 0 | -- | Secondary sort order |
| `is_special_sku` | TINYINT(1) | No | 0 | -- | Special handling flag |
| `out_of_stock` | TINYINT(1) | No | 0 | -- | Stock status |
| `source_provider` | VARCHAR(50) | Yes | NULL | -- | **NEW:** woohoo/ezpin/gyftrr/kgen/vd |
| `last_synced_at` | TIMESTAMP | Yes | NULL | -- | **NEW:** Last provider sync |
| `sync_status` | VARCHAR(20) | Yes | NULL | -- | **NEW:** synced/pending/error |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated |

**Relationships:**

| Relationship | Target | Type | Via | Description |
|-------------|--------|------|-----|-------------|
| `products → categories` | categories | N:1 | category_id | Product category |
| `products → brands` | brands | N:1 | brand_id | Product brand |
| `products → categories` | categories | N:M | category_product | Multi-category pivot |
| `products → orders` | orders | 1:N | sku = sku | Orders for product |
| `products → tenant_products` | tenant_products | 1:N | product_id | Tenant access records |
| `products → slides` | slides | 1:N | product_id | Banner links |

### `brand_card_themes`

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Theme row ID |
| `product_id` | BIGINT UNSIGNED | Yes | NULL | INDEX | Product-specific card theme |
| `brand_id` | BIGINT UNSIGNED | Yes | NULL | INDEX | Brand-level card theme |
| `brand_name` | VARCHAR(255) | Yes | NULL | INDEX | Name fallback for brand mapping |
| `logo_url` | VARCHAR(255) | Yes | NULL | -- | Fallback logo URL |
| `bg_color` | VARCHAR(16) | Yes | NULL | -- | Card background color |
| `text_color` | VARCHAR(16) | Yes | NULL | -- | Card headline/value text color |
| `accent_color` | VARCHAR(16) | Yes | NULL | -- | Accent color for discount/value messaging |
| `is_active` | TINYINT(1) | No | 1 | INDEX | Enabled theme flag |
| `priority` | INT UNSIGNED | No | 0 | INDEX | Lower number = higher preference |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated |

**Vouchagram catalog snapshots (related, not a replacement for `products`):**

| Table | Role |
|-------|------|
| `vouchagram_catalog_snapshots` | One row per admin **Fetch brands** run (`mode` = `send` or `pull`). |
| `vouchagram_catalog_snapshot_items` | JSON `payload` per brand row from `getbrands`. Used for history and optional **import to `products`** without calling the API again. |

Live catalog import into **`products`** uses `CatalogSyncService` (`vouchagram_send` / `vouchagram_pull`, `catalog_audience`). See [VOUCHER_PROVIDERS.md](./VOUCHER_PROVIDERS.md) §12.

### `categories`

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `parent_id` | BIGINT UNSIGNED | Yes | NULL | FK → categories.id (self) | Parent category |
| `order` | INT | No | 1 | -- | Display order |
| `name` | VARCHAR(255) | No | -- | -- | Category name |
| `slug` | VARCHAR(255) | No | -- | UNIQUE | URL slug |
| `thumbnail` | VARCHAR(255) | Yes | NULL | -- | Category image |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated |

**Self-referential:** `parent_id → categories.id` (tree structure)

### `brands`

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `name` | VARCHAR(255) | No | -- | -- | Brand name |
| `slug` | VARCHAR(255) | No | -- | UNIQUE | URL slug |
| `logo` | VARCHAR(255) | Yes | NULL | -- | Brand logo path |
| `order` | INT | No | 0 | -- | Display order |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated |

### `category_product` (Pivot)

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `category_id` | BIGINT UNSIGNED | No | -- | FK → categories.id CASCADE | Category |
| `product_id` | BIGINT UNSIGNED | No | -- | FK → products.id CASCADE | Product |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated |

---

## 5. Orders Domain

### `orders`

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `user_id` | BIGINT UNSIGNED | Yes | NULL | FK → users.id SET NULL | Ordering user |
| `tenant_id` | BIGINT UNSIGNED | Yes | NULL | FK → tenants.id SET NULL | **NEW:** Tenant scope |
| `merchant_order_id` | CHAR(36) | Yes | NULL | UNIQUE | UUID order reference |
| `woohoo_order_id` | VARCHAR(255) | Yes | NULL | -- | Woohoo order ID |
| `order_status` | VARCHAR(50) | Yes | NULL | INDEX | CREATED/PENDING/PAID/PROCESSING/COMPLETE/FAILED/REFUNDED |
| `sku` | VARCHAR(255) | Yes | NULL | INDEX | Product SKU |
| `product_name` | VARCHAR(255) | Yes | NULL | -- | Product name snapshot |
| `quantity` | INT | No | 1 | -- | Quantity ordered |
| `denomination` | VARCHAR(50) | Yes | NULL | -- | Card denomination |
| `amount` | DECIMAL(10,2) | Yes | NULL | -- | Base amount |
| `price` | DECIMAL(10,2) | Yes | NULL | -- | Unit price |
| `grand_payable_amount` | DECIMAL(10,2) | Yes | NULL | -- | Final payable |
| `discounted_amount_value` | DECIMAL(10,2) | Yes | NULL | -- | Discount value |
| `amount_payable_after_discount` | DECIMAL(10,2) | Yes | NULL | -- | After discount |
| `offer_id` | BIGINT UNSIGNED | Yes | NULL | FK → offers.id SET NULL | **NEW:** Applied offer |
| `offer_discount` | DECIMAL(10,2) | Yes | NULL | -- | **NEW:** Offer discount amount |
| `gst_number` | VARCHAR(50) | Yes | NULL | -- | Customer GST |
| `currency` | VARCHAR(10) | Yes | NULL | -- | Payment currency |
| `country` | VARCHAR(50) | Yes | NULL | -- | Billing country |
| `sender_*` | various | Yes | NULL | -- | Sender details (name, email, phone, address) |
| `receiver_*` | various | Yes | NULL | -- | Receiver details (name, email, mobile, msg) |
| `cards` | JSON | Yes | NULL | -- | Delivered voucher codes (**encrypted in v2**) |
| `additionalTxnFields` | JSON | Yes | NULL | -- | Extra transaction data |
| `order_cancel` | TINYINT(1) | Yes | NULL | -- | Cancellation flag |
| `order_payment` | VARCHAR(50) | Yes | NULL | -- | Payment method used |
| `gift_send_option` | VARCHAR(50) | Yes | NULL | -- | Delivery preference |
| `delivery_mode` | VARCHAR(50) | Yes | NULL | -- | Delivery mode |
| `gift_theme_id` | BIGINT UNSIGNED | Yes | NULL | INDEX | Selected gift theme for `send_as_gift` checkout |
| `gift_message_title` | VARCHAR(120) | Yes | NULL | -- | Gift card message title line |
| `vd_brand_code` | VARCHAR(50) | Yes | NULL | -- | Value Design brand |
| `vd_discount` | DECIMAL(5,2) | Yes | NULL | -- | VD discount |
| `refno` | VARCHAR(255) | Yes | NULL | -- | Provider reference |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated |

**Relationships:**

| Relationship | Target | Type | Via | Description |
|-------------|--------|------|-----|-------------|
| `orders → users` | users | N:1 | user_id | Order owner |
| `orders → tenants` | tenants | N:1 | tenant_id | Tenant scope |
| `orders → products` | products | N:1 | sku = sku | Product ordered |
| `orders → order_summaries` | order_summaries | 1:1 | order_summaries.order_id | Payment summary |
| `orders → unlimit_payments` | unlimit_payments | 1:1 | unlimit_payments.order_id | Unlimit payment |
| `orders → offers` | offers | N:1 | offer_id | Applied offer |
| `orders → offer_usages` | offer_usages | 1:N | offer_usages.order_id | Usage tracking |

### `gift_card_themes`

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Theme ID |
| `name` | VARCHAR(255) | No | -- | -- | Theme display name |
| `slug` | VARCHAR(255) | No | -- | UNIQUE | Stable theme key |
| `thumbnail_url` | VARCHAR(255) | Yes | NULL | -- | Thumbnail used in selector |
| `image_url` | VARCHAR(255) | Yes | NULL | -- | Full card artwork URL |
| `is_active` | TINYINT(1) | No | 1 | INDEX | Only active themes are selectable |
| `sort_order` | INT UNSIGNED | No | 0 | INDEX | Display ordering |
| `metadata` | JSON | Yes | NULL | -- | Optional extra theme metadata |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated |

### `order_summaries`

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `order_id` | BIGINT UNSIGNED | No | -- | FK → orders.id CASCADE | Parent order |
| `payment_id` | INT UNSIGNED | Yes | NULL | -- | Payment reference |
| `payment_status` | VARCHAR(50) | Yes | NULL | -- | Gateway status |
| `order_status` | VARCHAR(50) | Yes | NULL | -- | Fulfillment status |
| `payment_gateway` | VARCHAR(50) | Yes | NULL | -- | ccavenue/unlimit/razorpay/wallet |
| `summary_status` | VARCHAR(50) | Yes | NULL | -- | Combined status |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated |

---

## 6. Payments Domain

### `unlimit_payments`

See full column listing in [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md#24-payments).

**Key relationships:**

| Relationship | Target | Type | Via | Description |
|-------------|--------|------|-----|-------------|
| `unlimit_payments → orders` | orders | N:1 | order_id | Associated order |
| `unlimit_payments → users` | users | N:1 | user_id | Paying user |

### `payments`

Generic payment log. No foreign keys -- serves as an append-only log.

### `invoices`

Invoice records tied to merchant_order_id (string match, no FK).

---

## 7. Wallet Domain

### `wallets`

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `user_id` | BIGINT UNSIGNED | No | -- | FK → users.id CASCADE, UNIQUE | Wallet owner |
| `tenant_id` | BIGINT UNSIGNED | Yes | NULL | FK → tenants.id SET NULL | **NEW:** Tenant scope |
| `balance` | DECIMAL(12,2) | No | 0.00 | -- | Current balance |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated |

### `wallet_transactions`

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `wallet_id` | BIGINT UNSIGNED | No | -- | FK → wallets.id CASCADE | Parent wallet |
| `amount` | DECIMAL(12,2) | No | -- | -- | Transaction amount |
| `type` | ENUM | No | -- | -- | credit/debit |
| `reference` | VARCHAR(255) | No | -- | UNIQUE | Transaction reference |
| `description` | TEXT | Yes | NULL | -- | Transaction description |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated |

### `wallet_load_requests` **(NEW)**

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `tenant_id` | BIGINT UNSIGNED | Yes | NULL | FK → tenants.id SET NULL | Tenant scope |
| `user_id` | BIGINT UNSIGNED | No | -- | FK → users.id CASCADE | Requesting user |
| `amount` | DECIMAL(12,2) | No | -- | -- | Load amount |
| `bank_reference` | VARCHAR(255) | No | -- | -- | UTR / bank ref |
| `bank_name` | VARCHAR(255) | Yes | NULL | -- | Bank name |
| `proof_document` | VARCHAR(255) | Yes | NULL | -- | Upload file path |
| `status` | ENUM | No | 'pending' | INDEX | pending/approved/rejected |
| `approved_by` | BIGINT UNSIGNED | Yes | NULL | FK → users.id SET NULL | Admin who approved |
| `approved_at` | TIMESTAMP | Yes | NULL | -- | Approval timestamp |
| `rejection_reason` | TEXT | Yes | NULL | -- | If rejected |
| `notes` | TEXT | Yes | NULL | -- | Internal notes |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated |

---

## 8. Offers & Promotions Domain

### `offers` **(NEW)**

See full column listing in [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md#33-offers--promotions).

**Relationships:**

| Relationship | Target | Type | Via | Description |
|-------------|--------|------|-----|-------------|
| `offers → tenants` | tenants | N:1 | tenant_id | Tenant-specific (null = global) |
| `offers → offer_usages` | offer_usages | 1:N | offer_usages.offer_id | Usage records |
| `offers → orders` | orders | 1:N | orders.offer_id | Orders using this offer |

### `offer_usages` **(NEW)**

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `offer_id` | BIGINT UNSIGNED | No | -- | FK → offers.id CASCADE | Offer |
| `user_id` | BIGINT UNSIGNED | No | -- | FK → users.id CASCADE | User |
| `order_id` | BIGINT UNSIGNED | No | -- | FK → orders.id CASCADE | Order |
| `discount_amount` | DECIMAL(10,2) | No | -- | -- | Discount applied |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |

---

## 9. KGen / EVC Domain

### `kgen_voucher_orders`

**Relationships:**
- `kgen_voucher_orders.user_id → users.id` (N:1)

### `kgen_products`

Standalone table, no foreign keys. Synced from KGen API.

### `evc_statuses`, `evc_card_items`, `value_design_evc_requests`

Loosely coupled via `order_id` (string match, no FK constraint).

---

## 10. Content / CMS Domain

### `homepage_sections`

Standalone table. Managed via admin CRUD.

### `slides`

**Relationships:**
- `slides.product_id → products.id` (N:1, SET NULL)
- `slides.category_id → categories.id` (N:1, SET NULL)
- `slides.brand_id → brands.id` (N:1, SET NULL)

---

## 11. Security & Audit Domain

### `audit_logs` **(NEW)**

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `tenant_id` | BIGINT UNSIGNED | Yes | NULL | INDEX | Tenant scope |
| `user_id` | BIGINT UNSIGNED | Yes | NULL | INDEX | Actor |
| `action` | VARCHAR(255) | No | -- | INDEX | e.g. 'order.created' |
| `auditable_type` | VARCHAR(255) | Yes | NULL | INDEX (composite) | Model class |
| `auditable_id` | BIGINT UNSIGNED | Yes | NULL | INDEX (composite) | Model ID |
| `old_values` | JSON | Yes | NULL | -- | Before state |
| `new_values` | JSON | Yes | NULL | -- | After state |
| `ip_address` | VARCHAR(45) | Yes | NULL | -- | Client IP |
| `user_agent` | VARCHAR(500) | Yes | NULL | -- | Browser/client |
| `url` | VARCHAR(500) | Yes | NULL | -- | Request URL |
| `created_at` | TIMESTAMP | Yes | NULL | INDEX | Timestamp |

### `two_factor_secrets` **(NEW)**

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `user_id` | BIGINT UNSIGNED | No | -- | FK → users.id CASCADE, UNIQUE | User |
| `secret` | VARCHAR(255) | No | -- | -- | **ENCRYPTED** TOTP secret |
| `recovery_codes` | TEXT | Yes | NULL | -- | **ENCRYPTED** JSON array |
| `confirmed_at` | TIMESTAMP | Yes | NULL | -- | When 2FA was confirmed |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated |

### `ip_whitelists` **(NEW)**

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `tenant_id` | BIGINT UNSIGNED | Yes | NULL | FK → tenants.id CASCADE | Tenant |
| `ip_address` | VARCHAR(45) | No | -- | INDEX (composite) | IPv4/IPv6 |
| `label` | VARCHAR(255) | Yes | NULL | -- | Description |
| `is_active` | TINYINT(1) | No | 1 | -- | Active rule |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated |

---

## 12. API & Integration Domain

### `api_keys` **(NEW)**

| Column | Type | Nullable | Default | Constraints | Description |
|--------|------|----------|---------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | PK | Primary key |
| `tenant_id` | BIGINT UNSIGNED | Yes | NULL | FK → tenants.id CASCADE | Tenant |
| `user_id` | BIGINT UNSIGNED | No | -- | FK → users.id CASCADE | Owner |
| `name` | VARCHAR(255) | No | -- | -- | Key label |
| `key` | VARCHAR(64) | No | -- | UNIQUE | Public API key |
| `secret` | VARCHAR(128) | No | -- | -- | **ENCRYPTED** HMAC secret |
| `permissions` | JSON | Yes | NULL | -- | Allowed endpoints |
| `rate_limit` | INT | No | 120 | -- | Requests/minute |
| `ip_whitelist` | JSON | Yes | NULL | -- | Allowed IPs |
| `is_active` | TINYINT(1) | No | 1 | -- | Key active |
| `last_used_at` | TIMESTAMP | Yes | NULL | -- | Last usage |
| `expires_at` | TIMESTAMP | Yes | NULL | -- | Expiry date |
| `created_at` | TIMESTAMP | Yes | NULL | -- | Created |
| `updated_at` | TIMESTAMP | Yes | NULL | -- | Updated |

### `api_tokens` (existing -- provider tokens)

Stores external API access tokens (e.g., Woohoo OAuth tokens). Not for reseller use.

---

## 13. System Domain

### `failed_jobs`

Standard Laravel failed queue jobs table.

### `jobs` **(NEW)**

Standard Laravel queue jobs table (required when `QUEUE_CONNECTION=database` or `redis`).

### `notifications` **(NEW)**

Standard Laravel notifications table for in-app notification feed.

### `settings` **(NEW)**

Application key–value settings (admin **Panel → Settings**, Woohoo bearer token storage, invoice T&C, etc.). Replaces legacy Voyager `settings` usage.

| Column | Type | Nullable | Constraints | Description |
|--------|------|----------|-------------|-------------|
| `id` | BIGINT UNSIGNED | No | PK | Primary key |
| `key` | VARCHAR(255) | Yes | UNIQUE | Setting key (e.g. `site.invoice_t&c`, `woohoo.bearer_token`) |
| `display_name` | VARCHAR(255) | Yes | -- | Human label |
| `value` | LONGTEXT | Yes | -- | Stored value |
| `details` | TEXT | Yes | -- | Extra JSON/metadata |
| `created_at` | TIMESTAMP | Yes | -- | Created |
| `updated_at` | TIMESTAMP | Yes | -- | Updated |

---

## 14. Cross-Domain Relationship Summary

### All Foreign Key Relationships

| Source Table | Source Column | Target Table | Target Column | On Delete |
|-------------|--------------|-------------|--------------|-----------|
| `tenant_users` | `tenant_id` | `tenants` | `id` | CASCADE |
| `tenant_users` | `user_id` | `users` | `id` | CASCADE |
| `tenant_products` | `tenant_id` | `tenants` | `id` | CASCADE |
| `tenant_products` | `product_id` | `products` | `id` | CASCADE |
| `tenant_payment_gateways` | `tenant_id` | `tenants` | `id` | CASCADE |
| `tenant_voucher_providers` | `tenant_id` | `tenants` | `id` | CASCADE |
| `api_keys` | `tenant_id` | `tenants` | `id` | CASCADE |
| `api_keys` | `user_id` | `users` | `id` | CASCADE |
| `offers` | `tenant_id` | `tenants` | `id` | CASCADE |
| `offer_usages` | `offer_id` | `offers` | `id` | CASCADE |
| `offer_usages` | `user_id` | `users` | `id` | CASCADE |
| `offer_usages` | `order_id` | `orders` | `id` | CASCADE |
| `orders` | `user_id` | `users` | `id` | SET NULL |
| `orders` | `tenant_id` | `tenants` | `id` | SET NULL |
| `orders` | `offer_id` | `offers` | `id` | SET NULL |
| `order_summaries` | `order_id` | `orders` | `id` | CASCADE |
| `wallets` | `user_id` | `users` | `id` | CASCADE |
| `wallet_transactions` | `wallet_id` | `wallets` | `id` | CASCADE |
| `wallet_load_requests` | `tenant_id` | `tenants` | `id` | SET NULL |
| `wallet_load_requests` | `user_id` | `users` | `id` | CASCADE |
| `wallet_load_requests` | `approved_by` | `users` | `id` | SET NULL |
| `products` | `category_id` | `categories` | `id` | SET NULL |
| `products` | `brand_id` | `brands` | `id` | SET NULL |
| `category_product` | `category_id` | `categories` | `id` | CASCADE |
| `category_product` | `product_id` | `products` | `id` | CASCADE |
| `categories` | `parent_id` | `categories` | `id` | SET NULL |
| `slides` | `product_id` | `products` | `id` | SET NULL |
| `slides` | `category_id` | `categories` | `id` | SET NULL |
| `slides` | `brand_id` | `brands` | `id` | SET NULL |
| `unlimit_payments` | `user_id` | `users` | `id` | SET NULL |
| `unlimit_payments` | `order_id` | `orders` | `id` | SET NULL |
| `k_gen_orders` | `user_id` | `users` | `id` | SET NULL |
| `audit_logs` | `tenant_id` | `tenants` | `id` | SET NULL |
| `audit_logs` | `user_id` | `users` | `id` | SET NULL |
| `two_factor_secrets` | `user_id` | `users` | `id` | CASCADE |
| `ip_whitelists` | `tenant_id` | `tenants` | `id` | CASCADE |

| `security_event_logs` | `user_id` | `users` | `id` | SET NULL |
| `security_event_logs` | `tenant_id` | `tenants` | `id` | SET NULL |
| `security_event_logs` | `resolved_by` | `users` | `id` | SET NULL |
| `blocked_ips` | `blocked_by` | `users` | `id` | SET NULL |

---

## 6. Security Tables

### `security_event_logs`

Dedicated high-volume log for all security-relevant events (VPN detection, fraud checks, login failures, attack attempts). Separate from `audit_logs` for performance and specialized querying.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `event_type` | VARCHAR(50) | No | — | Event category (e.g., vpn_detected, login_failed, wallet_fraud_check) |
| `severity` | ENUM | No | — | info, low, medium, high, critical |
| `ip_address` | VARCHAR(45) | Yes | NULL | Source IP (IPv4 or IPv6) |
| `user_id` | BIGINT UNSIGNED | Yes | NULL | Associated user (NULL for anonymous) |
| `tenant_id` | BIGINT UNSIGNED | Yes | NULL | Associated tenant |
| `user_agent` | TEXT | Yes | NULL | Browser/app user-agent string |
| `request_url` | VARCHAR(2048) | Yes | NULL | URL that triggered the event |
| `request_method` | VARCHAR(10) | Yes | NULL | HTTP method (GET, POST, etc.) |
| `country_code` | CHAR(2) | Yes | NULL | GeoIP country code |
| `city` | VARCHAR(100) | Yes | NULL | GeoIP city |
| `is_vpn` | BOOLEAN | No | FALSE | Whether VPN/proxy was detected |
| `device_id` | VARCHAR(255) | Yes | NULL | Mobile app device fingerprint |
| `metadata` | JSON | Yes | NULL | Event-specific data (fraud scores, detection methods, etc.) |
| `resolved` | BOOLEAN | No | FALSE | Whether event has been reviewed/resolved |
| `resolved_by` | BIGINT UNSIGNED | Yes | NULL | Admin who resolved the event |
| `resolved_at` | TIMESTAMP | Yes | NULL | When the event was resolved |
| `resolution_note` | TEXT | Yes | NULL | Admin notes on resolution |
| `created_at` | TIMESTAMP | No | CURRENT_TIMESTAMP | Event timestamp |

**Relationships:**
| Relationship | Target | Cardinality | FK Column | Description |
|---|---|---|---|---|
| `security_event_logs → users` | users | N:1 | user_id | User who triggered event |
| `security_event_logs → tenants` | tenants | N:1 | tenant_id | Tenant context |
| `security_event_logs → users (resolver)` | users | N:1 | resolved_by | Admin who resolved |

### `blocked_ips`

IP addresses that are blocked from accessing the system, either automatically by the threat detection engine or manually by an admin.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT UNSIGNED | No | AUTO_INCREMENT | Primary key |
| `ip_address` | VARCHAR(45) | No | — | Blocked IP address (unique) |
| `reason` | TEXT | No | — | Why the IP was blocked |
| `blocked_at` | TIMESTAMP | No | — | When the block started |
| `expires_at` | TIMESTAMP | Yes | NULL | When the block expires (NULL = check `permanent`) |
| `auto_blocked` | BOOLEAN | No | FALSE | TRUE if blocked by automated threat detection |
| `blocked_by` | BIGINT UNSIGNED | Yes | NULL | Admin who blocked (NULL for auto-blocks) |
| `block_count` | INT UNSIGNED | No | 1 | Number of times this IP has been blocked |
| `permanent` | BOOLEAN | No | FALSE | TRUE if permanently blocked |
| `created_at` | TIMESTAMP | No | CURRENT_TIMESTAMP | Record creation |
| `updated_at` | TIMESTAMP | No | CURRENT_TIMESTAMP | Last update |

**Relationships:**
| Relationship | Target | Cardinality | FK Column | Description |
|---|---|---|---|---|
| `blocked_ips → users` | users | N:1 | blocked_by | Admin who blocked |

---

## Related Documents

- [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) -- Full CREATE TABLE statements
- [ARCHITECTURE.md](ARCHITECTURE.md) -- System architecture
- [CODE_STANDARDS.md](CODE_STANDARDS.md) -- Do not log raw PII/payment fields from ORM rows in application logs
- [B2B_TENANCY.md](B2B_TENANCY.md) -- Tenant isolation details
- [SECURITY.md](SECURITY.md) -- VPN detection, fraud prevention, threat blocking
