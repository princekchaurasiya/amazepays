<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\B2bPortalController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\KGenAdminController;
use App\Http\Controllers\Admin\OfferController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProviderDashboardController;
use App\Http\Controllers\Admin\SecurityDashboardController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ValueDesignAdminController;
use App\Http\Controllers\Admin\VouchagramController;
use App\Http\Controllers\Admin\WalletController;
use App\Http\Controllers\Admin\WoohooAdminController;
use App\Http\Controllers\Auth\TwoFactorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Panel Routes (Inertia + React)
|--------------------------------------------------------------------------
| All routes here require:
|   - Authentication (auth middleware)
|   - Admin or Super Admin role
|   - 2FA verified (for super-admin routes)
|
| Admin panel lives at /panel
*/

Route::prefix('panel')->name('admin.')->middleware(['auth', 'two.factor'])->group(function () {

    // 2FA setup / challenge (exempt from two.factor middleware)
    Route::prefix('2fa')->name('2fa.')->withoutMiddleware('two.factor')->group(function () {
        Route::get('/setup', [TwoFactorController::class, 'setup'])->name('setup');
        Route::post('/confirm', [TwoFactorController::class, 'confirm'])->name('confirm');
        Route::get('/challenge', [TwoFactorController::class, 'challenge'])->name('challenge');
        Route::post('/verify', [TwoFactorController::class, 'verify'])->name('verify');
        Route::post('/disable', [TwoFactorController::class, 'disable'])->name('disable')->middleware('password.confirm');
    });

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard')
        ->middleware('permission:dashboard.view');

    // B2B portal (tenant-scoped)
    Route::prefix('b2b')->name('b2b.')->group(function () {
        Route::get('/place-order', [B2bPortalController::class, 'placeOrder'])->name('place-order')
            ->middleware('permission:b2b.place_order');
        Route::get('/orders', [B2bPortalController::class, 'orders'])->name('orders')
            ->middleware('permission:b2b.view_orders');
        Route::get('/team', [B2bPortalController::class, 'team'])->name('team')
            ->middleware('permission:b2b.manage_team');
        Route::get('/wallet', [B2bPortalController::class, 'wallet'])->name('wallet')
            ->middleware('permission:b2b.wallet.view');
    });

    // Products
    Route::prefix('products')->name('products.')->middleware('permission:products.view')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/create', [ProductController::class, 'create'])->name('create')
            ->middleware('permission:products.create');
        Route::post('/', [ProductController::class, 'store'])->name('store')
            ->middleware('permission:products.create');

        Route::put('/{product}/content', [ProductController::class, 'updateContent'])->name('content.update')
            ->middleware('permission:products.update');
        Route::delete('/{product}/content/{field}', [ProductController::class, 'resetContent'])->name('content.reset')
            ->middleware('permission:products.update')
            ->where('field', 'custom_description|terms_and_conditions|how_to_redeem');

        Route::post('/{product}/media', [ProductController::class, 'storeMedia'])->name('media.store')
            ->middleware('permission:products.update');
        Route::put('/{product}/media/reorder', [ProductController::class, 'reorderMedia'])->name('media.reorder')
            ->middleware('permission:products.update');
        Route::put('/{product}/media/{media}/hero', [ProductController::class, 'setHeroMedia'])->name('media.hero')
            ->middleware('permission:products.update');
        Route::delete('/{product}/media/{media}', [ProductController::class, 'destroyMedia'])->name('media.destroy')
            ->middleware('permission:products.update');

        Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit')
            ->middleware('permission:products.update');
        Route::put('/{product}', [ProductController::class, 'update'])->name('update')
            ->middleware('permission:products.update');
        Route::patch('/{product}/toggle', [ProductController::class, 'toggleVisibility'])->name('toggle')
            ->middleware('permission:products.publish');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->name('destroy')
            ->middleware('permission:products.delete');
    });

    // Categories (storefront navigation)
    Route::prefix('categories')->name('categories.')->middleware('permission:categories.manage')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::put('/{category}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
        Route::post('/{category}/products', [CategoryController::class, 'assignProducts'])->name('assign_products');
    });

    // Orders
    Route::prefix('orders')->name('orders.')->middleware('permission:orders.view')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('cancel')
            ->middleware('permission:orders.cancel');
        Route::post('/{order}/refund', [OrderController::class, 'refund'])->name('refund')
            ->middleware('permission:orders.refund');
        Route::post('/{order}/approve', [OrderController::class, 'approve'])->name('approve')
            ->middleware('permission:orders.approve');
    });

    // Users
    Route::prefix('users')->name('users.')->middleware('permission:users.view')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::put('/{user}', [UserController::class, 'update'])->name('update')
            ->middleware('permission:users.update');
        Route::post('/{user}/block', [UserController::class, 'block'])->name('block')
            ->middleware('permission:users.block');
        Route::post('/{user}/unblock', [UserController::class, 'unblock'])->name('unblock')
            ->middleware('permission:users.block');
    });

    // Tenants / B2B clients
    Route::prefix('tenants')->name('tenants.')->middleware('permission:tenants.view')->group(function () {
        Route::get('/', [TenantController::class, 'index'])->name('index');
        Route::get('/create', [TenantController::class, 'create'])->name('create')
            ->middleware('permission:tenants.create');
        Route::post('/', [TenantController::class, 'store'])->name('store')
            ->middleware('permission:tenants.create');
        Route::get('/{tenant}', [TenantController::class, 'show'])->name('show');
        Route::put('/{tenant}', [TenantController::class, 'update'])->name('update')
            ->middleware('permission:tenants.update');
        Route::post('/{tenant}/suspend', [TenantController::class, 'suspend'])->name('suspend')
            ->middleware('permission:tenants.suspend');
        Route::post('/{tenant}/products', [TenantController::class, 'assignProducts'])->name('assign_products')
            ->middleware('permission:tenants.assign_products');
    });

    // Wallets (literal routes before {wallet})
    Route::prefix('wallets')->name('wallets.')->middleware('permission:wallets.view')->group(function () {
        Route::get('/', [WalletController::class, 'index'])->name('index');
        Route::get('/load-requests', [WalletController::class, 'loadRequests'])->name('load_requests')
            ->middleware('permission:wallets.load_requests.view');
        Route::post('/load-requests/{loadRequest}/approve', [WalletController::class, 'approveLoad'])->name('load_requests.approve')
            ->middleware('permission:wallets.load_requests.approve');
        Route::post('/load-requests/{loadRequest}/reject', [WalletController::class, 'rejectLoad'])->name('load_requests.reject')
            ->middleware('permission:wallets.load_requests.reject');

        Route::get('/{wallet}', [WalletController::class, 'show'])->name('show');
        Route::post('/{wallet}/credit', [WalletController::class, 'credit'])->name('credit')
            ->middleware('permission:wallets.credit');
        Route::post('/{wallet}/debit', [WalletController::class, 'debit'])->name('debit')
            ->middleware('permission:wallets.debit');
        Route::post('/{wallet}/freeze', [WalletController::class, 'freeze'])->name('freeze')
            ->middleware('permission:wallets.freeze');
        Route::post('/{wallet}/unfreeze', [WalletController::class, 'unfreeze'])->name('unfreeze')
            ->middleware('permission:wallets.freeze');
    });

    // Support tickets
    Route::prefix('tickets')->name('tickets.')->middleware('permission:tickets.view')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->name('index');
        Route::get('/lookup-user', [TicketController::class, 'lookupUser'])->name('lookup-user')
            ->middleware('permission:tickets.create');
        Route::get('/create', [TicketController::class, 'create'])->name('create')
            ->middleware('permission:tickets.create');
        Route::post('/', [TicketController::class, 'store'])->name('store')
            ->middleware('permission:tickets.create');
        Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
        Route::put('/{ticket}', [TicketController::class, 'update'])->name('update')
            ->middleware('permission:tickets.update');
        Route::post('/{ticket}/reply', [TicketController::class, 'reply'])->name('reply')
            ->middleware('permission:tickets.reply');
        Route::post('/{ticket}/resolve', [TicketController::class, 'resolve'])->name('resolve')
            ->middleware('permission:tickets.resolve');
    });

    // Offers
    Route::prefix('offers')->name('offers.')->middleware('permission:offers.view')->group(function () {
        Route::get('/', [OfferController::class, 'index'])->name('index');
        Route::get('/create', [OfferController::class, 'create'])->name('create')
            ->middleware('permission:offers.create');
        Route::post('/', [OfferController::class, 'store'])->name('store')
            ->middleware('permission:offers.create');
        Route::get('/{offer}/edit', [OfferController::class, 'edit'])->name('edit')
            ->middleware('permission:offers.update');
        Route::put('/{offer}', [OfferController::class, 'update'])->name('update')
            ->middleware('permission:offers.update');
        Route::patch('/{offer}/toggle', [OfferController::class, 'toggle'])->name('toggle')
            ->middleware('permission:offers.toggle');
        Route::delete('/{offer}', [OfferController::class, 'destroy'])->name('destroy')
            ->middleware('permission:offers.delete');
    });

    // Audit Logs (export before {log})
    Route::prefix('audit-logs')->name('audit-logs.')->middleware('permission:audit_logs.view')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
        Route::get('/export', [AuditLogController::class, 'export'])->name('export')
            ->middleware('permission:audit_logs.export');
        Route::get('/{log}', [AuditLogController::class, 'show'])->name('show');
    });

    // Security Dashboard
    Route::prefix('security')->name('security.')->middleware('permission:security.view')->group(function () {
        Route::get('/', [SecurityDashboardController::class, 'index'])->name('index');
        Route::get('/events', [SecurityDashboardController::class, 'events'])->name('events');
        Route::get('/events/{event}', [SecurityDashboardController::class, 'showEvent'])->name('events.show');
        Route::post('/events/{event}/resolve', [SecurityDashboardController::class, 'resolveEvent'])->name('events.resolve')
            ->middleware('permission:security.resolve_event');
        Route::get('/blocked-ips', [SecurityDashboardController::class, 'blockedIps'])->name('blocked-ips');
        Route::post('/blocked-ips', [SecurityDashboardController::class, 'blockIp'])->name('blocked-ips.store')
            ->middleware('permission:security.block_ip');
        Route::delete('/blocked-ips/{ip}', [SecurityDashboardController::class, 'unblockIp'])->name('blocked-ips.destroy')
            ->middleware('permission:security.unblock_ip');
        Route::get('/blocked-mobiles', [SecurityDashboardController::class, 'blockedMobiles'])->name('blocked-mobiles');
        Route::post('/blocked-mobiles', [SecurityDashboardController::class, 'blockMobile'])->name('blocked-mobiles.store')
            ->middleware('permission:security.block_mobile');
        Route::delete('/blocked-mobiles/{mobile}', [SecurityDashboardController::class, 'unblockMobile'])->name('blocked-mobiles.destroy')
            ->where('mobile', '[0-9]{10}')
            ->middleware('permission:security.unblock_mobile');
        Route::get('/users/{user}/profile', [SecurityDashboardController::class, 'userProfile'])->name('user-profile');
        Route::get('/fraud-queue', [SecurityDashboardController::class, 'fraudQueue'])->name('fraud-queue')
            ->middleware('permission:security.view_fraud_queue');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->middleware('permission:settings.view')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::put('/', [SettingsController::class, 'update'])->name('update')
            ->middleware('permission:settings.update');
        Route::post('/sections', [SettingsController::class, 'storeSection'])->name('sections.store')
            ->middleware('permission:settings.update');
        Route::put('/sections/{section}', [SettingsController::class, 'updateSection'])->name('sections.update')
            ->middleware('permission:settings.update');
        Route::delete('/sections/{section}', [SettingsController::class, 'destroySection'])->name('sections.destroy')
            ->middleware('permission:settings.update');
        Route::post('/sections/reorder', [SettingsController::class, 'reorderSections'])->name('sections.reorder')
            ->middleware('permission:settings.update');
    });

    // Voucher provider dashboards
    Route::get('/providers', [ProviderDashboardController::class, 'index'])->name('providers.index')
        ->middleware('permission:providers.view');
    Route::get('/kgen', [KGenAdminController::class, 'index'])->name('kgen.index')
        ->middleware('permission:providers.view');
    Route::get('/value-design', [ValueDesignAdminController::class, 'index'])->name('value-design.index')
        ->middleware('permission:providers.view');
    Route::get('/woohoo-admin', [WoohooAdminController::class, 'index'])->name('woohoo.index')
        ->middleware('permission:providers.view');

    // Vouchagram (Send + Pull API tools & catalog sync)
    Route::prefix('vouchagram')->name('vouchagram.')->middleware('permission:providers.view')->group(function () {
        Route::get('/', [VouchagramController::class, 'index'])->name('index');
        Route::post('/fetch-brands', [VouchagramController::class, 'fetchBrands'])->name('fetch-brands');
        Route::post('/send-voucher', [VouchagramController::class, 'sendVoucher'])->name('send-voucher');
        Route::post('/pull-voucher', [VouchagramController::class, 'pullVoucher'])->name('pull-voucher');
        Route::post('/check-send-status', [VouchagramController::class, 'checkSendStatus'])->name('check-send-status');
        Route::post('/check-pull-status', [VouchagramController::class, 'checkPullStatus'])->name('check-pull-status');
        Route::post('/check-stock', [VouchagramController::class, 'checkStock'])->name('check-stock');
        Route::post('/store-list', [VouchagramController::class, 'getStoreList'])->name('store-list');
        Route::post('/sync-catalog', [VouchagramController::class, 'syncCatalog'])->name('sync-catalog');
    });
});
