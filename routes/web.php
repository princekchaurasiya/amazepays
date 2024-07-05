<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    UserPanelController, CommonController, PaymentController, MyOrderController, SmsController, WoohooOrderController, OtpLoginController, SearchController, OtpVerificationController, ProductPageController, CCAvenueController, PaymentDetailsExportController, ProfileController, ProductSlugController, ErrorController,ProductCategoryController, CreateOrderController, DocumentController, ViewCardDetailsController, ContactUsController, ChangePasswordUpdateController, Voyager\VoyagerGenerateBearerTokenController, Voyager\VoyagerGetCategoryController, Voyager\VoyagerFetchProductListController, Voyager\VoyagerFetchProductDataController, Voyager\VoyagerProductDiscountImportController,
    Voyager\VoyagerOrderExportController
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
    Route::resource('cc-avenue', 'VoyagerCcAvenueController');
    Route::get('/import-data', [DocumentController::class, 'importDocument']);
    Route::view('/upload-document', 'documentUpload');
    Route::post('/upload-data', [DocumentController::class, 'uploadData'])->name('uploadData');

Route::match(['get', 'post'], '/voyager/bearer-token', [VoyagerGenerateBearerTokenController::class, 'generateBearerToken'])->name('voyager.bearerToken');
Route::match(['get', 'post'], '/voyager/get-category', [VoyagerGetCategoryController::class, 'getCategory'])->name('voyager.getCategory');
Route::match(['get', 'post'], '/voyager/fetch-product-list', [VoyagerFetchProductListController::class, 'fetchProductList'])->name('voyager.productList');
Route::match(['get', 'post'], '/voyager/fetch-product-data', [VoyagerFetchProductDataController::class, 'fetchProductData'])->name('voyager.fetchProductData');
Route::get('admin/import-product-discount',  [VoyagerProductDiscountImportController::class, 'import'])->name('import-product-discount');

Route::get('/download-order-sheet', [VoyagerOrderExportController::class, 'export'])->name('downloadOrderSheet');

});


Route::get('logout', [UserPanelController::class, 'userLogOut'])->name('userLogOut');
Route::get('/', [UserPanelController::class, 'homePage'])->name('home');

// Route to handle redirection based on authentication status
Route::get('/redirect-based-on-auth', [ProductSlugController::class, 'redirectBasedOnAuth'])->name('redirect-based-on-auth');


// Route to handle redirection based on authentication
Route::get('/gift-product/{slug}', [ProductPageController::class, 'handleGiftRedirect'])->name('giftProductBySlug');

Route::get('/product/{slug}', [ProductSlugController::class, 'getProductBySlug'])->name('get-product-by-slug');

Route::get('/product-category', [ProductCategoryController::class, 'getProductCategory'])->name('get-product-category');


Route::get('/productPage/{id}', function () {
    return view('userpanel/productPage_old');
})->name('productPage');

Route::group(['middleware' => 'guest'], function () {
    Route::post('/user-registration', [UserPanelController::class, 'userRegistration'])->name('user-registration');
    Route::post('/user-login', [UserPanelController::class, 'userLogin'])->name('user-login');
});

Route::group(['middleware' => 'auth'], function () {
    Route::get('/user-logout', [UserPanelController::class, 'userLogOut'])->name('user-logout');
    Route::post('/apply-coupan', [UserPanelController::class, 'applyCoupan'])->name('apply-coupan');
    Route::get('/profile', function () {
        return view('userpanel/profile');
    })->name('profile');
    Route::get('/my-order', [MyOrderController::class, 'displayOrder'])->name('my-order');
});

Route::get('/unauthenticated', function () {
    $message = session('message', 'You are not authenticated.');
    return redirect()->route('error', ['message' => $message]);
})->name('unauthenticated')->middleware('web');

Route::post('/check-data', [CommonController::class, 'checkData'])->name('check-data');
Route::get('/view-all-product', [UserPanelController::class, 'viewAllProduct'])->name('view-all-product');
Route::get('/change-password', function () {
    return view('userpanel/change-password');
})->name('change-password');
Route::post('/change-password-update', [ChangePasswordUpdateController::class, 'updatePassword'])->name('password-change');
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
Route::get('/all_transaction', function () {
    return view('userpanel/all_transaction');
});
Route::match(['get', 'post'], '/checkout/{slug}', [ProductPageController::class, 'storePayNowData'])->name('checkoutPage');

Route::post('/place-order', [CheckoutController::class, 'placeOrder'])->name('placeOrder');


Route::post('/update-session-data', [ProductPageController::class, 'updateSessionData'])->name('updateSessionData');



Route::post('/save-gift-card-form-values', [ProductPageController::class, 'saveGiftCardFormValues'])
    ->name('saveGiftCardFormValues');

Route::post('/payment-process', [CCAvenueController::class, 'processPayment'])->name('payment-process');


Route::post('/payment-cancel', [CCAvenueController::class, 'handlePaymentCancellation'])->name('payment-cancel');

Route::post('/response_ccavenue',
[CCAvenueController::class, 'responseCcavenue'])->name('response_ccavenue');


Route::post('/send-sms', [SmsController::class, 'sendSms'])->name('send-sms');
Route::post('/verify-otp', [OtpVerificationController::class, 'verifyOtp'])->name('verify-otp');
Route::get('/invoice', function () {
    return view('layouts.invoice');
})->name('invoice');
Route::get('/export', [PaymentDetailsExportController::class, 'export']);
Route::get('/error', [ErrorController::class, 'handleError'])->name('error');
Route::post('/update-profile', [ProfileController::class, 'update'])->name('update-profile');
Route::post('/card-details', [ViewCardDetailsController::class, 'index'])->name('view-card-details');
Route::post('/save-contact', [ContactUsController::class, 'saveContact'])->name('save-contact');
Route::get('/search', [SearchController::class, 'search'])->name('search');
Route::post('/woohoo/create-order', [WoohooOrderController::class, 'createOrder'])->name('woohoo.createOrder');
Route::view('/gift', 'layouts.giftmail');
