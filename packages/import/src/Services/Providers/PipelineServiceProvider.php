<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Providers;

use Elaitech\Import\Services\Pipeline\Contracts\ImportPipelineInterface;
use Elaitech\Import\Services\Pipeline\Contracts\PrepareServiceInterface;
use Elaitech\Import\Services\Pipeline\Implementations\ImportPipelineService;
use Elaitech\Import\Services\Pipeline\Loaders\ImportPipelineYamlLoader;
use Elaitech\Import\Services\Pipeline\Orchestrators\PipelineOrchestrator;
use Elaitech\Import\Services\Pipeline\Pipes\DownloadPipe;
use Elaitech\Import\Services\Pipeline\Pipes\FilterPipe;
use Elaitech\Import\Services\Pipeline\Pipes\ImagesPreparePipe;
use Elaitech\Import\Services\Pipeline\Pipes\MapPipe;
use Elaitech\Import\Services\Pipeline\Pipes\PreparePipe;
use Elaitech\Import\Services\Pipeline\Pipes\ReadPipe;
use Elaitech\Import\Services\Pipeline\Pipes\SavePipe;
use Elaitech\Import\Services\Prepare\Factories\PrepareFactory;
use Illuminate\Support\ServiceProvider;

final class PipelineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DownloadPipe::class);
        $this->app->singleton(ReadPipe::class);
        $this->app->singleton(MapPipe::class);
        $this->app->singleton(FilterPipe::class);
        $this->app->singleton(ImagesPreparePipe::class);
        $this->app->singleton(PreparePipe::class);
        $this->app->singleton(SavePipe::class);

        $this->app->singleton(PrepareServiceInterface::class, PrepareFactory::class);
        $this->app->singleton(PrepareFactory::class);

        $this->app->singleton(PipelineOrchestrator::class);

        $this->app->singleton(ImportPipelineInterface::class, ImportPipelineService::class);
        $this->app->singleton(ImportPipelineService::class);
        $this->app->singleton(ImportPipelineYamlLoader::class);
    }

    public function boot(): void {}
}
