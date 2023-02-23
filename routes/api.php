<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\quickBooks\customer\CustomerController;
use App\Http\Controllers\Api\quickBooks\payment\PaymentController;
use App\Http\Controllers\Auth\QBAuthController;
use App\Http\Controllers\Api\quickBooks\invoice\InvoiceController;
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
            Route::get('all', [CustomerController ::class, 'index'])->name('customer.all');
            Route::post('create', [CustomerController ::class, 'store'])->name('customer.create');
            Route::post('show', [CustomerController ::class, 'show'])->name('customer.show');
        });

        Route::prefix('invoice')->group(function () {
            Route::get('get', [InvoiceController ::class, 'index'])->name('invoice.all');
            Route::post('create', [InvoiceController ::class, 'store'])->name('invoice.create');
            Route::post('show', [InvoiceController ::class, 'show'])->name('invoice.show');
        });

        Route::prefix('payment')->group(function () {
            Route::get('all', [PaymentController ::class, 'index'])->name('payment.all');
            Route::post('create', [PaymentController ::class, 'store'])->name('payment.create');
            //Route::post('payInvoices', [PaymentController ::class, 'payInvoices'])->name('payment.payInvoices');
        });

        Route::post('qb_tokens', [QBAuthController ::class, 'getToken'])->name('qb_tokens');
    });

});

Route::post('tokens', [RegisteredUserController ::class, 'apiStore'])->name('tokens');


Route::fallback(function (){
    return response()->json([
        'message' => 'API resource not found!'
    ],404);
});
