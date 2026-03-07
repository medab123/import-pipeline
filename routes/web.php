<?php

use App\Ai\Agents\ImportMapping;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Services\Pipeline\Services\FeedKeysService;
use Illuminate\Support\Facades\Route;

use Illuminate\Foundation\Application;
use Inertia\Inertia;


Route::get('test', function () {
    $targetFields = \App\Models\TargetField::select('field')->pluck('field')->toArray();
    $sourceFields = app(FeedKeysService::class)->getFeedKeys(ImportPipeline::find(6));
    $model = ImportMapping::make();
    $model->setSourceFields($sourceFields);
    $model->setTargetFields($targetFields);
    $res = $model->prompt('');
    dd($res->toArray());
});


Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return to_route('dashboard.import.pipelines.index');
})->middleware(['auth', 'verified'])->name('dashboard');

include __DIR__ . '/import-dashboard.php';
include __DIR__ . '/product-dashboard.php';
include __DIR__ . '/organization-dashboard.php';
