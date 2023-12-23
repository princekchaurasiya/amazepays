<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('signature-validation',[App\Http\Controllers\ApiController::class, 'signature_validation']);
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => 'api'], function(){
    Route::post('sendUserLoginOtp', [App\Http\Controllers\APIs\AuthenticationController::class, 'sendUserLoginOtp']);
    Route::post('verifyUserLoginOtp', [App\Http\Controllers\APIs\AuthenticationController::class, 'verifyUserLoginOtp']);
    Route::post('userRegistration', [App\Http\Controllers\APIs\AuthenticationController::class, 'userRegistration']);
    Route::get('allProduct', [App\Http\Controllers\APIs\AuthenticationController::class, 'allProduct']);
    Route::get('singleProductDetails', [App\Http\Controllers\APIs\AuthenticationController::class, 'singleProductDetails']);
});


