<?php

use App\Http\Controllers\TransactionExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect('/admin'));

// Export route (protected by MoonShine auth middleware)
Route::middleware(['moonshine'])->group(function () {
    Route::get('/admin/transactions/export', TransactionExportController::class)
        ->name('moonshine.transactions.export');
});
