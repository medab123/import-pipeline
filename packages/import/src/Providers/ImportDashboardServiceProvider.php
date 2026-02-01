<?php

declare(strict_types=1);

namespace Elaitech\Import\Providers;

use Elaitech\Import\Contracts\Repositories\ImportPipeline\ImportPipelineRepositoryInterface;
use Elaitech\Import\Contracts\Services\ImportDashboard\ImportDashboardServiceInterface;
use Elaitech\Import\Services\ImportDashboard\ImportPipelineRepository;
use Elaitech\Import\Services\Pipeline\Contracts\PipelineExecutionServiceInterface;
use Elaitech\Import\Services\Pipeline\Contracts\PipelineSchedulingServiceInterface;
use Elaitech\Import\Services\Pipeline\Factories\ImportPipelineConfigFactory;
use Elaitech\Import\Services\Pipeline\Services\PipelineExecutionService;
use Elaitech\Import\Services\Pipeline\Services\PipelineSchedulingService;
use Elaitech\Import\Services\Pipeline\Services\PipelineTestDataService;
use Elaitech\Import\Services\ImportDashboard\ImportDashboardService;
use Illuminate\Support\ServiceProvider;

final class ImportDashboardServiceProvider extends ServiceProvider
{
    public array $bindings = [
        ImportPipelineRepositoryInterface::class => ImportPipelineRepository::class,
        ImportDashboardServiceInterface::class => ImportDashboardService::class,
        ImportPipelineConfigFactory::class => ImportPipelineConfigFactory::class,
        PipelineExecutionServiceInterface::class => PipelineExecutionService::class,
        PipelineSchedulingServiceInterface::class => PipelineSchedulingService::class,
        PipelineTestDataService::class => PipelineTestDataService::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/import-pipelines.php',
            'import-pipelines'
        );

        // Register all import service providers
        $this->app->register(\Elaitech\Import\Services\Providers\DownloaderServiceProvider::class);
        $this->app->register(\Elaitech\Import\Services\Providers\ReaderServiceProvider::class);
        $this->app->register(\Elaitech\Import\Services\Providers\FilterServiceProvider::class);
        $this->app->register(\Elaitech\Import\Services\Providers\PrepareServiceProvider::class);
        $this->app->register(\Elaitech\Import\Services\Providers\PipelineServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Routes are now loaded from the application's routes/import-dashboard.php
        // Controllers and ViewModels have been moved to the application

        // Publish configuration files
        $this->publishes([
            __DIR__.'/../../config/import-pipelines.php' => config_path('import-pipelines.php'),
            __DIR__.'/../../config/import-pipeline-schema.yaml' => config_path('import-pipeline-schema.yaml'),
        ], 'import-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'import-migrations');
    }
}
