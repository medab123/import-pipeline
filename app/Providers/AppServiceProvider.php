<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Organization;
use App\Policies\ImportPipelinePolicy;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Models\ImportPipelineConfig;
use Elaitech\Import\Models\ImportPipelineExecution;
use Elaitech\Import\Models\ImportPipelineLog;
use Elaitech\Import\Models\ImportPipelineTemplate;
use Elaitech\Import\Services\Jobs\ProcessImportPipelineJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The import models that need tenant scoping.
     *
     * @var array<class-string>
     */
    private array $tenantModels = [
        ImportPipeline::class,
        ImportPipelineConfig::class,
        ImportPipelineExecution::class,
        ImportPipelineLog::class,
        ImportPipelineTemplate::class,
    ];

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
        $this->registerTenantAutoAssignment();
        $this->registerTenantGlobalScopes();
        $this->registerPolicies();
        $this->registerQueueTenantContext();
    }

    /**
     * Auto-assign organization_uuid on model creation for all import models.
     */
    private function registerTenantAutoAssignment(): void
    {
        foreach ($this->tenantModels as $modelClass) {
            $modelClass::creating(function ($model) {
                if (auth()->check() && ! $model->organization_uuid) {
                    $model->organization_uuid = auth()->user()->organization_uuid;
                }
            });
        }
    }

    /**
     * Apply global scopes for tenant-level row isolation on all import models.
     */
    private function registerTenantGlobalScopes(): void
    {
        foreach ($this->tenantModels as $modelClass) {
            $modelClass::addGlobalScope('organization', function (Builder $query) {
                if (auth()->check()) {
                    $query->where(
                        $query->getModel()->getTable() . '.organization_uuid',
                        auth()->user()->organization_uuid
                    );
                }
            });
        }
    }

    /**
     * Register authorization policies.
     */
    private function registerPolicies(): void
    {
        Gate::policy(ImportPipeline::class, ImportPipelinePolicy::class);
        Gate::policy(\App\Models\User::class, \App\Policies\UserPolicy::class);
    }

    /**
     * Restore tenant context before queued jobs run.
     *
     * Since we cannot modify package job classes, we intercept job processing
     * events and bind the organization into the container from the serialized
     * pipeline's organization_uuid.
     */
    private function registerQueueTenantContext(): void
    {
        Queue::before(function (JobProcessing $event) {
            $job = $event->job;
            $payload = $job->payload();

            $command = unserialize($payload['data']['command'] ?? '');

            if ($command instanceof ProcessImportPipelineJob) {
                $pipeline = $command->pipeline;

                if ($pipeline && $pipeline->organization_uuid) {
                    $organization = Organization::withoutGlobalScopes()
                        ->where('uuid', $pipeline->organization_uuid)
                        ->first();

                    if ($organization) {
                        app()->instance('organization', $organization);
                    }
                }
            }
        });
    }
}

