<?php

use App\Http\Controllers\AthenaGiftCardController;
use App\Http\Controllers\BrandExportController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\ContactUsController;
use App\Http\Controllers\DeliveryPartnerController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\GetEvcRequestController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\KGenOrderController;
use App\Http\Controllers\KGenWalletController;
use App\Http\Controllers\MyOrderController;
use App\Http\Controllers\PaymentStatusController;
use App\Http\Controllers\ProductSlugController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Storefront\StaticPageController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StorefrontBrandController;
use App\Http\Controllers\StorefrontBusinessController;
use App\Http\Controllers\StorefrontCategoryController;
use App\Http\Controllers\TransactionReportController;
use App\Http\Controllers\UserBlockController;
use App\Http\Controllers\VDHomeController;
use App\Http\Controllers\VDWebController;
use App\Http\Controllers\ViewCardDetailsController;
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
Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');
Route::get('/search', [SearchController::class, 'search'])->name('search');
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
Route::post('/check-data', [CommonController::class, 'checkData'])->middleware('admin.user')->name('check-data');

// ── Public Catalog APIs ─────────────────────────────────────────────
Route::get('/api/vd-brands/home', [VDHomeController::class, 'getVDBrandsForHome'])->name('vd.brands.home');

// ── KGen Public Routes ──────────────────────────────────────────────
Route::get('/kgen-products', [DeliveryPartnerController::class, 'showproducts'])->name('products');
Route::get('/products/{productID}', [DeliveryPartnerController::class, 'getproductsbyID'])->name('products.getById');
Route::get('/kgen-place-order', [KGenOrderController::class, 'showForm'])->name('place-order.form');
Route::post('/kgen-place-order', [KGenOrderController::class, 'placeOrder'])->name('place-order.submit');
Route::middleware('auth')->group(function () {
    Route::get('/kgen-orders', [KGenOrderController::class, 'listOrders'])->name('orders.list');
    Route::get('/get-kgenorders', [KGenOrderController::class, 'getOrders'])->name('orders.get');
});
Route::get('/order/{order}/assets', [KGenOrderController::class, 'showAssets'])->name('order.assets');
Route::get('/order/{order}/download', [KGenOrderController::class, 'downloadAsset'])->name('order.download');
Route::get('/order/{orderID}/monitor', [KGenOrderController::class, 'monitorOrder'])->name('order.monitor');
Route::get('/kgen-order/success/{orderId}', [KGenOrderController::class, 'showSuccess'])->name('kgen.order.success');
Route::get('/kgen-order/failed/{orderId}', [KGenOrderController::class, 'showFailed'])->name('kgen.order.failed');
Route::get('/get-evc-request/create', [GetEvcRequestController::class, 'create'])->name('getevc.request');
Route::post('/get-evc-request', [GetEvcRequestController::class, 'store']);

// ── Admin-Only Legacy Routes (admin.user middleware) ────────────────
Route::middleware('admin.user')->group(function () {
    Route::get('/admin/brands/export', [BrandExportController::class, 'export'])->name('brands.export');
    Route::get('/admin/stores/filter', [StoreController::class, 'filterStores'])->name('admin.stores.filter');
    Route::get('/admin/stores/select', [StoreController::class, 'showForm'])->name('admin.stores.form');
    Route::post('/admin/stores/fetch', [StoreController::class, 'fetchStoresForBrand'])->name('admin.stores.fetch');
    Route::post('/admin/stores/sync', [StoreController::class, 'syncAndShow'])->name('admin.stores.sync');
    Route::get('/admin/stores/export', [StoreController::class, 'exportStores'])->name('admin.stores.export');
    Route::get('/admin/vd/brands', [VDWebController::class, 'showBrands'])->name('admin.vd.brands');
    Route::get('/admin/vd/dashboard', fn () => Inertia::render('Admin/ValueDesign/VdDataPage', [
        'title' => 'Value Design dashboard',
        'data' => ['message' => 'Legacy dashboard migrated to React. Use VD brands and EVC tools from this admin section.'],
    ]))->name('admin.vd.dashboard');
    Route::get('/admin/evc/request', [VDWebController::class, 'requestEvc'])->name('admin.evc.request');
    Route::post('/admin/evc/store-request', [VDWebController::class, 'storeGetEvcRequest'])->name('admin.evc.store-request');
    Route::post('/admin/evc/decrypt-store', [VDWebController::class, 'decryptAndStoreEvc'])->name('admin.evc.decrypt-store');
    Route::post('/admin/evc/status', [VDWebController::class, 'VDgetEvcStatus'])->name('admin.evc.status');
    Route::get('/admin/evc/form', fn () => Inertia::render('Admin/ValueDesign/VdDataPage', [
        'title' => 'EVC form',
        'data' => ['message' => 'Use POST endpoints under /admin/evc/* or the get-evc-request flow from the storefront.'],
    ]))->name('admin.evc.form');
    Route::get('/admin/evc-details/{orderId}/{requestRefNo}', [VDWebController::class, 'evcDetails'])->name('admin.evc.details');
    Route::post('/admin/evc/get-activated', [VDWebController::class, 'VDgetActivatedEvc'])->name('admin.evc.activated');
    Route::post('/admin/api/vd-brands/clear-cache', [VDHomeController::class, 'clearVDBrandsCache'])->name('admin.vd.brands.clear-cache');
    Route::get('/admin/api/vd-brands/test-connection', [VDHomeController::class, 'testVDConnection'])->name('admin.vd.brands.test-connection');
    Route::get('/admin/lysto/giftcards', [AthenaGiftCardController::class, 'index'])->name('admin.lysto.giftcards');
    Route::get('/admin/lysto/giftcards/{giftcard_id}/skus', [AthenaGiftCardController::class, 'getSkus'])->name('admin.lysto.giftcards.show');
    Route::get('/admin/lysto/orders', [AthenaGiftCardController::class, 'getOrder'])->name('admin.lysto.orders');
    Route::get('/admin/lysto/wallet-balance', [AthenaGiftCardController::class, 'getWalletBalance'])->name('admin.lysto.wallet-balance');
    Route::get('/admin/lysto/giftcards2', [AthenaGiftCardController::class, 'showGiftcards'])->name('admin.lysto.giftcards2.index');
    Route::get('/admin/lysto/giftcard/purchase/view', [AthenaGiftCardController::class, 'purchaseView'])->name('admin.lysto.giftcard.purchase.view');
    Route::post('/admin/lysto/giftcard/purchase', [AthenaGiftCardController::class, 'purchase'])->name('admin.lysto.giftcard.purchase');
    Route::get('/admin/lysto/dashboard', fn () => Inertia::render('Admin/ValueDesign/VdDataPage', [
        'title' => 'Lysto / Athena gift cards',
        'data' => ['message' => 'Use routes under /admin/lysto/* for gift cards, SKUs, and orders.'],
    ]))->name('admin.lysto.dashboard');
    Route::get('/admin/kgen/wallet', [KGenWalletController::class, 'wallet'])->name('admin.kgen.wallet');
    Route::get('/admin/kgen/wallet/export-csv', [KGenWalletController::class, 'exportCsv'])->name('admin.kgen.wallet.exportCsv');
    Route::get('/admin/kgen/transactions', [KGenWalletController::class, 'index'])->name('admin.kgen.transactions.index');
    Route::get('/admin/kgen/wallet/latest-balance', [KGenWalletController::class, 'latest'])->name('admin.kgen.wallet.latest');
    Route::get('/admin/send-transaction-report/{id}', [TransactionReportController::class, 'sendToCardPay'])->name('send.transaction.report');
    Route::get('/admin/invoices/{id}/create-invoice', [InvoiceController::class, 'storeAndSendInvoice'])->name('create_invoice');
    Route::post('/admin/upload-data', [DocumentController::class, 'uploadData'])->name('uploadData');
    Route::get('/admin/export-blocked-users', [UserBlockController::class, 'exportBlockedUsers'])->name('admin.export.blocked.users');
    Route::post('/admin/block-user', [UserBlockController::class, 'blockUser'])->name('admin.block.user');
    Route::post('/admin/unblock-user', [UserBlockController::class, 'unblockUser'])->name('admin.unblock.user');
    Route::get('/admin/check-user-restrictions', [UserBlockController::class, 'checkUserRestrictions'])->name('admin.check.user.restrictions');
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
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FILE_NOT_FOUND', 'message' => 'The requested file does not exist.'],
            ], 404);
        }

        $basePath = realpath(storage_path('app/public'));
        if ($basePath === false) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FILE_NOT_FOUND', 'message' => 'The requested file does not exist.'],
            ], 404);
        }

        $candidate = storage_path('app/public/'.str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
        $fullPath = realpath($candidate);

        $isAllowedPath = $fullPath !== false
            && ($fullPath === $basePath || str_starts_with($fullPath, $basePath.DIRECTORY_SEPARATOR));

        if (! $isAllowedPath) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'FILE_NOT_FOUND', 'message' => 'The requested file does not exist.'],
            ], 404);
        }

        if (is_file($fullPath)) {
            return response()->file($fullPath);
        }

        return response()->json([
            'success' => false,
            'error' => ['code' => 'FILE_NOT_FOUND', 'message' => 'The requested file does not exist.'],
        ], 404);
    }

    return response()->json([
        'success' => false,
        'error' => ['code' => 'NOT_FOUND', 'message' => 'The requested URL was not found on this server.'],
    ], 404);
});
