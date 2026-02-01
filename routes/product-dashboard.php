<?php

declare(strict_types=1);

use App\Http\Controllers\Dashboard\Product\ProductActivityLogController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('dashboard/products')
    ->name('dashboard.products.')
    ->group(function () {
        Route::get('activity-logs', [ProductActivityLogController::class, 'search'])
            ->name('activity-logs.search');

        Route::get('{uuid}/activity-logs', [ProductActivityLogController::class, 'index'])
            ->name('activity-logs');

        Route::get('{uuid}/activity-logs/{activity}', [ProductActivityLogController::class, 'show'])
            ->name('activity-logs.show');
    });
