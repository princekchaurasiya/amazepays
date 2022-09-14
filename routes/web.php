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
