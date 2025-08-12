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
Route::get('/export', [PaymentDetailsExportController::class, 'export']);
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
                Log::info('Created directory', ['directory' => $directory]);
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


Route::get('/order-failure', function () {
    return view('order-failure'); // This will render the order-failure.blade.php view
});

// Transaction routes that need protection
Route::middleware(['auth', 'check.transaction'])->group(function () {
    Route::post('/update-session-data', [ProductPageController::class, 'updateSessionData'])->name('updateSessionData');
   Route::match(['get', 'post'], '/checkout/{slug}', [ProductPageController::class, 'storePayNowData'])->name('checkoutPage');
   //Route::match(['get', 'post'], '/checkout/{slug}',[UnlimitPaymentController::class, 'store'])->name('unlimit.store');
   // Route::post('/store-pay-now/{slug}', [UserPanelController::class, 'storePayNowData'])->name('store-pay-now');
    Route::post('/save-gift-card-form', [UserPanelController::class, 'saveGiftCardFormValues'])->name('save-gift-card-form');
   // Route::post('/payment-process', [CCAvenueController::class, 'processPayment'])->name('payment-process');
    Route::post('/payment-process',[UnlimitPaymentController::class, 'store'])->name('unlimit.store');
    //Route::post('/response_ccavenue', [CCAvenueController::class, 'responseCcavenue'])->name('response_ccavenue');
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

Route::get('/payment/return', function () {
    return view('payment.return'); // or handle logic in a controller
});

Route::get('/payment', function () {
    return view('payment'); // This assumes the file is at resources/views/payment.blade.php
});

Route::get('/payment/return', [UnlimitPaymentController::class, 'handleReturnSuccess'])->name('unlimit.return');

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
Route::post('/payment/upi', [UPIPaymentController::class, 'store'])->name('payment.upi');

//routes to Net Banking Payment
use App\Http\Controllers\NetBankPaymentController;
Route::post('/payment/netbnk', [NetBankPaymentController::class, 'store'])->name('payment.netbnk');

//For Voyager Invoice method
use App\Http\Controllers\InvoiceController;

//Route::post('/create-invoice', [InvoiceController::class, 'storeAndSendInvoice'])->name('invoice.create');

use App\Invoice;
Route::get('/admin/invoices/{id}/create-invoice', [\App\Http\Controllers\InvoiceController::class, 'storeAndSendInvoice'])->name('create_invoice');


use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;

Route::get('/export-users', function () {
    return Excel::download(new UsersExport, 'users_report.xlsx');
})->name('export.users');


use App\Http\Controllers\ExcelMergeController2;

Route::get('/admin/excel-merge', [ExcelMergeController2::class, 'showForm'])->name('excel.form')->middleware('admin.user');
Route::post('/admin/excel-merge', [ExcelMergeController2::class, 'merge'])->name('excel.merge')->middleware('admin.user');

use App\Http\Controllers\UnlimitExportController;
Route::get('/export-unlimit-payments', [UnlimitExportController::class, 'exportPayments']);

use App\Http\Controllers\PaymentExportController;

Route::get('/export-payments', [PaymentExportController::class, 'export'])->name('payments.export');

//Value design APIs
Route::get('/vdweb/token', [VDWebController::class, 'getToken']);
Route::get('/vdweb/brands', [VDWebController::class, 'getBrandsFromToken']);
Route::get('value-design/brands',[VDWebController::class,'displayBrands']);
Route::get('/fetchbrands', function () {
    return view('fetchbrands'); // or any basic page/form
});
Route::post('/fetchbrands', [StoreBrandsController::class, 'getAndStoreBrands']);
use App\Http\Controllers\BrandExportController;

Route::get('/admin/brands/export', [BrandExportController::class, 'export'])->name('brands.export');

//Route::get('/brands/decrypt-sync', [VDAESdecrptController2::class, 'handleEncryptedPayload']);

use App\Http\Controllers\StoreController;

Route::get('/brands/select', [StoreController::class, 'showBrandSelection'])->name('brands.select');
Route::post('/stores/fetch', [StoreController::class, 'fetchStoresForBrand'])->name('stores.fetch');

Route::get('/stores/select', [StoreController::class, 'showForm'])->name('stores.form');
Route::post('/stores/sync', [StoreController::class, 'syncAndShow'])->name('stores.sync');
Route::get('/stores/filter', [StoreController::class, 'filterStores'])->name('stores.filter');
Route::get('/stores/export', [StoreController::class, 'exportStores'])->name('stores.export');

Route::get('/vddashboard', function () {
    return view('value_design.dashboard');
});

Route::get('/vdbrands', [VDWebController::class, 'showBrands'])->name('brands.index');
Route::post('/evc/store-request', [VDWebController::class, 'storeGetEvcRequest']);
Route::get('/evc/request', [VDWebController::class, 'requestEvc'])->name('evc.request');
Route::post('/evc/decrypt-store', [VDWebController::class, 'decryptAndStoreEvc']);
Route::post('/evc-req', [VDWebController::class, 'showEvcDetails'])->name('request.evc');
Route::get('/evc-details/{orderId}/{requestRefNo}', [VDWebController::class,'evcDetails'])->name('evc.details');


//Route::post('/evc/status', [VDWebController::class, 'getEvcStatus'])->name('evc.status');

Route::post('/evc/status', [VDWebController::class, 'VDgetEvcStatus'])->name('evc.status');

Route::view('/evc/form', 'evc.form');

Route::get('/vdwalletbalance', [VDWebController::class, 'getWalletBalance']);

Route::post('/evc/get-activated', [VDWebController::class, 'VDgetActivatedEvc'])
     ->name('evc.getActivated');

//Lysto API Integration

Route::get('/giftcards', [AthenaGiftCardController::class, 'index']);
Route::get('/giftcards/{giftcard_id}/skus', [AthenaGiftCardController::class, 'getSkus'])->name('giftcards.show');

//Route::view('/purchase-form', 'giftcard-purchase');

Route::get('/orders', [AthenaGiftCardController::class, 'getOrder']);

Route::get('/wallet-balance', [AthenaGiftCardController::class, 'getWalletBalance']);

Route::view('/dashboard', 'giftcard-dashboard');

Route::get('/giftcards2', [AthenaGiftcardController::class, 'showGiftcards'])->name('giftcards.index');
//Route::get('/giftcards2/{id}', [AthenaGiftcardController::class, 'showGiftcards2'])->name('giftcards.show');

Route::get('/giftcard/purchase/view', [AthenaGiftCardController::class, 'purchaseView'])->name('giftcard.purchase.view');
Route::post('/giftcard/purchase', [AthenaGiftCardController::class, 'purchase'])->name('giftcard.purchase');


//Unlimit redirect
Route::get('/redirect-to-woohoo', function () {
    return view('woohoo.redirect-to-woohoo'); 
});

Route::post('/vd-update-session-data', [VDPageController::class, 'updateSessionData'])->name('vdupdateSessionData');

 Route::match(['get', 'post'], '/vd-checkout', [VDPageController::class, 'storePayNowData'])->name('vdcheckoutPage');

 Route::post('/vd-checkout', [VDPageController::class, 'storePayNowData'])->name('vdcheckout.store');


   Route::match(['get', 'post'], '/vd-checkout', [VDPageController::class, 'storePayNowData'])->name('vdcheckoutPage');
   Route::post('/vd-checkout', [VDPageController::class, 'storePayNowData'])->name('vdcheckout.store');

// VD Home API routes
use App\Http\Controllers\VDHomeController;
Route::get('/api/vd-brands/home', [VDHomeController::class, 'getVDBrandsForHome'])->name('vd.brands.home');
Route::post('/api/vd-brands/clear-cache', [VDHomeController::class, 'clearVDBrandsCache'])->name('vd.brands.clear-cache');
Route::get('/api/vd-brands/test-connection', [VDHomeController::class, 'testVDConnection'])->name('vd.brands.test-connection');


// KGEN API
use App\Http\Controllers\DeliveryPartnerController;

Route::get('/products', [DeliveryPartnerController::class, 'getProducts'])->name('products');
Route::get('/authenticate', [DeliveryPartnerController::class, 'authenticate'])->name('authenticate');

use App\Http\Controllers\KGenOrderController;

Route::get('/place-order', [KGenOrderController::class, 'showForm'])->name('place-order.form');
Route::post('/place-order', [KGenOrderController::class, 'placeOrder'])->name('place-order.submit');

Route::get('/kgen-orders', [KGenOrderController::class, 'listOrders'])->name('orders.list');
Route::get('/get-kgenorders', [KGenOrderController::class, 'getOrders'])->name('orders.get');

use App\Http\Controllers\KGenWalletController;

Route::get('/wallet', [KGenWalletController::class, 'wallet'])->name('wallet');

