<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserPanelController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\MyOrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CcAvenuePayment;
use App\Models\QsOrder;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\OtpLoginController;
use App\Http\Controllers\OtpVerificationController;
use App\Http\Controllers\PaymentDetailsExportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductSkuController;
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
// Route::logout();
Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
    Route::resource('cc-avenue', 'VoyagerCcAvenueController');
});


Route::get('/', [UserPanelController::class, 'homePage'])->name('home');


//category api for woohoo should be called once only 
Route::get('/get-category', [CommonController::class, 'getCategory'])->name('get-category');

//prodcut list api for woohoo should be called once only 
Route::get('/get-product-list', [CommonController::class, 'getProductList'])->name('get-product-list');

//prodcut api for woohoo should be called once only 
// Route::get('/get-product-sku/{slug}', [CommonController::class, 'getProductbySKU'])->name('get-product-sku');

Route::get('/get-product-sku/{slug}', [ProductSkuController::class, 'getProductbySKU'])->name('get-product-sku');



Route::get('/gift_card_detail_page/{id}', function () {
    return view('userpanel/gift_card_detail_page_old');
})->name('gift_card_detail_page');

Route::post('/generate-authcode', [CommonController::class, 'generateAuthcode'])->name('generate-authcode'); //admin




Route::group(['middleware' => 'guest'], function () {
    Route::post('/user-registration', [UserPanelController::class, 'userRegistration'])->name('user-registration');
    Route::post('/user-login', [UserPanelController::class, 'userLogin'])->name('user-login');
});
Route::group(['middleware' => 'auth'], function () {
    // Route::post('/order-card', [CommonController::class, 'orderCard'])->name('order-card'); // auth user only

    Route::post('/checkout/{sku}', [UserPanelController::class, 'checkOut'])->name('checkout');

    Route::get('/user-logout', [UserPanelController::class, 'userLogOut'])->name('user-logout');

    Route::post('/apply-coupan', [UserPanelController::class, 'applyCoupan'])->name('apply-coupan'); //auth user only

    Route::post('/remove-apply-coupan', [UserPanelController::class, 'removeApplyCoupan'])->name('remove-apply-coupan'); //auth user only

    Route::get('/profile', function () {
        return view('userpanel/profile');
    })->name('profile');  //auth user only
    Route::get('/my-order', [MyOrderController::class, 'displayOrder'])->name('myOrder'); //auth user only
});

Route::post('/check-data', [CommonController::class, 'checkData'])->name('check-data');
Route::get('/view-all-product', [UserPanelController::class, 'viewAllProduct'])->name('view-all-product');

Route::get('/change-password', function () {
    return view('userpanel/change-password');
})->name('change-password');

Route::get('/about', function () {
    return view('userpanel/about');
});
Route::get('/contact_us', function () {
    return view('userpanel/contact-form');
});
// Route::get('/f&q', function () {
//     return view('userpanel/f&q');
// });
Route::get('/terms_of_use', function () {
    return view('userpanel/terms_of_use');
});
Route::get('/private_policy', function () {
    return view('userpanel/private_policy');
});
Route::get('/all_transaction', function () {
    return view('userpanel/all_transaction');
});

// Routes for payment

Route::get('/payment', function () {
    return view('paymentFolder.payment-index');
})->name('payment');

// Route::post('/payment-process', function (Request $request) {
//     $cc_avenue_payment = new CcAvenuePayment();
//     $cc_avenue_payment->user_id = Auth::user()->id;
//     $cc_avenue_payment->order_id = $request->order_id;
//     $cc_avenue_payment->price = $request->denomination;
//     $cc_avenue_payment->qty = $request->quantity;
//     $cc_avenue_payment->currency_code = $request->numericCode;
//     if($cc_avenue_payment->save()){
//         return view('paymentFolder.ccavRequestHandler');
//     } else {
//         return redirect()->route('payment-process');
//     }
// });

Route::post('/payment-process', [UserPanelController::class, 'orderProceed']);

Route::post('/response_ccavenue', [PaymentController::class, 'responseCcavenue'])->name('response_ccavenue');

Route::get('/payment-complete', function () {
    return view('paymentFolder.ccavResponseHandler');
})->name('payment-complete');

Route::post('payment-success', [PaymentController::class, 'processData'])->name('success');


Route::get('payment-cancel', function () {
    return view('userpanel.order_details');
})->name('cancel');

// Route::get('/my-order', function () {
//     return view('userpanel/my-order');
// })->name('my-order');


Route::post('/send-sms', [SmsController::class, 'sendSms'])->name('send-sms');

Route::post('/verify-otp', [OtpVerificationController::class, 'verifyOtp'])->name('verify-otp');



Route::get('/invoice', function () {
    return view('layouts.invoice');
})->name('invoice');


Route::get('/export', [PaymentDetailsExportController::class, 'export']);



// Route::get('/send-test-sms', [SmsController::class, 'sendTestSms'])->name('send-test-sms');

Route::get('/error', function () {
    return view('userpanel.wentWrong');
})->name('error');

Route::post('/update-profile', [ProfileController::class, 'update'])->name('update-profile');



// order status api 
Route::get('/get-order-status/{refno}', [CommonController::class, 'getStatusByReferenceNumber'])->name('get-order-status');

// ccard activation api 
Route::get('/activate-card', [CommonController::class, 'callCardActivation'])->name('activate-card');


// order list api 
Route::get('/order-details', [UserPanelController::class, 'orderDetails'])->name('order-details');

// order list api 
Route::get('/order-list', [UserPanelController::class, 'orderList'])->name('order-list');



Route::view('/success', 'paymentFolder.payment-success')->name('payment-success');

Route::view('/payment-failed', 'paymentFolder.payment-failed')->name('payment-failed');


Route::get('/order-failed', function () {
    return view('order.order-failed');
})->name('order-failed');

