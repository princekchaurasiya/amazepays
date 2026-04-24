# AmazePays

B2B and B2C voucher/gift card distribution platform built on **Laravel 13** and **PHP 8.3**.

## Requirements

- **PHP 8.3+** -- Laravel 13 requires PHP 8.3 minimum
- **Composer 2.x**
- **Node.js 18+** for Vite / Inertia (`npm run dev`)
- **MySQL 8.0+** or MariaDB 10.6+
- **Redis** (for queues, caching, and rate limiting)

### Windows Setup

XAMPP ships PHP 8.2. Install PHP 8.3 alongside:

```powershell
winget install PHP.PHP.8.3 --accept-package-agreements
```

Enable extensions in `php.ini`: `openssl`, `curl`, `mbstring`, `fileinfo`, `intl`, `mysqli`, `pdo_mysql`, `sodium`, `zip`, `gd`.

In each terminal:

```powershell
. .\scripts\use-php83.ps1
```

## Quick Start

```powershell
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate:fresh --seed
npm install && npm run dev
php artisan serve
```

Horizon declares `ext-pcntl` / `ext-posix` (Unix-only). Composer `platform` stubs are configured for Windows development. Run queue workers on Linux/WSL/Docker in production.

## Database Baseline (Phase 3)

We maintain a **fresh, domain-grouped migration baseline** in `database/migrations/` and keep historical/legacy migrations in `database/migrations_archive/`.

- **Reset + seed (recommended for local dev)**:

```powershell
php artisan migrate:fresh --seed
```

- **Run guard tests (schema discipline)**:

```powershell
php artisan test
```

See `[docs/DATABASE_BASELINE.md](docs/DATABASE_BASELINE.md)` for the baseline table list, cross-domain FK wiring, and cascade rules.

## Project Structure

```
app/
  Console/Commands/     # Artisan commands (SyncVoucherCatalog, etc.)
  Contracts/            # Interfaces (PaymentGatewayInterface, VoucherProviderInterface)
  Enums/                # ResponseCode, ProviderErrorCode (central API/provider outcomes)
  Support/              # ProviderResponseTranslator, etc.
  Data/                 # Immutable DTOs (OrderData, BillingData, PricingResult)
  Events/               # Domain events
  Exceptions/           # Custom exceptions (InsufficientBalanceException, etc.)
  Helpers/              # CommonHelper + global helpers (money(), tenant_id(), audit())
  Http/
    Controllers/
      Admin/            # Inertia admin panel controllers
      Api/V1/           # Mobile app + B2B JSON API
      Payment/          # Payment gateway callback controllers
      Storefront/       # Storefront helpers (e.g. checkout); public pages use root controllers + Blade
      Voucher/          # Voucher provider callback controllers
    Middleware/          # Auth, rate limiting, VPN detection, security
    Requests/           # Form Request classes (Admin/, Api/, Storefront/, Payment/)
    Traits/             # ApiResponse trait
  Listeners/
  Models/
  Providers/
  Scopes/               # TenantScope for multi-tenancy
  Services/
    Catalog/            # CatalogSyncService
    Order/              # OrderCreationService, OrderStatusMachine, OrderNotificationService
    Payment/            # Gateway implementations (CCAvenue, Razorpay, Unlimit)
    Pricing/            # PricingService, DiscountResolver (single source of truth)
    Voucher/            # Provider implementations (Woohoo, KGen, ValueDesign, etc.)
    Wallet/             # WalletService (with lockForUpdate)
routes/
  web.php               # Public storefront
  auth.php              # Login, registration, OTP
  checkout.php          # Checkout flows, order creation
  payments.php          # Payment initiation + return URLs
  webhooks.php          # Inbound payment callbacks
  admin.php             # Inertia admin panel (/panel)
  api.php               # Mobile app + B2B API (/api/v1)
  console.php           # Scheduled tasks
docs/
  ARCHITECTURE.md       # System design
  CODE_STANDARDS.md     # Coding conventions
  ROUTE_MAP.md          # Complete route reference
  DATABASE_SCHEMA.md    # Database structure
  API_DOCUMENTATION.md  # API reference
  PAYMENT_GATEWAYS.md   # Payment integration guide
  VOUCHER_PROVIDERS.md  # Voucher provider integration guide
  SECURITY.md           # Security measures
  B2B_TENANCY.md        # Multi-tenant architecture
  ADMIN_PANEL.md        # Admin panel guide
  WALLET_SYSTEM.md      # Wallet system design
```

## Key Architectural Decisions

1. **PricingService** -- Single source of truth for all monetary calculations. Frontend never sends amounts.
2. **lockForUpdate()** -- All financial operations use pessimistic locking to prevent race conditions.
3. **Order State Machine** -- Status transitions enforced by `OrderStatusMachine::transition()`.
4. **ApiResponse trait** -- Standardized JSON envelope on every API response.
5. **Form Requests** -- All input validated via dedicated request classes; controllers only use `$request->validated()`.
6. **Idempotency** -- Payment callbacks and wallet operations use idempotency keys.

## Documentation

See the `docs/` folder for comprehensive documentation on each subsystem.
