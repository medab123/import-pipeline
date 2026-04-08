<?php

use App\Ai\Agents\ImportMapping;
use App\Models\TargetField;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Services\Pipeline\Services\FeedKeysService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('test', function () {
    $targetFields = TargetField::select('field')->pluck('field')->toArray();
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
    $user = auth()->user();

    if ($user->hasPermissionTo('view pipelines')) {
        return to_route('dashboard.import.pipelines.index');
    }

    if ($user->hasPermissionTo('view dealers')) {
        return to_route('dashboard.dealers.index');
    }

    abort(403, 'You do not have permission to access the dashboard.');
})->middleware(['auth', 'verified'])->name('dashboard');

include __DIR__.'/import-dashboard.php';
include __DIR__.'/product-dashboard.php';
include __DIR__.'/organization-dashboard.php';
include __DIR__.'/dealer-dashboard.php';
