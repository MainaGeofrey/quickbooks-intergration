<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\quickBooks\customer\CustomerController;
use App\Http\Controllers\Auth\QBAuthController;
use App\Http\Controllers\Auth\RegisteredUserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return response()->json([
            'message' => 'API resource found here!'
],404);});

Route::group(['middleware' => ['auth:api']], function ()   {
	Route::prefix('v1')->group(function () {
		Route::prefix('customer')->group(function () {
            Route::post('create', [CustomerController ::class, 'store'])->name('customer.create');
        });
    });

});

Route::post('tokens', [RegisteredUserController ::class, 'apiStore'])->name('tokens');
Route::post('qb_tokens', [QBAuthController ::class, 'getToken'])->name('qb_tokens');

Route::fallback(function (){
    return response()->json([
        'message' => 'API resource not found!'
    ],404);
});
