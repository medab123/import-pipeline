<?php

declare(strict_types=1);

use App\Http\Controllers\Dashboard\Import\PipelineController;
use App\Http\Controllers\Dashboard\Import\StepperPipelineController;
use App\Http\Controllers\Dashboard\Import\TestDataMapperController;
use App\Http\Controllers\Dashboard\Import\TestDownloaderController;
use App\Http\Controllers\Dashboard\Import\TestFilterController;
use App\Http\Controllers\Dashboard\Import\TestReaderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Import Dashboard Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the import dashboard functionality.
| These routes handle pipeline management, execution monitoring,
| template management, and analytics.
|
*/

Route::middleware(['auth', 'organization'])->prefix('dashboard/import')->name('dashboard.import.')->group(function () {

    // Pipeline CRUD
    Route::get('/pipelines', [PipelineController::class, 'index'])->name('pipelines.index');
    Route::get('/pipelines/{pipeline}', [PipelineController::class, 'show'])->name('pipelines.show');
    Route::delete('/pipelines/{pipeline}', [PipelineController::class, 'destroy'])->name('pipelines.destroy');

    // Pipeline Actions
    Route::post('/pipelines/import', [PipelineController::class, 'import'])->name('pipelines.import');
    Route::patch('/pipelines/{pipeline}/toggle-status', [PipelineController::class, 'toggleStatus'])->name('pipelines.toggle-status');
    Route::post('/pipelines/{pipeline}/process-now', [PipelineController::class, 'processNow'])->name('pipelines.process-now');
    Route::get('/pipelines/{pipeline}/executions', [PipelineController::class, 'executions'])->name('pipelines.executions');
    Route::get('/pipelines/{pipeline}/executions/{execution}', [PipelineController::class, 'showExecution'])->name('pipelines.executions.show');
    Route::get('/pipelines/{pipeline}/activity-logs', [PipelineController::class, 'activityLogs'])->name('pipelines.activity-logs');
    Route::get('/pipelines/{pipeline}/activity-logs/{activity}', [PipelineController::class, 'showActivityLog'])->name('pipelines.activity-logs.show');
    Route::get('/pipelines/{pipeline}/export', [PipelineController::class, 'export'])->name('pipelines.export');
    // API Endpoints

    // Stepper Pipeline Routes
    Route::get('/pipelines/stepper/create', [StepperPipelineController::class, 'create'])->name('pipelines.stepper.create');
    Route::get('/pipelines/{pipeline}/stepper/edit', [StepperPipelineController::class, 'edit'])->name('pipelines.stepper.edit');
    Route::get('/pipelines/{pipeline}/{step}', [StepperPipelineController::class, 'showStep'])->name('pipelines.step.show');
    Route::post('/pipelines/{pipeline}/{step}', [StepperPipelineController::class, 'storeStep'])->name('pipelines.step.store');

    // Test Downloader
    Route::post('/pipelines/{pipeline}/downloader/test', TestDownloaderController::class)->name('pipelines.downloader.test');

    // Test Reader
    Route::post('/pipelines/{pipeline}/reader/test', TestReaderController::class)->name('pipelines.reader.test');

    // Test Filter
    Route::post('/pipelines/{pipeline}/filter/test', TestFilterController::class)->name('pipelines.filter.test');

    // Test Data Mapper
    Route::post('/pipelines/{pipeline}/mapper/test', TestDataMapperController::class)->name('pipelines.mapper.test');

});
