# AmazePays Admin Panel Architecture

> **Version:** 2.0  
> **Last Updated:** April 2026  
> **Tech Stack:** Laravel 13 + Inertia.js v2 + React 18 + TypeScript + Tailwind CSS + shadcn/ui

---

## Table of Contents

1. [Overview](#1-overview)
2. [Voyager Migration Plan](#2-voyager-migration-plan)
3. [Tech Stack Details](#3-tech-stack-details)
4. [Module Specifications](#4-module-specifications)
5. [Component Architecture](#5-component-architecture)
6. [Routing Structure](#6-routing-structure)
7. [Shared Data (Inertia)](#7-shared-data-inertia)
8. [File Structure](#8-file-structure)

---

## 1. Overview

The admin panel replaces the existing Voyager 1.6 installation, eliminating direct database access (BREAD) and providing a secure, custom-built interface with policy-based authorization on every action.

### Why Replace Voyager

| Concern | Voyager | Custom (Inertia + React) |
|---------|---------|--------------------------|
| DB access | Raw BREAD (any table) | Controller-mediated only |
| Authorization | Role-based (coarse) | Permission-based (granular) |
| Audit trail | None | Full audit logging |
| 2FA | Not supported | TOTP integration |
| UI customization | Limited (override Blade views) | Full control (React components) |
| Multi-tenancy | Not tenant-aware | Built-in tenant scoping |
| API consistency | Separate from API | Shared services layer |
| Maintenance | Abandoned (v1.6, Laravel 8) | Active ecosystem |

---

## 2. Voyager Migration Plan

### Phase 1: Parallel Installation

```
1. Upgrade Laravel 8 → 13 (incremental: 9 → 10 → 11 → 12 → 13)
2. Install Inertia.js + React + Vite
3. Build admin shell (layout, auth, dashboard)
4. Keep Voyager running on /admin (legacy)
5. New admin on /panel (temporary)
```

### Phase 2: Module Migration

Migrate one module at a time. Each module:
1. Build React pages + backend controller
2. Test thoroughly
3. Disable corresponding Voyager BREAD
4. Redirect old Voyager URL to new panel URL

**Migration order:**

| Order | Module | Reason |
|-------|--------|--------|
| 1 | Dashboard | Simple, proves the stack works |
| 2 | Products | Most used, complex UI needed |
| 3 | Orders | Read-heavy, needs good search/filter |
| 4 | Users | Auth-critical, proves RBAC |
| 5 | Wallets | Financial, needs audit trail |
| 6 | Payment Gateways | Credential vault is new |
| 7 | Tenants | New functionality |
| 8 | Offers | New functionality |
| 9 | Reports | Data-heavy, needs exports |
| 10 | API Keys | New functionality |
| 11 | Settings | System config migration |
| 12 | Audit Logs | New functionality |
| 13 | Security Dashboard | VPN detection, fraud queue, blocked IPs |

### Phase 3: Voyager Removal -- COMPLETE

All Voyager code has been removed:

- `User` model now extends `Authenticatable` directly
- `config/voyager.php`, `VoyagerServiceProvider`, all Voyager controllers and views deleted
- 18 Voyager seeders deleted; `DatabaseSeeder` cleaned
- All Blade views migrated from `Voyager::image()` to `Storage::url()`
- Route file cleaned: dead `if (class_exists(Voyager))` block removed
- `CommonHelper.php` and `Handler.php` cleaned of Voyager references

---

## 3. Tech Stack Details

### Backend

```php
// Inertia setup in Laravel

// HandleInertiaRequests middleware
class HandleInertiaRequests extends Middleware
{
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'roles' => $request->user()->getRoleNames(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name'),
                    'tenant' => $request->user()->currentTenant(),
                ] : null,
            ],
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
            ],
        ]);
    }
}
```

### Frontend

```typescript
// resources/js/app.tsx (entry point)
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

createInertiaApp({
  resolve: (name) =>
    resolvePageComponent(
      `./Pages/${name}.tsx`,
      import.meta.glob('./Pages/**/*.tsx'),
    ),
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />);
  },
});
```

---

## 4. Module Specifications

### 4.1 Dashboard

**Key Metrics:**
- Total orders today / this week / this month
- Revenue by payment gateway (pie chart)
- Wallet total balance across all users
- Top 10 products by volume
- Recent orders (last 20)
- System health (queue depth, failed jobs, provider status)

**Data sources:**
```php
class DashboardController
{
    public function index()
    {
        return Inertia::render('Admin/Dashboard', [
            'ordersToday'    => Order::whereDate('created_at', today())->count(),
            'revenueToday'   => Order::whereDate('created_at', today())->sum('grand_payable_amount'),
            'walletBalance'  => Wallet::sum('balance'),
            'topProducts'    => Order::topProducts(10),
            'recentOrders'   => Order::latest()->limit(20)->with('user')->get(),
            'gatewayRevenue' => OrderSummary::revenueByGateway(),
            'pendingLoads'   => WalletLoadRequest::pending()->count(),
            'failedJobs'     => DB::table('failed_jobs')->count(),
        ]);
    }
}
```

### 4.2 Product Management

**Features:**
- Paginated table with server-side search, sort, and filter
- Filters: category, brand, visibility, stock status, source provider
- Inline toggle for `show_product`
- Bulk actions: publish, hide, assign to category
- Product detail/edit form with rich text editor
- Manual product creation (admin-only, non-synced products)
- Trigger catalog sync per provider

**Homepage — hot deals vs other deals:** The public homepage splits visible products into two blocks controlled under **Settings → Homepage sections** (`hot_deals` and `other_deals`). A product appears in **Hot deals** when it has a non-null **`hot_deal_rank`** (nullable decimal; lower values surface earlier). The maximum number of hot-deal cards comes from the hot-deals section config key `priority_product_count`. Products with **`hot_deal_rank` null** are candidates for **Other deals**; within that group, **`display_order`** controls ordering. This field is unrelated to slide `priority`, support-ticket `priority`, or API sort parameters except that catalog `sort_by=priority` is accepted as a backward-compatible alias for `hot_deal_rank`.

### 4.3 Order Management

**Features:**
- Paginated table with search (order ID, user, SKU)
- Filters: status, date range, payment gateway, tenant
- Order detail view: full order info, payment details, voucher codes
- Actions: view, export CSV, refund (with reason)
- Status timeline showing each state transition with timestamps

### 4.4 Tenant Management

**Features:**
- Tenant list with status badges (active/suspended/inactive)
- Create tenant wizard (multi-step)
- Tenant detail: org info, users, products, gateways, orders summary
- Suspend / activate actions
- Product assignment (bulk select)
- Gateway credential management
- Margin and credit limit settings

### 4.5 Wallet Management

**Features:**
- All wallets list with balance, tenant, last transaction
- Wallet load requests queue (pending/approved/rejected)
- Approve/reject workflow with mandatory notes
- Manual credit/debit with reason (super-admin only)
- Transaction history per wallet with export

### 4.6 Offer Management

**Features:**
- CRUD for offers with form validation
- Live preview of offer application on sample product
- Usage analytics (how many times used, revenue impact)
- Duplicate offer action (copy existing with new dates)
- Bulk activate/deactivate

### 4.7 Payment Gateway Configuration

**Features:**
- Per-tenant gateway credential vault
- Write-only credential fields (never displayed after saving)
- Test connection button
- Environment toggle (sandbox/production)
- Gateway enable/disable per tenant

### 4.8 Report Module

**Report types:**
- Transaction report (date range, tenant, gateway)
- Settlement report (by gateway, reconciliation)
- Product performance report (volume, revenue by product)
- Tenant performance report (orders, revenue by tenant)
- Wallet report (load/debit summary by tenant)

**Export formats:** CSV, XLSX, PDF

### 4.9 API Key Management

**Features:**
- Issue new API keys for resellers
- View key metadata (created, last used, rate limit)
- Configure permissions per key
- IP whitelist per key
- Revoke keys (immediate effect)
- Usage statistics (requests per day/hour)

### 4.10 Settings

**Categories:**
- General: app name, logo, timezone, currency
- Notifications: email templates, SMS provider config
- Providers: default voucher provider credentials (system-level)
- Gateways: default payment gateway credentials (system-level)
- Security: VPN blocking toggle, 2FA enforcement, session timeout
- Maintenance: enable/disable maintenance mode

### 4.11 Audit Log Viewer

**Features:**
- Searchable log table (user, action, date range, tenant)
- Log detail view with old/new value diff
- Filter by action category
- Export audit trail per tenant (compliance)

### 4.12 Security Dashboard

**Features:**
- **Threat Overview:** Active critical threats, VPN attempts today, blocked IPs, failed logins, wallet fraud flags
- **Top Threat IPs:** Table of IPs with highest security event count in last 24 hours, with one-click block/unblock
- **Security Event Timeline:** Filterable list of all `security_event_logs` by type, severity, date range, IP, user
- **Event Detail View:** Full metadata, resolution workflow (resolve with notes)
- **VPN Detection Log:** Dedicated view for VPN/proxy detection events with map visualization
- **Blocked IPs Manager:** View/add/remove blocked IPs, set expiry, toggle permanent blocks
- **Wallet Fraud Queue:** Unresolved wallet fraud flags ordered by risk score, with approve/block actions
- **User Security Profile:** Per-user view showing login history, unique IPs, VPN detections, fraud flags
- **Real-Time Alerts:** Toast notifications for critical security events via WebSocket/polling

**Access:** Super Admin and Admin roles only

---

## 5. Component Architecture

### Shared Admin Components (implemented)

```
resources/js/Components/Admin/
├── index.ts               # Barrel export
├── Breadcrumbs.tsx        # Dashboard > Orders > #12345 navigation
├── ConfirmDialog.tsx      # Modal confirmation for destructive actions
├── DataTable.tsx          # Reusable table with search, pagination, export, empty state
├── FlashToast.tsx         # Success/error/warning toast from Inertia flash
├── HelpTooltip.tsx        # (?) icon with plain-English explanation on hover
├── StatCard.tsx           # Dashboard metric card with icon and optional trend
└── StatusBadge.tsx        # Human-readable status labels with color coding
```

#### StatusBadge label map

| DB Value | Admin Label | Color |
|---|---|---|
| `pending` | Waiting for Payment | Yellow |
| `processing` | Being Fulfilled | Blue |
| `completed` / `fulfilled` | Delivered | Green |
| `failed` | Failed | Red |
| `cancelled` | Cancelled | Gray |
| `refunded` | Refunded | Purple |
| `refund_requested` | Refund Requested | Purple |
| `partially_fulfilled` | Partially Delivered | Orange |
| `frozen` | Frozen | Blue |
| `blocked` | Blocked | Red |

#### ConfirmDialog usage

Every destructive admin action (block user, freeze wallet, cancel order, delete product)
opens a `ConfirmDialog` with:
- A plain-English title and description explaining what will happen
- Variant coloring (`danger` for destructive, `warning` for reversible, `info` for neutral)
- Loading state during submission

### Layout

```
resources/js/Layouts/
├── AdminLayout.tsx         # Sidebar + header + flash toasts + help link + main content
├── AuthLayout.tsx          # Centered card (login, 2FA)
└── B2BLayout.tsx           # B2B portal layout (separate sidebar)
```

`AdminLayout` includes:
- Collapsible sidebar with permission-filtered navigation items
- Top-bar with notification bell and user dropdown menu
- `FlashToast` component rendering `success`/`error`/`warning` flash messages
- "Need Help?" link in sidebar footer linking to internal docs

### Admin Layout Structure

```
┌──────────────────────────────────────────────────┐
│ Header: Logo | Search | Notifications | Profile  │
├──────────┬───────────────────────────────────────┤
│          │                                       │
│ Sidebar  │          Main Content Area            │
│          │                                       │
│ Dashboard│  ┌─────────────────────────────────┐  │
│ Products │  │ Page Header                     │  │
│ Orders   │  │ Title + Breadcrumbs + Actions   │  │
│ Users    │  ├─────────────────────────────────┤  │
│ Tenants  │  │                                 │  │
│ Wallets  │  │ Content (table, form, etc.)     │  │
│ Offers   │  │                                 │  │
│ Gateways │  │                                 │  │
│ Reports  │  │                                 │  │
│ API Keys │  │                                 │  │
│ Settings │  │                                 │  │
│ Audit Log│  └─────────────────────────────────┘  │
│ Security │                                       │
│          │                                       │
└──────────┴───────────────────────────────────────┘
```

---

## 6. Routing Structure

```php
// routes/web.php -- Admin routes

Route::middleware(['auth', 'verified', '2fa.verified', 'role:super-admin|admin|finance|b2b-manager|b2c-manager'])
    ->prefix('panel')
    ->name('panel.')
    ->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Products (B2C admin CRUD)
    Route::resource('products', ProductController::class);
    Route::patch('products/{product}/toggle', [ProductController::class, 'toggle'])->name('products.toggle');
    Route::post('products/sync', [ProductController::class, 'sync'])->name('products.sync');

    // Categories
    Route::resource('categories', CategoryController::class);
    Route::post('categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');

    // Brands
    Route::resource('brands', BrandController::class);

    // Orders
    Route::resource('orders', OrderController::class)->only(['index', 'show']);
    Route::post('orders/{order}/refund', [OrderController::class, 'refund'])->name('orders.refund');
    Route::get('orders/export', [OrderController::class, 'export'])->name('orders.export');

    // Users
    Route::resource('users', UserController::class);
    Route::patch('users/{user}/block', [UserController::class, 'block'])->name('users.block');

    // Tenants
    Route::resource('tenants', TenantController::class);
    Route::patch('tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
    Route::patch('tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('tenants.activate');
    Route::post('tenants/{tenant}/products', [TenantController::class, 'assignProducts'])->name('tenants.products');
    Route::resource('tenants.gateways', TenantGatewayController::class)->shallow();
    Route::resource('tenants.providers', TenantProviderController::class)->shallow();

    // Wallets
    Route::get('wallets', [WalletController::class, 'index'])->name('wallets.index');
    Route::get('wallets/load-requests', [WalletController::class, 'loadRequests'])->name('wallets.load-requests');
    Route::patch('wallets/load-requests/{request}/approve', [WalletController::class, 'approve'])->name('wallets.approve');
    Route::patch('wallets/load-requests/{request}/reject', [WalletController::class, 'reject'])->name('wallets.reject');
    Route::post('wallets/{wallet}/load-requests', [WalletController::class, 'storeLoadOnBehalf'])->name('wallets.load_requests.store_on_behalf');
    Route::get('wallets/load-requests/{loadRequest}/proof', [WalletController::class, 'downloadLoadProof'])->name('wallets.load_requests.proof');
    Route::post('wallets/{wallet}/debit', [WalletController::class, 'debit'])->name('wallets.debit');

    // Offers
    Route::resource('offers', OfferController::class);
    Route::post('offers/{offer}/duplicate', [OfferController::class, 'duplicate'])->name('offers.duplicate');

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
    Route::get('reports/{report}/download', [ReportController::class, 'download'])->name('reports.download');

    // API Keys
    Route::resource('api-keys', ApiKeyController::class)->except(['edit', 'update']);
    Route::patch('api-keys/{apiKey}/revoke', [ApiKeyController::class, 'revoke'])->name('api-keys.revoke');

    // Settings
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

    // Audit Logs
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('audit-logs/{log}', [AuditLogController::class, 'show'])->name('audit-logs.show');
    Route::get('audit-logs/export', [AuditLogController::class, 'export'])->name('audit-logs.export');

    // Security Dashboard
    Route::prefix('security')->name('security.')->group(function () {
        Route::get('/', [SecurityDashboardController::class, 'index'])->name('index');
        Route::get('/events', [SecurityDashboardController::class, 'events'])->name('events');
        Route::get('/events/{event}', [SecurityDashboardController::class, 'showEvent'])->name('events.show');
        Route::post('/events/{event}/resolve', [SecurityDashboardController::class, 'resolveEvent'])->name('events.resolve');
        Route::get('/blocked-ips', [SecurityDashboardController::class, 'blockedIps'])->name('blocked-ips');
        Route::post('/blocked-ips', [SecurityDashboardController::class, 'blockIp'])->name('blocked-ips.store');
        Route::delete('/blocked-ips/{ip}', [SecurityDashboardController::class, 'unblockIp'])->name('blocked-ips.destroy');
        Route::get('/user/{user}/profile', [SecurityDashboardController::class, 'userProfile'])->name('user-profile');
        Route::get('/fraud-queue', [SecurityDashboardController::class, 'fraudQueue'])->name('fraud-queue');
    });

    // Homepage: sections under Settings; hero images under Settings → Hero carousel
    // See routes/admin.php — `SlideController` is registered as
    // `GET|POST|PUT|DELETE /panel/settings/hero-slides` (permission: settings.view / settings.update).
    // Legacy `GET /panel/slides` redirects to the hero-slides index.
});
```

---

## 7. Shared Data (Inertia)

Data available on every page via `HandleInertiaRequests` middleware:

```typescript
// TypeScript types for shared data
interface SharedData {
  auth: {
    user: {
      id: number;
      name: string;
      email: string;
      roles: string[];
      permissions: string[];
      tenant: Tenant | null;
    } | null;
  };
  flash: {
    success: string | null;
    error: string | null;
  };
  navigation: {
    items: NavItem[];         // Filtered by user permissions
    pendingLoadRequests: number; // Badge count
    failedJobs: number;         // Badge count
  };
}

// Usage in React components
import { usePage } from '@inertiajs/react';
const { auth, flash } = usePage<SharedData>().props;
```

---

## 8. File Structure

```
resources/js/
├── app.tsx                       # Inertia entry point
├── Pages/
│   ├── Admin/
│   │   ├── Dashboard.tsx
│   │   ├── Products/
│   │   │   ├── Index.tsx         # Product list
│   │   │   ├── Create.tsx        # Create form
│   │   │   ├── Edit.tsx          # Edit form
│   │   │   └── Show.tsx          # Product detail
│   │   ├── Orders/
│   │   │   ├── Index.tsx
│   │   │   └── Show.tsx
│   │   ├── Users/
│   │   │   ├── Index.tsx
│   │   │   ├── Create.tsx
│   │   │   └── Edit.tsx
│   │   ├── Tenants/
│   │   │   ├── Index.tsx
│   │   │   ├── Create.tsx        # Multi-step wizard
│   │   │   ├── Show.tsx          # Tenant detail (tabs)
│   │   │   └── Edit.tsx
│   │   ├── Wallets/
│   │   │   ├── Index.tsx
│   │   │   └── LoadRequests.tsx
│   │   ├── Offers/
│   │   │   ├── Index.tsx
│   │   │   ├── Create.tsx
│   │   │   └── Edit.tsx
│   │   ├── Reports/
│   │   │   └── Index.tsx
│   │   ├── ApiKeys/
│   │   │   └── Index.tsx
│   │   ├── Settings/
│   │   │   └── Index.tsx
│   │   └── AuditLogs/
│   │       ├── Index.tsx
│   │       └── Show.tsx
│   ├── B2B/
│   │   ├── Dashboard.tsx
│   │   ├── Orders/
│   │   ├── Wallet.tsx
│   │   ├── Team.tsx
│   │   └── Settings.tsx
│   └── Auth/
│       ├── Login.tsx
│       ├── TwoFactorChallenge.tsx
│       └── ForgotPassword.tsx
├── Components/
│   ├── ui/                       # shadcn/ui
│   ├── DataTable.tsx
│   ├── PageHeader.tsx
│   └── ...
├── Layouts/
│   ├── AdminLayout.tsx
│   ├── B2BLayout.tsx
│   └── AuthLayout.tsx
├── hooks/
│   ├── usePermission.ts          # Check current user permissions
│   └── useDebounce.ts            # Search debounce
└── types/
    ├── index.ts                  # Shared types
    └── models.ts                 # Model interfaces
```

---

## Related Documents

- [ARCHITECTURE.md](ARCHITECTURE.md) -- System architecture and tech stack
- [CODE_STANDARDS.md](CODE_STANDARDS.md) -- Controller validation and logging conventions
- [B2B_TENANCY.md](B2B_TENANCY.md) -- B2B portal and tenant management
- [SECURITY.md](SECURITY.md) -- Auth, RBAC, and 2FA details
- [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) -- Table definitions
