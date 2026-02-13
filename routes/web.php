<?php

use Illuminate\Support\Facades\Route;

use Illuminate\Foundation\Application;
use Inertia\Inertia;

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
