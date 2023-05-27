<?php

use Illuminate\Support\Facades\Route;


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
Route::get('/', [App\Http\Controllers\UserPanelController::class, 'homePage'])->name('home');
Route::get('/gift_card_detail_page/{id}', function () {
    return view('userpanel/gift_card_detail_page_old');
})->name('gift_card_detail_page');                                                                                          

Route::post('/generate-authcode', [App\Http\Controllers\CommonController::class, 'generateAuthcode'])->name('generate-authcode'); //admin


Route::get('/get-category',  [App\Http\Controllers\CommonController::class, 'getCategory'])->name('get-category');
Route::get('/get-product',  [App\Http\Controllers\CommonController::class, 'getProducts'])->name('get-product');
Route::get('/get-product-sku/{slug}',  [App\Http\Controllers\CommonController::class, 'getProductbySKU'])->name('get-product-sku');

Route::group(['middleware'=>'guest'],function(){
    Route::post('/user-registration',[App\Http\Controllers\UserPanelController::class, 'userRegistration'])->name('user-registration');
    Route::post('/user-login',[App\Http\Controllers\UserPanelController::class, 'userLogin'])->name('user-login');
});
Route::group(['middleware'=>'auth'],function(){
    Route::post('/order-card',  [App\Http\Controllers\CommonController::class, 'orderCard'])->name('order-card'); // auth user only
    Route::post('/checkout/{sku}',[App\Http\Controllers\UserPanelController::class, 'checkOut'])->name('checkout');
    Route::get('/user-logout',[App\Http\Controllers\UserPanelController::class, 'userLogOut'])->name('user-logout');
    Route::post('/apply-coupan', [App\Http\Controllers\UserPanelController::class, 'applyCoupan'])->name('apply-coupan'); //auth user only
    Route::post('/remove-apply-coupan', [App\Http\Controllers\UserPanelController::class, 'removeApplyCoupan'])->name('remove-apply-coupan'); //auth user only
});

Route::post('/check-data', [App\Http\Controllers\CommonController::class, 'checkData'])->name('check-data');
Route::get('/view-all-product', [App\Http\Controllers\UserPanelController::class, 'viewAllProduct'])->name('view-all-product');

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
    return view('paymentFolder.ccavRequestHandler');
});


Route::get('/payment-complete', function () {
    return view('paymentFolder.ccavResponseHandler');
})->name('payment-complete');

// Route::post('/payment-process', 'App\Http\Controllers\PaymentController@processPayment')->name('payment-process');



// Routes for payment success and failure actions

// Route::get('/payment/success', 'App\Http\Controllers\PaymentController@paymentSuccess')->name('payment-success');

// Route::get('/payment/failed', 'App\Http\Controllers\PaymentController@paymentFailed')->name('payment-failed');