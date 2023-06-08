<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserPanelController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\PaymentController;


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
});
Route::get('/', [UserPanelController::class, 'homePage'])->name('home');
Route::get('/gift_card_detail_page/{id}', function () {
    return view('userpanel/gift_card_detail_page_old');
})->name('gift_card_detail_page');                                                                                          

Route::post('/generate-authcode', [CommonController::class, 'generateAuthcode'])->name('generate-authcode'); //admin


Route::get('/get-category',  [CommonController::class, 'getCategory'])->name('get-category');
Route::get('/get-product',  [CommonController::class, 'getProducts'])->name('get-product');
Route::get('/get-product-sku/{slug}',  [CommonController::class, 'getProductbySKU'])->name('get-product-sku');

Route::group(['middleware'=>'guest'],function(){
    Route::post('/user-registration',[UserPanelController::class, 'userRegistration'])->name('user-registration');
    Route::post('/user-login',[UserPanelController::class, 'userLogin'])->name('user-login');
});
Route::group(['middleware'=>'auth'],function(){
    Route::post('/order-card',  [CommonController::class, 'orderCard'])->name('order-card'); // auth user only
    
    Route::post('/checkout/{sku}',[UserPanelController::class, 'checkOut'])->name('checkout');
    Route::get('/user-logout',[UserPanelController::class, 'userLogOut'])->name('user-logout');
    Route::post('/apply-coupan', [UserPanelController::class, 'applyCoupan'])->name('apply-coupan'); //auth user only
    Route::post('/remove-apply-coupan', [UserPanelController::class, 'removeApplyCoupan'])->name('remove-apply-coupan'); //auth user only
});

Route::post('/check-data', [CommonController::class, 'checkData'])->name('check-data');
Route::get('/view-all-product', [UserPanelController::class, 'viewAllProduct'])->name('view-all-product');

Route::get('/profile', function () {
    return view('userpanel/profile');
})->name('profile');

Route::get('/my-order', function () {
    return view('userpanel/my-order');
})->name('my-order');

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

Route::post('/payment-process', function () {
    // dd(12345);
    return view('paymentFolder.ccavRequestHandler');
});

Route::post('/response_ccavenue', 'PaymentController@responseCcavenue')->name('response_ccavenue');

Route::get('/payment-complete', function () {
    return view('paymentFolder.ccavResponseHandler');
})->name('payment-complete');
// Route::match(['get', 'post'], '/payment-complete', function () {
//     return view('paymentFolder.ccavResponseHandler');
// })->name('payment-complete');
// Route::post('/payment-process', 'PaymentController@processPayment')->name('payment-process');



// Routes for payment success and failure actions

//Route::get('/payment-complete', 'PaymentController@paymentSuccess');

// Route::get('/payment/failed', 'PaymentController@paymentFailed')->name('payment-failed');

// Route::get('payment-success', function(){
//     dd('success');
// })->name('success');

Route::post('payment-success', 'PaymentController@processData')->name('success');


Route::get('payment-cancel', function(){
    dd('cancel');
})->name('cancel');