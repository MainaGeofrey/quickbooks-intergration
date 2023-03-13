<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\quickBooks\customer\CustomerController;
use App\Http\Controllers\Api\quickBooks\balance\BalanceController;
use App\Http\Controllers\Api\quickBooks\Bill\BillController;
use App\Http\Controllers\Api\quickBooks\Vendor\VendorController;
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

Route::group(['middleware' => ['auth','qb.auth']], function ()   {

	Route::prefix('v1')->group(function () {
		Route::prefix('customer')->group(function () {
            Route::get('all', [CustomerController ::class, 'index'])->name('customer.all');
            Route::post('create', [CustomerController ::class, 'store'])->name('customer.create');
            Route::post('show', [CustomerController ::class, 'show'])->name('customer.show');
        });
        Route::prefix('balance')->group(function () {
            Route::get('total', [BalanceController ::class, 'index'])->name('balance.total');
            Route::post('show', [BalanceController ::class, 'show'])->name('balance.show');
        });

        Route::prefix('invoice')->group(function () {
            Route::get('all', [InvoiceController ::class, 'index'])->name('invoice.all');
            Route::post('create', [InvoiceController ::class, 'store'])->name('invoice.create');
            Route::post('show', [InvoiceController ::class, 'show'])->name('invoice.show');
        });

        Route::prefix('payment')->group(function () {
            Route::get('all', [PaymentController ::class, 'index'])->name('payment.all');
            Route::post('create', [PaymentController ::class, 'store'])->name('payment.create');
            //Route::post('payInvoices', [PaymentController ::class, 'payInvoices'])->name('payment.payInvoices');
        });

        Route::prefix('bill')->group(function () {
            Route::get('all', [BillController ::class, 'index'])->name('bill.all');
            Route::post('create', [BillController ::class, 'store'])->name('bill.create');
            Route::post('show', [BillController ::class, 'show'])->name('bill.show');
            // Route::get('vendors', [BillController ::class, 'vendors'])->name('bill.vendors');
        });

        Route::prefix('vendor')->group(function () {
            Route::get('all', [VendorController ::class, 'index'])->name('vendor.all');
            Route::post('create', [VendorController ::class, 'store'])->name('vendor.create');
            Route::post('show', [vendorController ::class, 'show'])->name('vendor.show');
            // Route::get('vendors', [BillController ::class, 'vendors'])->name('bill.vendors');
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
