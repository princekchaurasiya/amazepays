# AmazePays Database Schema

> **Version:** 2.0  
> **Last Updated:** April 2026  
> **Database:** MySQL 8.0  
> **ORM:** Eloquent (Laravel 13)

---

## Table of Contents

1. [Schema Overview](#1-schema-overview)
2. [Existing Tables](#2-existing-tables)
3. [New Tables (Architecture v2)](#3-new-tables-architecture-v2)
4. [Migration Plan](#4-migration-plan)
5. [Index Strategy](#5-index-strategy)
6. [Relationship Map](#6-relationship-map)

---

## 1. Schema Overview

### Entity-Relationship Diagram

```
┌──────────┐     ┌──────────────┐     ┌──────────────┐
│  users   │────<│ tenant_users │>────│   tenants    │
│          │     └──────────────┘     │              │
│          │                          │              │
│          │──────< wallets           │──────< tenant_products
│          │──────< orders         │──────< tenant_payment_gateways
│          │──────< k_gen_orders      │──────< tenant_voucher_providers
│          │──────< unlimit_payments   │──────< api_keys
│          │──────< user_ips          │──────< offers
│          │──────< otps              │──────< wallet_load_requests
│          │──────< audit_logs        └──────────────┘
└──────────┘
      │
      │           ┌──────────────┐     ┌──────────────────────┐
      │           │ products     │────<│ category_product     │
      │           │              │     │ (pivot)              │
      │           │              │     └──────────────────────┘
      │           │              │              │
      │           │              │     ┌────────┴─────────────┐
      │           │              │>────│ categories           │
      │           │              │>────│ brands               │
      │           │              │     │                      │
      │           └──────┬───────┘     └──────────────────────┘
      │                  │
      │           ┌──────┴───────┐
      └──────────>│  orders   │
                  │              │────< order_summaries
                  │              │────< unlimit_payments
                  │              │────< ccavenue_payments
                  └──────────────┘
```

---

## 2. Existing Tables

### 2.1 Users & Authentication

#### `users`
```sql
CREATE TABLE users (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    email           VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password        VARCHAR(255) NOT NULL,
    mobile          VARCHAR(20) NULL,
    remember_token  VARCHAR(100) NULL,
    avatar          VARCHAR(255) NULL DEFAULT 'users/default.png',
    role_id         BIGINT UNSIGNED NULL,
    is_admin        TINYINT(1) DEFAULT 0,
    is_blocked      TINYINT(1) DEFAULT 0,
    can_transact    TINYINT(1) DEFAULT 1,
    restricted_features JSON NULL,
    restriction_reason TEXT NULL,
    billing_zip     VARCHAR(20) NULL,
    billing_address VARCHAR(255) NULL,
    billing_address_two VARCHAR(255) NULL,
    billing_city    VARCHAR(100) NULL,
    billing_state   VARCHAR(100) NULL,
    billing_country VARCHAR(100) NULL,
    settings        TEXT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

#### `password_resets`
```sql
CREATE TABLE password_resets (
    email       VARCHAR(255) NOT NULL INDEX,
    token       VARCHAR(255) NOT NULL,
    created_at  TIMESTAMP NULL
);
```

#### `personal_access_tokens` (Sanctum)
```sql
CREATE TABLE personal_access_tokens (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tokenable_type  VARCHAR(255) NOT NULL,
    tokenable_id    BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(255) NOT NULL,
    token           VARCHAR(64) NOT NULL UNIQUE,
    abilities       TEXT NULL,
    last_used_at    TIMESTAMP NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    INDEX (tokenable_type, tokenable_id)
);
```

#### `otps`
```sql
CREATE TABLE otps (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NULL,
    mobile_number   VARCHAR(20) NULL,
    otp             VARCHAR(10) NOT NULL,
    expires_at      TIMESTAMP NOT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

#### `email_verification_codes`
```sql
CREATE TABLE email_verification_codes (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(255) NOT NULL,
    code        VARCHAR(10) NOT NULL,
    expires_at  TIMESTAMP NOT NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL
);
```

#### `user_ips`
```sql
CREATE TABLE user_ips (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NULL,
    ip_address  VARCHAR(45) NOT NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL
);
```

#### `api_tokens`
```sql
CREATE TABLE api_tokens (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    access_token    LONGTEXT NOT NULL,
    expires_at      TIMESTAMP NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

### 2.2 Catalog / Products

#### `products`
```sql
CREATE TABLE products (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                        VARCHAR(255) NULL,
    sku                         VARCHAR(255) NULL UNIQUE,
    description                 TEXT NULL,
    price                       TEXT NULL,
    currency                    TEXT NULL,
    images                      TEXT NULL,
    minPrice                    VARCHAR(50) NULL,
    maxPrice                    VARCHAR(50) NULL,
    discount_percentage         DECIMAL(5,2) DEFAULT 0,
    CGST                        DECIMAL(5,2) DEFAULT 0,
    SGST                        DECIMAL(5,2) DEFAULT 0,
    IGST                        DECIMAL(5,2) DEFAULT 0,
    slug                        VARCHAR(255) NULL,
    category_id        BIGINT UNSIGNED NULL,
    brand_id                    BIGINT UNSIGNED NULL,
    how_to_redeem      TEXT NULL,
    terms_and_conditions            TEXT NULL,
    custom_description TEXT NULL,
    custom_image                VARCHAR(255) NULL,
    show_product                TINYINT(1) DEFAULT 0,
    priority                    INT DEFAULT 0,
    secondary_priority          INT DEFAULT 0,
    is_special_sku              TINYINT(1) DEFAULT 0,
    out_of_stock                TINYINT(1) DEFAULT 0,
    created_at                  TIMESTAMP NULL,
    updated_at                  TIMESTAMP NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (brand_id) REFERENCES brands(id)
);
```

#### `product_categories`
```sql
CREATE TABLE product_categories (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    url             VARCHAR(255) NULL,
    description     TEXT NULL,
    images          JSON NULL,
    subcategory_name VARCHAR(255) NULL,
    subcategory_url VARCHAR(255) NULL,
    subcategory_description TEXT NULL,
    subcategory_images JSON NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

#### `categories`
```sql
CREATE TABLE categories (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id   BIGINT UNSIGNED NULL,
    order       INT DEFAULT 1,
    name        VARCHAR(255) NOT NULL,
    slug        VARCHAR(255) NOT NULL UNIQUE,
    thumbnail   VARCHAR(255) NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL
);
```

#### `brands`
```sql
CREATE TABLE brands (
    id      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name    VARCHAR(255) NOT NULL,
    slug    VARCHAR(255) NOT NULL UNIQUE,
    logo    VARCHAR(255) NULL,
    order   INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

#### `brand_card_themes`
```sql
CREATE TABLE brand_card_themes (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id  BIGINT UNSIGNED NULL,
    brand_id    BIGINT UNSIGNED NULL,
    brand_name  VARCHAR(255) NULL,
    logo_url    VARCHAR(255) NULL,
    bg_color    VARCHAR(16) NULL,
    text_color  VARCHAR(16) NULL,
    accent_color VARCHAR(16) NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    priority    INT UNSIGNED NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    INDEX idx_brand_card_themes_product_id (product_id),
    INDEX idx_brand_card_themes_brand_id (brand_id),
    INDEX idx_brand_card_themes_brand_name (brand_name),
    INDEX idx_brand_card_themes_is_active (is_active),
    INDEX idx_brand_card_themes_priority (priority)
);
```

#### `category_product` (pivot)
```sql
CREATE TABLE category_product (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id    BIGINT UNSIGNED NOT NULL,
    product_id           BIGINT UNSIGNED NOT NULL,
    created_at              TIMESTAMP NULL,
    updated_at              TIMESTAMP NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

#### `brands` (Value Design)
```sql
CREATE TABLE brands (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    brand_code      VARCHAR(50) NOT NULL UNIQUE,
    name            VARCHAR(255) NULL,
    selling_price   DECIMAL(10,2) NULL,
    discount_percentage DECIMAL(5,2) NULL,
    stock           INT NULL,
    images          JSON NULL,
    redeem_steps    TEXT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

#### `store_details`
```sql
CREATE TABLE store_details (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_code      VARCHAR(50) NULL UNIQUE,
    brand_code      VARCHAR(50) NULL,
    address         VARCHAR(255) NULL,
    city            VARCHAR(100) NULL,
    state           VARCHAR(100) NULL,
    pincode         VARCHAR(10) NULL,
    contact_number  VARCHAR(20) NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

### 2.3 Orders

#### `orders`
```sql
CREATE TABLE orders (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id                     BIGINT UNSIGNED NULL,
    merchant_order_id           CHAR(36) NULL UNIQUE,
    woohoo_order_id             VARCHAR(255) NULL,
    order_status                VARCHAR(50) NULL,
    sku                         VARCHAR(255) NULL,
    product_name                VARCHAR(255) NULL,
    quantity                    INT DEFAULT 1,
    denomination                VARCHAR(50) NULL,
    amount                      DECIMAL(10,2) NULL,
    price                       DECIMAL(10,2) NULL,
    grand_payable_amount        DECIMAL(10,2) NULL,
    discounted_amount_value     DECIMAL(10,2) NULL,
    amount_payable_after_discount DECIMAL(10,2) NULL,
    discount_percentage         DECIMAL(5,2) NULL,
    gst_number                  VARCHAR(50) NULL,
    currency                    VARCHAR(10) NULL,
    country                     VARCHAR(50) NULL,
    sender_first_name           VARCHAR(255) NULL,
    sender_email                VARCHAR(255) NULL,
    sender_phone_no             VARCHAR(20) NULL,
    sender_post_code            VARCHAR(20) NULL,
    sender_address_1            TEXT NULL,
    sender_address_2            TEXT NULL,
    sender_city                 VARCHAR(100) NULL,
    sender_state                VARCHAR(100) NULL,
    receiver_name               VARCHAR(255) NULL,
    receiver_email              VARCHAR(255) NULL,
    receiver_mobile             VARCHAR(20) NULL,
    receiver_msg                TEXT NULL,
    gift_theme_id               BIGINT UNSIGNED NULL,
    gift_message_title          VARCHAR(120) NULL,
    cards                       JSON NULL,
    additionalTxnFields         JSON NULL,
    order_cancel                TINYINT(1) NULL,
    order_payment               VARCHAR(50) NULL,
    gift_send_option            VARCHAR(50) NULL,
    delivery_mode               VARCHAR(50) NULL,
    vd_brand_code               VARCHAR(50) NULL,
    vd_discount                 DECIMAL(5,2) NULL,
    refno                       VARCHAR(255) NULL,
    created_at                  TIMESTAMP NULL,
    updated_at                  TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_order_status (order_status),
    INDEX idx_sku (sku),
    INDEX idx_gift_theme_id (gift_theme_id)
);
```

#### `gift_card_themes`
```sql
CREATE TABLE gift_card_themes (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    slug            VARCHAR(255) NOT NULL UNIQUE,
    thumbnail_url   VARCHAR(255) NULL,
    image_url       VARCHAR(255) NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    sort_order      INT UNSIGNED NOT NULL DEFAULT 0,
    metadata        JSON NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    INDEX idx_gift_card_themes_is_active (is_active),
    INDEX idx_gift_card_themes_sort_order (sort_order)
);
```

#### `order_summaries`
```sql
CREATE TABLE order_summaries (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        BIGINT UNSIGNED NOT NULL,
    payment_id      INT UNSIGNED NULL,
    payment_status  VARCHAR(50) NULL,
    order_status    VARCHAR(50) NULL,
    payment_gateway VARCHAR(50) NULL,
    summary_status  VARCHAR(50) NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
```

### 2.4 Payments

#### `unlimit_payments`
```sql
CREATE TABLE unlimit_payments (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             BIGINT UNSIGNED NULL,
    order_id            BIGINT UNSIGNED NULL,
    merchant_order_id   VARCHAR(255) NULL,
    tracking_id         VARCHAR(255) NULL,
    bank_ref_no         VARCHAR(255) NULL,
    payment_status      VARCHAR(50) NULL,
    order_status        VARCHAR(50) NULL,
    failure_message     TEXT NULL,
    payment_mode        VARCHAR(50) NULL,
    card_name           VARCHAR(100) NULL,
    status_code         VARCHAR(20) NULL,
    status_message      VARCHAR(255) NULL,
    currency            VARCHAR(10) NULL,
    amount              DECIMAL(10,2) NULL,
    price               DECIMAL(10,2) NULL,
    qty                 INT NULL,
    billing_name        VARCHAR(255) NULL,
    billing_address     TEXT NULL,
    billing_city        VARCHAR(100) NULL,
    billing_state       VARCHAR(100) NULL,
    billing_zip         VARCHAR(20) NULL,
    billing_country     VARCHAR(100) NULL,
    billing_tel         VARCHAR(20) NULL,
    billing_email       VARCHAR(255) NULL,
    delivery_name       VARCHAR(255) NULL,
    delivery_address    TEXT NULL,
    delivery_city       VARCHAR(100) NULL,
    delivery_state      VARCHAR(100) NULL,
    delivery_zip        VARCHAR(20) NULL,
    delivery_country    VARCHAR(100) NULL,
    delivery_tel        VARCHAR(20) NULL,
    merchant_param1     VARCHAR(255) NULL,
    merchant_param2     VARCHAR(255) NULL,
    merchant_param3     VARCHAR(255) NULL,
    merchant_param4     VARCHAR(255) NULL,
    merchant_param5     VARCHAR(255) NULL,
    vault               VARCHAR(255) NULL,
    offer_type          VARCHAR(50) NULL,
    offer_code          VARCHAR(50) NULL,
    discount_value      DECIMAL(10,2) NULL,
    mer_amount          DECIMAL(10,2) NULL,
    eci_value           VARCHAR(20) NULL,
    retry               VARCHAR(10) NULL,
    response_code       VARCHAR(20) NULL,
    billing_notes       TEXT NULL,
    trans_date          VARCHAR(50) NULL,
    bin_country         VARCHAR(10) NULL,
    unlimit_response    TEXT NULL,
    raw_callback        TEXT NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_order_id (order_id),
    INDEX idx_merchant_order_id (merchant_order_id)
);
```

#### `payments` (generic payment log)
```sql
CREATE TABLE payments (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    method              VARCHAR(50) NULL,
    merchant_order_id   CHAR(36) NULL,
    status              VARCHAR(50) NULL,
    amount              DECIMAL(10,2) NULL,
    currency            VARCHAR(10) NULL,
    pan_mask            VARCHAR(20) NULL,
    card_type           VARCHAR(20) NULL,
    customer_name       VARCHAR(255) NULL,
    customer_email      VARCHAR(255) NULL,
    customer_phone      VARCHAR(20) NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL
);
```

#### `invoices`
```sql
CREATE TABLE invoices (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id          VARCHAR(255) NULL,
    merchant_order_id   VARCHAR(255) NULL,
    items               JSON NULL,
    customer_email      VARCHAR(255) NULL,
    api_response        JSON NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL
);
```

#### `billings`
```sql
CREATE TABLE billings (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NULL,
    address         TEXT NULL,
    city            VARCHAR(100) NULL,
    state           VARCHAR(100) NULL,
    zip             VARCHAR(20) NULL,
    country         VARCHAR(100) NULL,
    phone           VARCHAR(20) NULL,
    email           VARCHAR(255) NULL,
    order_id        BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

### 2.5 Wallet

#### `wallets`
```sql
CREATE TABLE wallets (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NOT NULL,
    balance     DECIMAL(12,2) DEFAULT 0.00,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_user_id (user_id)
);
```

#### `wallet_transactions`
```sql
CREATE TABLE wallet_transactions (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wallet_id   BIGINT UNSIGNED NOT NULL,
    amount      DECIMAL(12,2) NOT NULL,
    type        ENUM('credit', 'debit') NOT NULL,
    reference   VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
    INDEX idx_wallet_id (wallet_id),
    INDEX idx_type (type)
);
```

### 2.6 KGen / EVC

#### `k_gen_orders`
```sql
CREATE TABLE k_gen_orders (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NULL,
    variant_id      VARCHAR(255) NULL,
    external_ref    VARCHAR(255) NULL UNIQUE,
    mrp             DECIMAL(10,2) NULL,
    payable_price   DECIMAL(10,2) NULL,
    selling_price   DECIMAL(10,2) NULL,
    status          VARCHAR(50) NULL,
    kgen_status     VARCHAR(50) NULL,
    vouchers        JSON NULL,
    api_response    TEXT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_status (user_id, status)
);
```

#### `kgen_products`
```sql
CREATE TABLE kgen_products (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    productID       VARCHAR(255) NOT NULL UNIQUE,
    display_name    VARCHAR(255) NULL,
    description     TEXT NULL,
    attachments     JSON NULL,
    categories      JSON NULL,
    variants        JSON NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

#### `k_gen_wallet_balances`
```sql
CREATE TABLE k_gen_wallet_balances (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    balance     DECIMAL(12,2) DEFAULT 0.00,
    currency    VARCHAR(10) NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL
);
```

#### `evc_requests`
```sql
CREATE TABLE evc_requests (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        VARCHAR(255) NOT NULL UNIQUE,
    distributor     VARCHAR(255) NULL,
    sku             VARCHAR(255) NULL,
    quantity        INT NULL,
    amount          DECIMAL(10,2) NULL,
    req_id          VARCHAR(255) NULL,
    receipt_no      VARCHAR(255) NULL,
    buyer_address   TEXT NULL,
    receiver_name   VARCHAR(255) NULL,
    receiver_email  VARCHAR(255) NULL,
    receiver_mobile VARCHAR(20) NULL,
    gift_send_option VARCHAR(50) NULL,
    vd_brand_code   VARCHAR(50) NULL,
    vd_discount     DECIMAL(5,2) NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

#### `evc_statuses`
```sql
CREATE TABLE evc_statuses (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        VARCHAR(255) NULL,
    request_ref_no  VARCHAR(255) NULL,
    status          VARCHAR(50) NULL,
    details         JSON NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

#### `evc_card_items`
```sql
CREATE TABLE evc_card_items (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    brand_name      VARCHAR(255) NULL,
    card_number     VARCHAR(255) NULL,
    card_pin        VARCHAR(255) NULL,
    balance         DECIMAL(10,2) NULL,
    receipt_no      VARCHAR(255) NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

### 2.7 Content / CMS

#### `homepage_sections`
```sql
CREATE TABLE homepage_sections (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_name    VARCHAR(255) NOT NULL,
    content         JSON NULL,
    status          TINYINT(1) DEFAULT 1
);
```

#### `slides` (storefront hero carousel)

Stores hero banner rows for web and mobile. **Wide asset:** `desktop_image`. **Mobile asset:** `image_mobile` (not `mobile_image`). Other columns include `cta_link`, `custom_url`, `priority`, `display_on_page` (e.g. `homepage`), `status`, optional FKs to `products`, `categories`, `storefront_brands`. See migration `2024_10_09_153402_create_slides_table.php` for the canonical schema.

```sql
-- Illustrative; use migrations as source of truth
CREATE TABLE slides (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    desktop_image       VARCHAR(255) NULL,
    image_mobile        VARCHAR(255) NULL,
    cta_link            VARCHAR(255) NULL,
    custom_url          VARCHAR(255) NULL,
    link_type           VARCHAR(64) NULL,
    product_id          INT UNSIGNED NULL,
    category_id         INT UNSIGNED NULL,
    brand_id            BIGINT UNSIGNED NULL,
    priority            INT NULL,
    status              TINYINT NULL,
    display_on_page     VARCHAR(64) NULL,
    img_alt_tag         VARCHAR(255) NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL
);
```

### 2.8 Operations / Misc

#### `transaction_reports`
```sql
CREATE TABLE transaction_reports (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    start_date  DATE NULL,
    end_date    DATE NULL,
    report_type TEXT NULL,
    request_id  VARCHAR(255) NULL,
    time        TIMESTAMP NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL
);
```

#### `failed_jobs`
```sql
CREATE TABLE failed_jobs (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid        VARCHAR(255) NOT NULL UNIQUE,
    connection  TEXT NOT NULL,
    queue       TEXT NOT NULL,
    payload     LONGTEXT NOT NULL,
    exception   LONGTEXT NOT NULL,
    failed_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 3. New Tables (Architecture v2)

### 3.1 Tenancy

#### `tenants`
```sql
CREATE TABLE tenants (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(255) NOT NULL,
    slug                VARCHAR(255) NOT NULL UNIQUE,
    logo                VARCHAR(255) NULL,
    contact_email       VARCHAR(255) NOT NULL,
    contact_phone       VARCHAR(20) NULL,
    status              ENUM('active', 'suspended', 'inactive') DEFAULT 'active',
    settings            JSON NULL,
    margin_percentage   DECIMAL(5,2) DEFAULT 0.00,
    credit_limit        DECIMAL(12,2) DEFAULT 0.00,
    website             VARCHAR(255) NULL,
    gst_number          VARCHAR(50) NULL,
    address             TEXT NULL,
    city                VARCHAR(100) NULL,
    state               VARCHAR(100) NULL,
    pincode             VARCHAR(10) NULL,
    country             VARCHAR(100) DEFAULT 'India',
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,
    INDEX idx_status (status)
);
```

#### `tenant_users`
```sql
CREATE TABLE tenant_users (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id   BIGINT UNSIGNED NOT NULL,
    user_id     BIGINT UNSIGNED NOT NULL,
    role        ENUM('owner', 'manager', 'operator') DEFAULT 'operator',
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_tenant_user (tenant_id, user_id)
);
```

#### `tenant_products`
```sql
CREATE TABLE tenant_products (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    product_id   BIGINT UNSIGNED NOT NULL,
    custom_price    DECIMAL(10,2) NULL,
    margin_override DECIMAL(5,2) NULL,
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_tenant_product (tenant_id, product_id)
);
```

#### `tenant_payment_gateways`
```sql
CREATE TABLE tenant_payment_gateways (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    gateway         ENUM('ccavenue', 'razorpay', 'unlimit') NOT NULL,
    credentials     TEXT NOT NULL,           -- Encrypted JSON via Laravel Crypt
    is_active       TINYINT(1) DEFAULT 1,
    environment     ENUM('sandbox', 'production') DEFAULT 'sandbox',
    display_name    VARCHAR(255) NULL,
    notes           TEXT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_tenant_gateway (tenant_id, gateway)
);
```

#### `tenant_voucher_providers`
```sql
CREATE TABLE tenant_voucher_providers (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    provider        ENUM('woohoo', 'ezpin', 'gyftrr', 'kgen', 'value_design', 'lysto') NOT NULL,
    credentials     TEXT NOT NULL,           -- Encrypted JSON via Laravel Crypt
    is_active       TINYINT(1) DEFAULT 1,
    priority        INT DEFAULT 0,           -- For failover ordering
    settings        JSON NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_tenant_provider (tenant_id, provider)
);
```

### 3.2 API Keys (Resellers)

#### `api_keys`
```sql
CREATE TABLE api_keys (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NULL,
    user_id         BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(255) NOT NULL,
    key             VARCHAR(64) NOT NULL UNIQUE,     -- Public API key
    secret          VARCHAR(128) NOT NULL,           -- Encrypted HMAC secret
    permissions     JSON NULL,                       -- Allowed endpoints
    rate_limit      INT DEFAULT 120,                 -- Requests per minute
    ip_whitelist    JSON NULL,                       -- Allowed IPs (null = all)
    is_active       TINYINT(1) DEFAULT 1,
    last_used_at    TIMESTAMP NULL,
    expires_at      TIMESTAMP NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_key (key),
    INDEX idx_tenant (tenant_id)
);
```

### 3.3 Offers / Promotions

#### `offers`
```sql
CREATE TABLE offers (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id               BIGINT UNSIGNED NULL,         -- NULL = platform-wide
    name                    VARCHAR(255) NOT NULL,
    description             TEXT NULL,
    type                    ENUM('percentage_discount', 'flat_discount', 'cashback_wallet',
                                 'buy_x_get_y', 'first_order') NOT NULL,
    value                   DECIMAL(10,2) NOT NULL,       -- Discount amount or %
    max_discount            DECIMAL(10,2) NULL,           -- Cap for % discounts
    min_order_amount        DECIMAL(10,2) DEFAULT 0,
    max_uses_total          INT NULL,                     -- NULL = unlimited
    max_uses_per_user       INT DEFAULT 1,
    current_uses            INT DEFAULT 0,
    applicable_products     JSON NULL,                    -- Product IDs, null = all
    applicable_categories   JSON NULL,                    -- Category IDs, null = all
    applicable_brands       JSON NULL,                    -- Brand IDs, null = all
    start_date              TIMESTAMP NOT NULL,
    end_date                TIMESTAMP NOT NULL,
    is_active               TINYINT(1) DEFAULT 1,
    promo_code              VARCHAR(50) NULL UNIQUE,      -- For code-based offers
    auto_apply              TINYINT(1) DEFAULT 0,
    priority                INT DEFAULT 0,                -- Higher = applied first
    banner_image            VARCHAR(255) NULL,
    terms_and_conditions    TEXT NULL,
    created_at              TIMESTAMP NULL,
    updated_at              TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_active_dates (is_active, start_date, end_date),
    INDEX idx_promo_code (promo_code),
    INDEX idx_tenant (tenant_id)
);
```

#### `offer_usages`
```sql
CREATE TABLE offer_usages (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    offer_id    BIGINT UNSIGNED NOT NULL,
    user_id     BIGINT UNSIGNED NOT NULL,
    order_id    BIGINT UNSIGNED NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL,
    created_at  TIMESTAMP NULL,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_offer_user (offer_id, user_id)
);
```

### 3.4 Wallet Enhancement

#### `wallet_load_requests`
```sql
CREATE TABLE wallet_load_requests (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           BIGINT UNSIGNED NULL,
    user_id             BIGINT UNSIGNED NOT NULL,
    amount              DECIMAL(12,2) NOT NULL,
    bank_reference      VARCHAR(255) NOT NULL,       -- UTR / Transaction ref
    bank_name           VARCHAR(255) NULL,
    proof_document      VARCHAR(255) NULL,           -- Upload path
    status              ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by         BIGINT UNSIGNED NULL,
    approved_at         TIMESTAMP NULL,
    rejection_reason    TEXT NULL,
    notes               TEXT NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_tenant_user (tenant_id, user_id)
);
```

### 3.5 Audit & Security

#### `audit_logs`
```sql
CREATE TABLE audit_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NULL,
    user_id         BIGINT UNSIGNED NULL,
    action          VARCHAR(255) NOT NULL,       -- e.g. 'user.created', 'order.refunded'
    auditable_type  VARCHAR(255) NULL,           -- Model class
    auditable_id    BIGINT UNSIGNED NULL,        -- Model ID
    old_values      JSON NULL,
    new_values      JSON NULL,
    ip_address      VARCHAR(45) NULL,
    user_agent      VARCHAR(500) NULL,
    url             VARCHAR(500) NULL,
    created_at      TIMESTAMP NULL,
    INDEX idx_tenant (tenant_id),
    INDEX idx_user (user_id),
    INDEX idx_auditable (auditable_type, auditable_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
);
```

#### `two_factor_secrets`
```sql
CREATE TABLE two_factor_secrets (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL UNIQUE,
    secret          VARCHAR(255) NOT NULL,       -- Encrypted TOTP secret
    recovery_codes  TEXT NULL,                   -- Encrypted JSON array
    confirmed_at    TIMESTAMP NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### `ip_whitelists`
```sql
CREATE TABLE ip_whitelists (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id   BIGINT UNSIGNED NULL,
    ip_address  VARCHAR(45) NOT NULL,
    label       VARCHAR(255) NULL,
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant_ip (tenant_id, ip_address)
);
```

### 3.6 Notifications

#### `notifications` (Laravel default)
```sql
CREATE TABLE notifications (
    id              CHAR(36) PRIMARY KEY,
    type            VARCHAR(255) NOT NULL,
    notifiable_type VARCHAR(255) NOT NULL,
    notifiable_id   BIGINT UNSIGNED NOT NULL,
    data            TEXT NOT NULL,
    read_at         TIMESTAMP NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    INDEX idx_notifiable (notifiable_type, notifiable_id)
);
```

### 3.7 Queue (required for database driver)

#### `jobs`
```sql
CREATE TABLE jobs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue           VARCHAR(255) NOT NULL,
    payload         LONGTEXT NOT NULL,
    attempts        TINYINT UNSIGNED NOT NULL,
    reserved_at     INT UNSIGNED NULL,
    available_at    INT UNSIGNED NOT NULL,
    created_at      INT UNSIGNED NOT NULL,
    INDEX idx_queue (queue)
);
```

### 3.8 Security & Threat Detection

#### `security_event_logs`
```sql
CREATE TABLE security_event_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type      VARCHAR(50) NOT NULL,
    severity        ENUM('info', 'low', 'medium', 'high', 'critical') NOT NULL,
    ip_address      VARCHAR(45) NULL,
    user_id         BIGINT UNSIGNED NULL,
    tenant_id       BIGINT UNSIGNED NULL,
    user_agent      TEXT NULL,
    request_url     VARCHAR(2048) NULL,
    request_method  VARCHAR(10) NULL,
    country_code    CHAR(2) NULL,
    city            VARCHAR(100) NULL,
    is_vpn          BOOLEAN DEFAULT FALSE,
    device_id       VARCHAR(255) NULL,
    metadata        JSON NULL,
    resolved        BOOLEAN DEFAULT FALSE,
    resolved_by     BIGINT UNSIGNED NULL,
    resolved_at     TIMESTAMP NULL,
    resolution_note TEXT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_event_type (event_type),
    INDEX idx_severity (severity),
    INDEX idx_ip (ip_address),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    INDEX idx_unresolved (resolved, severity, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
);
```

#### `blocked_ips`
```sql
CREATE TABLE blocked_ips (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address    VARCHAR(45) NOT NULL,
    reason        TEXT NOT NULL,
    blocked_at    TIMESTAMP NOT NULL,
    expires_at    TIMESTAMP NULL,
    auto_blocked  BOOLEAN DEFAULT FALSE,
    blocked_by    BIGINT UNSIGNED NULL,
    block_count   INT UNSIGNED DEFAULT 1,
    permanent     BOOLEAN DEFAULT FALSE,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE INDEX idx_blocked_ip (ip_address),
    INDEX idx_expires (expires_at),
    FOREIGN KEY (blocked_by) REFERENCES users(id) ON DELETE SET NULL
);
```

---

## 4. Migration Plan

### New Migrations to Create

| Migration | Table | Purpose |
|-----------|-------|---------|
| `create_tenants_table` | `tenants` | B2B client organizations |
| `create_tenant_users_table` | `tenant_users` | User-tenant pivot |
| `create_tenant_products_table` | `tenant_products` | Tenant product access |
| `create_tenant_payment_gateways_table` | `tenant_payment_gateways` | Per-tenant gateway creds |
| `create_tenant_voucher_providers_table` | `tenant_voucher_providers` | Per-tenant provider creds |
| `create_api_keys_table` | `api_keys` | Reseller API keys |
| `create_offers_table` | `offers` | Promotions/deals |
| `create_offer_usages_table` | `offer_usages` | Offer usage tracking |
| `create_wallet_load_requests_table` | `wallet_load_requests` | Bank load requests |
| `create_audit_logs_table` | `audit_logs` | Admin action audit trail |
| `create_two_factor_secrets_table` | `two_factor_secrets` | 2FA TOTP secrets |
| `create_ip_whitelists_table` | `ip_whitelists` | Per-tenant IP whitelist |
| `create_notifications_table` | `notifications` | Laravel notifications |
| `create_jobs_table` | `jobs` | Queue jobs |
| `add_tenant_id_to_orders_table` | `orders` | Add tenant scoping |
| `add_tenant_id_to_wallets_table` | `wallets` | Add tenant scoping |
| `add_source_provider_to_products` | `products` | Track product source |
| `create_security_event_logs_table` | `security_event_logs` | Security threat tracking |
| `create_blocked_ips_table` | `blocked_ips` | Auto/manual IP blocking |

### Columns to Add to Existing Tables

| Table | Column | Type | Purpose |
|-------|--------|------|---------|
| `orders` | `tenant_id` | BIGINT UNSIGNED NULL FK | Tenant scoping |
| `orders` | `offer_id` | BIGINT UNSIGNED NULL FK | Applied offer |
| `orders` | `offer_discount` | DECIMAL(10,2) NULL | Discount amount |
| `wallets` | `tenant_id` | BIGINT UNSIGNED NULL FK | Tenant scoping |
| `products` | `source_provider` | VARCHAR(50) NULL | woohoo/ezpin/gyftrr/kgen/vd |
| `products` | `last_synced_at` | TIMESTAMP NULL | Last catalog sync |
| `products` | `sync_status` | VARCHAR(20) NULL | synced/pending/error |
| `users` | `two_factor_enabled` | TINYINT(1) DEFAULT 0 | 2FA flag |
| `users` | `registration_country` | CHAR(2) NULL | GeoIP at signup, for fraud checks |
| `orders` | `device_fingerprint` | VARCHAR(255) NULL | Device used for purchase |
| `orders` | `code_view_count` | INT UNSIGNED DEFAULT 0 | Times voucher codes viewed |
| `orders` | `purchase_ip` | VARCHAR(45) NULL | IP at time of purchase |
| `orders` | `purchase_country` | CHAR(2) NULL | GeoIP at time of purchase |

---

## 5. Index Strategy

### Performance-Critical Indexes

| Table | Index | Columns | Reason |
|-------|-------|---------|--------|
| `orders` | `idx_tenant_status` | `(tenant_id, order_status)` | Tenant-scoped order queries |
| `orders` | `idx_user_created` | `(user_id, created_at)` | User order history |
| `products` | `idx_visible_priority` | `(show_product, priority)` | Catalog browsing |
| `wallet_transactions` | `idx_wallet_created` | `(wallet_id, created_at)` | Transaction history |
| `audit_logs` | `idx_created` | `(created_at)` | Time-range queries |
| `offers` | `idx_active_dates` | `(is_active, start_date, end_date)` | Active offer lookup |
| `api_keys` | `idx_key` | `(key)` | API key lookup on every request |
| `security_event_logs` | `idx_event_created` | `(event_type, created_at)` | Security event timeline |
| `security_event_logs` | `idx_unresolved` | `(resolved, severity, created_at)` | Unresolved threat dashboard |
| `blocked_ips` | `idx_blocked_ip` | `(ip_address)` | Fast IP blocklist lookup |

---

## 6. Relationship Map

See [DATABASE_DICTIONARY.md](DATABASE_DICTIONARY.md) for the complete column-level data dictionary with all foreign key relationships mapped.

---

## 7. Catalog table names (current migrations)

Greenfield installs create the voucher/catalog data in **`products`**, **`synced_categories`** (API category mirror), and **`orders`** directly—there are no `qs_products` / `qs_categories` / `qs_orders` **table** names in current migrations. Voyager CMS still uses the **`categories`** table separately.

Some **legacy SQL backups** under `public/` may still define old `qs_*` tables; treat those as historical only.

| Legacy dump name (old) | Current schema |
|------------------------|----------------|
| `qs_products` | `products` |
| `qs_categories` (when used for API categories) | `synced_categories` |
| `qs_orders` | `orders` |

Current schema uses **`storefront_brands`** for homepage/catalog brands. Old SQL dumps may still contain `amazepay_available_brands`; run `php artisan migrate:fresh` on greenfield or add a one-off rename if you must keep data.

**Column renames** (historical / content migrations):

| Table | Old Column | New Column | Reason |
|-------|-----------|------------|--------|
| `products` | `amazepay_product_description` | `custom_description` | Clean naming |
| `products` | `amazepay_how_to_redeem` | `how_to_redeem` | Remove prefix |
| `products` | `amazepay_t_and_c` | `terms_and_conditions` | Descriptive name |
| `products` | `amazepay_category_id` | `category_id` | Remove prefix |
| `orders` | `qs_product_id` | `product_id` | Remove prefix |

**Model renames:**

| Old Model | New Model | Table |
|-----------|-----------|-------|
| `QsProduct` | `Product` | `products` |
| `QsOrder` | `Order` | `orders` |
| `QsCategory` | `ProductCategory` | `product_categories` (if split) or `Category` |
| *(legacy name)* | `Category` | `categories` |
| `AmazepayAvailableBrand` | `Brand` | `brands` |

---

## Related Documents

- [DATABASE_DICTIONARY.md](DATABASE_DICTIONARY.md) -- Column-level details and relationship mapping
- [ARCHITECTURE.md](ARCHITECTURE.md) -- System architecture overview
- [CODE_STANDARDS.md](CODE_STANDARDS.md) -- Avoid logging sensitive column values in app logs
- [B2B_TENANCY.md](B2B_TENANCY.md) -- Tenant scoping details
- [SECURITY.md](SECURITY.md) -- Security event logs and blocked IPs tables
