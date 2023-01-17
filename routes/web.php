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
Route::get('/', function () {
    return view('userpanel/index');
});
Route::get('/gift_card_detail_page/{id}', function () {
    return view('userpanel/gift_card_detail_page');
})->name('gift_card_detail_page');                                                                                          

Route::post('/generate-authcode', [App\Http\Controllers\CommonController::class, 'generateAuthcode'])->name('generate-authcode');


Route::get('/get-category',  [App\Http\Controllers\CommonController::class, 'getCategory'])->name('get-category');
Route::get('/get-product',  [App\Http\Controllers\CommonController::class, 'getProducts'])->name('get-product');
Route::get('/get-product-sku',  [App\Http\Controllers\CommonController::class, 'getProductbySKU'])->name('get-product-sku');

Route::get('/checkout', function () {
    return view('userpanel/checkout');
})->name('checkout');


Route::post('/check-data', [App\Http\Controllers\CommonController::class, 'checkData'])->name('check-data');

Route::get('/profile', function () {
    return view('userpanel/profile');
})->name('profile');

Route::get('/my-order', function () {
    return view('userpanel/my-order');
})->name('my-order');

Route::get('/change-password', function () {
    return view('userpanel/change-password');
})->name('change-password');

Route::get('/view-all-product/{slug}', function () {
    return view('userpanel/view_all_product');
})->name('view-all-product');

Route::get('/about', function () {
    return view('userpanel/about');
});
Route::get('/contact_us', function () {
    return view('userpanel/contact-form');
});
Route::get('/f&q', function () {
    return view('userpanel/f&q');
});
Route::get('/terms_of_use', function () {
    return view('userpanel/terms_of_use');
});
Route::get('/private_policy', function () {
    return view('userpanel/private_policy');
});
Route::get('/all_transaction', function () {
    return view('userpanel/all_transaction');
});
