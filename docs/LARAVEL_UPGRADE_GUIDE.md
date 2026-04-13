# Laravel 8 → 13 Upgrade Guide

> Run each step sequentially. Test after every major version bump.  
> Budget 8-10 days total. Use a separate git branch: `feature/laravel-13-upgrade`

---

## Pre-Upgrade Checklist

```bash
# 1. Create upgrade branch
git checkout -b feature/laravel-13-upgrade

# 2. Full backup
php artisan down
mysqldump -u root -p amazepays_db > backup_pre_upgrade.sql

# 3. Run existing test suite - note all failures as baseline
php artisan test
```

---

## Step 1: Laravel 8 → 9

```bash
# Update composer.json
composer require laravel/framework:^9.0 --no-update
composer require laravel/sanctum:^3.0 --no-update
composer require laravel/tinker:^2.7 --no-update
composer require nunomaduro/collision:^6.0 --no-update

# Remove Voyager temporarily (incompatible with L9+)
composer remove tcg/voyager --no-update

# Remove old dev deps
composer remove facade/ignition --no-update
composer require spatie/laravel-ignition:^1.0 --no-update

# Run update
composer update

# Publish stubs
php artisan vendor:publish --tag=laravel-assets --force
```

**Breaking changes to fix (L8→L9):**
- `$casts` property: `'field' => 'encrypted'` syntax may need update
- `Http::fake()` signature changes
- Guzzle 7 response changes
- `Carbon::now()` timezone handling
- Run: `php artisan test` and fix failures

---

## Step 2: Laravel 9 → 10

```bash
composer require laravel/framework:^10.0 --no-update
composer require laravel/sanctum:^3.2 --no-update
composer require phpunit/phpunit:^10.0 --no-update
composer require fakerphp/faker:^1.21 --no-update
composer update
```

**Breaking changes to fix (L9→L10):**
- PHP minimum: 8.1 (update server/XAMPP)
- `$dates` property removed → use `$casts` with `datetime`
- `Str::password()` added, check for conflicts
- Run: `php artisan test` and fix failures

---

## Step 3: Laravel 10 → 11

```bash
composer require laravel/framework:^11.0 --no-update
composer require laravel/sanctum:^4.0 --no-update
composer require spatie/laravel-ignition:^2.0 --no-update
composer update
```

**Breaking changes to fix (L10→L11):**
- New `bootstrap/app.php` structure (middleware registration changes)
- `app/Http/Kernel.php` deprecated → migrate to `bootstrap/app.php`
- `app/Providers/RouteServiceProvider.php` merged into `bootstrap/app.php`
- Run: `php artisan test` and fix failures

---

## Step 4: Laravel 11 → 12

```bash
composer require laravel/framework:^12.0 --no-update
composer update
```

**Breaking changes to fix (L11→L12):**
- Minor changes, mostly attribute updates
- Run: `php artisan test`

---

## Step 5: Laravel 12 → 13

```bash
composer require laravel/framework:^13.0 --no-update
composer require php:^8.2 --no-update
composer update
```

**Breaking changes to fix (L12→L13):**
- PHP minimum: 8.2
- Run: `php artisan test`

---

## Step 6: Install New Packages

```bash
# Core new packages
composer require spatie/laravel-permission:^6.0
composer require inertiajs/inertia-laravel:^2.0
composer require tightenco/ziggy:^2.0
composer require laravel/horizon:^5.0
composer require pragmarx/google2fa-laravel:^2.2
composer require craftsys/msg91-laravel:^5.0
composer require razorpay/razorpay:^2.9
composer require maxmind-db/reader:^1.11

# Frontend
npm install @inertiajs/react react react-dom @types/react @types/react-dom
npm install -D vite @vitejs/plugin-react typescript tailwindcss postcss autoprefixer
npm install lucide-react class-variance-authority clsx tailwind-merge
npm install @radix-ui/react-dialog @radix-ui/react-dropdown-menu @radix-ui/react-select
npm install @tanstack/react-table @tanstack/react-query
npm install recharts date-fns
```

---

## Step 7: Update bootstrap/app.php (Laravel 11+ style)

Replace the old `app/Http/Kernel.php` approach with the new `bootstrap/app.php`:

```php
// bootstrap/app.php
<?php

use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ThreatDetection;
use App\Http\Middleware\DetectVpnProxy;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware (all requests)
        $middleware->use([
            \Illuminate\Http\Middleware\TrustProxies::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
            SecurityHeaders::class,
            ThreatDetection::class,
            DetectVpnProxy::class,
        ]);

        // Web middleware group
        $middleware->web(append: [
            HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // API middleware group
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Aliases
        $middleware->alias([
            'auth'               => \App\Http\Middleware\Authenticate::class,
            'admin'              => \App\Http\Middleware\AdminMiddleware::class,
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'vpn.check'          => DetectVpnProxy::class,
            'step.up'            => \App\Http\Middleware\StepUpAuth::class,
            'transaction.pin'    => \App\Http\Middleware\VerifyTransactionPin::class,
            'cooling.off'        => \App\Http\Middleware\CoolingOffPeriod::class,
            'two.factor'         => \App\Http\Middleware\RequireTwoFactor::class,
            'tenant'             => \App\Http\Middleware\ResolveTenant::class,
            'ip.whitelist'       => \App\Http\Middleware\VerifyIpWhitelist::class,
            'purchase.limits'    => \App\Http\Middleware\CheckPurchaseLimits::class,
            'business.hours'     => \App\Http\Middleware\CheckBusinessHours::class,
        ]);

        $middleware->throttleWithRedis();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

---

## Step 8: Run Migrations

```bash
# Run all new migrations
php artisan migrate

# Verify table renames
php artisan tinker
>>> Schema::hasTable('products');   # should be true
>>> Schema::hasTable('orders');     # should be true
>>> Schema::hasTable('synced_categories'); # synced Woohoo/API categories
```

---

## Step 9: Publish Package Assets

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Inertia\ServiceProvider"
php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider"
php artisan horizon:install
php artisan key:generate --force
php artisan optimize:clear
```

---

## Step 10: Frontend Build

```bash
# Initialize Tailwind
npx tailwindcss init -p

# Build assets
npm run build

# Development watch
npm run dev
```

---

## Verification Checklist

After all steps:

- [ ] `php artisan test` -- all tests pass (or documented failures explained)
- [ ] Login works
- [ ] Existing order flow works
- [ ] Wallet credit/debit works
- [ ] Unlimit payment flow works
- [ ] Admin panel accessible (old Voyager still works until Phase 4)
- [ ] Redis connected (`php artisan tinker` → `Cache::put('test', 1)` → `Cache::get('test')`)
- [ ] Queue working (`php artisan queue:work --once`)

---

## See also

- [CODE_STANDARDS.md](CODE_STANDARDS.md) -- Request validation (`$request->validated()` / whitelists) and logging after upgrades
