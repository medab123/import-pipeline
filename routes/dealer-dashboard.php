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
        Route::post('dealers/{dealer}/fbmp-tokens', [DealerController::class, 'storeFbmpToken'])
            ->name('dealers.fbmp-tokens.store');
        Route::post('dealers/{dealer}/fbmp-tokens/{token}/regenerate', [DealerController::class, 'regenerateFbmpToken'])
            ->name('dealers.fbmp-tokens.regenerate');
        Route::delete('dealers/{dealer}/fbmp-tokens/{token}', [DealerController::class, 'revokeFbmpToken'])
            ->name('dealers.fbmp-tokens.destroy');

        Route::resource('dealers', DealerController::class);
        Route::resource('payment-transactions', PaymentTransactionController::class);
        Route::resource('scraps', ScrapController::class);
    });
