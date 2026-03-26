<?php

declare(strict_types=1);

use App\Http\Controllers\Dashboard\Organization\OrganizationTokenController;
use App\Http\Controllers\Dashboard\Organization\TargetFieldController;
use App\Http\Controllers\Dashboard\Organization\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'organization', 'permission:manage organization'])
    ->prefix('dashboard/organization')
    ->name('dashboard.organization.')
    ->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Target Fields
        // Specific routes MUST come before the resource route so that "export" and "import"
        // are not interpreted as the {target_field} route parameter.
        Route::get('target-fields/export', [TargetFieldController::class, 'export'])
            ->name('target-fields.export');

        Route::post('target-fields/import', [TargetFieldController::class, 'import'])
            ->name('target-fields.import');

        // Constrain the resource parameter so only numeric IDs match {target_field}
        Route::resource('target-fields', TargetFieldController::class)
            ->whereNumber('target_field');

        // API Tokens
        Route::get('/tokens', [OrganizationTokenController::class, 'index'])->name('tokens.index');
        Route::post('/tokens', [OrganizationTokenController::class, 'store'])->name('tokens.store');
        Route::delete('/tokens/{organizationToken}', [OrganizationTokenController::class, 'destroy'])->name('tokens.destroy');
    });
