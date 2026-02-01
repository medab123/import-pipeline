<?php

namespace App\Providers;

use Elaitech\Import\Contracts\Services\Product\ProductActivityLogServiceInterface;
use Elaitech\Import\Services\Product\ProductActivityLogService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
