<?php

declare(strict_types=1);

use App\Http\Controllers\Api\OrganizationResourceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pipeline API Routes
|--------------------------------------------------------------------------
|
| Each pipeline has its own token stored on import_pipelines.token.
| A token grants access only to that specific pipeline and its executions.
|
| Pass the token in the Authorization header:
|   Authorization: Bearer org_{token}
| or via the custom header:
|   X-Organization-Token: org_{token}
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
