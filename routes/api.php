<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| All routes protected by HTTP Basic Auth via 'auth.basic' middleware.
*/

Route::middleware('auth.basic')->group(function () {

    // Accounts
    Route::get('/accounts', [AccountController::class, 'index']);
    Route::get('/accounts/{account}/balance', [AccountController::class, 'balance']);

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
});
