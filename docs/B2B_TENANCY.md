# AmazePays B2B Multi-Tenancy Architecture

> **Version:** 2.0  
> **Last Updated:** April 2026

---

## Table of Contents

1. [Overview](#1-overview)
2. [Tenant Model](#2-tenant-model)
3. [Role-Based Access Control](#3-role-based-access-control)
4. [Tenant Scoping](#4-tenant-scoping)
5. [B2B Client Onboarding](#5-b2b-client-onboarding)
6. [B2B Client Portal](#6-b2b-client-portal)
7. [Product Assignment & Pricing](#7-product-assignment--pricing)
8. [B2C Admin CRUD Operations](#8-b2c-admin-crud-operations)
9. [Offers & Promotions Management](#9-offers--promotions-management)
10. [Tenant Suspension & Deactivation](#10-tenant-suspension--deactivation)
11. [Implementation Details](#11-implementation-details)

---

## 1. Overview

AmazePays uses a **single-database, tenant-scoped** multi-tenancy model. All B2B clients (tenants) share the same MySQL database, but data isolation is enforced via:

- **`tenant_id`** column on all tenant-aware tables
- **Global Eloquent scope** that auto-filters queries by the authenticated user's tenant
- **Middleware** that resolves the current tenant on every request
- **Policy-based authorization** that enforces access boundaries

### Tenant Types

| Type | Description | Panel Access |
|------|-------------|-------------|
| Platform (Super Admin) | AmazePays itself | Full admin panel |
| B2B Client | Business distributor/reseller | B2B portal + API |
| B2C Consumer | End consumer | Storefront + mobile app |

B2C consumers do **not** have a tenant. Their `tenant_id` is `NULL` on orders and wallets.

---

## 2. Tenant Model

### `tenants` Table

```php
// app/Models/Tenant.php

class Tenant extends Model
{
    protected $fillable = [
        'name', 'slug', 'logo', 'contact_email', 'contact_phone',
        'status', 'settings', 'margin_percentage', 'credit_limit',
        'website', 'gst_number', 'address', 'city', 'state',
        'pincode', 'country',
    ];

    protected $casts = [
        'settings' => 'array',
        'margin_percentage' => 'decimal:2',
        'credit_limit' => 'decimal:2',
    ];

    // Users belonging to this tenant
    public function users()
    {
        return $this->belongsToMany(User::class, 'tenant_users')
            ->withPivot('role', 'is_active')
            ->withTimestamps();
    }

    // Products assigned to this tenant
    public function products()
    {
        return $this->belongsToMany(Product::class, 'tenant_products')
            ->withPivot('custom_price', 'margin_override', 'is_active')
            ->withTimestamps();
    }

    // Payment gateway configurations
    public function paymentGateways()
    {
        return $this->hasMany(TenantPaymentGateway::class);
    }

    // Voucher provider configurations
    public function voucherProviders()
    {
        return $this->hasMany(TenantVoucherProvider::class);
    }

    // API keys issued for this tenant
    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }

    // Wallet load requests
    public function walletLoadRequests()
    {
        return $this->hasMany(WalletLoadRequest::class);
    }

    // Offers specific to this tenant
    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    // Scoped orders
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Status checks
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }
}
```

### Tenant Settings JSON Structure

```json
{
  "allowed_payment_methods": ["wallet", "ccavenue", "razorpay"],
  "order_approval_required": false,
  "max_order_amount": 500000,
  "min_order_amount": 100,
  "notification_emails": ["finance@client.com", "ops@client.com"],
  "webhook_url": "https://client.com/webhooks/amazepays",
  "auto_fulfill": true,
  "invoice_prefix": "CLI",
  "timezone": "Asia/Kolkata"
}
```

---

## 3. Role-Based Access Control

### Role Hierarchy

```
super-admin
├── admin
│   ├── finance
│   ├── b2b-manager
│   │   └── b2b-client (tenant-scoped)
│   │       └── b2b-operator (tenant-scoped)
│   └── b2c-manager
└── reseller (API-only, tenant-scoped)
```

### Spatie Permission Setup

```php
// database/seeders/RolesAndPermissionsSeeder.php

// Roles
$superAdmin = Role::create(['name' => 'super-admin']);
$admin      = Role::create(['name' => 'admin']);
$finance    = Role::create(['name' => 'finance']);
$b2bManager = Role::create(['name' => 'b2b-manager']);
$b2bClient  = Role::create(['name' => 'b2b-client']);
$b2bOperator = Role::create(['name' => 'b2b-operator']);
$b2cManager = Role::create(['name' => 'b2c-manager']);
$reseller   = Role::create(['name' => 'reseller']);

// Permissions
$permissions = [
    // Products
    'products.view', 'products.create', 'products.edit', 'products.delete',
    'products.publish', 'products.sync',

    // Orders
    'orders.view', 'orders.create', 'orders.cancel', 'orders.refund', 'orders.export',

    // Users
    'users.view', 'users.create', 'users.edit', 'users.block',

    // Tenants
    'tenants.view', 'tenants.create', 'tenants.edit', 'tenants.suspend',

    // Wallets
    'wallets.view', 'wallets.credit', 'wallets.debit', 'wallets.approve-load',

    // Payment Gateways
    'gateways.view', 'gateways.configure',

    // Voucher Providers
    'providers.view', 'providers.configure',

    // Reports
    'reports.view', 'reports.export',

    // API Keys
    'api-keys.view', 'api-keys.create', 'api-keys.revoke',

    // Settings
    'settings.view', 'settings.edit',

    // Audit Logs
    'audit.view',

    // Offers
    'offers.view', 'offers.create', 'offers.edit', 'offers.delete',

    // CMS / Homepage
    'cms.view', 'cms.edit',
];
```

### Permission Matrix

| Permission | Super Admin | Admin | Finance | B2B Manager | B2B Client | B2B Operator | B2C Manager |
|-----------|:-----------:|:-----:|:-------:|:-----------:|:----------:|:------------:|:-----------:|
| products.view | Yes | Yes | -- | Yes | Assigned | Assigned | Yes |
| products.create | Yes | Yes | -- | -- | -- | -- | Yes |
| products.edit | Yes | Yes | -- | -- | -- | -- | Yes |
| products.publish | Yes | Yes | -- | -- | -- | -- | Yes |
| orders.view | Yes | Yes | Yes | Yes | Own tenant | Own tenant | Yes |
| orders.create | Yes | Yes | -- | Yes | Yes | Yes | -- |
| orders.refund | Yes | Yes | -- | -- | -- | -- | -- |
| tenants.view | Yes | Yes | Yes | Yes | Own | -- | -- |
| tenants.create | Yes | Yes | -- | -- | -- | -- | -- |
| wallets.view | Yes | Yes | Yes | Yes | Own tenant | Own tenant | -- |
| wallets.approve-load | Yes | Yes | -- | -- | -- | -- | -- |
| gateways.configure | Yes | Yes | -- | -- | -- | -- | -- |
| reports.view | Yes | Yes | Yes | Yes | Own tenant | -- | Yes |
| offers.create | Yes | Yes | -- | -- | -- | -- | Yes |
| cms.edit | Yes | Yes | -- | -- | -- | -- | Yes |
| audit.view | Yes | Yes | -- | -- | -- | -- | -- |

---

## 4. Tenant Scoping

### Global Scope

```php
// app/Scopes/TenantScope.php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        if (!$user) return;

        // Super admins and platform admins see everything
        if ($user->hasRole(['super-admin', 'admin', 'finance'])) return;

        $tenantId = $user->currentTenantId();

        if ($tenantId) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        } else {
            // B2C user: only see own data (no tenant scope, use user_id)
            if ($model->getTable() !== 'products') {
                $builder->where($model->getTable() . '.user_id', $user->id);
            }
        }
    }
}
```

### Tenant Trait

```php
// app/Traits/BelongsToTenant.php

namespace App\Traits;

use App\Scopes\TenantScope;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        // Auto-set tenant_id on creation
        static::creating(function ($model) {
            if (auth()->check() && !$model->tenant_id) {
                $model->tenant_id = auth()->user()->currentTenantId();
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
```

### Models Using Tenant Scope

```php
// Apply to tenant-aware models:
class Order extends Model
{
    use BelongsToTenant;
    // ...
}

// Also apply to: WalletLoadRequest, AuditLog, ApiKey
// Do NOT apply to: User, Product, ProductCategory, Brand (global models)
```

### Middleware

```php
// app/Http/Middleware/ResolveTenant.php

class ResolveTenant
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()) {
            $tenant = $request->user()->currentTenant();

            if ($tenant && $tenant->isSuspended()) {
                abort(403, 'Your organization account is suspended. Contact support.');
            }

            app()->instance('currentTenant', $tenant);
        }

        return $next($request);
    }
}
```

---

## 5. B2B Client Onboarding

### Onboarding Flow

```
Admin creates tenant
    │
    ▼
Fill organization details:
  - Company name, GST, address
  - Contact person, email, phone
  - Default margin percentage
  - Credit limit
    │
    ▼
Create owner user:
  - Email, name, mobile
  - Auto-generate temporary password
  - Send invitation email
    │
    ▼
Assign products:
  - Select from global catalog
  - Set custom pricing (optional)
  - Set per-product margin overrides (optional)
    │
    ▼
Configure payment methods:
  - Enable wallet payments
  - Add gateway credentials (optional)
    │
    ▼
Configure voucher providers:
  - Use platform defaults, OR
  - Add tenant-specific provider credentials
    │
    ▼
Activate tenant (status = active)
    │
    ▼
B2B client logs in and starts ordering
```

### Admin UI (Inertia + React)

```
Tenant Onboarding Form (multi-step wizard):

Step 1: Organization Details
┌──────────────────────────────────────┐
│ Company Name: [__________________]   │
│ GST Number:   [__________________]   │
│ Website:      [__________________]   │
│ Address:      [__________________]   │
│ City:         [________] State: [__] │
│ Pincode:      [______]               │
│ Contact Email:[__________________]   │
│ Contact Phone:[__________________]   │
│ [Next →]                             │
└──────────────────────────────────────┘

Step 2: Financial Settings
┌──────────────────────────────────────┐
│ Margin %:     [5.00]                 │
│ Credit Limit: [₹ 100,000.00]        │
│ Payment Methods:                     │
│   [x] Wallet                         │
│   [ ] CCAvenue                       │
│   [ ] Razorpay                       │
│   [ ] Unlimit                        │
│ [← Back] [Next →]                    │
└──────────────────────────────────────┘

Step 3: Product Assignment
┌──────────────────────────────────────┐
│ Search: [____________] [Filter ▼]    │
│                                      │
│ [x] Amazon Gift Card    ₹487.50     │
│ [x] Flipkart Gift Card  ₹490.00    │
│ [ ] Swiggy Gift Card    ₹475.00    │
│ [x] Myntra Gift Card    ₹480.00    │
│                                      │
│ Selected: 3 products                 │
│ [← Back] [Next →]                    │
└──────────────────────────────────────┘

Step 4: Owner Account
┌──────────────────────────────────────┐
│ Owner Name:  [__________________]    │
│ Owner Email: [__________________]    │
│ Owner Phone: [__________________]    │
│                                      │
│ [← Back] [Create Tenant]            │
└──────────────────────────────────────┘
```

---

## 6. B2B Client Portal

### Portal Sections

| Section | Features |
|---------|----------|
| Dashboard | Wallet balance, orders today/month, top products, recent transactions |
| Place Order | Browse assigned products, add to cart, select payment method, submit |
| Bulk Order | Upload CSV (SKU, qty, denomination), validate, submit |
| Order History | Search/filter orders, view details, download voucher codes, export |
| Wallet | Balance, transaction history, request load (UTR + proof upload) |
| Team | Invite operators, manage sub-users, toggle active status |
| Reports | Date-range reports: orders, spend, product breakdown |
| API Keys | View/create API keys (if reseller role enabled) |
| Settings | Company profile, notification preferences, webhook URL |

### B2B Order Flow

```
B2B Client Portal
    │
    ▼
Browse assigned products (tenant_products filter)
    │
    ▼
Select product + denomination + quantity
    │
    ▼
Choose payment method:
  ├── Wallet → Check balance → Debit → Place order
  └── Gateway → Redirect to payment → On success → Place order
    │
    ▼
Order queued for async fulfillment
    │
    ▼
Voucher codes delivered via:
  - Portal (view in order details)
  - Email to configured address
  - Webhook to client system (if configured)
```

---

## 7. Product Assignment & Pricing

### Pricing Model

```
Final B2B price = Base price - (Base price × margin_percentage / 100)

Where margin_percentage can be:
1. tenant_products.margin_override (per-product, if set)
2. tenants.margin_percentage (tenant default, if no override)
3. 0% (no discount -- same as B2C price)
```

**Example:**

| Product | Base Price | Tenant Margin (5%) | Product Override (8%) | Final Price |
|---------|-----------|--------------------|-----------------------|-------------|
| Amazon ₹500 | ₹500 | ₹475 | -- | ₹475 |
| Flipkart ₹1000 | ₹1000 | ₹950 | ₹920 (8%) | ₹920 |

### Product Assignment Rules

- Only assigned products visible to B2B client
- Admin can bulk-assign (all products, by category, by brand)
- Products can be deactivated per-tenant without global impact
- Custom descriptions/images per-tenant (planned, stored in `tenant_products.settings`)

---

## 8. B2C Admin CRUD Operations

### Product Management

The B2C admin panel provides full CRUD for managing the consumer storefront:

**Product CRUD:**

| Operation | Endpoint (Inertia) | Description |
|-----------|-------------------|-------------|
| List | `GET /admin/products` | Paginated list with search, filters (category, brand, stock, visibility) |
| Create | `GET /admin/products/create` | Form to manually add a product |
| Store | `POST /admin/products` | Validate and save new product |
| Edit | `GET /admin/products/{id}/edit` | Edit form pre-filled with product data |
| Update | `PUT /admin/products/{id}` | Validate and update product |
| Delete | `DELETE /admin/products/{id}` | Soft delete (set `show_product = false`) |
| Toggle Visibility | `PATCH /admin/products/{id}/toggle` | Quick show/hide toggle |
| Bulk Sync | `POST /admin/products/sync` | Trigger provider catalog sync |

**Product Form Fields (Admin):**

```
┌──────────────────────────────────────────┐
│ Product Edit                              │
│                                          │
│ Name: [_________________________]         │
│ SKU:  [_________________________] (RO*)   │
│ Source: [Woohoo ▼]              (RO*)     │
│                                          │
│ Custom Description:                       │
│ [                                    ]    │
│ [          Rich text editor          ]    │
│ [                                    ]    │
│                                          │
│ How to Redeem:                           │
│ [                                    ]    │
│                                          │
│ Terms & Conditions:                       │
│ [                                    ]    │
│                                          │
│ Category: [Shopping ▼]                    │
│ Brand:    [Amazon ▼]                      │
│                                          │
│ Images:                                  │
│ [Upload custom image] or use provider    │
│                                          │
│ Pricing:                                 │
│ Discount %: [2.50]                       │
│ CGST: [0.00] SGST: [0.00] IGST: [0.00] │
│                                          │
│ Display:                                 │
│ Priority: [5] Secondary Priority: [0]    │
│ [x] Show on storefront                   │
│ [ ] Special SKU                          │
│ [ ] Out of stock                         │
│                                          │
│ *RO = Read-Only for synced products      │
│                                          │
│ [Cancel] [Save Product]                  │
└──────────────────────────────────────────┘
```

### Category Management

| Operation | Description |
|-----------|-------------|
| List | Tree view of categories with drag-drop ordering |
| Create | Name, slug (auto-generated), parent category, thumbnail |
| Edit | Update name, slug, thumbnail, parent |
| Delete | Only if no products assigned (or reassign first) |
| Reorder | Drag-and-drop priority ordering |

### Brand Management

| Operation | Description |
|-----------|-------------|
| List | Grid view with logos |
| Create | Name, slug, logo upload |
| Edit | Update name, logo |
| Delete | Only if no products assigned |

### Homepage / CMS Management

| Operation | Description |
|-----------|-------------|
| Banner Slides | CRUD for hero banners (desktop/mobile images, link to product/category/brand) |
| Homepage Sections | Toggle sections on/off, edit section content (featured products, deals, etc.) |
| Featured Products | Select products to feature on homepage |

---

## 9. Offers & Promotions Management

### Offer CRUD

Full admin CRUD for creating and managing promotional offers on voucher cards:

**List View:**

```
┌──────────────────────────────────────────────────────────────┐
│ Offers & Promotions                        [+ Create Offer]  │
│                                                              │
│ Filter: [All ▼] [Active ▼] [Date range: _____ to _____]    │
│                                                              │
│ ┌──────────────────────────────────────────────────────────┐ │
│ │ Name              │ Type     │ Value │ Usage │ Status    │ │
│ ├───────────────────┼──────────┼───────┼───────┼──────────┤ │
│ │ Summer Sale 2026  │ % Off    │ 10%   │ 45/100│ Active   │ │
│ │ First Order Bonus │ Flat Off │ ₹100  │ 23/∞  │ Active   │ │
│ │ Diwali Cashback   │ Cashback │ 15%   │ 0/500 │ Scheduled│ │
│ │ Buy 2 Get 1       │ Bundle   │ 1 free│ 12/50 │ Expired  │ │
│ └──────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────┘
```

**Create / Edit Form:**

```
┌──────────────────────────────────────────┐
│ Create Offer                              │
│                                          │
│ Name: [_________________________]         │
│ Description: [__________________]         │
│                                          │
│ Type: [Percentage Discount ▼]             │
│   Options: Percentage Discount            │
│            Flat Discount                  │
│            Cashback to Wallet             │
│            Buy X Get Y                    │
│            First Order Discount           │
│                                          │
│ Value: [10] %                            │
│ Max Discount Cap: [₹ 500]               │
│ Min Order Amount: [₹ 200]               │
│                                          │
│ Usage Limits:                            │
│ Total uses: [100] (blank = unlimited)    │
│ Per user:   [1]                          │
│                                          │
│ Applicable To:                           │
│ Products:   [Select products...]         │
│ Categories: [Select categories...]       │
│ Brands:     [Select brands...]           │
│ (Leave all blank = applies to everything)│
│                                          │
│ Schedule:                                │
│ Start: [2026-04-07] End: [2026-05-07]   │
│                                          │
│ Promo Code: [SUMMER10] (optional)        │
│ [x] Auto-apply (no code needed)          │
│                                          │
│ Scope:                                   │
│ ( ) Platform-wide (all customers)        │
│ ( ) Specific tenant: [Select ▼]          │
│                                          │
│ Banner Image: [Upload]                   │
│                                          │
│ [Cancel] [Save Offer]                    │
└──────────────────────────────────────────┘
```

### Offer Validation Rules

- `start_date` must be before `end_date`
- `value` must be positive
- `max_discount` required when type is `percentage_discount`
- `promo_code` must be unique (if provided)
- Cannot edit `type` after offer has been used
- Cannot reduce `max_uses_total` below `current_uses`

---

## 10. Tenant Suspension & Deactivation

### Suspension Triggers

| Trigger | Action | Reversible |
|---------|--------|-----------|
| Manual (admin) | Admin suspends via UI | Yes |
| Credit limit exceeded | Auto-suspend when wallet is negative beyond limit | Yes (on reload) |
| Overdue payment | After 30 days of unpaid invoices | Yes (on payment) |
| Security concern | Suspicious activity detected | Yes (after review) |
| Contract expiry | Contract end date reached | Yes (on renewal) |

### Suspension Effects

- B2B portal shows "Account suspended" message
- API keys return `403 Forbidden`
- Existing orders continue processing (no mid-order cancellation)
- New orders blocked
- Wallet load requests blocked
- Data preserved (not deleted)

### Deactivation (Permanent)

- Admin sets status to `inactive`
- All API keys revoked
- User accounts unlinked from tenant
- Data retained for compliance (configurable retention period)
- Credentials wiped from `tenant_payment_gateways` and `tenant_voucher_providers`

---

## 11. Implementation Details

### User Model Extension

```php
// Add to User model

public function tenants()
{
    return $this->belongsToMany(Tenant::class, 'tenant_users')
        ->withPivot('role', 'is_active')
        ->withTimestamps();
}

public function currentTenant(): ?Tenant
{
    return $this->tenants()
        ->wherePivot('is_active', true)
        ->first();
}

public function currentTenantId(): ?int
{
    return $this->currentTenant()?->id;
}

public function belongsToTenant(int $tenantId): bool
{
    return $this->tenants()->where('tenants.id', $tenantId)->exists();
}
```

### Route Groups

```php
// routes/web.php

// Admin routes (super-admin, admin)
Route::middleware(['auth', 'verified', 'role:super-admin|admin|finance|b2b-manager|b2c-manager'])
    ->prefix('admin')
    ->group(function () {
        Route::resource('tenants', TenantController::class);
        Route::resource('products', ProductController::class);
        Route::resource('offers', OfferController::class);
        Route::resource('categories', CategoryController::class);
        Route::resource('brands', BrandController::class);
        Route::resource('orders', OrderController::class)->only(['index', 'show']);
        // ... more admin routes
    });

// B2B portal routes (tenant-scoped)
Route::middleware(['auth', 'verified', 'role:b2b-client|b2b-operator', 'tenant.resolve'])
    ->prefix('b2b')
    ->group(function () {
        Route::get('dashboard', [B2BDashboardController::class, 'index']);
        Route::resource('orders', B2BOrderController::class);
        Route::get('wallet', [B2BWalletController::class, 'index']);
        Route::post('wallet/load-request', [B2BWalletController::class, 'requestLoad']);
        Route::resource('team', B2BTeamController::class);
    });
```

---

## Related Documents

- [ADMIN_PANEL.md](ADMIN_PANEL.md) -- Admin panel modules and Voyager migration
- [CODE_STANDARDS.md](CODE_STANDARDS.md) -- Validated input, webhook whitelisting, logging rules
- [WALLET_SYSTEM.md](WALLET_SYSTEM.md) -- Wallet load flow details
- [DATABASE_DICTIONARY.md](DATABASE_DICTIONARY.md) -- Tenant table schemas
- [SECURITY.md](SECURITY.md) -- RBAC and authorization details
- [API_DOCUMENTATION.md](API_DOCUMENTATION.md) -- Reseller API (tenant-scoped)
