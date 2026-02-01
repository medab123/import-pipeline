<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

include __DIR__ . '/import-dashboard.php';
include __DIR__ . '/product-dashboard.php';
