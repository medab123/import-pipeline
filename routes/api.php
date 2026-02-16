<?php

declare(strict_types=1);

use App\Http\Controllers\Api\OrganizationResourceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Organization API Routes
|--------------------------------------------------------------------------
|
| These routes are protected by organization token authentication.
| Clients must provide a valid organization token in the Authorization header:
| Authorization: Bearer org_{token}
| or
| X-Organization-Token: org_{token}
|
*/

Route::middleware('organization-auth')->prefix('v1')->name('api.v1.')->group(function () {
    // Pipelines
    Route::get('/pipelines', [OrganizationResourceController::class, 'pipelines'])->name('pipelines.index');
    Route::get('/pipelines/{pipeline}', [OrganizationResourceController::class, 'pipeline'])->name('pipelines.show');

    // Executions
    Route::get('/pipelines/{pipeline}/executions', [OrganizationResourceController::class, 'executions'])->name('pipelines.executions.index');
    Route::get('/pipelines/{pipeline}/executions/{execution}', [OrganizationResourceController::class, 'execution'])->name('pipelines.executions.show');
    Route::get('/pipelines/{pipeline}/results', [OrganizationResourceController::class, 'executionResults'])->name('pipelines.executions.results');
});
