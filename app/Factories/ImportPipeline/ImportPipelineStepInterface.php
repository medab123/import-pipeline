<?php

namespace App\Factories\ImportPipeline;

use Elaitech\Import\Models\ImportPipeline;
use Spatie\ViewModels\ViewModel;

interface ImportPipelineStepInterface
{
    public \Elaitech\Import\Contracts\Services\ImportDashboard\ImportDashboardServiceInterface $importDashboardService {
        set;
    }

    /**
     * Get the view model for the step.
     */
    public function getViewModel(ImportPipeline $pipeline): ViewModel;

    /**
     * Process the step data.
     */
    public function process(ImportPipeline $pipeline, array $data): ImportPipeline;

    /**
     * Get the validation rules for the step.
     */
    public function getValidationRules(): array;

    /**
     * Check if the step is available for the pipeline.
     */
    public function isAvailable(ImportPipeline $pipeline): bool;

    /**
     * Get the view path for the step.
     */
    public function getViewPath(): string;

    /**
     * Get the success message for completing the step.
     */
    public function getSuccessMessage(): string;

    /**
     * Get the error message for when the step is not available.
     */
    public function getErrorMessage(): string;
}
