# AmazePays Platform Architecture

> **Version:** 2.0  
> **Last Updated:** April 2026  
> **Status:** Active -- Phases 1-7, 9 implemented

---

## Table of Contents

1. [Overview](#1-overview)
2. [High-Level Architecture](#2-high-level-architecture)
3. [Technology Stack](#3-technology-stack)
4. [Client Layer](#4-client-layer)
5. [API Gateway Layer](#5-api-gateway-layer)
6. [Core Business Layer](#6-core-business-layer)
7. [External Integrations](#7-external-integrations)
8. [Data Layer](#8-data-layer)
9. [Deployment Architecture](#9-deployment-architecture)
10. [Migration Roadmap](#10-migration-roadmap)

---

## 1. Overview

AmazePays is a B2B and B2C voucher/gift card distribution platform that aggregates multiple voucher providers (Woohoo, EZ Pin, Gyftrr, KGen, Value Design, Lysto/Athena) and payment gateways (CCAvenue, Razorpay, Unlimit) into a single unified platform.

### Core Capabilities

- **Voucher Aggregation** -- Unified catalog from multiple providers with automated sync
- **Multi-Tenant B2B** -- Isolated client portals with custom pricing, margins, and catalog access
- **B2C Storefront** -- Consumer-facing store with wallet, offers, and gift card delivery
- **Payment Abstraction** -- Pluggable payment gateways configurable per tenant via admin UI
- **Wallet System** -- Per-user wallets with bank load, credit/debit, and transaction history
- **Reseller & Loyalty APIs** -- Public APIs for third-party integrations
- **Mobile Apps** -- iOS and Android via React Native
- **Promotional Offers** -- Backend-managed offers/deals on voucher cards with CRUD operations

---

## 2. High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                         CLIENT LAYER                                │
│                                                                     │
│  ┌──────────┐ ┌───────────┐ ┌──────────┐ ┌────────┐ ┌───────────┐ │
│  │  Admin    │ │ B2B Client│ │   B2C    │ │ Mobile │ │ Reseller  │ │
│  │  Panel    │ │  Portal   │ │  Store   │ │  App   │ │ API       │ │
│  │(Inertia  │ │(Inertia   │ │(Blade/   │ │(React  │ │Consumers  │ │
│  │ + React) │ │ + React)  │ │ React)   │ │Native) │ │           │ │
│  └────┬─────┘ └─────┬─────┘ └────┬─────┘ └───┬────┘ └─────┬─────┘ │
└───────┼─────────────┼────────────┼────────────┼────────────┼────────┘
        │             │            │            │            │
┌───────┴─────────────┴────────────┴────────────┴────────────┴────────┐
│                       API GATEWAY LAYER                             │
│                                                                     │
│  ┌──────────────────┐ ┌─────────────────┐ ┌──────────────────────┐ │
│  │  Inertia Routes  │ │  REST API v1    │ │  Webhook Handlers    │ │
│  │  (Admin + B2B)   │ │  (Mobile +      │ │  (Payment Callbacks) │ │
│  │                  │ │   Resellers)    │ │                      │ │
│  └────────┬─────────┘ └───────┬─────────┘ └──────────┬───────────┘ │
└───────────┼───────────────────┼──────────────────────┼──────────────┘
            │                   │                      │
┌───────────┴───────────────────┴──────────────────────┴──────────────┐
│                     CORE BUSINESS LAYER                             │
│                                                                     │
│  ┌────────────┐ ┌──────────────┐ ┌──────────────┐ ┌─────────────┐ │
│  │ Auth       │ │ Voucher      │ │ Payment      │ │ Wallet      │ │
│  │ Service    │ │ Engine       │ │ Gateway      │ │ Service     │ │
│  │(Sanctum    │ │(EZPin/Gyftrr │ │ Abstraction  │ │(Bank Load)  │ │
│  │ + RBAC)   │ │ /Woohoo)     │ │              │ │             │ │
│  └────────────┘ └──────────────┘ └──────────────┘ └─────────────┘ │
│  ┌────────────┐ ┌──────────────┐ ┌──────────────┐ ┌─────────────┐ │
│  │ Order      │ │ Tenant       │ │ Offer/Promo  │ │ Notification│ │
│  │ Service    │ │ Scoping      │ │ Engine       │ │ Service     │ │
│  │            │ │ Service      │ │              │ │             │ │
│  └────────────┘ └──────────────┘ └──────────────┘ └─────────────┘ │
└──────────┬──────────────────────────────────┬───────────────────────┘
           │                                  │
┌──────────┴──────────────┐    ┌──────────────┴───────────────────────┐
│   EXTERNAL SERVICES     │    │          DATA LAYER                  │
│                         │    │                                      │
│  Woohoo API             │    │  MySQL (Single DB, Tenant Scoped)    │
│  EZ Pin API             │    │  Redis (Cache + Queue + Sessions)    │
│  Gyftrr API             │    │  File Storage (Encrypted Creds)      │
│  KGen API               │    │  S3/Local (Media + Documents)        │
│  Value Design API       │    │                                      │
│  Lysto/Athena API       │    │                                      │
│  CCAvenue               │    │                                      │
│  Razorpay               │    │                                      │
│  Unlimit                │    │                                      │
│  Bank APIs              │    │                                      │
└─────────────────────────┘    └──────────────────────────────────────┘
```

---

## 3. Technology Stack

### Backend

| Component | Current | Target |
|-----------|---------|--------|
| Framework | Laravel 8 | **Laravel 13** |
| PHP | 7.3 / 8.0 | **PHP 8.3+** |
| Admin Panel | Voyager 1.6 | **Inertia.js + React** (custom) |
| Auth | Sanctum 2.x | **Sanctum 4.x** + Spatie Permission |
| Queue | sync | **Redis** (Laravel Horizon) |
| Cache | file | **Redis** |
| Search | DB queries | **Laravel Scout + Meilisearch** (optional) |

### Frontend (Admin + B2B Portal)

| Component | Technology |
|-----------|-----------|
| SPA Framework | Inertia.js v2 |
| UI Library | React 18 + TypeScript |
| CSS | Tailwind CSS v4 |
| Component Library | shadcn/ui |
| Build Tool | Vite |
| State Management | React Context + Inertia shared data |
| Tables / Data Grids | TanStack Table |
| Forms | React Hook Form + Zod validation |
| Charts | Recharts |

### Mobile App

| Component | Technology |
|-----------|-----------|
| Framework | React Native (Expo) |
| Language | TypeScript |
| Navigation | React Navigation v7 |
| State | Zustand |
| API Client | Axios + TanStack Query |
| Secure Storage | react-native-keychain |
| Push Notifications | Firebase Cloud Messaging |

### Infrastructure

| Component | Technology |
|-----------|-----------|
| Database | MySQL 8.0 |
| Cache / Queue | Redis 7.x |
| Web Server | Nginx + PHP-FPM |
| Process Manager | Laravel Horizon (queue dashboard) |
| Scheduler | Laravel Task Scheduling (cron) |
| CI/CD | GitHub Actions or GitLab CI |
| Monitoring | Laravel Telescope (dev), Sentry (prod) |

---

## 4. Client Layer

### 4.1 Admin Panel (Inertia + React)

Replaces Voyager. Full CRUD for all entities with policy-based authorization. No direct database exposure.

**Modules:** Dashboard, Users, Tenants (B2B Clients), Products, Orders, Payments, Wallets, Offers/Promotions, Reports, API Keys, Settings, Audit Log.

See [ADMIN_PANEL.md](ADMIN_PANEL.md) for detailed module specifications.

### 4.2 B2B Client Portal (Inertia + React)

Tenant-scoped portal where B2B clients manage their own operations:
- View assigned product catalog with custom pricing
- Place orders (single and bulk)
- Manage wallet (request bank loads, view balance)
- View order history and download reports
- Manage sub-users (operators)

### 4.3 B2C Storefront

**UI stack:** Blade templates, **Tailwind CSS** (via Vite `resources/css/app.css`), and legacy jQuery/Slick where still used on long-form pages. Public header uses a Hubble-style layout (`layouts/partials/storefront-header.blade.php`, `category-nav.blade.php`). B2B marketing landing: `GET /business` (`StorefrontBusinessController`), linking to the authenticated B2B portal under `/panel/b2b`.

Consumer-facing storefront:
- Browse/search voucher catalog by category/brand
- Purchase vouchers via payment gateways
- Wallet-based payments
- Order tracking and voucher delivery (email, SMS, in-app)
- Promotional offers and deals

**B2C Admin CRUD:** Full backend management of the consumer storefront including:
- Product visibility, pricing, descriptions, images
- Category and brand management
- Homepage section management (banners, featured products, deals)
- Offer/promotion creation with rules (discount %, date range, min order, eligible products)

### 4.4 Mobile App (React Native)

Cross-platform iOS/Android app consuming REST API v1. See [MOBILE_APP.md](MOBILE_APP.md).

### 4.5 Reseller / Loyalty API Consumers

Third-party systems consuming the public REST API. See [API_DOCUMENTATION.md](API_DOCUMENTATION.md).

---

## 5. API Gateway Layer

### Route Groups

| Route Prefix | Middleware | Purpose |
|--------------|-----------|---------|
| `/admin/*` | `auth`, `verified`, `role:super-admin\|admin` | Inertia admin panel |
| `/b2b/*` | `auth`, `verified`, `role:b2b-client`, `tenant.scope` | B2B client portal |
| `/api/v1/*` | `auth:sanctum`, `throttle:api` | Mobile app API |
| `/api/v1/reseller/*` | `verify.api-key`, `verify.hmac`, `throttle:reseller` | Reseller API |
| `/api/v1/loyalty/*` | `client.credentials`, `throttle:loyalty` | Loyalty program API |
| `/webhooks/*` | `verify.signature`, `throttle:webhooks` | Payment callbacks |

### Middleware Stack

```
Global:
  ├── ForceHttps (production)
  ├── SecurityHeaders (CSP, HSTS, X-Frame-Options)
  ├── ThreatDetection (IP blocklist, attack payload scan)
  ├── DetectVpnProxy (multi-source VPN/proxy/Tor detection)
  └── TrustProxies

Web Group:
  ├── EncryptCookies
  ├── Session
  ├── VerifyCsrfToken
  └── Inertia (HandleInertiaRequests)

API Group:
  ├── Throttle (per-group limits)
  ├── Sanctum / API Key auth
  └── TenantScope (auto-scope queries)
```

---

## 6. Core Business Layer

### Service Architecture

All business logic lives in service classes under `app/Services/`. Controllers are thin -- they validate input, call services, and return responses. HTTP input must use Form Requests / `$request->validated()` or whitelisted `$request->only()`—never `$request->all()` for validation, sessions, or logging (see [CODE_STANDARDS.md](CODE_STANDARDS.md#http-request-input--logging)).

```
app/
├── Contracts/
│   ├── PaymentGatewayInterface.php
│   ├── VoucherProviderInterface.php
│   └── NotificationChannelInterface.php
├── Services/
│   ├── Payment/
│   │   ├── PaymentGatewayFactory.php
│   │   ├── CCAvenueGateway.php
│   │   ├── RazorpayGateway.php
│   │   └── UnlimitGateway.php
│   ├── Voucher/
│   │   ├── VoucherProviderFactory.php
│   │   ├── WoohooProvider.php
│   │   ├── EZPinProvider.php
│   │   ├── GyftrProvider.php
│   │   ├── KGenProvider.php
│   │   └── ValueDesignProvider.php
│   ├── WalletService.php
│   ├── OrderService.php
│   ├── TenantService.php
│   ├── OfferService.php
│   ├── AuditService.php
│   └── NotificationService.php
├── Scopes/
│   └── TenantScope.php
├── Policies/
│   ├── OrderPolicy.php
│   ├── ProductPolicy.php
│   ├── TenantPolicy.php
│   └── WalletPolicy.php
└── Events/
    ├── OrderPlaced.php
    ├── OrderCompleted.php
    ├── WalletCredited.php
    └── PaymentReceived.php
```

---

## 7. External Integrations

### Voucher Providers

| Provider | Status | API Type | Auth |
|----------|--------|----------|------|
| Woohoo | Active (deep integration) | REST v3 | OAuth2 (client credentials) |
| EZ Pin | Planned | REST | API Key + Secret |
| Gyftrr | Planned | REST | API Key |
| KGen | Active | REST | API Key |
| Value Design (VD) | Active | REST | API Key + Partner ID |
| Lysto/Athena | Active | REST v1 | Bearer Token + Partner ID |

### Payment Gateways

| Gateway | Status | Integration Type |
|---------|--------|-----------------|
| CCAvenue | Active | Server-to-server redirect |
| Unlimit | Active | API + Webhook callback |
| Razorpay | Planned | API + Webhook callback |

---

## 8. Data Layer

### Database Strategy

Single MySQL database with tenant-scoped data isolation:
- All tenant-bound tables include `tenant_id` column
- Global Eloquent scope auto-filters by authenticated user's tenant
- Super Admin bypasses tenant scope for cross-tenant operations
- Sensitive credentials encrypted at rest via Laravel's `encrypted` cast

See [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) and [DATABASE_DICTIONARY.md](DATABASE_DICTIONARY.md) for full schema details.

### Caching Strategy

| Data | Cache Driver | TTL | Invalidation |
|------|-------------|-----|-------------|
| Product catalog | Redis | 15 min | On sync job |
| User sessions | Redis | 120 min | On logout |
| Tenant config | Redis | 60 min | On settings update |
| Rate limit counters | Redis | per-window | Automatic |
| Provider wallet balances | Redis | 5 min | On transaction |
| VPN/proxy check results | Redis | 24 hrs | Manual flush |
| IP blocklist | Redis | 1 hr | On block/unblock |
| Request rate counters | Redis | 1 min | Automatic |

### Queue Strategy

| Queue | Priority | Workers | Jobs |
|-------|----------|---------|------|
| `payments` | High | 3 | Payment verification, webhook processing |
| `orders` | High | 3 | Order placement, status polling |
| `notifications` | Medium | 2 | Email, SMS, push notifications |
| `sync` | Low | 1 | Catalog sync, report generation |
| `security` | High | 2 | Fraud checks, threat alerts, IP blocking |
| `default` | Low | 1 | Miscellaneous |

---

## 9. Deployment Architecture

```
                    ┌──────────────┐
                    │   Cloudflare  │
                    │   (CDN/WAF)   │
                    └──────┬───────┘
                           │
                    ┌──────┴───────┐
                    │    Nginx     │
                    │  (Reverse    │
                    │   Proxy)     │
                    └──────┬───────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
       ┌──────┴─────┐ ┌───┴────┐ ┌────┴──────┐
       │  PHP-FPM   │ │ Horizon│ │ Scheduler │
       │  (Laravel) │ │(Queue  │ │  (Cron)   │
       │            │ │Workers)│ │           │
       └──────┬─────┘ └───┬────┘ └────┬──────┘
              │            │           │
              └────────────┼───────────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
       ┌──────┴─────┐ ┌───┴────┐ ┌────┴──────┐
       │   MySQL    │ │  Redis │ │ File      │
       │   8.0      │ │  7.x   │ │ Storage   │
       └────────────┘ └────────┘ └───────────┘
```

---

## 10. Migration Roadmap

### Phase 1: Foundation (4-6 weeks)

- Upgrade Laravel 8 → 13 (through 9 → 10 → 11 → 12 → 13)
- Remove Voyager, install Inertia.js + React
- Implement Spatie laravel-permission (RBAC)
- Build admin shell (auth, layout, dashboard)
- Payment gateway abstraction layer
- Encrypted credential vault

### Phase 2: B2B Platform (3-4 weeks)

- Tenant system (models, scopes, middleware)
- B2B client portal
- Wallet bank load with approval workflow
- EZ Pin + Gyftrr provider integration
- Voucher provider abstraction layer
- B2C admin CRUD (products, categories, offers)

### Phase 3: APIs + Mobile (4-6 weeks)

- REST API v1 (mobile endpoints)
- Reseller API with HMAC auth
- Loyalty program API
- React Native app (B2C)
- API key management in admin

### Phase 4: Security + Polish (2-3 weeks)

- 2FA for admin/B2B users
- Audit logging
- Security headers and hardening
- Penetration testing
- Documentation finalization

---

## Related Documents

- [SYSTEM_DESIGN.md](SYSTEM_DESIGN.md) -- Detailed system design with data flows
- [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) -- Table definitions and migrations
- [DATABASE_DICTIONARY.md](DATABASE_DICTIONARY.md) -- Column-level data dictionary with relationships
- [PAYMENT_GATEWAYS.md](PAYMENT_GATEWAYS.md) -- Payment gateway integration guide
- [VOUCHER_PROVIDERS.md](VOUCHER_PROVIDERS.md) -- Voucher provider integration guide
- [API_DOCUMENTATION.md](API_DOCUMENTATION.md) -- REST API specification
- [CODE_STANDARDS.md](CODE_STANDARDS.md) -- Request validation, webhooks, logging
- [B2B_TENANCY.md](B2B_TENANCY.md) -- Multi-tenant architecture
- [MOBILE_APP.md](MOBILE_APP.md) -- React Native app architecture
- [SECURITY.md](SECURITY.md) -- Security measures and policies
- [ADMIN_PANEL.md](ADMIN_PANEL.md) -- Admin panel modules
- [WALLET_SYSTEM.md](WALLET_SYSTEM.md) -- Wallet architecture
- [DEPLOYMENT.md](DEPLOYMENT.md) -- Deployment and CI/CD
- [REQUIREMENT_QUESTIONS.md](REQUIREMENT_QUESTIONS.md) -- Open questions for refinement
