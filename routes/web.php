<?php

use Illuminate\Support\Facades\Route;
use TCG\Voyager\Events\RoutingAdmin;
use Illuminate\Support\Facades\Log;
use TCG\Voyager\Facades\Voyager;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\UnlimitPaymentController;
use App\Http\Services\VDWebApiService;
use App\Http\Services\AthenaGiftCardService;

use App\Http\Controllers\{
    HomePageController,
    UserPanelController,
    AmazepayCategoryController,
    AmazepayBrandController,
    CommonController,
    MyOrderController,
    SmsController,
    WoohooOrderController,
    OtpLoginController,
    SearchController,
    OtpVerificationController,
    ProductPageController,
    VDPageController,
    CCAvenueController,
    PaymentDetailsExportController,
    ProfileController,
    ProductSlugController,
    ErrorController,
    CreateOrderController,
    DocumentController,
    ViewCardDetailsController,
    ContactUsController,
    CardBalanceController,
    ChangePasswordUpdateController,
    Voyager\VoyagerGenerateBearerTokenController,
    Voyager\VoyagerGetCategoryController,
    Voyager\VoyagerFetchProductListController,
    Voyager\VoyagerFetchProductDataController,
    Voyager\VoyagerOrderExportController,
    Voyager\ProductDetailsExportController,
    UserBlockController,
    VDWebController,
    VDAESdecrptController2,
    StoreBrandsController,
    AthenaGiftCardController,
    AuthController,
    VDPaymentController,
    DeliveryPartnerController,
    KGenPaymentController,
};

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
    $namespacePrefix = '\\' . config('voyager.controllers.namespace') . '\\';

    // User Blocking Routes with proper middleware
    Route::middleware(['web', 'auth', 'admin.user'])->group(function () {
        // View route
        Route::get('/user-blocking', function () {
            return view('admin.user-blocking');
        })->name('admin.user.blocking');

        // API routes for user blocking
        Route::post('/block-user', [UserBlockController::class, 'blockUser'])->name('admin.block.user');
        Route::post('/unblock-user', [UserBlockController::class, 'unblockUser'])->name('admin.unblock.user');
        Route::get('/check-user-restrictions', [UserBlockController::class, 'checkUserRestrictions'])->name('admin.check.user.restrictions');
    });

    // Route::resource('/cc-avenue-payment', 'VoyagerCcAvenueController');
    // Route::get('/import-data', [DocumentController::class, 'importDocument']);
    Route::group(['middleware' => ['admin.user']], function () use ($namespacePrefix) {
        Route::view('/upload-document', 'documentUpload');
        event(new RoutingAdmin());

        // API Management Routes
        Route::get('/api-management', [\App\Http\Controllers\Voyager\VoyagerApiController::class, 'index'])->name('admin.api.index');
        Route::post('/api-management/generate-bearer-token', [\App\Http\Controllers\Voyager\VoyagerApiController::class, 'generateBearerToken'])->name('admin.api.generate-bearer-token');
        Route::post('/api-management/fetch-category', [\App\Http\Controllers\Voyager\VoyagerApiController::class, 'fetchCategoryData'])->name('admin.api.fetch-category');
        Route::post('/api-management/fetch-product-list', [\App\Http\Controllers\Voyager\VoyagerApiController::class, 'fetchProductList'])->name('admin.api.fetch-product-list');
        Route::post('/api-management/fetch-product-data', [\App\Http\Controllers\Voyager\VoyagerApiController::class, 'fetchProductData'])->name('admin.api.fetch-product-data');
        Route::post('/api-management/fetch-kgen-products', [\App\Http\Controllers\Voyager\VoyagerApiController::class, 'fetchKgenProducts'])->name('admin.api.fetch-kgen-products');
        Route::post('/api-management/fetch-featured-kgen-products', [\App\Http\Controllers\Voyager\VoyagerApiController::class, 'fetchFeaturedKgenProducts'])->name('admin.api.fetch-featured-kgen-products');
        Route::post('/api-management/fetch-vd-brands', [\App\Http\Controllers\Voyager\VoyagerApiController::class, 'fetchVDBrands'])->name('admin.api.fetch-vd-brands');
        Route::post('/api-management/sync-vd-stores', [\App\Http\Controllers\Voyager\VoyagerApiController::class, 'syncVDStores'])->name('admin.api.sync-vd-stores');
        Route::post('/api-management/get-vd-wallet-balance', [\App\Http\Controllers\Voyager\VoyagerApiController::class, 'getVDWalletBalance'])->name('admin.api.get-vd-wallet-balance');
        Route::post('/api-management/fetch-kgen-wallet-balance', [\App\Http\Controllers\Voyager\VoyagerApiController::class, 'fetchKGenWalletBalance'])->name('admin.api.fetch-kgen-wallet-balance');
        Route::post('/api-management/fetch-lysto-gift-cards', [\App\Http\Controllers\Voyager\VoyagerApiController::class, 'fetchLystoGiftCards'])->name('admin.api.fetch-lysto-gift-cards');
        Route::post('/api-management/get-lysto-wallet-balance', [\App\Http\Controllers\Voyager\VoyagerApiController::class, 'getLystoWalletBalance'])->name('admin.api.get-lysto-wallet-balance');
        Route::get('/api-management/export-user-payment-details', [\App\Http\Controllers\Voyager\VoyagerApiController::class, 'exportUserPaymentDetails'])->name('admin.api.export-user-payment-details');

        Route::post('/upload-data', [DocumentController::class, 'uploadData'])->name('uploadData');

        Route::get('download-product-details', [ProductDetailsExportController::class, 'export'])->name('download-product-details');

        Route::match(['get', 'post'], '/voyager/bearer-token', [VoyagerGenerateBearerTokenController::class, 'generateBearerToken'])->name('voyager.bearerToken');
        Route::match(['get', 'post'], '/voyager/get-category', [VoyagerGetCategoryController::class, 'getCategory'])->name('voyager.getCategory');
        Route::match(['get', 'post'], '/voyager/fetch-product-list', [VoyagerFetchProductListController::class, 'fetchProductList'])->name('voyager.productList');
        Route::match(['get', 'post'], '/voyager/fetch-product-data', [VoyagerFetchProductDataController::class, 'fetchProductData'])->name('voyager.fetchProductData');
        // Route::get('admin/import-product-discount',  [VoyagerProductDiscountImportController::class, 'import'])->name('import-product-discount');

        Route::get('/download-order-sheet', [VoyagerOrderExportController::class, 'export'])->name('downloadOrderSheet');

        // In routes/web.php
        Route::get('cactus/logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index']);

        Route::post('/resend-order', [WoohooOrderController::class, 'createOrder'])->name('resend.order');

        // In routes/web.php
Route::get('/export-blocked-users', [UserBlockController::class, 'exportBlockedUsers'])->name('admin.export.blocked.users');


    });
});


Route::get('logout', [UserPanelController::class, 'userLogOut'])->name('userLogOut');

// Public routes that don't require authentication
Route::get('/', [HomePageController::class, 'homePage'])->name('home');
Route::get('/product/{slug}', [ProductSlugController::class, 'getProductBySlug'])->name('get-product-by-slug');
Route::get('/view-all-product', [UserPanelController::class, 'viewAllProduct'])->name('view-all-product');
Route::get('/about', function () {
    return view('userpanel/about');
})->name('about');
Route::get('/contact-us', function () {
    return view('userpanel/contact-form');
})->name('contact-us');
Route::get('/terms-of-use', function () {
    return view('userpanel/terms-of-use');
})->name('terms-of-use');
Route::get('/privacy-policy', function () {
    return view('userpanel/privacy-policy');
})->name('privacy-policy');
Route::get('/search', [SearchController::class, 'search'])->name('search');
Route::get('/category/{slug}', [AmazepayCategoryController::class, 'show'])->name('categories.show');
Route::get('/brand/{slug}', [AmazepayBrandController::class, 'show'])->name('brands.show');
Route::get('/faq', function () {
    return view('faq.show');
})->name('faq');

// Route to handle redirection based on authentication status
// Route::get('/redirect-based-on-auth', [ProductSlugController::class, 'redirectBasedOnAuth'])->name('redirect-based-on-auth');


// Route to handle redirection based on authentication
// Route::get('/gift-product/{slug}', [ProductPageController::class, 'handleGiftRedirect'])->name('giftProductBySlug');

// Route::get('/product-category', [ProductCategoryController::class, 'getProductCategory'])->name('get-product-category');


// Route::get('/productPage/{id}', function () {
//     return view('userpanel/productPage_old');
// })->name('productPage');



Route::get('/refund-policy', function () {
    return view('userpanel/refund-policy');
})->name('refundPolicy');



Route::group(['middleware' => 'guest'], function () {
    Route::post('/user-registration', [UserPanelController::class, 'userRegistration'])->name('user-registration');
    Route::post('/user-login', [UserPanelController::class, 'userLogin'])->name('user-login');

    // Add a catch-all route for guests trying to access protected pages
    Route::get('/login', function () {
        return view('userpanel/login');
    })->name('login');

    Route::get('/unauthorized', function () {
        return view('unauthorized');
    })->name('unauthorized');
});

Route::get('/verify-email', [AuthController::class, 'showVerifyForm'])->name('verify.email');
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/resend-verification', [AuthController::class, 'resendVerification'])->name('resend.verification');

Route::group(['middleware' => 'auth'], function () {
    Route::get('/user-logout', [UserPanelController::class, 'userLogOut'])->name('user-logout');
    Route::post('/apply-coupan', [UserPanelController::class, 'applyCoupan'])->name('apply-coupan');
    Route::get('/my-order', [MyOrderController::class, 'displayOrder'])->name('my-order');
    Route::post('/save-gift-card-form', [UserPanelController::class, 'saveGiftCardFormValues'])->name('save-gift-card-form');
    Route::get('/change-password', function () {
        return view('userpanel/change-password');
    })->name('change-password');
    Route::post('/check-mobile-number', [ProfileController::class, 'isMobileNumberInUse'])->name('check-mobile-number');
   // Route::post('/payment-cancel', [CCAvenueController::class, 'handlePaymentCancellation'])->name('payment-cancel');
    Route::post('/update-profile', [ProfileController::class, 'updateProfile'])->name('update-profile');
    Route::get('/view-card-details/{orderId}', [ViewCardDetailsController::class, 'index'])->name('view-card-details');
    Route::post('/profile-update-send-otp', [SmsController::class, 'profileUpdateSendOtp'])->name('profile-update-send-otp');
    Route::post('/profile-update-verify-otp', [ProfileController::class, 'profileUpdateVerifyOtp'])->name('profile-update-verify-otp');
});



Route::post('/check-data', [CommonController::class, 'checkData'])->name('check-data');

Route::post('/change-password-update', [ChangePasswordUpdateController::class, 'updatePassword'])->name('password-change');

Route::post('/send-sms', [SmsController::class, 'loginWithOtp'])->name('send-sms');
Route::post('/register-otp', [SmsController::class, 'registerWithOtp'])->name('send-register-otp');
Route::post('/forget-password-send-otp', [SmsController::class, 'forgetPasswordWithMobileOtp'])->name('send-forgot-password-otp');
Route::post('/user-forgot-password', [UserPanelController::class, 'userForgotPassword'])->name('user-forgot-password');

Route::post('/verify-otp', [OtpVerificationController::class, 'loginVerifyOtp'])->name('verify-otp');
// Route for OTP verification during registration
Route::post('/verify-register-otp', [OtpVerificationController::class, 'registerVerifyOtp'])->name('verify-register-otp');
Route::get('/invoice', function () {
    return view('layouts.invoice');
})->name('invoice');
// OLD EXPORT ROUTE - REMOVED
// Route::get('/export', [PaymentDetailsExportController::class, 'export']); // Now part of joined export in API Management
Route::get('/error', [ErrorController::class, 'handleError'])->name('error');

Route::post('/save-contact', [ContactUsController::class, 'saveContact'])->name('save-contact');

Route::view('/gift', 'layouts.giftmail');

// This fallback route should be the last route in the file
Route::fallback(function () {
    $path = request()->path();

    // Check if this is a storage file request
    if (str_starts_with($path, 'storage/')) {
        $relativePath = substr($path, 8); // Remove 'storage/' prefix
        $fullPath = storage_path('app/public/' . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));

        // Check if file exists in public storage
        if (file_exists($fullPath)) {
            return response()->file($fullPath);
        }

        // If file doesn't exist, check if it's a Voyager image
        if (str_contains($path, 'slides/') || str_contains($path, 'amazepay-available-brands/') || str_contains($path, 'amazepay-categories/')) {
            // Create directory if it doesn't exist
            $directory = dirname($fullPath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Return a 404 response with helpful information
            $imageType = '';
            if (str_contains($path, 'slides/')) {
                $imageType = 'slide';
            } elseif (str_contains($path, 'amazepay-available-brands/')) {
                $imageType = 'brand';
            } elseif (str_contains($path, 'amazepay-categories/')) {
                $imageType = 'category';
            }

            return response()->json([
                'error' => 'Image not found',
                'message' => "The {$imageType} image has not been uploaded yet. Please upload an image through the admin panel.",
                'path' => $path,
                'full_path' => $fullPath,
                'image_type' => $imageType
            ], 404);
        }

        // For other storage files, return 404
        return response()->json([
            'error' => 'File not found',
            'message' => 'The requested file does not exist.',
            'path' => $path
        ], 404);
    }

    // For non-storage routes, return 404
    return response()->json([
        'error' => 'Not Found',
        'message' => 'The requested URL was not found on this server.',
        'path' => $path
    ], 404);
});

Route::get('/404', function () {
    abort(404);
})->name('404');


Route::get('/profile', function () {
    return view('userpanel/profile');
})->middleware('auth')->name('profile');




Route::get('/unauthenticated', function () {
    $message = session('message', 'You are not authenticated.');
    return redirect()->route('error', ['message' => $message]);
})->name('unauthenticated')->middleware('web');



//Route::post('/response_ccavenue', [CCAvenueController::class, 'responseCcavenue'])->name('response_ccavenue');


Route::post('/woohoo/create-order', [WoohooOrderController::class, 'createOrder'])->name('woohoo.createOrder');
Route::get('/woohoo/check-status', [WoohooOrderController::class, 'checkTransactionStatus'])->name('woohoo.checkStatus');
Route::post('/woohoo/clear-session', [WoohooOrderController::class, 'clearSessionData'])->name('woohoo.clearSession');

// Woohoo processing routes
use App\Http\Controllers\WoohooProcessingController;
Route::get('/woohoo/processing', [WoohooProcessingController::class, 'showProcessing'])->name('woohoo.processing');
Route::get('/woohoo/processing/create-order', [WoohooProcessingController::class, 'createOrder'])->name('woohoo.processing.createOrder');
Route::post('/woohoo/processing/create-order', [WoohooProcessingController::class, 'createOrder'])->name('woohoo.processing.createOrder.post');

Route::get('/order-failure', function () {
    return view('order-failure'); // This will render the order-failure.blade.php view
});

// Transaction routes that need protection
Route::middleware(['auth', 'check.transaction'])->group(function () {
    Route::post('/update-session-data', [ProductPageController::class, 'updateSessionData'])->name('updateSessionData');
    
    // Checkout routes - GET shows form, POST processes order
    Route::get('/checkout/{slug}', [ProductPageController::class, 'storePayNowData'])->name('checkoutPage');
    Route::post('/checkout/{slug}', [ProductPageController::class, 'storePayNowData'])->name('checkoutPage.post');
    
    // Payment processing routes
    Route::post('/payment-process', [UnlimitPaymentController::class, 'store'])->name('unlimit.store');
    
    Route::post('/save-gift-card-form', [UserPanelController::class, 'saveGiftCardFormValues'])->name('save-gift-card-form');
});

//Storing formData in database
use App\Http\Controllers\BillingController;
Route::post('/store-billing-data', [BillingController::class, 'store'])->name('storeBillingData');

//test

//Route for Auth
//Route::get('/cardpay/token', [UnlimitPaymentController::class, 'getToken']);
//Route::post('/unlimit/get-token', [UnlimitPaymentController::class, 'getToken'])->name('unlimit.getToken');

//Route for passing amount
Route::post('/unlimit-payment', [UnlimitPaymentController::class, 'showPaymentForm'])->name('unlimit.form');

// Route to show the form
/*Route::get('/unlimit/form', function () {
    return view('paymentFolder.unlimitpayment');
});*/

// Route to handle form submission
//Route::post('/unlimit/checkout', [UnlimitPaymentController::class, 'store'])->name('unlimit.checkout');
//Route::post('/unlimit/store', [UnlimitPaymentController::class, 'store'])->name('unlimit.store');



Route::get('/payment', function () {
    return view('payment'); // This assumes the file is at resources/views/payment.blade.php
});

// SECURITY: Rate limit payment return URLs
// This handles payment returns from Unlimit gateway and updates payment status
Route::middleware(['throttle:payments'])->group(function () {
    // User comes back from Unlimit - handles both GET and POST
    Route::match(['get', 'post'], '/unlimit/return', [\App\Http\Controllers\WoohooProcessingController::class, 'handleReturn'])
        ->name('unlimit.return');
});

// Create Woohoo order after return stores session (duplicate route removed - using woohoo.processing.createOrder instead)
// Route::get('/woohoo/create-order', [\App\Http\Controllers\WoohooProcessingController::class, 'createOrder'])
//     ->name('woohoo.createOrder');

// Show processing page
Route::get('/woohoo/process', [WoohooProcessingController::class, 'createOrder'])
    ->name('woohoo.process');

//Route::match(['GET','POST'], '/payment/return', [UnlimitPaymentController::class, 'handleReturnSuccess'])->name('unlimit.return');

// SECURITY: Rate limit and verify signature for webhooks
Route::middleware(['throttle:payment-callbacks', 'verify.unlimit.signature'])->group(function () {
    // Unlimit webhook endpoint
    Route::post('/unlimit/webhook', [UnlimitPaymentController::class, 'webhook'])->name('unlimit.webhook');
});

Route::get('/payment/success', function () {
    return view('payment.success');
})->name('payment.success');

// For failure page
Route::get('/payment/failed', function () {
    return view('payment.failed');
})->name('payment.failed');

Route::get('/payment/processed', function () {
    return view('payment.processed');
})->name('payment.processed');

Route::post('/unlimit-payment', [UnlimitPaymentController::class, 'process'])->name('unlimit.payment');

//For Voyager SendtoCardPay method
use Illuminate\Support\Facades\Http;
use App\TransactionReport;
use App\Http\Controllers\TransactionReportController;

Route::get('/admin/send-transaction-report/{id}', [TransactionReportController::class, 'sendToCardPay'])
    ->name('send.transaction.report');

//UPI payment route
//Route::post('/unlimit/upi_payment', [UPIPaymentController::class, 'store'])->name('payment.upi');

//routes to UPI Payment
use App\Http\Controllers\UPIPaymentController;
// SECURITY: Require authentication and rate limiting for payment routes
Route::middleware(['auth', 'web', 'throttle:payments'])->group(function () {
    Route::post('/payment/upi', [UPIPaymentController::class, 'store'])->name('payment.upi');
});
// SECURITY: Rate limit payment return URLs (can be accessed without auth initially)
Route::middleware(['throttle:payments'])->group(function () {
    Route::match(['GET','POST'], '/upi/return', [UPIPaymentController::class, 'handleReturn'])->name('upi.return');
});
// SECURITY: Rate limit and verify signature for webhooks
Route::middleware(['throttle:payment-callbacks', 'verify.unlimit.signature'])->group(function () {
    Route::post('/upi/webhook', [UPIPaymentController::class, 'webhook'])->name('upi.webhook');
});

//routes to Net Banking Payment
use App\Http\Controllers\NetBankPaymentController;
Route::post('/payment/netbnk', [NetBankPaymentController::class, 'store'])->name('payment.netbnk');

//For Voyager Invoice method
use App\Http\Controllers\InvoiceController;

//Route::post('/create-invoice', [InvoiceController::class, 'storeAndSendInvoice'])->name('invoice.create');

use App\Invoice;
Route::get('/admin/invoices/{id}/create-invoice', [\App\Http\Controllers\InvoiceController::class, 'storeAndSendInvoice'])->name('create_invoice');

/*Route::get('/about', function () {
    return view('userpanel/about');
})->middleware('block.vpn');*/

// OLD EXPORT ROUTES - REMOVED
// These have been consolidated into a single "Export User & Payment Details" button in Admin Panel -> API Management
// The new export joins Users and Payment Details by email, eliminating the need for separate exports and Excel merge
// 
// Removed routes:
// - /export-users (UsersExport) - Now part of joined export
// - /export-payments (PaymentsExport) - Now part of joined export  
// - /export (PaymentDetailsExport) - Now part of joined export
// - /admin/excel-merge (ExcelMergeController2) - No longer needed, join is done in code

use App\Http\Controllers\UnlimitExportController;
// OLD EXPORT ROUTE - REMOVED
// Route::get('/export-unlimit-payments', [UnlimitExportController::class, 'exportPayments']); // This fetches from API and stores, not an export - kept for reference

// OLD EXPORT ROUTE - REMOVED
// use App\Http\Controllers\PaymentExportController;
// Route::get('/export-payments', [PaymentExportController::class, 'export'])->name('payments.export'); // Now part of joined export in API Management

//Value design APIs - MOVED TO ADMIN PANEL
// All Value Design API operations are now available in Admin Panel -> API Management
// The following routes have been removed as they were exposed to normal users:
// - /vdweb/token, /vdweb/brands, value-design/brands
// - /fetchbrands (GET and POST)
// - /brands/select, /stores/fetch, /stores/select, /stores/sync, /stores/export
// - /vddashboard, /vdbrands
// - /evc/store-request, /evc/request, /evc/decrypt-store, /evc-req, /evc-details
// - /evc/status, /evc/form, /vdwalletbalance, /evc/get-activated

// Admin-only Value Design routes (view pages moved to admin)
use App\Http\Controllers\BrandExportController;
use App\Http\Controllers\StoreController;

Route::group(['middleware' => ['admin.user']], function () {
    // Brand export (already admin-only)
    Route::get('/admin/brands/export', [BrandExportController::class, 'export'])->name('brands.export');
    
    // Store management views (moved from public to admin)
    Route::get('/admin/stores/filter', [StoreController::class, 'filterStores'])->name('admin.stores.filter');
    Route::get('/admin/stores/select', [StoreController::class, 'showForm'])->name('admin.stores.form');
    Route::post('/admin/stores/fetch', [StoreController::class, 'fetchStoresForBrand'])->name('admin.stores.fetch');
    Route::post('/admin/stores/sync', [StoreController::class, 'syncAndShow'])->name('admin.stores.sync');
    Route::get('/admin/stores/export', [StoreController::class, 'exportStores'])->name('admin.stores.export');
    
    // Value Design view pages (moved from public to admin)
    Route::get('/admin/vd/brands', [VDWebController::class, 'showBrands'])->name('admin.vd.brands');
    Route::get('/admin/vd/dashboard', function () {
        return view('value_design.dashboard');
    })->name('admin.vd.dashboard');
    
    // EVC operations (moved from public to admin)
    Route::get('/admin/evc/request', [VDWebController::class, 'requestEvc'])->name('admin.evc.request');
    Route::post('/admin/evc/store-request', [VDWebController::class, 'storeGetEvcRequest'])->name('admin.evc.store-request');
    Route::post('/admin/evc/decrypt-store', [VDWebController::class, 'decryptAndStoreEvc'])->name('admin.evc.decrypt-store');
    Route::post('/admin/evc/status', [VDWebController::class, 'VDgetEvcStatus'])->name('admin.evc.status');
    Route::view('/admin/evc/form', 'evc.form')->name('admin.evc.form');
    Route::get('/admin/evc-details/{orderId}/{requestRefNo}', [VDWebController::class,'evcDetails'])->name('admin.evc.details');
    Route::post('/admin/evc/get-activated', [VDWebController::class, 'VDgetActivatedEvc'])->name('admin.evc.activated');
});

use App\Http\Controllers\GetEvcRequestController;

Route::get('/get-evc-request/create', [GetEvcRequestController::class, 'create'])->name('getevc.request');
Route::post('/get-evc-request', [GetEvcRequestController::class, 'store']);

//Value Design Orders
Route::get('/my-vdorder', [MyOrderController::class, 'displayValueDesignOrder'])->name('my-vdorder');
//Lysto (Athena Gift Card) API Integration - MOVED TO ADMIN PANEL
// All Lysto API operations are now available in Admin Panel -> API Management
// The following routes have been removed as they were exposed to normal users:
// - /giftcards, /giftcards/{giftcard_id}/skus, /orders, /wallet-balance
// - /giftcards2, /giftcard/purchase/view, /giftcard/purchase, /dashboard

// Admin-only Lysto routes
Route::group(['middleware' => ['admin.user']], function () {
    Route::get('/admin/lysto/giftcards', [AthenaGiftCardController::class, 'index'])->name('admin.lysto.giftcards');
    Route::get('/admin/lysto/giftcards/{giftcard_id}/skus', [AthenaGiftCardController::class, 'getSkus'])->name('admin.lysto.giftcards.show');
    Route::get('/admin/lysto/orders', [AthenaGiftCardController::class, 'getOrder'])->name('admin.lysto.orders');
    Route::get('/admin/lysto/wallet-balance', [AthenaGiftCardController::class, 'getWalletBalance'])->name('admin.lysto.wallet-balance');
    Route::get('/admin/lysto/giftcards2', [AthenaGiftCardController::class, 'showGiftcards'])->name('admin.lysto.giftcards2.index');
    Route::get('/admin/lysto/giftcard/purchase/view', [AthenaGiftCardController::class, 'purchaseView'])->name('admin.lysto.giftcard.purchase.view');
    Route::post('/admin/lysto/giftcard/purchase', [AthenaGiftCardController::class, 'purchase'])->name('admin.lysto.giftcard.purchase');
    Route::view('/admin/lysto/dashboard', 'giftcard-dashboard')->name('admin.lysto.dashboard');
});


//Unlimit redirect
Route::get('/redirect-to-woohoo', function () {
    return view('woohoo.redirect-to-woohoo'); 
});

Route::post('/vd-update-session-data', [VDPageController::class, 'updateSessionData'])->name('vdupdateSessionData');

 Route::match(['get', 'post'], '/vd-checkout', [VDPageController::class, 'storePayNowData'])->name('vdcheckoutPage');

 Route::post('/vd-checkout', [VDPageController::class, 'storePayNowData'])->name('vdcheckout.store');


   Route::match(['get', 'post'], '/vd-checkout', [VDPageController::class, 'storePayNowData'])->name('vdcheckoutPage');
   Route::post('/vd-checkout', [VDPageController::class, 'storePayNowData'])->name('vdcheckout.store');

// VD Home API routes - MOVED TO ADMIN PANEL
// All VD API operations are now available in Admin Panel -> API Management
use App\Http\Controllers\VDHomeController;

// Home brands endpoint - kept public for homepage display (customer-facing)
Route::get('/api/vd-brands/home', [VDHomeController::class, 'getVDBrandsForHome'])->name('vd.brands.home');

// Admin-only VD API routes
Route::group(['middleware' => ['admin.user']], function () {
    Route::post('/admin/api/vd-brands/clear-cache', [VDHomeController::class, 'clearVDBrandsCache'])->name('admin.vd.brands.clear-cache');
    Route::get('/admin/api/vd-brands/test-connection', [VDHomeController::class, 'testVDConnection'])->name('admin.vd.brands.test-connection');
});


// KGEN API

//Route::get('/kgen-products', [DeliveryPartnerController::class, 'getProducts'])->name('products');
//Route::get('/kgen-showproducts', [DeliveryPartnerController::class, 'showproducts'])
//    ->name('kgen.products');
Route::get('/kgen-products', [DeliveryPartnerController::class, 'showproducts'])->name('products');
//Route::get('/authenticate', [DeliveryPartnerController::class, 'authenticate'])->name('authenticate');
Route::get('/products/{productID}', [DeliveryPartnerController::class, 'getproductsbyID'])
    ->name('products.getById');

// database - MOVED TO ADMIN PANEL
// Route::get('/fetch-products', [DeliveryPartnerController::class, 'fetchAndStoreProducts']); // Now available in Admin Panel -> API Management

use App\Http\Controllers\KGenOrderController;

Route::get('/kgen-place-order', [KGenOrderController::class, 'showForm'])->name('place-order.form');
Route::post('/kgen-place-order', [KGenOrderController::class, 'placeOrder'])->name('place-order.submit');

Route::get('/kgen-orders', [KGenOrderController::class, 'listOrders'])->name('orders.list');
Route::get('/get-kgenorders', [KGenOrderController::class, 'getOrders'])->name('orders.get');

Route::get('/order/{order}/assets', [KGenOrderController::class, 'showAssets'])->name('order.assets');
Route::get('/order/{order}/download', [KGenOrderController::class, 'downloadAsset'])->name('order.download');

Route::get('/order/{orderID}/monitor', [KGenOrderController::class, 'monitorOrder'])->name('order.monitor');

// KGen Wallet routes - MOVED TO ADMIN PANEL
// All KGen wallet operations are now available in Admin Panel -> API Management
// The following routes have been removed as they were exposed to normal users:
// - /kgen-wallet, /kgen/wallet/fetch-balance, /kgen/wallet/latest-balance
// - /kgen/transactions, /export-csv

// Admin-only KGen Wallet routes
use App\Http\Controllers\KGenWalletController;

Route::group(['middleware' => ['admin.user']], function () {
    Route::get('/admin/kgen/wallet', [KGenWalletController::class, 'wallet'])->name('admin.kgen.wallet');
    Route::get('/admin/kgen/wallet/export-csv', [KGenWalletController::class, 'exportCsv'])->name('admin.kgen.wallet.exportCsv');
    Route::get('/admin/kgen/transactions', [KGenWalletController::class, 'index'])->name('admin.kgen.transactions.index');
    Route::get('/admin/kgen/wallet/latest-balance', [KGenWalletController::class, 'latest'])->name('admin.kgen.wallet.latest');
});

//VD Payment
Route::get('/vd/payment/return', [VDPaymentController::class, 'handleReturnSuccess'])->name('vd.return');
Route::post('/vd-payment', [VDPaymentController::class, 'store'])->name('vd.payment');

// Qs Products: Upload disabled products sheet
use App\Http\Controllers\Voyager\QsProductStockImportController;
Route::post('/admin/qs-products/upload-disabled', [QsProductStockImportController::class, 'uploadDisabledProducts'])
    ->name('admin.qs_products.upload_disabled')
    ->middleware(['web', 'auth', 'admin.user']);


use App\Http\Controllers\UnlimitController;

Route::middleware(['auth'])->group(function() {
    // Step 1: Checkout form submit
    Route::post('/unlimit/checkout', [UnlimitController::class, 'checkout'])->name('unlimit.checkout');

    // Step 2: Callback from Unlimit
  //  Route::post('/unlimit/callback', [UnlimitController::class, 'callback'])->name('unlimit.callback');

    // Step 3: Return URL after payment - REMOVED: Using WoohooProcessingController::handleReturn instead (line 356)
    // This route was causing conflicts - payment status updates are handled by WoohooProcessingController
  //  Route::middleware(['throttle:payments'])->group(function () {
  //      Route::match(['get','post'], '/unlimit/return', [UnlimitController::class, 'return'])->name('unlimit.return');
  //  });

    // Optional: check payment status API
    Route::get('/unlimit/status/{merchant_order_id}', [UnlimitController::class, 'checkTransactionStatus'])->name('unlimit.status');
});

// Kgen Payment Routes
Route::get('/kgen-payment/initiate', [KGenPaymentController::class, 'initiate'])->name('kgen.payment.initiate');
Route::get('/kgen-payment/success/{orderId}', [KGenPaymentController::class, 'handleReturnSuccess'])->name('kgen.payment.success');
Route::get('/kgen-payment/failed/{orderId}', [KGenPaymentController::class, 'handleReturnFailed'])->name('kgen.payment.failed');

// Order Status Routes
Route::get('/kgen-order/success/{orderId}', [KGenOrderController::class, 'showSuccess'])->name('kgen.order.success');
Route::get('/kgen-order/failed/{orderId}', [KGenOrderController::class, 'showFailed'])->name('kgen.order.failed');
