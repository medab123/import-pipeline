<?php

declare(strict_types=1);

use App\Http\Controllers\Dashboard\Dealer\DealerController;
use App\Http\Controllers\Dashboard\PaymentTransaction\PaymentTransactionController;
use App\Http\Controllers\Dashboard\Scrap\ScrapController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'organization', 'permission:view dealers'])
    ->prefix('dashboard')
    ->name('dashboard.')
    ->group(function () {
        Route::resource('dealers', DealerController::class);
        Route::resource('payment-transactions', PaymentTransactionController::class);
        Route::resource('scraps', ScrapController::class);
    });
