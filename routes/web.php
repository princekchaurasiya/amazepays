<?php

use App\Enums\ResponseCode;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\Voucher\LystoGiftCardController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\ContactUsController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MyOrderController;
use App\Http\Controllers\PaymentStatusController;
use App\Http\Controllers\ProductSlugController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\Storefront\CardBalanceController;
use App\Http\Controllers\Storefront\StaticPageController;
use App\Http\Controllers\StorefrontBrandController;
use App\Http\Controllers\StorefrontBusinessController;
use App\Http\Controllers\StorefrontCategoryController;
use App\Http\Controllers\TransactionReportController;
use App\Http\Controllers\UserBlockController;
use App\Http\Controllers\Voucher\ValueDesignStorefrontController;
use App\Http\Controllers\ViewCardDetailsController;
use App\Support\Http\ResponsePayload;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/admin', '/panel', 301);

/*
|--------------------------------------------------------------------------
| Public Storefront Routes
|--------------------------------------------------------------------------
| Public (unauthenticated) browsing and authenticated user-panel routes.
| Auth, checkout, payment, webhook, and admin routes live in their own files.
*/

// ── Homepage & Catalog ──────────────────────────────────────────────
Route::get('/', [HomePageController::class, 'homePage'])->name('home');
Route::get('/product/{slug}', [ProductSlugController::class, 'getProductBySlug'])->name('get-product-by-slug');
Route::get('/view-all-product', [HomePageController::class, 'viewAllProduct'])->name('view-all-product');

// Throttled: suggest is the highest-risk scraping target on any storefront.
Route::get('/search/suggest', [SearchController::class, 'suggest'])
    ->middleware('throttle:60,1')
    ->name('search.suggest');
Route::get('/search', [SearchController::class, 'search'])
    ->middleware('throttle:30,1')
    ->name('search');

Route::get('/category/{slug}', [StorefrontCategoryController::class, 'show'])->name('categories.show');
Route::get('/brand/{slug}', [StorefrontBrandController::class, 'show'])->name('brands.show');
Route::get('/business', [StorefrontBusinessController::class, 'show'])->name('business.landing');

// ── Static Pages ────────────────────────────────────────────────────
Route::get('/about', [StaticPageController::class, 'about'])->name('about');
Route::get('/contact-us', [StaticPageController::class, 'contact'])->name('contact-us');
Route::get('/terms-of-use', [StaticPageController::class, 'terms'])->name('terms-of-use');
Route::get('/privacy-policy', [StaticPageController::class, 'privacy'])->name('privacy-policy');
Route::get('/refund-policy', [StaticPageController::class, 'refund'])->name('refundPolicy');
Route::get('/faq', [StaticPageController::class, 'faq'])->name('faq');
Route::get('/card-balance', [CardBalanceController::class, 'index'])->name('card.balance');
Route::post('/card-balance/check', [CardBalanceController::class, 'check'])
    ->name('card.balance.check')
    ->middleware('throttle:20,1');
Route::get('/gift', fn () => Inertia::render('Storefront/GiftMailPreview'))->name('gift.preview');
Route::get('/payment-status', PaymentStatusController::class)->name('payment.status');

// ── Error Pages ─────────────────────────────────────────────────────
Route::get('/error', [ErrorController::class, 'handleError'])->name('error');
Route::get('/404', fn () => abort(404))->name('404');

// ── Authenticated User Panel ────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile', [StaticPageController::class, 'profile'])->name('profile');
    Route::get('/my-order', [MyOrderController::class, 'displayOrder'])->name('my-order');
    Route::get('/my-vdorder', [MyOrderController::class, 'displayValueDesignOrder'])->name('my-vdorder');
    Route::post('/check-mobile-number', [ProfileController::class, 'isMobileNumberInUse'])->name('check-mobile-number');
    Route::post('/update-profile', [ProfileController::class, 'updateProfile'])->name('update-profile');
    Route::get('/view-card-details/{orderId}', [ViewCardDetailsController::class, 'index'])->name('view-card-details');
    Route::post('/profile-update-send-otp', [SmsController::class, 'profileUpdateSendOtp'])->name('profile-update-send-otp');
    Route::post('/profile-update-verify-otp', [ProfileController::class, 'profileUpdateVerifyOtp'])->name('profile-update-verify-otp');
    Route::get('/invoice', [StaticPageController::class, 'invoice'])->name('invoice');
});

// ── Contact & Misc ──────────────────────────────────────────────────
Route::post('/save-contact', [ContactUsController::class, 'saveContact'])->name('save-contact');

// ── Public Catalog APIs ─────────────────────────────────────────────
Route::get('/api/vd-brands/home', [ValueDesignStorefrontController::class, 'brandsForHome'])->name('vd.brands.home');

// ── Legacy KGen / public EVC form (retired) ───────────────────────────
// Old storefront used /kgen/* and stored rows in legacy tables. Phase 3 uses
// ProviderOrder + GiftCard; admin Value Design tools live under /panel/value-design.
Route::redirect('/kgen', '/', 301);
Route::redirect('/kgen/{any}', '/', 301)->where('any', '.*');

// ── Admin-Only Routes (legacy admin.user middleware) ─────────────────
// NOTE: Provider dashboards and tools are now under /panel (routes/admin.php).
Route::middleware('admin.user')->group(function () {
    Route::get('/admin/brands/export', [ExportController::class, 'brands'])->name('brands.export');
    // Legacy VD routes retired (kept for inbound old bookmarks/links).
    // NOTE: route names were also renamed to panel.* to avoid carrying the "admin" naming forward.
    Route::get('/admin/vd/brands', fn () => redirect('/panel/value-design'))->name('panel.legacy.vd.brands');
    Route::get('/admin/vd/dashboard', fn () => redirect('/panel/value-design'))->name('panel.legacy.vd.dashboard');
    Route::get('/admin/evc/request', fn () => redirect('/panel/value-design'))->name('panel.legacy.vd.evc.request');
    Route::post('/admin/evc/store-request', fn () => ResponsePayload::fail(ResponseCode::NOT_FOUND, 'payments.legacy_endpoint_retired_use_panel_value_design', httpStatus: 410))->name('panel.legacy.vd.evc.store-request');
    Route::post('/admin/evc/decrypt-store', fn () => ResponsePayload::fail(ResponseCode::NOT_FOUND, 'payments.legacy_endpoint_retired_use_panel_value_design', httpStatus: 410))->name('panel.legacy.vd.evc.decrypt-store');
    Route::post('/admin/evc/status', fn () => ResponsePayload::fail(ResponseCode::NOT_FOUND, 'payments.legacy_endpoint_retired_use_panel_value_design', httpStatus: 410))->name('panel.legacy.vd.evc.status');
    // Legacy route removed (Blade view deleted); redirect to panel instead.
    Route::get('/admin/evc/form', fn () => redirect('/panel/value-design'))->name('panel.legacy.vd.evc.form');
    Route::get('/admin/evc-details/{orderId}/{requestRefNo}', fn () => redirect('/panel/value-design'))->name('panel.legacy.vd.evc.details');
    Route::post('/admin/evc/get-activated', fn () => ResponsePayload::fail(ResponseCode::NOT_FOUND, 'payments.legacy_endpoint_retired_use_panel_value_design', httpStatus: 410))->name('panel.legacy.vd.evc.activated');
    // Provider-distributor dashboards moved to /panel/*; legacy /admin/* routes removed.

    // FIX: was GET — sends an external email/API call; must be POST to prevent
    // accidental triggering via browser prefetch or URL sharing.
    Route::post('/admin/send-transaction-report/{id}', [TransactionReportController::class, 'sendToCardPay'])->name('send.transaction.report');

    // FIX: was GET — creates a database record; must be POST per HTTP semantics.
    Route::post('/admin/invoices/{id}/create-invoice', [InvoiceController::class, 'storeAndSendInvoice'])->name('create_invoice');

    Route::post('/admin/upload-data', [DocumentController::class, 'uploadData'])->name('uploadData');
    Route::get('/admin/export-blocked-users', [UserBlockController::class, 'exportBlockedUsers'])->name('admin.export.blocked.users');
    Route::post('/admin/block-user', [UserBlockController::class, 'blockUser'])->name('admin.block.user');
    Route::post('/admin/unblock-user', [UserBlockController::class, 'unblockUser'])->name('admin.unblock.user');
    Route::get('/admin/check-user-restrictions', [UserBlockController::class, 'checkUserRestrictions'])->name('admin.check.user.restrictions');

    // FIX: renamed from /check-data — descriptive name + moved inside admin.user guard.
    // Ensure CommonController::checkData validates all inputs strictly.
    Route::post('/admin/check-data', [CommonController::class, 'checkData'])->name('admin.check.data');
});

// ── Fallback ────────────────────────────────────────────────────────
Route::fallback(function () {
    $path = request()->path();

    // Missing Vite build files must not return JSON (breaks stylesheet/script MIME checks).
    if (str_starts_with($path, 'build/')) {
        return response('Not Found', 404, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    if (str_starts_with($path, 'storage/')) {
        $relativePath = substr($path, 8);
        if ($relativePath === '' || str_contains($relativePath, '..') || str_contains($relativePath, "\0")) {
            return ResponsePayload::fail(ResponseCode::NOT_FOUND, 'responses.NOT_FOUND', details: [
                'reason' => 'storage_path_invalid',
            ], httpStatus: 404);
        }

        $basePath = realpath(storage_path('app/public'));
        if ($basePath === false) {
            return ResponsePayload::fail(ResponseCode::NOT_FOUND, 'responses.NOT_FOUND', details: [
                'reason' => 'storage_base_missing',
            ], httpStatus: 404);
        }

        $candidate = storage_path('app/public/'.str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
        $fullPath = realpath($candidate);

        $isAllowedPath = $fullPath !== false
            && ($fullPath === $basePath || str_starts_with($fullPath, $basePath.DIRECTORY_SEPARATOR));

        if (! $isAllowedPath) {
            return ResponsePayload::fail(ResponseCode::NOT_FOUND, 'responses.NOT_FOUND', details: [
                'reason' => 'storage_path_disallowed',
            ], httpStatus: 404);
        }

        if (is_file($fullPath)) {
            return response()->file($fullPath);
        }

        return ResponsePayload::fail(ResponseCode::NOT_FOUND, 'responses.NOT_FOUND', details: [
            'reason' => 'storage_file_missing',
        ], httpStatus: 404);
    }

    return ResponsePayload::fail(ResponseCode::NOT_FOUND, 'responses.NOT_FOUND', httpStatus: 404);
});
