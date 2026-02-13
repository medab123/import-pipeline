<?php

declare(strict_types=1);

use App\Http\Controllers\Dashboard\Organization\UserController;
use App\Http\Controllers\Dashboard\Organization\TargetFieldController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'organization'])
    ->prefix('dashboard/organization')
    ->name('dashboard.organization.')
    ->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Target Fields
        Route::resource('target-fields', TargetFieldController::class);

        // API Tokens
        Route::resource('tokens', App\Http\Controllers\Dashboard\Organization\OrganizationTokenController::class)
            ->only(['index', 'store', 'destroy']);
    });
